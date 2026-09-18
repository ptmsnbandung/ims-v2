<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class Pengguna extends Model implements AuthenticatableContract
{
    use Authenticatable, Notifiable;

    protected $table = 'tb_pengguna';
    protected $primaryKey = 'kode_pengguna';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'kode_pengguna',
        'kode_karyawan',
        'kode_level',
        'username',
        'password',
        'status_aktif',
        'as_sales',
        'last_ip',
        'las_login',
        'remember_token',
        'date_create',
        'user_create',
        'date_update',
        'user_update',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the name of the unique identifier for the user.
     */
    public function getAuthIdentifierName(): string
    {
        return 'kode_pengguna';
    }

    /**
     * Get the unique identifier for the user.
     */
    public function getAuthIdentifier(): mixed
    {
        return $this->getAttribute($this->getAuthIdentifierName());
    }

    /**
     * Get the password for the user.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->password;
    }

    /**
     * Relation to Level Pengguna
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(LevelPengguna::class, 'kode_level', 'kode_level');
    }

    /**
     * Relation to Karyawan
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'kode_karyawan', 'kode_karyawan');
    }

    /**
     * Check if user is active (status_aktif == '1')
     */
    public function isActive(): bool
    {
        return (string) $this->status_aktif === '1';
    }

    /**
     * Get Display Name (from Karyawan or Username)
     */
    public function getNamaAttribute(): string
    {
        return $this->karyawan?->nama_karyawan ?? $this->username ?? $this->kode_pengguna;
    }

    /**
     * Get Role Name
     */
    public function getNamaLevelAttribute(): string
    {
        return $this->level?->nama_level ?? 'Pengguna';
    }

    /**
     * Check if user is Teknik (Level 4 / lv9812 / Teknik)
     */
    public function isTeknik(): bool
    {
        $namaLevel = strtolower($this->level?->nama_level ?? '');
        $kodeLevel = $this->kode_level;

        return str_contains($namaLevel, 'teknik') || $kodeLevel === 'lv9812' || $this->level?->level === 4;
    }

    /**
     * Check if user is NOC (Level 3 / lv68132 / NOC)
     */
    public function isNoc(): bool
    {
        $namaLevel = strtolower($this->level?->nama_level ?? '');
        $kodeLevel = $this->kode_level;

        return str_contains($namaLevel, 'noc') || $kodeLevel === 'lv68132' || $this->level?->level === 3;
    }

    /**
     * Check if user is Finance (Level 6 / lv33501 / Finance)
     */
    public function isFinance(): bool
    {
        $namaLevel = strtolower($this->level?->nama_level ?? '');
        $kodeLevel = $this->kode_level;

        return str_contains($namaLevel, 'finance') || $kodeLevel === 'lv33501' || $this->level?->level === 6;
    }

    /**
     * Check if user is Direktur / Superadmin (Level 1 / lv67752 / DIREKTUR)
     */
    public function isDirektur(): bool
    {
        $namaLevel = strtolower($this->level?->nama_level ?? '');
        $kodeLevel = $this->kode_level;

        return str_contains($namaLevel, 'direktur') || str_contains($namaLevel, 'admin') || $kodeLevel === 'lv67752' || $this->level?->level === 1;
    }

    /**
     * Check if user is Admin / Master Admin
     */
    public function isAdmin(): bool
    {
        return $this->isDirektur() || str_contains(strtolower($this->username ?? ''), 'admin');
    }

    /**
     * Check multiple roles
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $role) {
            $roleLower = strtolower(trim($role));
            if ($roleLower === 'teknik' && $this->isTeknik()) {
                return true;
            }
            if ($roleLower === 'noc' && $this->isNoc()) {
                return true;
            }
            if ($roleLower === 'finance' && $this->isFinance()) {
                return true;
            }
            if (($roleLower === 'direktur' || $roleLower === 'admin' || $roleLower === 'administrator') && $this->isAdmin()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Badge CSS classes based on role
     */
    public function getRoleBadgeClassesAttribute(): string
    {
        if ($this->isAdmin()) {
            return 'bg-rose-50 text-rose-700 border-rose-200';
        }
        if ($this->isFinance()) {
            return 'bg-amber-50 text-amber-700 border-amber-200';
        }
        if ($this->isNoc()) {
            return 'bg-sky-50 text-sky-700 border-sky-200';
        }
        if ($this->isTeknik()) {
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        }

        $namaLevel = strtolower($this->level?->nama_level ?? '');
        if (str_contains($namaLevel, 'sales') || str_contains($namaLevel, 'salses')) {
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        }
        if (str_contains($namaLevel, 'legal') || str_contains($namaLevel, 'customer')) {
            return 'bg-purple-50 text-purple-700 border-purple-200';
        }

        return 'bg-slate-100 text-slate-700 border-slate-200';
    }

    /**
     * Role short description
     */
    public function getRoleDescriptionAttribute(): string
    {
        if ($this->isAdmin()) {
            return 'Master Admin & Akses Penuh Sistem';
        }
        if ($this->isFinance()) {
            return 'Manajemen Keuangan, Billing Tagihan, & Invoicing';
        }
        if ($this->isNoc()) {
            return 'Eksekusi Jaringan, Aktivasi, Suspend, & Terminasi';
        }
        if ($this->isTeknik()) {
            return 'Drafter & Pendaftaran Pelanggan Baru';
        }

        return $this->nama_level ?? 'Pengguna Sistem IMS';
    }
}
