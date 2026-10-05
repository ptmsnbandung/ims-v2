<?php

namespace App\Services\Network;

use App\Services\Olt\OltConnectionService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CustomerProvisioningService
{
    protected MikrotikService $mikrotik;
    protected OltConnectionService $olt;

    public function __construct(MikrotikService $mikrotik, OltConnectionService $olt)
    {
        $this->mikrotik = $mikrotik;
        $this->olt = $olt;
    }

    /**
     * UNIFIED ACTION: AKTIFKAN LAYANAN
     * 1. Update Database Status -> 20 (Aktif)
     * 2. MikroTik -> Enable PPPoE Secret (disabled=no)
     * 3. MikroTik -> Kick Active Session (re-auth / reconnect instant)
     * 4. OLT -> Remote Reboot ONU (mode pon-onu-mng)
     * 5. Log ke Activity Audit Trail
     */
    public function activateCustomer(string $nomorInternet, ?string $operator = null, ?string $note = null): array
    {
        $now = now()->format('Y-m-d H:i:s');
        $operator = $operator ?: (auth()->user()->nama ?? 'System NOC');

        // 1. Ambil data pelanggan
        $customer = DB::table('trx_batchjob_register')
            ->where('nomor_internet', $nomorInternet)
            ->first();

        if (!$customer) {
            return [
                'success' => false,
                'message' => "Pelanggan dengan nomor internet {$nomorInternet} tidak ditemukan di database.",
            ];
        }

        $pppoeUsername = trim($customer->ont_us ?? '');
        $indexOlt = trim($customer->index_olt ?? '');
        $kodeOlt = trim($customer->olt ?? '');
        $routerId = $customer->router_id ?? null;

        // 1b. Resolve router spesifik pelanggan dari trx_batchjob_register.router_id
        $this->resolveCustomerRouter($routerId, $nomorInternet);

        $results = [
            'database' => false,
            'mikrotik_enable' => false,
            'mikrotik_kick' => false,
            'olt_reboot' => false,
            'messages' => [],
        ];

        // 2. Update Database: Status Reg -> 20 (Aktif)
        try {
            DB::table('trx_batchjob_register')
                ->where('nomor_internet', $nomorInternet)
                ->update([
                    'status_reg' => '20',
                    'date_update' => $now,
                    'user_update' => $operator,
                ]);

            // Jika ada data suspend yang aktif, update status_suspend ke selesai (13)
            if (Schema::hasTable('trx_suspend')) {
                DB::table('trx_suspend')
                    ->where('nomor_internet', $nomorInternet)
                    ->whereIn('status_suspend', ['11', '12', '18'])
                    ->update([
                        'status_suspend' => '13',
                        'suspend_end' => now()->format('Y-m-d'),
                        'date_update' => $now,
                        'user_update' => $operator,
                    ]);
            }

            $results['database'] = true;
            $results['messages'][] = 'Status database berhasil diubah menjadi AKTIF (#20)';
        } catch (Exception $e) {
            $results['messages'][] = 'Gagal update database: ' . $e->getMessage();
        }

        // 3. MikroTik: Enable PPPoE Secret
        if ($pppoeUsername) {
            try {
                $mkEnable = $this->mikrotik->enableUser($pppoeUsername);
                $results['mikrotik_enable'] = $mkEnable['success'];
                $results['messages'][] = 'MikroTik Enable: ' . $mkEnable['message'];
            } catch (Exception $e) {
                $results['messages'][] = 'MikroTik Enable Error: ' . $e->getMessage();
            }

            // 4. MikroTik: Kick Active Connection (Re-auth)
            try {
                $mkKick = $this->mikrotik->kickActiveConnection($pppoeUsername);
                $results['mikrotik_kick'] = $mkKick['success'];
                $results['messages'][] = 'MikroTik Kick: ' . $mkKick['message'];
            } catch (Exception $e) {
                $results['messages'][] = 'MikroTik Kick Error: ' . $e->getMessage();
            }
        } else {
            $results['messages'][] = 'MikroTik: User PPPoE (ont_us) kosong, lewati konfigurasi router.';
        }

        // 5. OLT: Remote Reboot ONU
        if ($indexOlt) {
            try {
                $oltReboot = $this->olt->rebootOnu($indexOlt, $kodeOlt);
                $results['olt_reboot'] = $oltReboot['success'];
                $results['messages'][] = 'OLT Reboot: ' . $oltReboot['message'];
            } catch (Exception $e) {
                $results['messages'][] = 'OLT Reboot Error: ' . $e->getMessage();
            }
        } else {
            $results['messages'][] = 'OLT: index_olt belum terdaftar, lewati remote reboot.';
        }

        // 6. Audit Trail Logging
        if (Schema::hasTable('trx_batchjob_register_log')) {
            try {
                DB::table('trx_batchjob_register_log')->insert([
                    'kode_batchjob_register_log' => 'L-' . $nomorInternet . '-' . rand(1000, 9999),
                    'nomor_internet' => $nomorInternet,
                    'status_reg' => '20',
                    'kat_log' => '20',
                    'note_schedule' => "AKTIVASI LENGKAP: PPPoE '{$pppoeUsername}' di-enable & kick, OLT ONU '{$indexOlt}' direboot. Op: {$operator}. " . ($note ? "({$note})" : ''),
                    'date_schedule' => now()->format('Y-m-d'),
                    'time_schedule' => now()->format('H:i:s'),
                    'date_create' => $now,
                    'user_create' => $operator,
                ]);
            } catch (Exception $e) {
                Log::warning('Gagal log aktivasi: ' . $e->getMessage());
            }
        }

        $isOverallSuccess = $results['database'] && ($results['mikrotik_enable'] || !$pppoeUsername);

        return [
            'success' => $isOverallSuccess,
            'title' => 'Layanan Pelanggan Berhasil Diaktifkan',
            'summary' => implode(' | ', $results['messages']),
            'details' => $results,
        ];
    }


    /**
     * UNIFIED ACTION: SUSPEND / ISOLIR LAYANAN
     * 1. Update Database Status -> 21 (Suspend / Isolir)
     * 2. MikroTik -> Disable PPPoE Secret (disabled=yes)
     * 3. MikroTik -> Kick Active Session (Langsung Terputus)
     * 4. OLT -> Remote Reboot ONU (mode pon-onu-mng)
     * 5. Log ke Activity Audit Trail
     */
    public function suspendCustomer(string $nomorInternet, ?string $operator = null, ?string $reason = null): array
    {
        $now = now()->format('Y-m-d H:i:s');
        $operator = $operator ?: (auth()->user()->nama ?? 'System NOC');
        $reason = $reason ?: 'Isolir / Suspend Layanan Pelanggan';

        // 1. Ambil data pelanggan
        $customer = DB::table('trx_batchjob_register')
            ->where('nomor_internet', $nomorInternet)
            ->first();

        if (!$customer) {
            return [
                'success' => false,
                'message' => "Pelanggan dengan nomor internet {$nomorInternet} tidak ditemukan di database.",
            ];
        }

        $pppoeUsername = trim($customer->ont_us ?? '');
        $indexOlt = trim($customer->index_olt ?? '');
        $kodeOlt = trim($customer->olt ?? '');
        $routerId = $customer->router_id ?? null;

        // 1b. Resolve router spesifik pelanggan dari trx_batchjob_register.router_id
        $this->resolveCustomerRouter($routerId, $nomorInternet);

        $results = [
            'database' => false,
            'mikrotik_disable' => false,
            'mikrotik_kick' => false,
            'olt_reboot' => false,
            'messages' => [],
        ];

        // 2. Update Database: Status Reg -> 21 (Suspend)
        try {
            DB::table('trx_batchjob_register')
                ->where('nomor_internet', $nomorInternet)
                ->update([
                    'status_reg' => '21',
                    'date_update' => $now,
                    'user_update' => $operator,
                ]);

            // Jika ada tabel trx_suspend, buat atau perbarui record suspend aktif (12)
            if (Schema::hasTable('trx_suspend')) {
                $kodeSuspend = 'SUS-' . $nomorInternet . '-' . date('Ymd');
                $existing = DB::table('trx_suspend')->where('nomor_internet', $nomorInternet)->where('status_suspend', '12')->first();
                if (!$existing) {
                    DB::table('trx_suspend')->insert([
                        'kode_suspend' => $kodeSuspend,
                        'nomor_internet' => $nomorInternet,
                        'desc_suspend' => $reason,
                        'status_suspend' => '12',
                        'suspend_start' => now()->format('Y-m-d'),
                        'date_create' => $now,
                        'user_create' => $operator,
                        'date_update' => $now,
                        'user_update' => $operator,
                        'hide' => '0',
                    ]);
                }
            }

            $results['database'] = true;
            $results['messages'][] = 'Status database berhasil diubah menjadi SUSPEND (#21)';
        } catch (Exception $e) {
            $results['messages'][] = 'Gagal update database: ' . $e->getMessage();
        }

        // 3. MikroTik: Disable PPPoE Secret
        if ($pppoeUsername) {
            try {
                $mkDisable = $this->mikrotik->disableUser($pppoeUsername);
                $results['mikrotik_disable'] = $mkDisable['success'];
                $results['messages'][] = 'MikroTik Disable: ' . $mkDisable['message'];
            } catch (Exception $e) {
                $results['messages'][] = 'MikroTik Disable Error: ' . $e->getMessage();
            }

            // 4. MikroTik: Kick Active Connection (Seketika Terputus)
            try {
                $mkKick = $this->mikrotik->kickActiveConnection($pppoeUsername);
                $results['mikrotik_kick'] = $mkKick['success'];
                $results['messages'][] = 'MikroTik Kick: ' . $mkKick['message'];
            } catch (Exception $e) {
                $results['messages'][] = 'MikroTik Kick Error: ' . $e->getMessage();
            }
        } else {
            $results['messages'][] = 'MikroTik: User PPPoE (ont_us) kosong, lewati konfigurasi router.';
        }

        // 5. OLT: Remote Reboot ONU
        if ($indexOlt) {
            try {
                $oltReboot = $this->olt->rebootOnu($indexOlt, $kodeOlt);
                $results['olt_reboot'] = $oltReboot['success'];
                $results['messages'][] = 'OLT Reboot: ' . $oltReboot['message'];
            } catch (Exception $e) {
                $results['messages'][] = 'OLT Reboot Error: ' . $e->getMessage();
            }
        } else {
            $results['messages'][] = 'OLT: index_olt belum terdaftar, lewati remote reboot.';
        }

        // 6. Audit Trail Logging
        if (Schema::hasTable('trx_batchjob_register_log')) {
            try {
                DB::table('trx_batchjob_register_log')->insert([
                    'kode_batchjob_register_log' => 'L-' . $nomorInternet . '-' . rand(1000, 9999),
                    'nomor_internet' => $nomorInternet,
                    'status_reg' => '21',
                    'kat_log' => '21',
                    'note_schedule' => "SUSPEND/ISOLIR LENGKAP: PPPoE '{$pppoeUsername}' di-disable & kick, OLT ONU '{$indexOlt}' direboot. Op: {$operator}. Alasan: {$reason}",
                    'date_schedule' => now()->format('Y-m-d'),
                    'time_schedule' => now()->format('H:i:s'),
                    'date_create' => $now,
                    'user_create' => $operator,
                ]);
            } catch (Exception $e) {
                Log::warning('Gagal log suspend: ' . $e->getMessage());
            }
        }

        $isOverallSuccess = $results['database'] && ($results['mikrotik_disable'] || !$pppoeUsername);

        return [
            'success' => $isOverallSuccess,
            'title' => 'Layanan Pelanggan Berhasil Disuspend / Diisolir',
            'summary' => implode(' | ', $results['messages']),
            'details' => $results,
        ];
    }

    /**
     * Resolve router MikroTik spesifik yang menangani pelanggan ini.
     * Mengambil konfigurasi dari tabel `routers` berdasarkan `router_id` pelanggan di `trx_batchjob_register`.
     *
     * Jika tidak ada router_id spesifik, menggunakan router default (aktif pertama).
     */
    private function resolveCustomerRouter(?int $routerId, string $nomorInternet): void
    {
        try {
            if (!Schema::hasTable('routers')) {
                return;
            }

            $router = null;
            if ($routerId) {
                $router = DB::table('routers')
                    ->where('id', $routerId)
                    ->where('is_active', 1)
                    ->first();
            }

            // Fallback ke router aktif pertama jika router_id tidak ditemukan
            if (!$router) {
                $router = DB::table('routers')
                    ->where('is_active', 1)
                    ->first();
            }

            if (!$router || empty($router->host)) {
                Log::warning("[CustomerProvisioning] Tidak ada router MikroTik aktif untuk pelanggan {$nomorInternet}.");
                return;
            }

            // Decode password jika terenkripsi
            $pass = $router->password;
            try {
                $pass = \Illuminate\Support\Facades\Crypt::decryptString($pass);
            } catch (\Exception $e) {
                // Password tidak terenkripsi, gunakan langsung
            }

            // Inject konfigurasi router spesifik ke MikrotikService
            $this->mikrotik->setRouter([
                'host'     => $router->host,
                'port'     => (int)($router->port ?: 18735),
                'username' => $router->username,
                'password' => $pass,
            ]);

            Log::info("[CustomerProvisioning] Router resolved: [{$router->name}] {$router->host} untuk pelanggan {$nomorInternet}");
        } catch (\Exception $e) {
            Log::error('[CustomerProvisioning] resolveCustomerRouter error: ' . $e->getMessage());
        }
    }
}
