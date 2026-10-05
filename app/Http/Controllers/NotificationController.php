<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    /**
     * Polling endpoint untuk notifikasi baru saat aplikasi sedang dibuka.
     * Mengembalikan event pendaftaran baru, tiket gangguan, up/downgrade, terminasi, dan suspend.
     */
    public function poll(Request $request): JsonResponse
    {
        $sinceTimestamp = $request->input('since');
        
        // Default to last 3 minutes if no since provided
        if (!empty($sinceTimestamp) && is_numeric($sinceTimestamp)) {
            $since = Carbon::createFromTimestamp((int) $sinceTimestamp);
        } else {
            $since = Carbon::now()->subMinutes(3);
        }

        $sinceFormatted = $since->format('Y-m-d H:i:s');
        $notifications = [];

        // 1. Cek Pendaftaran Pelanggan Baru (trx_pendaftaran)
        if (Schema::hasTable('trx_pendaftaran')) {
            $newRegistrations = DB::table('trx_pendaftaran')
                ->where('hide', 0)
                ->where(function ($q) use ($sinceFormatted) {
                    $q->where('date_create', '>=', $sinceFormatted)
                      ->orWhere('date_update', '>=', $sinceFormatted);
                })
                ->whereIn('status_reg', ['11', '12', '13']) // Pendaftaran Baru / Draft
                ->orderBy('date_create', 'desc')
                ->limit(5)
                ->get();

            foreach ($newRegistrations as $reg) {
                $nama = $reg->nama_pelanggan ?: 'Pelanggan';
                $notifications[] = [
                    'id' => 'reg_' . $reg->nomor_internet . '_' . strtotime($reg->date_create ?? 'now'),
                    'type' => 'pendaftaran',
                    'title' => 'Pelanggan Baru Terdaftar',
                    'message' => "Pendaftaran baru atas nama {$nama} (No: {$reg->nomor_internet})",
                    'speech_text' => "Ada pelanggan baru terdaftar, atas nama {$nama}",
                    'url' => route('teknik.pendaftaran'),
                    'created_at' => $reg->date_create,
                ];
            }
        }

        // 2. Cek Tiket Gangguan Baru (trx_tiket_gangguan)
        if (Schema::hasTable('trx_tiket_gangguan')) {
            $newTickets = DB::table('trx_tiket_gangguan')
                ->where('status', '11') // 11: Request Baru
                ->where('date_create', '>=', $sinceFormatted)
                ->orderBy('date_create', 'desc')
                ->limit(5)
                ->get();

            foreach ($newTickets as $t) {
                $nama = $t->nama_pelanggan ?: 'Pelanggan';
                $keluhan = $t->keluhan ? ' - ' . substr($t->keluhan, 0, 40) : '';
                $notifications[] = [
                    'id' => 'tiket_' . ($t->id ?? $t->nomor_internet ?? uniqid()) . '_' . strtotime($t->date_create ?? 'now'),
                    'type' => 'tiket',
                    'title' => 'Tiket Gangguan Baru',
                    'message' => "Tiket gangguan dari {$nama}{$keluhan}",
                    'speech_text' => "Ada tiket gangguan baru dari {$nama}",
                    'url' => route('teknik.tiket.gangguan'),
                    'created_at' => $t->date_create,
                ];
            }
        }

        // 3. Cek Permintaan Ubah Layanan UP / Downgrade (trx_ubah_layanan)
        if (Schema::hasTable('trx_ubah_layanan')) {
            $newUpdowns = DB::table('trx_ubah_layanan')
                ->where('status_ubah_layanan', '11') // 11: Request Baru dari Finance
                ->where('date_create', '>=', $sinceFormatted)
                ->orderBy('date_create', 'desc')
                ->limit(5)
                ->get();

            foreach ($newUpdowns as $u) {
                $nama = $u->nama_pelanggan ?? $u->nomor_internet ?? 'Pelanggan';
                $notifications[] = [
                    'id' => 'updown_' . $u->kode_trx_ubah_layanan,
                    'type' => 'updown',
                    'title' => 'Permintaan UP / Downgrade',
                    'message' => "Request perubahan paket untuk {$nama}",
                    'speech_text' => "Ada permintaan ubah layanan bandwidth untuk {$nama}",
                    'url' => route('teknik.permintaan.up-downgrade'),
                    'created_at' => $u->date_create,
                ];
            }
        }

        // 4. Cek Permintaan Suspend (trx_suspend)
        if (Schema::hasTable('trx_suspend')) {
            $newSuspends = DB::table('trx_suspend')
                ->where('status_suspend', '11') // 11: Request Suspend
                ->where('date_create', '>=', $sinceFormatted)
                ->orderBy('date_create', 'desc')
                ->limit(5)
                ->get();

            foreach ($newSuspends as $s) {
                $nama = $s->nama_pelanggan ?? $s->nomor_internet ?? 'Pelanggan';
                $notifications[] = [
                    'id' => 'suspend_' . ($s->kode_suspend ?? uniqid()),
                    'type' => 'suspend',
                    'title' => 'Permintaan Isolir (Suspend)',
                    'message' => "Request isolir layanan untuk {$nama}",
                    'speech_text' => "Ada permintaan isolir suspend tagihan untuk {$nama}",
                    'url' => route('teknik.permintaan.suspend'),
                    'created_at' => $s->date_create,
                ];
            }
        }

        // 5. Cek Permintaan Terminasi (trx_terminasi)
        if (Schema::hasTable('trx_terminasi')) {
            $newTerminasis = DB::table('trx_terminasi')
                ->where('status_terminasi', '11') // 11: Request Terminasi
                ->where('date_create', '>=', $sinceFormatted)
                ->orderBy('date_create', 'desc')
                ->limit(5)
                ->get();

            foreach ($newTerminasis as $term) {
                $nama = $term->nama_pelanggan ?? $term->nomor_internet ?? 'Pelanggan';
                $notifications[] = [
                    'id' => 'terminasi_' . ($term->kode_terminasi ?? uniqid()),
                    'type' => 'terminasi',
                    'title' => 'Permintaan Terminasi',
                    'message' => "Request putus berlangganan untuk {$nama}",
                    'speech_text' => "Ada permintaan terminasi layanan untuk {$nama}",
                    'url' => route('teknik.permintaan.terminasi'),
                    'created_at' => $term->date_create,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'timestamp' => time(),
            'notifications' => $notifications,
        ]);
    }
}
