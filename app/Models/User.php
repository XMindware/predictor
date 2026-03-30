<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    // ─── Role constants ───────────────────────────────────────────────────────
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN       = 'admin';
    const ROLE_MEMBER      = 'member';
    const ROLE_VISITOR     = 'visitor';

    const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_MEMBER,
        self::ROLE_VISITOR,
    ];

    // ─── Mass assignable ──────────────────────────────────────────────────────
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─── Role helpers ─────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isMember(): bool
    {
        return $this->role === self::ROLE_MEMBER;
    }

    public function isVisitor(): bool
    {
        return $this->role === self::ROLE_VISITOR;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if the user has at least a minimum role level.
     * Hierarchy: super_admin > admin > member > visitor
     */
    public function hasMinRole(string $minRole): bool
    {
        $hierarchy = [
            self::ROLE_VISITOR     => 0,
            self::ROLE_MEMBER      => 1,
            self::ROLE_ADMIN       => 2,
            self::ROLE_SUPER_ADMIN => 3,
        ];

        return ($hierarchy[$this->role] ?? 0) >= ($hierarchy[$minRole] ?? 0);
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_ADMIN       => 'Admin',
            self::ROLE_MEMBER      => 'Member',
            self::ROLE_VISITOR     => 'Visitor',
            default                => ucfirst($this->role),
        };
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function memberships(): HasMany
    {
        return $this->hasMany(UserMembership::class);
    }

    public function activeMembership(): HasOne
    {
        return $this->hasOne(UserMembership::class)
            ->where('status', 'active')
            ->latest();
    }
}
