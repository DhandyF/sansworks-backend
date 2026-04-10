# CORS and Rate Limiting Configuration Guide

## Overview
The Sansworks API now implements Cross-Origin Resource Sharing (CORS) and rate limiting to enable secure frontend integration and protect against API abuse.

## CORS Configuration

### What is CORS?
CORS (Cross-Origin Resource Sharing) is a security mechanism that allows or restricts cross-origin HTTP requests when a frontend application (Vue.js) communicates with a backend API on a different domain/port.

### Configuration Files

#### 1. CORS Configuration (`config/cors.php`)

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],

'allowed_origins' => [
    'http://localhost:5173',    # Vue.js dev server
    'http://localhost:3000',    # Alternative dev server
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000',
    env('FRONTEND_URL'),        # Production frontend URL
],

'allowed_methods' => ['*'],

'allowed_headers' => [
    'Content-Type',
    'Authorization',
    'X-Requested-With',
    'Accept',
    'Origin',
    'Access-Control-Request-Method',
    'Access-Control-Request-Headers',
],

'supports_credentials' => true, // Allow tokens/cookies
```

#### 2. Environment Variables (`.env`)

```bash
# Development
FRONTEND_URL=http://localhost:5173

# Production
FRONTEND_URL=https://sansworks-frontend.com
```

### How CORS Works

#### Preflight Request (OPTIONS)
```http
OPTIONS /api/sizes HTTP/1.1
Host: api.sansworks.com
Origin: http://localhost:5173
Access-Control-Request-Method: GET
Access-Control-Request-Headers: authorization
```

**Response:**
```http
HTTP/1.1 200 OK
Access-Control-Allow-Origin: http://localhost:5173
Access-Control-Allow-Methods: GET, POST, PUT, DELETE
Access-Control-Allow-Headers: authorization, content-type
Access-Control-Allow-Credentials: true
```

#### Actual Request
```http
GET /api/sizes HTTP/1.1
Host: api.sansworks.com
Origin: http://localhost:5173
Authorization: Bearer token123
```

**Response:**
```http
HTTP/1.1 200 OK
Access-Control-Allow-Origin: http://localhost:5173
Access-Control-Allow-Credentials: true
Content-Type: application/json
```

### Frontend Integration

#### Vue.js Configuration
```javascript
// vite.config.js
export default defineConfig({
  plugins: [vue()],
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      }
    }
  }
})
```

#### Axios Configuration
```javascript
// src/utils/api.js
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  withCredentials: true, // Important for tokens
  headers: {
    'Content-Type': 'application/json',
  }
})

// Add token to requests
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default api
```

### CORS Troubleshooting

#### Issue: CORS policy blocked
```
Access to XMLHttpRequest at 'http://localhost:8000/api/sizes'
from origin 'http://localhost:5173' has been blocked by CORS policy
```

**Solutions:**
1. Check `FRONTEND_URL` in `.env`
2. Verify frontend is running on allowed port
3. Check browser console for specific error
4. Ensure `supports_credentials => true` for token auth

#### Issue: Credentials not included
```
Request header field authorization is not allowed
by Access-Control-Allow-Headers in preflight response
```

**Solution:** Add 'Authorization' to `allowed_headers` in CORS config

## Rate Limiting Configuration

### What is Rate Limiting?
Rate limiting protects API endpoints from abuse by restricting the number of requests a user can make within a time period.

### Rate Limiting Strategy

#### 1. Authentication Endpoints (Strict)
```php
'auth' => fn ($request) => $request->is('api/auth/login', 'api/auth/register')
    ? Limit::perMinute(5, 1) // 5 requests per minute, 1 per second
    : Limit::perMinute(60),
```

**Limits:**
- Login: 5 attempts per minute, 1 per second
- Register: 5 attempts per minute, 1 per second
- Password reset: 5 attempts per minute

**Purpose:** Prevent brute force attacks on authentication

#### 2. General API Endpoints (Moderate)
```php
Limit::perMinute(60) // 60 requests per minute
```

**Limits:**
- GET requests: 60 per minute
- POST requests: 60 per minute
- PUT requests: 60 per minute
- DELETE requests: 60 per minute

**Purpose:** Allow normal usage while preventing abuse

#### 3. Expensive Operations (Conservative)
```php
'statistics' => Limit::perMinute(20) // 20 requests per minute
```

**Limits:**
- Statistics calculations: 20 per minute
- Report generation: 20 per minute
- Bulk operations: 20 per minute

**Purpose:** Protect server resources from heavy operations

### Rate Limit Response

When rate limit is exceeded:

```json
{
    "success": false,
    "message": "Too many attempts. Please try again in 60 seconds.",
    "exception": "Illuminate\\Http\\Exceptions\\ThrottleRequestsException"
}
```

HTTP Status: 429 Too Many Requests

Headers:
```
Retry-After: 60
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1641234567
```

### Environment Variables

```bash
# Rate Limiting Configuration
RATE_LIMIT_AUTH_PER_MINUTE=5      # Auth endpoints
RATE_LIMIT_AUTH_PER_SECOND=1      # Auth endpoints burst
RATE_LIMIT_API_PER_MINUTE=60      # General API
RATE_LIMIT_STATISTICS_PER_MINUTE=20 # Heavy operations
```

### Testing Rate Limiting

#### 1. Test Authentication Rate Limit
```bash
# This will trigger rate limit after 5 attempts
for i in {1..10}; do
  curl -X POST http://localhost:8000/api/auth/login \
    -H "Content-Type: application/json" \
    -d '{"login":"test","password":"test"}'
  echo "Attempt $i"
done
```

#### 2. Test API Rate Limit
```bash
# Store your token
TOKEN="your_token_here"

# Send requests rapidly
for i in {1..70}; do
  curl -X GET http://localhost:8000/api/sizes \
    -H "Authorization: Bearer $TOKEN"
  echo "Request $i"
done
```

#### 3. Check Rate Limit Headers
```bash
curl -I http://localhost:8000/api/sizes \
  -H "Authorization: Bearer $TOKEN"
```

Look for:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```

## Production Configuration

### CORS Production Settings

```php
// config/cors.php
'allowed_origins' => [
    'https://sansworks-frontend.com',  // Production frontend
    'https://admin.sansworks.com',      // Admin panel
],

'supports_credentials' => true,
'max_age' => 86400, // 24 hours
```

### Rate Limiting Production Settings

```php
// More lenient for legitimate users
'auth' => Limit::perMinute(10, 2),     // 10 per minute, 2 per second
'api' => Limit::perMinute(100),         // 100 per minute
'statistics' => Limit::perMinute(30),    // 30 per minute
```

### Using Redis for Rate Limiting

For production with multiple servers:

```bash
# .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379
```

Rate limiting will use Redis for distributed rate limiting.

## Security Best Practices

### 1. Always Use HTTPS in Production
```javascript
// Production frontend
const api = axios.create({
  baseURL: 'https://api.sansworks.com/api',
  withCredentials: true,
})
```

### 2. Implement Token Refresh
```javascript
// Handle token expiration
api.interceptors.response.use(
  response => response,
  async error => {
    if (error.response?.status === 401) {
      // Token expired, refresh or redirect to login
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)
```

### 3. Rate Limit by User
```php
// Different limits for different user roles
$middleware->api(throttle: [
    'staff' => Limit::perMinute(30),      // Staff: 30/min
    'manager' => Limit::perMinute(100),    // Manager: 100/min
    'admin' => Limit::perMinute(200),      // Admin: 200/min
]);
```

### 4. Monitor Rate Limit Violations
```php
// Log rate limit violations
Event::listen(RequestHandled::class, function ($event) {
    if ($event->response->status() === 429) {
        Log::warning('Rate limit exceeded', [
            'ip' => $event->request->ip(),
            'user_id' => auth()->id(),
            'url' => $event->request->url(),
        ]);
    }
});
```

## Testing Checklist

### CORS Testing
- [ ] Frontend can access API from localhost
- [ ] Preflight OPTIONS requests succeed
- [ ] POST/PUT/DELETE requests work
- [ ] Authorization header is accepted
- [ ] Credentials (tokens) are included
- [ ] Production frontend URL is configured

### Rate Limiting Testing
- [ ] Auth endpoints are rate-limited
- [ ] General API has reasonable limits
- [ ] Expensive operations have stricter limits
- [ ] Rate limit headers are present
- [ ] Retry-After header is set
- [ ] Redis works for distributed limiting

## Monitoring and Debugging

### Enable Request Logging
```bash
# .env
LOG_LEVEL=debug
```

### Check Rate Limit Status
```php
// In controller
use Illuminate\Support\Facades\RateLimiter;

public function checkRateLimit()
{
    $key = 'auth:' . request()->ip();
    $limit = RateLimiter::attempts($key);
    $remaining = RateLimiter::remaining($key);

    return response()->json([
        'limit' => 60,
        'remaining' => $remaining,
        'attempts' => $limit,
    ]);
}
```

### Clear Rate Limits (Development)
```bash
php artisan cache:clear
php artisan tinker --execute="Cache::forget('throttle:auth:127.0.0.1')"
```

## Common Issues and Solutions

### Issue: CORS still blocked after configuration
**Check:**
1. Clear browser cache
2. Verify `.env` has correct `FRONTEND_URL`
3. Check for typos in allowed origins
4. Ensure `supports_credentials => true` for token auth

### Issue: Rate limiting too strict
**Solution:** Adjust limits in `bootstrap/app.php` or use environment variables

### Issue: Frontend can't authenticate
**Check:**
1. `withCredentials: true` in axios config
2. Token format: `Bearer {token}`
3. Token is valid and not expired
4. CORS allows `Authorization` header

## Next Steps

1. **Test with actual Vue.js frontend**
2. **Set up Redis for production rate limiting**
3. **Configure production CORS origins**
4. **Monitor rate limit violations in logs**
5. **Set up alerts for excessive rate limit hits**

Your API is now ready for secure frontend integration with proper CORS and rate limiting protection!
