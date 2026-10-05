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
     * Mengembalikan event sesuai hak akses Role pengguna:
     * - NOC & Teknik: Tiket Gangguan, Jadwal Survey/Aktivasi, Request Eksekusi UP/Down, Suspend, Terminasi.
     * - Finance: Pendaftaran Baru (Billing), Konfirmasi Pembayaran Kas Masuk, dan Notifikasi Tagihan.
     * - Admin & Direktur: Mendapatkan semua notifikasi.
     */
    public function poll(Request $request): JsonResponse
    {
        /** @var \App\Models\Pengguna|null $user */
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'unauthenticated', 'notifications' => []], 401);
        }

        $sinceTimestamp = $request->input('since');
        
        // Default to last 3 minutes if no since provided
        if (!empty($sinceTimestamp) && is_numeric($sinceTimestamp)) {
            $since = Carbon::createFromTimestamp((int) $sinceTimestamp);
        } else {
            $since = Carbon::now()->subMinutes(3);
        }

        $sinceFormatted = $since->format('Y-m-d H:i:s');
        $notifications = [];

        $isAdminOrDirektur = $user->isAdmin() || $user->isDirektur();
        $isNoc = $user->isNoc();
        $isTeknik = $user->isTeknik();
        $isFinance = $user->isFinance();

        // -------------------------------------------------------------
        // 1. TIKET GANGGUAN BARU -> DITUJUKAN UNTUK NOC, TEKNIK, ADMIN
        // -------------------------------------------------------------
        if (($isAdminOrDirektur || $isNoc || $isTeknik) && Schema::hasTable('trx_tiket_gangguan')) {
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

        // -------------------------------------------------------------
        // 2. PENDAFTARAN BARU -> NOC/TEKNIK (SURVEY/INSTALASI) & FINANCE (BILLING REGISTRASI)
        // -------------------------------------------------------------
        if (($isAdminOrDirektur || $isTeknik || $isNoc || $isFinance) && Schema::hasTable('trx_pendaftaran')) {
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
                $speechText = $isFinance 
                    ? "Ada pendaftaran pelanggan baru atas nama {$nama} untuk penagihan registrasi"
                    : "Ada pelanggan baru terdaftar, atas nama {$nama}";

                $notifications[] = [
                    'id' => 'reg_' . $reg->nomor_internet . '_' . strtotime($reg->date_create ?? 'now'),
                    'type' => 'pendaftaran',
                    'title' => 'Pelanggan Baru Terdaftar',
                    'message' => "Pendaftaran baru atas nama {$nama} (No: {$reg->nomor_internet})",
                    'speech_text' => $speechText,
                    'url' => $isFinance ? route('finance.billing-registrasi') : route('teknik.pendaftaran'),
                    'created_at' => $reg->date_create,
                ];
            }
        }

        // -------------------------------------------------------------
        // 3. KONFIRMASI PEMBAYARAN / KAS MASUK -> DITUJUKAN UNTUK FINANCE & ADMIN
        // -------------------------------------------------------------
        if (($isAdminOrDirektur || $isFinance) && Schema::hasTable('payment_confirmations')) {
            $newPayments = DB::table('payment_confirmations')
                ->where('created_at', '>=', $sinceFormatted)
                ->where('status', '!=', 'approved')
                ->where('status', '!=', 'rejected')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            foreach ($newPayments as $pay) {
                $nama = $pay->nama_pelanggan ?? $pay->nomor_internet ?? 'Pelanggan';
                $notifications[] = [
                    'id' => 'pay_' . ($pay->id ?? uniqid()),
                    'type' => 'pembayaran',
                    'title' => 'Konfirmasi Pembayaran Masuk',
                    'message' => "Pembayaran baru dari {$nama} (" . ($pay->kode_billing_layanan ?? '') . ")",
                    'speech_text' => "Ada konfirmasi pembayaran baru dari {$nama}",
                    'url' => route('finance.billing-layanan', ['status_bayar' => 'menunggu_verifikasi']),
                    'created_at' => $pay->created_at,
                ];
            }
        }

        // -------------------------------------------------------------
        // 3.1 REQUEST INVOICE TAGIHAN DARI PELANGGAN -> DITUJUKAN UNTUK FINANCE & ADMIN
        // -------------------------------------------------------------
        if (($isAdminOrDirektur || $isFinance) && Schema::hasTable('trx_billing_request')) {
            $newBillingRequests = DB::table('trx_billing_request')
                ->where('status_request', 'pending')
                ->where('created_at', '>=', $sinceFormatted)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            foreach ($newBillingRequests as $breq) {
                $nama = $breq->nama_pelanggan ?: 'Pelanggan';
                $bulanThn = ($breq->bulan_tagihan ?? '') . '/' . ($breq->tahun_tagihan ?? '');
                $notifications[] = [
                    'id' => 'breq_' . $breq->id,
                    'type' => 'request_invoice',
                    'title' => 'Permintaan Invoice Tagihan',
                    'message' => "Pelanggan {$nama} mengajukan penerbitan invoice ({$bulanThn})",
                    'speech_text' => "Ada permintaan penerbitan invoice tagihan dari pelanggan {$nama}",
                    'url' => route('finance.billing-layanan'),
                    'created_at' => $breq->created_at,
                ];
            }
        }

        // -------------------------------------------------------------
        // 4. PERMINTAAN UP/DOWNGRADE -> DARI FINANCE UNTUK EKSEKUSI NOC/TEKNIK
        // -------------------------------------------------------------
        if (($isAdminOrDirektur || $isNoc || $isTeknik) && Schema::hasTable('trx_ubah_layanan')) {
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
                    'title' => 'Request Ubah Bandwidth (NOC)',
                    'message' => "Finance mengajukan perubahan paket untuk {$nama}",
                    'speech_text' => "Ada permintaan ubah layanan bandwidth dari Finance untuk {$nama}",
                    'url' => route('teknik.permintaan.up-downgrade'),
                    'created_at' => $u->date_create,
                ];
            }
        }

        // -------------------------------------------------------------
        // 5. PERMINTAAN SUSPEND (ISOLIR) -> DARI FINANCE UNTUK EKSEKUSI NOC
        // -------------------------------------------------------------
        if (($isAdminOrDirektur || $isNoc || $isTeknik) && Schema::hasTable('trx_suspend')) {
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
                    'title' => 'Request Isolir Jaringan (NOC)',
                    'message' => "Finance mengajukan isolir tagihan untuk {$nama}",
                    'speech_text' => "Ada permintaan isolir tagihan dari Finance untuk {$nama}",
                    'url' => route('teknik.permintaan.suspend'),
                    'created_at' => $s->date_create,
                ];
            }
        }

        // -------------------------------------------------------------
        // 6. PERMINTAAN TERMINASI -> DARI FINANCE UNTUK LAPANGAN/NOC
        // -------------------------------------------------------------
        if (($isAdminOrDirektur || $isNoc || $isTeknik) && Schema::hasTable('trx_terminasi')) {
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
                    'title' => 'Request Terminasi (NOC/Teknik)',
                    'message' => "Finance mengajukan putus berlangganan & penarikan ONT untuk {$nama}",
                    'speech_text' => "Ada permintaan terminasi layanan dari Finance untuk {$nama}",
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
