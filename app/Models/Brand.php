<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'status',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'brand_user', 'brand_id', 'user_id');
    }
}
