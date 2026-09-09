<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if user has a specific role or one of given roles.
     */
    public function hasRole(UserRole|string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles, true) || in_array($this->role->value, $roles, true);
        }

        if ($roles instanceof UserRole) {
            return $this->role === $roles;
        }

        return $this->role->value === $roles;
    }

    public function isTeknik(): bool
    {
        return $this->role === UserRole::TEKNIK;
    }

    public function isNoc(): bool
    {
        return $this->role === UserRole::NOC;
    }

    public function isFinance(): bool
    {
        return $this->role === UserRole::FINANCE;
    }

    public function isDirektur(): bool
    {
        return false;
    }

    public function isAdmin(): bool
    {
        return false;
    }

    public function getNamaAttribute(): string
    {
        return $this->name ?? 'User';
    }

    public function getNamaLevelAttribute(): string
    {
        return $this->role?->label() ?? 'Staff';
    }

    public function getRoleBadgeClassesAttribute(): string
    {
        return $this->role?->badgeClasses() ?? 'bg-slate-500/10 text-slate-400 border-slate-500/30';
    }
}
