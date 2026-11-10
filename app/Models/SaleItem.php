<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'product_id', 'product_name', 'sku', 'unit', 'quantity', 'unit_price',
        'cost_price', 'line_total', 'returned_quantity',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function returnableQuantity(): string
    {
        return Money::qtyFromMilli(Money::qtyToMilli($this->quantity) - Money::qtyToMilli($this->returned_quantity));
    }
}
