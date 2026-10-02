<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto Generate Invoice Massal Bulanan untuk Pelanggan Aktif & Suspend (Setiap tanggal 1 jam 08:00 pagi WIB)
Schedule::command('finance:auto-generate-invoices')
    ->cron('0 8 1 * *')
    ->timezone('Asia/Jakarta')
    ->description('Otomatis Generate Invoice Bulanan Massal Pelanggan Aktif & Suspend (Setiap Tanggal 1 jam 08:00 WIB)');

// Auto Request Suspend ke NOC untuk pelanggan yang belum bayar (Setiap tanggal 25 jam 06:00 pagi)
Schedule::command('suspend:unpaid-customers')
    ->cron('0 6 25 * *')
    ->timezone('Asia/Jakarta')
    ->description('Otomatis Request Suspend ke NOC untuk Pelanggan Belum Bayar (Jatuh Tempo Tanggal 25 jam 06:00 WIB)');


