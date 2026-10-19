<?php

namespace App\Enums;

enum UserRole: string
{
    case TEKNIK = 'teknik';
    case NOC = 'noc';
    case FINANCE = 'finance';
    case CEO = 'ceo';

    public function label(): string
    {
        return match ($this) {
            self::TEKNIK => 'Teknik (Drafter)',
            self::NOC => 'NOC (Network Operation Center)',
            self::FINANCE => 'Finance & Billing',
            self::CEO => 'Chief Executive Officer (CEO)',
        };
    }

    public function shortName(): string
    {
        return match ($this) {
            self::TEKNIK => 'Teknik',
            self::NOC => 'NOC',
            self::FINANCE => 'Finance',
            self::CEO => 'CEO',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TEKNIK => 'Drafter & Pendaftaran Pelanggan Baru',
            self::NOC => 'Eksekusi Jaringan, Aktivasi, Suspend, & Terminasi',
            self::FINANCE => 'Manajemen Keuangan, Billing, & Request Suspend',
            self::CEO => 'Executive Monitoring (Read-Only)',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::TEKNIK => 'bg-sky-500/10 text-sky-400 border-sky-500/30',
            self::NOC => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30',
            self::FINANCE => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
            self::CEO => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TEKNIK => 'user-plus',
            self::NOC => 'server',
            self::FINANCE => 'wallet',
            self::CEO => 'eye',
        };
    }
}
