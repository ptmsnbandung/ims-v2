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

        $usernameCandidates = $this->getUsernameCandidates($customer, $nomorInternet);
        $pppoeUsername = $usernameCandidates[0] ?? '';
        $indexOlt = trim($customer->index_olt ?? '');
        $kodeOlt = trim($customer->olt ?? '');
        $routerId = $customer->router_id ?? null;

        // 1b. Resolve router spesifik pelanggan dari trx_batchjob_register.router_id (dengan auto-discovery multi-router)
        $this->resolveCustomerRouter($routerId, $nomorInternet, $usernameCandidates);

        $results = [
            'database' => false,
            'mikrotik_enable' => false,
            'mikrotik_kick' => false,
            'olt_reboot' => false,
            'messages' => [],
        ];

        // 2. MikroTik: Enable PPPoE Secret terlebih dahulu
        if (!empty($usernameCandidates)) {
            try {
                $mkEnable = $this->mikrotik->enableUser($usernameCandidates);
                $results['mikrotik_enable'] = (bool)($mkEnable['success'] ?? false);
                $results['messages'][] = 'MikroTik Enable: ' . ($mkEnable['message'] ?? 'Tidak ada respon');
                if (!empty($mkEnable['matched_user'])) {
                    $pppoeUsername = $mkEnable['matched_user'];
                }
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
        if (!empty($usernameCandidates)) {
            try {
                $mkKick = $this->mikrotik->kickActiveConnection($usernameCandidates);
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

        $isOverallSuccess = $results['database'] && ($results['mikrotik_enable'] || empty($usernameCandidates));

        // Format summary yang bersih dan ringkas untuk alert user
        if ($isOverallSuccess) {
            $summaryParts = [];
            $summaryParts[] = "PPPoE '{$pppoeUsername}' berhasil diaktifkan kembali di MikroTik.";
            if ($results['mikrotik_kick']) {
                $summaryParts[] = "Sesi koneksi di-kick untuk re-autentikasi.";
            }
            if ($results['olt_reboot']) {
                $summaryParts[] = "ONT OLT direboot.";
            }
            $summaryParts[] = "Status pelanggan kini Aktif (#20).";
            $cleanSummary = implode(' ', $summaryParts);
        } else {
            $cleanSummary = "Aktivasi belum selesai secara penuh. " . implode('; ', array_slice($results['messages'], 0, 2));
        }

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
            'title' => $isOverallSuccess ? 'Layanan Pelanggan Berhasil Diaktifkan' : 'Gagal Menyelesaikan Aktivasi',
            'summary' => $cleanSummary,
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
    public function suspendCustomer(string $nomorInternet, ?string $operator = null, ?string $reason = null, bool $skipOltReboot = false): array
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

        $usernameCandidates = $this->getUsernameCandidates($customer, $nomorInternet);
        $pppoeUsername = $usernameCandidates[0] ?? '';
        $indexOlt = trim($customer->index_olt ?? '');
        $kodeOlt = trim($customer->olt ?? '');
        $routerId = $customer->router_id ?? null;

        // 1b. Resolve router spesifik pelanggan dari trx_batchjob_register.router_id (dengan auto-discovery multi-router)
        $this->resolveCustomerRouter($routerId, $nomorInternet, $usernameCandidates);

        $results = [
            'database' => false,
            'mikrotik_disable' => false,
            'mikrotik_kick' => false,
            'olt_reboot' => false,
            'messages' => [],
        ];

        // 2. MikroTik: Disable PPPoE Secret terlebih dahulu
        if (!empty($usernameCandidates)) {
            try {
                $mkDisable = $this->mikrotik->disableUser($usernameCandidates);
                $results['mikrotik_disable'] = (bool)($mkDisable['success'] ?? false);
                $results['messages'][] = 'MikroTik Disable: ' . ($mkDisable['message'] ?? 'Tidak ada respon');
                if (!empty($mkDisable['matched_user'])) {
                    $pppoeUsername = $mkDisable['matched_user'];
                }
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

            $results['database'] = true;
            $results['messages'][] = 'Status database berhasil diubah menjadi SUSPEND (#21)';

            // Jika ada tabel trx_suspend, buat atau perbarui record suspend aktif (12)
            if (Schema::hasTable('trx_suspend')) {
                try {
                    $activeSuspend = DB::table('trx_suspend')
                        ->where('nomor_internet', $nomorInternet)
                        ->where('status_suspend', '12')
                        ->first();

                    if ($activeSuspend) {
                        DB::table('trx_suspend')
                            ->where('kode_suspend', $activeSuspend->kode_suspend)
                            ->update([
                                'desc_suspend' => $reason,
                                'date_update'  => $now,
                                'user_update'  => $operator,
                            ]);
                    } else {
                        $baseKode = 'SUS-' . $nomorInternet . '-' . date('Ymd');
                        $existingByKode = DB::table('trx_suspend')->where('kode_suspend', $baseKode)->first();

                        if ($existingByKode && $existingByKode->nomor_internet == $nomorInternet) {
                            // Record dengan kode ini milik pelanggan yang sama, perbarui status menjadi 12
                            DB::table('trx_suspend')->where('kode_suspend', $baseKode)->update([
                                'desc_suspend'   => $reason,
                                'status_suspend' => '12',
                                'suspend_start'  => now()->format('Y-m-d'),
                                'suspend_end'    => null,
                                'date_update'    => $now,
                                'user_update'    => $operator,
                            ]);
                        } else {
                            $kodeSuspend = $existingByKode ? ($baseKode . '-' . date('His')) : $baseKode;
                            DB::table('trx_suspend')->insert([
                                'kode_suspend'   => $kodeSuspend,
                                'nomor_internet' => $nomorInternet,
                                'desc_suspend'   => $reason,
                                'status_suspend' => '12',
                                'suspend_start'  => now()->format('Y-m-d'),
                                'date_create'    => $now,
                                'user_create'    => $operator,
                                'date_update'    => $now,
                                'user_update'    => $operator,
                                'hide'           => '0',
                            ]);
                        }
                    }
                } catch (\Throwable $exSuspend) {
                    \Illuminate\Support\Facades\Log::warning("Notice update trx_suspend {$nomorInternet}: " . $exSuspend->getMessage());
                }
            }
        } catch (Exception $e) {
            $results['messages'][] = 'Gagal update database: ' . $e->getMessage();
        }

        // 4. MikroTik: Kick Active Connection (Seketika Terputus)
        if (!empty($usernameCandidates)) {
            try {
                $mkKick = $this->mikrotik->kickActiveConnection($usernameCandidates);
                $results['mikrotik_kick'] = (bool)($mkKick['success'] ?? false);
                $results['messages'][] = 'MikroTik Kick: ' . ($mkKick['message'] ?? '');
            } catch (Exception $e) {
                $results['messages'][] = 'MikroTik Kick Error: ' . $e->getMessage();
            }
        }

        // 5. OLT: Remote Reboot ONU (dilewati jika mode background queue massal)
        if ($skipOltReboot) {
            $results['olt_reboot'] = false;
            $results['messages'][] = 'OLT Reboot: Ditugaskan ke antrean background queue.';
        } elseif ($indexOlt) {
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

        $isOverallSuccess = $results['database'] && ($results['mikrotik_disable'] || empty($usernameCandidates));

        // Format summary yang bersih dan ringkas untuk alert user
        if ($isOverallSuccess) {
            $summaryParts = [];
            $summaryParts[] = "PPPoE '{$pppoeUsername}' berhasil dinonaktifkan di MikroTik.";
            if ($results['mikrotik_kick']) {
                $summaryParts[] = "Sesi koneksi diputus seketika.";
            }
            if ($results['olt_reboot']) {
                $summaryParts[] = "ONT OLT direboot.";
            }
            $summaryParts[] = "Status pelanggan kini Disuspend / Diisolir (#21).";
            $cleanSummary = implode(' ', $summaryParts);
        } else {
            $cleanSummary = "Suspend belum selesai secara penuh. " . implode('; ', array_slice($results['messages'], 0, 2));
        }

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
            'title' => $isOverallSuccess ? 'Layanan Pelanggan Berhasil Disuspend / Diisolir' : 'Gagal Menyelesaikan Suspend',
            'summary' => $cleanSummary,
            'details' => $results,
        ];
    }

    /**
     * UNIFIED ACTION: TERMINASI LAYANAN (HAPUS USER DI MIKROTIK & NONAKTIFKAN DI DATABASE)
     * 1. MikroTik -> Kick Active Session (putus sesi koneksi seketika)
     * 2. MikroTik -> Remove PPPoE Secret (/ppp/secret/remove) HAPUS USER PERMANEN
     * 3. Update Database Status -> 23 (Terminated / Nonaktif)
     * 4. Update trx_terminasi -> status_terminasi = 14 (KD14 Terminasi Selesai)
     * 5. Log ke Activity Audit Trail
     */
    public function terminateCustomer(string $nomorInternet, ?string $operator = null, ?string $note = null, ?string $kodeTrx = null, ?string $dateDone = null): array
    {
        $now = now()->format('Y-m-d H:i:s');
        $operator = $operator ?: (auth()->user()->nama ?? 'System NOC');
        $reason = $note ?: 'Terminasi Layanan Pelanggan (Hapus User Router)';
        $finalDateDone = $dateDone ? (strlen($dateDone) <= 10 ? $dateDone . ' ' . date('H:i:s') : $dateDone) : $now;

        // 1. Ambil data pelanggan dari trx_batchjob_register atau tb_pelanggan / view_pelanggan
        $customer = DB::table('trx_batchjob_register')
            ->where('nomor_internet', $nomorInternet)
            ->first();

        if (!$customer && Schema::hasTable('tb_pelanggan')) {
            $customer = DB::table('tb_pelanggan')
                ->where('nomor_internet', $nomorInternet)
                ->first();
        }

        $usernameCandidates = $customer ? $this->getUsernameCandidates($customer, $nomorInternet) : [$nomorInternet];
        $pppoeUsername = $usernameCandidates[0] ?? $nomorInternet;
        $routerId = $customer->router_id ?? null;

        // 1b. Resolve router spesifik pelanggan
        $this->resolveCustomerRouter($routerId, $nomorInternet, $usernameCandidates);

        $results = [
            'database' => false,
            'mikrotik_kick' => false,
            'mikrotik_remove' => false,
            'messages' => [],
        ];

        // 2. MikroTik: Kick sesi aktif terlebih dahulu agar langsung terputus
        if (!empty($usernameCandidates)) {
            try {
                $mkKick = $this->mikrotik->kickActiveConnection($usernameCandidates);
                $results['mikrotik_kick'] = (bool)($mkKick['success'] ?? false);
                $results['messages'][] = 'MikroTik Kick: ' . ($mkKick['message'] ?? '');
            } catch (Exception $e) {
                $results['mikrotik_kick'] = false;
                $results['messages'][] = 'MikroTik Kick Error: ' . $e->getMessage();
            }

            // 3. MikroTik: Hapus User PPPoE Secret (/ppp/secret/remove)
            try {
                $mkRemove = $this->mikrotik->removeUser($usernameCandidates);
                $results['mikrotik_remove'] = (bool)($mkRemove['success'] ?? false);
                $results['messages'][] = 'MikroTik Remove: ' . ($mkRemove['message'] ?? 'Tidak ada respon');
                if (!empty($mkRemove['matched_user'])) {
                    $pppoeUsername = $mkRemove['matched_user'];
                }
            } catch (Exception $e) {
                $results['mikrotik_remove'] = false;
                $results['messages'][] = 'MikroTik Remove Error: ' . $e->getMessage();
            }
        } else {
            $results['messages'][] = 'MikroTik: User PPPoE kosong, lewati router.';
            $results['mikrotik_remove'] = true;
        }

        // 4. Update Database: Ubah status pelanggan menjadi 23 (Terminated / Nonaktif)
        try {
            if (Schema::hasTable('trx_batchjob_register')) {
                DB::table('trx_batchjob_register')
                    ->where('nomor_internet', $nomorInternet)
                    ->update([
                        'status_reg' => '23',
                        'date_update' => $now,
                        'user_update' => $operator,
                    ]);
            }

            if (Schema::hasTable('tb_pelanggan')) {
                DB::table('tb_pelanggan')
                    ->where('nomor_internet', $nomorInternet)
                    ->update([
                        'status_reg' => '23',
                        'date_update' => $now,
                        'user_update' => $operator,
                    ]);
            }

            if (Schema::hasTable('m_pelanggan')) {
                DB::table('m_pelanggan')
                    ->where('nomor_internet', $nomorInternet)
                    ->update([
                        'status_reg' => '23',
                    ]);
            }

            // Update transaksi terminasi jika ada
            if ($kodeTrx) {
                DB::table('trx_terminasi')->where('kode_trx_terminasi', $kodeTrx)->update([
                    'status_terminasi' => '14', // (KD14) Terminasi Selesai
                    'date_termin_done' => $finalDateDone,
                    'note_termin_done' => $reason,
                    'date_update' => $now,
                    'user_update' => $operator,
                ]);
            } else {
                DB::table('trx_terminasi')->where('nomor_internet', $nomorInternet)->whereIn('status_terminasi', ['11', '12', '12.1', '13'])->update([
                    'status_terminasi' => '14',
                    'date_termin_done' => $finalDateDone,
                    'note_termin_done' => $reason,
                    'date_update' => $now,
                    'user_update' => $operator,
                ]);
            }

            $results['database'] = true;
            $results['messages'][] = 'Status database berhasil diubah menjadi NONAKTIF/TERMINATED (#23) & Terminasi Selesai (KD14).';
        } catch (Exception $e) {
            $results['messages'][] = 'Gagal update database: ' . $e->getMessage();
        }

        // 5. Audit Trail Logging (trx_batchjob_register_log)
        if (Schema::hasTable('trx_batchjob_register_log')) {
            try {
                DB::table('trx_batchjob_register_log')->insert([
                    'kode_batchjob_register_log' => 'L-' . $nomorInternet . '-' . rand(1000, 9999),
                    'nomor_internet' => $nomorInternet,
                    'status_reg' => '23',
                    'kat_log' => '23',
                    'note_schedule' => "TERMINASI LENGKAP: PPPoE '{$pppoeUsername}' DIHAPUS dari MikroTik & sesi diputus. Op: {$operator}. Alasan: {$reason}",
                    'date_schedule' => now()->format('Y-m-d'),
                    'time_schedule' => now()->format('H:i:s'),
                    'date_create' => $now,
                    'user_create' => $operator,
                ]);
            } catch (Exception $e) {
                Log::warning('Gagal log terminasi: ' . $e->getMessage());
            }
        }

        $isOverallSuccess = $results['database'] && ($results['mikrotik_remove'] || empty($usernameCandidates));

        // Format summary yang bersih dan ringkas
        if ($isOverallSuccess) {
            $summaryParts = [];
            $summaryParts[] = "PPPoE '{$pppoeUsername}' berhasil DIHAPUS dari MikroTik Router.";
            if ($results['mikrotik_kick']) {
                $summaryParts[] = "Sesi koneksi diputus seketika.";
            }
            $summaryParts[] = "Status pelanggan kini Nonaktif / Terminated (#23).";
            $cleanSummary = implode(' ', $summaryParts);
        } else {
            $cleanSummary = "Terminasi selesai di database, namun router memerlukan pengecekan: " . implode('; ', array_slice($results['messages'], 0, 2));
        }

        // 6. Activity Log Router (tabel activity_logs)
        if (Schema::hasTable('activity_logs')) {
            try {
                \App\Models\ActivityLog::record([
                    'user_id' => $operator,
                    'customer_id' => $nomorInternet,
                    'action' => 'terminate',
                    'old_status' => $customer->status_reg ?? null,
                    'new_status' => '23',
                    'description' => "Terminasi Layanan: User PPPoE '{$pppoeUsername}' DIHAPUS dari MikroTik & sesi diputus. Alasan: {$reason}",
                    'router_response' => implode(' | ', $results['messages']),
                    'router_success' => $isOverallSuccess,
                ]);
            } catch (Exception $e) {
                Log::warning('Gagal log activity_logs terminasi: ' . $e->getMessage());
            }
        }

        return [
            'success' => $isOverallSuccess,
            'title' => $isOverallSuccess ? 'Layanan Pelanggan Berhasil Diterminasi & Dihapus dari Router' : 'Gagal Menyelesaikan Terminasi',
            'summary' => $cleanSummary,
            'details' => $results,
        ];
    }

    /**
     * Helper untuk mengambil seluruh kandidat username PPPoE (ont_us, nomor_internet, pppoe_username, non-MS)
     */
    private function getUsernameCandidates(object $customer, string $nomorInternet): array
    {
        $raw = [
            trim($customer->ont_us ?? ''),
            trim($customer->pppoe_username ?? ''),
            trim($nomorInternet),
        ];

        $candidates = [];
        foreach ($raw as $val) {
            if ($val !== '') {
                $candidates[] = $val;
                // Add version without 'MS' or 'ms' prefix
                $stripped = preg_replace('/^MS/i', '', $val);
                if ($stripped !== '' && $stripped !== $val) {
                    $candidates[] = $stripped;
                }
            }
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * Resolve router MikroTik spesifik yang menangani pelanggan ini.
     * Mengambil konfigurasi dari tabel `routers` berdasarkan `router_id` pelanggan di `trx_batchjob_register`.
     * Jika user PPPoE tidak ditemukan pada router utama, otomatis mencari di seluruh router aktif lain
     * dan memperbarui router_id pelanggan di database agar sinkron secara otomatis.
     */
    private function resolveCustomerRouter(?int $routerId, string $nomorInternet, string|array|null $pppoeUsername = null): void
    {
        try {
            if (!Schema::hasTable('routers')) {
                return;
            }

            $routers = DB::table('routers')->where('is_active', 1)->get();
            if ($routers->isEmpty()) {
                Log::warning("[CustomerProvisioning] Tidak ada router MikroTik aktif di tabel routers.");
                return;
            }

            // 1. Ambil router utama (sesuai router_id atau router aktif pertama)
            $primaryRouter = null;
            if ($routerId) {
                $primaryRouter = $routers->firstWhere('id', $routerId);
            }
            if (!$primaryRouter) {
                $primaryRouter = $routers->first();
            }

            if (!$primaryRouter || empty($primaryRouter->host)) {
                Log::warning("[CustomerProvisioning] Router MikroTik tidak valid untuk pelanggan {$nomorInternet}.");
                return;
            }

            // Pasang konfigurasi router utama
            $this->applyRouterConfig($primaryRouter);
            Log::info("[CustomerProvisioning] Primary router selected: [{$primaryRouter->name}] {$primaryRouter->host} untuk pelanggan {$nomorInternet}");

            // 2. Jika ada pppoeUsername dan ada lebih dari 1 router, lakukan auto-discovery jika tidak ada di primary router
            if (!empty($pppoeUsername) && $routers->count() > 1) {
                $foundOnPrimary = $this->mikrotik->userExists($pppoeUsername);
                if (!$foundOnPrimary) {
                    $testNames = is_array($pppoeUsername) ? implode(', ', $pppoeUsername) : $pppoeUsername;
                    Log::info("[CustomerProvisioning] User '{$testNames}' tidak ditemukan di [{$primaryRouter->name}] ({$primaryRouter->host}). Mencoba auto-discovery ke router aktif lainnya...");

                    foreach ($routers as $otherRouter) {
                        if ($otherRouter->id === $primaryRouter->id) {
                            continue;
                        }

                        $this->applyRouterConfig($otherRouter);
                        $foundOther = $this->mikrotik->userExists($pppoeUsername);
                        if ($foundOther) {
                            Log::info("[CustomerProvisioning] Auto-Discovery SUKSES: User '{$foundOther}' ditemukan di router [{$otherRouter->name}] ({$otherRouter->host})! Memperbarui router_id pelanggan {$nomorInternet} -> {$otherRouter->id}");

                            try {
                                DB::table('trx_batchjob_register')
                                    ->where('nomor_internet', $nomorInternet)
                                    ->update(['router_id' => $otherRouter->id]);
                            } catch (\Throwable $dbErr) {
                                Log::warning("[CustomerProvisioning] Gagal update router_id di DB: " . $dbErr->getMessage());
                            }

                            return; // Router aktif sudah diset ke $otherRouter
                        }
                    }

                    // Jika tidak ditemukan di semua router, kembalikan ke primary router
                    $this->applyRouterConfig($primaryRouter);
                }
            }
        } catch (\Exception $e) {
            Log::error('[CustomerProvisioning] resolveCustomerRouter error: ' . $e->getMessage());
        }
    }

    private function applyRouterConfig(object $router): void
    {
        $pass = $router->password ?? '';
        try {
            $pass = \Illuminate\Support\Facades\Crypt::decryptString($pass);
        } catch (\Exception $e) {
            // Password plain text
        }

        $this->mikrotik->setRouter([
            'name'     => $router->name ?? null,
            'host'     => $router->host,
            'port'     => (int)($router->port ?: 18735),
            'username' => $router->username,
            'password' => $pass,
        ]);
    }
}
