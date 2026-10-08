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
                'title' => 'Pelanggan Tidak Ditemukan',
                'summary' => "Pelanggan dengan nomor internet {$nomorInternet} tidak ditemukan di database.",
                'details' => ['database' => false, 'messages' => ["Pelanggan {$nomorInternet} tidak ditemukan."]],
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

        // 2. MikroTik: Enable PPPoE Secret terlebih dahulu
        if ($pppoeUsername) {
            try {
                $mkEnable = $this->mikrotik->enableUser($pppoeUsername);
                $results['mikrotik_enable'] = (bool)($mkEnable['success'] ?? false);
                $results['messages'][] = 'MikroTik Enable: ' . ($mkEnable['message'] ?? 'Tidak ada respon');
            } catch (Exception $e) {
                $results['mikrotik_enable'] = false;
                $results['messages'][] = 'MikroTik Enable Error: ' . $e->getMessage();
            }

            // JIKA ROUTER GAGAL: JANGAN UBAH DATABASE!
            if (!$results['mikrotik_enable']) {
                $results['messages'][] = 'Database TIDAK diubah karena router MikroTik gagal mengaktifkan PPPoE secret.';

                if (Schema::hasTable('activity_logs')) {
                    try {
                        \App\Models\ActivityLog::record([
                            'user_id' => $operator,
                            'customer_id' => $nomorInternet,
                            'action' => 'activate',
                            'old_status' => $customer->status_reg ?? null,
                            'new_status' => $customer->status_reg ?? null,
                            'description' => "Gagal Aktivasi: MikroTik gagal mengaktifkan PPPoE '{$pppoeUsername}'. Database tidak diubah. " . ($note ? "Note: {$note}" : ''),
                            'router_response' => implode(' | ', $results['messages']),
                            'router_success' => false,
                        ]);
                    } catch (Exception $e) {
                        Log::warning('Gagal log activity_logs aktivasi failed: ' . $e->getMessage());
                    }
                }

                return [
                    'success' => false,
                    'title' => 'Aktivasi Gagal di Router MikroTik',
                    'summary' => implode(' | ', $results['messages']),
                    'details' => $results,
                ];
            }
        } else {
            $results['messages'][] = 'MikroTik: User PPPoE (ont_us) kosong, lewati verifikasi router.';
        }

        // 3. Update Database: Status Reg -> 20 (Aktif) HANYA JIKA ROUTER BERHASIL
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

        // 4. MikroTik: Kick Active Connection (Re-auth)
        if ($pppoeUsername) {
            try {
                $mkKick = $this->mikrotik->kickActiveConnection($pppoeUsername);
                $results['mikrotik_kick'] = (bool)($mkKick['success'] ?? false);
                $results['messages'][] = 'MikroTik Kick: ' . ($mkKick['message'] ?? '');
            } catch (Exception $e) {
                $results['messages'][] = 'MikroTik Kick Error: ' . $e->getMessage();
            }
        }

        // 5. OLT: Remote Reboot ONU
        if ($indexOlt) {
            try {
                $oltReboot = $this->olt->rebootOnu($indexOlt, $kodeOlt);
                $results['olt_reboot'] = (bool)($oltReboot['success'] ?? false);
                $results['messages'][] = 'OLT Reboot: ' . ($oltReboot['message'] ?? '');
            } catch (Exception $e) {
                $results['messages'][] = 'OLT Reboot Error: ' . $e->getMessage();
            }
        } else {
            $results['messages'][] = 'OLT: index_olt belum terdaftar, lewati remote reboot.';
        }

        // 6. Audit Trail Logging (trx_batchjob_register_log)
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

        // 6b. Activity Log Router (tabel activity_logs)
        if (Schema::hasTable('activity_logs')) {
            try {
                \App\Models\ActivityLog::record([
                    'user_id' => $operator,
                    'customer_id' => $nomorInternet,
                    'action' => 'activate',
                    'old_status' => $customer->status_reg ?? null,
                    'new_status' => '20',
                    'description' => "Aktivasi Layanan: PPPoE '{$pppoeUsername}' enable & kick, OLT ONU '{$indexOlt}' reboot. " . ($note ? "Note: {$note}" : ''),
                    'router_response' => implode(' | ', $results['messages']),
                    'router_success' => $isOverallSuccess,
                ]);
            } catch (Exception $e) {
                Log::warning('Gagal log activity_logs aktivasi: ' . $e->getMessage());
            }
        }

        return [
            'success' => $isOverallSuccess,
            'title' => 'Layanan Pelanggan Berhasil Diaktifkan',
            'summary' => implode(' | ', $results['messages']),
            'details' => $results,
        ];
    }


    /**
     * UNIFIED ACTION: SUSPEND / ISOLIR LAYANAN
     * 1. MikroTik -> Disable PPPoE Secret (disabled=yes) - FIRST
     * 2. JIKA BERHASIL -> Update Database Status -> 21 (Suspend / Isolir)
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
                'title' => 'Pelanggan Tidak Ditemukan',
                'summary' => "Pelanggan dengan nomor internet {$nomorInternet} tidak ditemukan di database.",
                'details' => ['database' => false, 'messages' => ["Pelanggan {$nomorInternet} tidak ditemukan."]],
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

        // 2. MikroTik: Disable PPPoE Secret terlebih dahulu
        if ($pppoeUsername) {
            try {
                $mkDisable = $this->mikrotik->disableUser($pppoeUsername);
                $results['mikrotik_disable'] = (bool)($mkDisable['success'] ?? false);
                $results['messages'][] = 'MikroTik Disable: ' . ($mkDisable['message'] ?? 'Tidak ada respon');
            } catch (Exception $e) {
                $results['mikrotik_disable'] = false;
                $results['messages'][] = 'MikroTik Disable Error: ' . $e->getMessage();
            }

            // JIKA ROUTER GAGAL: JANGAN UBAH DATABASE!
            if (!$results['mikrotik_disable']) {
                $results['messages'][] = 'Database TIDAK diubah karena router MikroTik gagal menonaktifkan PPPoE secret.';

                if (Schema::hasTable('activity_logs')) {
                    try {
                        \App\Models\ActivityLog::record([
                            'user_id' => $operator,
                            'customer_id' => $nomorInternet,
                            'action' => 'suspend',
                            'old_status' => $customer->status_reg ?? null,
                            'new_status' => $customer->status_reg ?? null,
                            'description' => "Gagal Suspend: MikroTik gagal menonaktifkan PPPoE '{$pppoeUsername}'. Database tidak diubah. Alasan: {$reason}",
                            'router_response' => implode(' | ', $results['messages']),
                            'router_success' => false,
                        ]);
                    } catch (Exception $e) {
                        Log::warning('Gagal log activity_logs suspend failed: ' . $e->getMessage());
                    }
                }

                return [
                    'success' => false,
                    'title' => 'Suspend Gagal di Router MikroTik',
                    'summary' => implode(' | ', $results['messages']),
                    'details' => $results,
                ];
            }
        } else {
            $results['messages'][] = 'MikroTik: User PPPoE (ont_us) kosong, lewati verifikasi router.';
        }

        // 3. Update Database: Status Reg -> 21 (Suspend) HANYA JIKA ROUTER BERHASIL
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

        // 4. MikroTik: Kick Active Connection (Seketika Terputus)
        if ($pppoeUsername) {
            try {
                $mkKick = $this->mikrotik->kickActiveConnection($pppoeUsername);
                $results['mikrotik_kick'] = (bool)($mkKick['success'] ?? false);
                $results['messages'][] = 'MikroTik Kick: ' . ($mkKick['message'] ?? '');
            } catch (Exception $e) {
                $results['messages'][] = 'MikroTik Kick Error: ' . $e->getMessage();
            }
        }

        // 5. OLT: Remote Reboot ONU
        if ($indexOlt) {
            try {
                $oltReboot = $this->olt->rebootOnu($indexOlt, $kodeOlt);
                $results['olt_reboot'] = (bool)($oltReboot['success'] ?? false);
                $results['messages'][] = 'OLT Reboot: ' . ($oltReboot['message'] ?? '');
            } catch (Exception $e) {
                $results['messages'][] = 'OLT Reboot Error: ' . $e->getMessage();
            }
        } else {
            $results['messages'][] = 'OLT: index_olt belum terdaftar, lewati remote reboot.';
        }

        // 6. Audit Trail Logging (trx_batchjob_register_log)
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

        // 6b. Activity Log Router (tabel activity_logs)
        if (Schema::hasTable('activity_logs')) {
            try {
                \App\Models\ActivityLog::record([
                    'user_id' => $operator,
                    'customer_id' => $nomorInternet,
                    'action' => 'suspend',
                    'old_status' => $customer->status_reg ?? null,
                    'new_status' => '21',
                    'description' => "Suspend/Isolir Layanan: PPPoE '{$pppoeUsername}' disable & kick. Alasan: {$reason}",
                    'router_response' => implode(' | ', $results['messages']),
                    'router_success' => $isOverallSuccess,
                ]);
            } catch (Exception $e) {
                Log::warning('Gagal log activity_logs suspend: ' . $e->getMessage());
            }
        }

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
