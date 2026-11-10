<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopSetting extends Model
{
    protected $fillable = [
        'currency', 'logo_path', 'receipt_header', 'receipt_footer', 'invoice_prefix',
        'tax_enabled', 'tax_rate', 'default_low_stock_threshold', 'max_discount_percent',
    ];

    protected $attributes = [
        'currency' => 'PKR', 'invoice_prefix' => 'INV', 'tax_enabled' => false, 'tax_rate' => '0.00',
        'default_low_stock_threshold' => '10.000', 'max_discount_percent' => '50.00',
    ];

    protected function casts(): array
    {
        return ['tax_enabled' => 'boolean'];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function currencySymbol(): string
    {
        return $this->currency === 'PKR' ? 'Rs' : $this->currency;
    }
}
