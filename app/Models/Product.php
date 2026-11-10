<?php

namespace App\Models;

use App\Enums\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = ['is_active' => true];

    protected $fillable = [
        'shop_id', 'category_id', 'name', 'sku', 'barcode', 'unit', 'price', 'cost_price',
        'low_stock_threshold', 'is_active', 'image_path',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'unit' => Unit::class];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
