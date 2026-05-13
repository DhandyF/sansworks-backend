#!/bin/bash
set -e

echo "=== SansWorks Server Setup ==="
echo "This script sets up a fresh Ubuntu 24.04 droplet"
echo ""

SERVER_IP=$(curl -s ifconfig.me)
echo "Detected server IP: $SERVER_IP"

read -p "Domain name (e.g. sansworks.com): " DOMAIN
read -p "DB password for 'sansworks' user: " DB_PASSWORD
read -p "GitHub backend repo URL (e.g. https://github.com/DhandyF/sansworks-backend.git): " BACKEND_REPO
read -p "GitHub frontend repo URL (e.g. https://github.com/DhandyF/sansworks-frontend.git): " FRONTEND_REPO

echo ""
echo "=== Updating system ==="
apt update && apt upgrade -y

echo "=== Creating deploy user ==="
if ! id deploy &>/dev/null; then
    adduser --disabled-password --gecos "" deploy
    usermod -aG sudo deploy
    mkdir -p /home/deploy/.ssh
    cp /root/.ssh/authorized_keys /home/deploy/.ssh/authorized_keys 2>/dev/null || true
    chown -R deploy:deploy /home/deploy/.ssh
    chmod 700 /home/deploy/.ssh
    chmod 600 /home/deploy/.ssh/authorized_keys
    echo "deploy ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/deploy
fi

echo "=== Installing packages ==="
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update

apt install -y nginx postgresql postgresql-contrib \
    php8.2-fpm php8.2-pgsql php8.2-mbstring php8.2-xml php8.2-bcmath \
    php8.2-curl php8.2-zip php8.2-intl php8.2-gd unzip git

curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "=== Configuring PostgreSQL ==="
sudo -u postgres psql <<EOF
CREATE DATABASE sansworks;
CREATE USER sansworks WITH ENCRYPTED PASSWORD '${DB_PASSWORD}';
ALTER DATABASE sansworks OWNER TO sansworks;
\q
EOF

echo "=== Creating project directory ==="
mkdir -p /var/www/sansworks/backend /var/www/sansworks/frontend

echo "=== Cloning repositories ==="
sudo -u deploy git clone "$BACKEND_REPO" /var/www/sansworks/backend
sudo -u deploy git clone "$FRONTEND_REPO" /var/www/sansworks/frontend

echo "=== Setting up Laravel ==="
cd /var/www/sansworks/backend
sudo -u deploy composer install --no-dev --optimize-autoloader

cp deploy/.env.production .env
sed -i "s/YOUR_DB_PASSWORD/${DB_PASSWORD}/g" .env
sed -i "s/yourdomain.com/${DOMAIN}/g" .env
sed -i "s/<droplet-ip>/${SERVER_IP}/g" .env
php artisan key:generate

php artisan migrate --force
php artisan db:seed --force

chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Setting up frontend ==="
cd /var/www/sansworks/frontend

cat > .env.production << EOF
VITE_API_URL=https://${DOMAIN}/api
EOF

sudo -u deploy npm install
sudo -u deploy npm run build

echo "=== Configuring Nginx ==="
cp /var/www/sansworks/backend/deploy/nginx/sansworks.conf /etc/nginx/sites-available/sansworks
sed -i "s/yourdomain.com/${DOMAIN}/g" /etc/nginx/sites-available/sansworks
ln -sf /etc/nginx/sites-available/sansworks /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.2/fpm/php.ini

nginx -t && systemctl restart nginx
systemctl restart php8.2-fpm

echo "=== Installing SSL ==="
apt install -y certbot python3-certbot-nginx
certbot --nginx -d "$DOMAIN" -d "www.$DOMAIN" --non-interactive --agree-tos --redirect
certbot renew --dry-run

echo "=== Setting up queue worker ==="
cp /var/www/sansworks/backend/deploy/systemd/sansworks-worker.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable sansworks-worker
systemctl start sansworks-worker

echo ""
echo "=== Setup Complete! ==="
echo "Your app: https://${DOMAIN}"
echo ""
echo "Next: Add GitHub secrets for auto-deploy:"
echo "  DROPLET_IP = ${SERVER_IP}"
echo "  DROPLET_USER = deploy"
echo "  SSH_PRIVATE_KEY = (your SSH private key)"