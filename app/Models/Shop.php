<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'owner_name', 'email', 'phone', 'address', 'city', 'status'];

    public function branches(): HasMany { return $this->hasMany(Branch::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function categories(): HasMany { return $this->hasMany(Category::class); }
    public function sales(): HasMany { return $this->hasMany(Sale::class); }
    public function settings(): HasOne { return $this->hasOne(ShopSetting::class); }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Settings row, created with defaults on first access. */
    public function config(): ShopSetting
    {
        return $this->settings ?? $this->settings()->create([]);
    }
}
