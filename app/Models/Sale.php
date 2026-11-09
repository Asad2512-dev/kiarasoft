<?php

namespace App\Models;

use App\Enums\SaleStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'shop_id', 'branch_id', 'cashier_id', 'invoice_no', 'idempotency_key', 'customer_name',
        'subtotal', 'discount_type', 'discount_value', 'discount_amount', 'tax_rate', 'tax_amount',
        'total', 'refunded_total', 'status', 'notes', 'sold_at',
    ];

    protected function casts(): array
    {
        return ['sold_at' => 'datetime', 'status' => SaleStatus::class];
    }

    public function shop() { return $this->belongsTo(Shop::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id')->withTrashed(); }
    public function items() { return $this->hasMany(SaleItem::class); }
    public function payment() { return $this->hasOne(Payment::class); }
    public function returns() { return $this->hasMany(SaleReturn::class); }

    public function netTotal(): string
    {
        return Money::fromMinor(Money::toMinor($this->total) - Money::toMinor($this->refunded_total));
    }

    public function isFullyReturned(): bool
    {
        return $this->status === SaleStatus::Returned;
    }
}
