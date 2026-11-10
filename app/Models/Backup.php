<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    protected $fillable = ['filename', 'size_bytes', 'checksum', 'status', 'created_by', 'notes', 'restore_tested_at'];

    protected function casts(): array
    {
        return ['restore_tested_at' => 'datetime'];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
