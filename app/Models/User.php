<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'phone',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function brands()
    {
        return $this->belongsToMany(Brand::class, 'brand_user', 'user_id', 'brand_id');
    }

    public function hasBrandAccess($brandId)
    {
        // Admin and operator have full access to all brands
        if ($this->role === 'admin' || $this->role === 'operator') {
            return true;
        }

        // Client users can only access assigned brands
        if ($this->role === 'client') {
            return $this->brands()->where('brands.id', $brandId)->exists();
        }

        // Other roles (if any) get no brand access
        return false;
    }
}
