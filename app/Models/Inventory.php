<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';

    protected $fillable = ['shop_id', 'branch_id', 'product_id', 'quantity', 'low_stock_threshold'];

    public function product() { return $this->belongsTo(Product::class)->withTrashed(); }
    public function branch() { return $this->belongsTo(Branch::class); }

    /** Effective threshold: branch override -> product -> shop default. */
    public function effectiveThreshold(?string $shopDefault = null): string
    {
        return $this->low_stock_threshold ?? $this->product?->low_stock_threshold ?? $shopDefault ?? '10';
    }

    public function stockStatus(?string $shopDefault = null): string
    {
        $q = Money::qtyToMilli($this->quantity);
        if ($q <= 0) {
            return 'out';
        }

        return $q <= Money::qtyToMilli($this->effectiveThreshold($shopDefault)) ? 'low' : 'ok';
    }
}
