<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto Request Suspend ke NOC untuk pelanggan yang belum bayar (Setiap tanggal 25 jam 06:00 pagi)
Schedule::command('suspend:unpaid-customers')
    ->cron('0 6 25 * *')
    ->description('Otomatis Request Suspend ke NOC untuk Pelanggan Belum Bayar (Jatuh Tempo Tanggal 25 jam 06:00)');

