<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mikrotik RouterOS Default Configuration
    |--------------------------------------------------------------------------
    | Konfigurasi default koneksi RouterOS API untuk manajemen PPPoE
    */
    'host' => env('MIKROTIK_HOST', '103.161.206.163'),
    'port' => (int) env('MIKROTIK_PORT', 18735),
    'user' => env('MIKROTIK_USER', 'msn'),
    'pass' => env('MIKROTIK_PASS', 'kayuagung2-9'),
    'timeout' => (int) env('MIKROTIK_TIMEOUT', 5),
    'ssl' => (bool) env('MIKROTIK_SSL', false),
];
