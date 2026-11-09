<?php

namespace App\Models;

use App\Enums\Role;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['shop_id', 'name', 'email', 'phone', 'role', 'is_active', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'role' => Role::class,
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    public function isShopAdmin(): bool
    {
        return $this->role === Role::ShopAdmin;
    }

    public function isManager(): bool
    {
        return $this->role === Role::BranchManager;
    }

    public function isCashier(): bool
    {
        return $this->role === Role::Cashier;
    }

    public function hasPermission(string $permission): bool
    {
        return Permissions::allows($this->role, $permission);
    }

    /** IDs of branches this user may operate on (shop admins: all branches of their shop). */
    public function accessibleBranchIds(bool $activeOnly = false): array
    {
        static $cache = [];
        $key = $this->id.'|'.(int) $activeOnly.'|'.$this->updated_at;
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        if ($this->isSuperAdmin() || ! $this->shop_id) {
            return $cache[$key] = [];
        }
        $q = $this->isShopAdmin()
            ? Branch::where('shop_id', $this->shop_id)
            : $this->branches()->where('branches.shop_id', $this->shop_id);
        if ($activeOnly) {
            $q->where('branches.is_active', true);
        }

        return $cache[$key] = $q->pluck('branches.id')->map(fn ($id) => (int) $id)->all();
    }

    public function canAccessBranch(int|Branch|null $branch): bool
    {
        $id = $branch instanceof Branch ? $branch->id : $branch;

        return $id !== null && in_array((int) $id, $this->accessibleBranchIds(), true);
    }

    public function accessibleBranches()
    {
        return Branch::whereIn('id', $this->accessibleBranchIds())->orderBy('name');
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            Role::SuperAdmin => 'admin.dashboard',
            Role::Cashier => 'pos.index',
            default => 'dashboard',
        };
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        return strtoupper(substr($parts[0] ?? '', 0, 1).substr($parts[1] ?? '', 0, 1));
    }
}
