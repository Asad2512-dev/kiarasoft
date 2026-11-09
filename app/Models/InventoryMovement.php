<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'shop_id', 'branch_id', 'product_id', 'user_id', 'type', 'quantity_change',
        'quantity_before', 'quantity_after', 'reason', 'reference_type', 'reference_id',
    ];

    protected function casts(): array
    {
        return ['type' => MovementType::class, 'created_at' => 'datetime'];
    }

    public function product() { return $this->belongsTo(Product::class)->withTrashed(); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function user() { return $this->belongsTo(User::class)->withTrashed(); }
    public function reference() { return $this->morphTo(); }
}
