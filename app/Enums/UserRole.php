<?php

namespace App\Enums;

enum UserRole: string
{
    case TEKNIK = 'teknik';
    case NOC = 'noc';
    case FINANCE = 'finance';

    public function label(): string
    {
        return match ($this) {
            self::TEKNIK => 'Teknik (Drafter)',
            self::NOC => 'NOC (Network Operation Center)',
            self::FINANCE => 'Finance & Billing',
        };
    }

    public function shortName(): string
    {
        return match ($this) {
            self::TEKNIK => 'Teknik',
            self::NOC => 'NOC',
            self::FINANCE => 'Finance',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TEKNIK => 'Drafter & Pendaftaran Pelanggan Baru',
            self::NOC => 'Eksekusi Jaringan, Aktivasi, Suspend, & Terminasi',
            self::FINANCE => 'Manajemen Keuangan, Billing, & Request Suspend',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::TEKNIK => 'bg-[#F0F9FF] text-[#0369A1] border-[#BAE6FD]',
            self::NOC => 'bg-[#EFF6FF] text-[#2563EB] border-[#DBEAFE]',
            self::FINANCE => 'bg-[#ECFDF5] text-[#047857] border-[#A7F3D0]',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TEKNIK => 'user-plus',
            self::NOC => 'server',
            self::FINANCE => 'wallet',
        };
    }
}
