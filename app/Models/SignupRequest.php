<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignupRequest extends Model
{
    protected $fillable = ['shop_name', 'owner_name', 'email', 'phone', 'city', 'message', 'status', 'reviewed_by', 'reviewed_at', 'shop_id', 'review_note'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
