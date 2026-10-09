<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    protected static array $tablesCache = [];

    /**
     * Fast Schema Table Check with In-Memory Cache to eliminate network DB queries
     */
    protected function hasTableFast(string $table): bool
    {
        if (isset(self::$tablesCache[$table])) {
            return self::$tablesCache[$table];
        }
        return self::$tablesCache[$table] = Cache::remember('schema_tbl_' . $table, 3600, function () use ($table) {
            return Schema::hasTable($table);
        });
    }

    /**
     * Polling endpoint untuk notifikasi baru saat aplikasi sedang dibuka.
     * Mengembalikan event sesuai hak akses Role pengguna:
     * - NOC & Teknik: Tiket Gangguan, Jadwal Survey/Aktivasi, Request Eksekusi UP/Down, Suspend, Terminasi, Report Instalasi.
     * - Finance: Pembayaran Tagihan Masuk (Layanan & Registrasi), Konfirmasi Bukti Transfer, Request Invoice, Pendaftaran Baru.
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
        $tz = 'Asia/Jakarta';
        
        // Default to last 30 seconds if no since timestamp provided
        if (!empty($sinceTimestamp) && is_numeric($sinceTimestamp)) {
            $since = Carbon::createFromTimestamp((int) $sinceTimestamp, $tz);
        } else {
            $since = Carbon::now($tz)->subSeconds(30);
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
        if (($isAdminOrDirektur || $isNoc || $isTeknik) && $this->hasTableFast('trx_tiket_gangguan')) {
            try {
                $newTickets = DB::table('trx_tiket_gangguan')
                    ->leftJoin('view_batchjob', 'trx_tiket_gangguan.nomor_internet', '=', 'view_batchjob.nomor_internet')
                    ->where('trx_tiket_gangguan.status', '11') // 11: Request Baru
                    ->where('trx_tiket_gangguan.date_create', '>=', $sinceFormatted)
                    ->select([
                        'trx_tiket_gangguan.tiket',
                        'trx_tiket_gangguan.nomor_internet',
                        'trx_tiket_gangguan.keluhan',
                        'trx_tiket_gangguan.date_create',
                        'view_batchjob.nama_pelanggan',
                    ])
                    ->orderBy('trx_tiket_gangguan.date_create', 'desc')
                    ->limit(5)
                    ->get();

                foreach ($newTickets as $t) {
                    $nama = $t->nama_pelanggan ?: ($t->nomor_internet ?: 'Pelanggan');
                    $keluhan = $t->keluhan ? ' - ' . substr($t->keluhan, 0, 40) : '';
                    $tKey = $t->tiket ?: $t->nomor_internet;
                    $tDate = $t->date_create ?: 'created';

                    $notifications[] = [
                        'id' => 'tiket_' . $tKey . '_' . md5($tDate),
                        'type' => 'tiket',
                        'title' => 'Tiket Gangguan Baru',
                        'message' => "Tiket gangguan dari {$nama}{$keluhan}",
                        'speech_text' => "Ada tiket gangguan baru dari {$nama}",
                        'url' => route('teknik.tiket.gangguan'),
                        'created_at' => $t->date_create,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Poll tiket gangguan error: ' . $e->getMessage());
            }
        }

        // -------------------------------------------------------------
        // 2. PENDAFTARAN BARU -> NOC/TEKNIK (SURVEY/INSTALASI) & FINANCE (BILLING REGISTRASI)
        // -------------------------------------------------------------
        if (($isAdminOrDirektur || $isTeknik || $isNoc || $isFinance) && $this->hasTableFast('trx_pendaftaran')) {
            try {
                $newRegistrations = DB::table('trx_pendaftaran')
                    ->where('hide', 0)
                    ->where('date_create', '>=', $sinceFormatted)
                    ->whereIn('status_reg', ['11', '12', '13']) // Pendaftaran Baru / Draft
                    ->select([
                        'nomor_internet',
                        'nama_pelanggan',
                        'date_create',
                        'date_update',
                    ])
                    ->orderBy('date_create', 'desc')
                    ->limit(5)
                    ->get();

                foreach ($newRegistrations as $reg) {
                    $nama = $reg->nama_pelanggan ?: 'Pelanggan';
                    $speechText = $isFinance 
                        ? "Ada pendaftaran pelanggan baru atas nama {$nama} untuk penagihan registrasi"
                        : "Ada pelanggan baru terdaftar, atas nama {$nama}";
                    
                    $regKey = $reg->nomor_internet ?: md5($reg->nama_pelanggan);
                    $regDate = $reg->date_create ?: 'reg';

                    $notifications[] = [
                        'id' => 'reg_' . $regKey . '_' . md5($regDate),
                        'type' => 'pendaftaran',
                        'title' => 'Pelanggan Baru Terdaftar',
                        'message' => "Pendaftaran baru atas nama {$nama} (No: {$reg->nomor_internet})",
                        'speech_text' => $speechText,
                        'url' => $isFinance ? route('finance.billing-registrasi') : route('teknik.pendaftaran'),
                        'created_at' => $reg->date_create,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Poll pendaftaran error: ' . $e->getMessage());
            }
        }

        // -------------------------------------------------------------
        // 3. PEMBAYARAN TAGIHAN MASUK -> DITUJUKAN UNTUK FINANCE & ADMIN
        // -------------------------------------------------------------
        if ($isAdminOrDirektur || $isFinance) {
            
            // 3.1 Tagihan Bulanan Baru yang Dibayar / Lunas (trx_billing_layanan)
            if ($this->hasTableFast('trx_billing_layanan')) {
                try {
                    $paidInvoices = DB::table('trx_billing_layanan')
                        ->leftJoin('view_batchjob', 'trx_billing_layanan.nomor_internet', '=', 'view_batchjob.nomor_internet')
                        ->where('trx_billing_layanan.status_bill_lay', '15') // 15 = Paid / Lunas
                        ->where(function ($q) use ($sinceFormatted) {
                            $q->where('trx_billing_layanan.payment_paid', '>=', $sinceFormatted)
                              ->orWhere('trx_billing_layanan.date_update', '>=', $sinceFormatted);
                        })
                        ->select([
                            'trx_billing_layanan.kode_billing_layanan',
                            'trx_billing_layanan.nomor_internet',
                            'trx_billing_layanan.total_layanan',
                            'trx_billing_layanan.amount_paid',
                            'trx_billing_layanan.payment_paid',
                            'trx_billing_layanan.date_update',
                            'trx_billing_layanan.merchant_type',
                            'view_batchjob.nama_pelanggan',
                        ])
                        ->orderBy('trx_billing_layanan.date_update', 'desc')
                        ->limit(5)
                        ->get();

                    foreach ($paidInvoices as $inv) {
                        $nama = $inv->nama_pelanggan ?: ($inv->nomor_internet ?: 'Pelanggan');
                        $nominal = (float) ($inv->amount_paid ?: $inv->total_layanan ?: 0);
                        $nominalFmt = 'Rp ' . number_format($nominal, 0, ',', '.');
                        $tgl = $inv->payment_paid ?: ($inv->date_update ?: 'paid');
                        $invKey = str_replace(['/', '-', ' '], '_', $inv->kode_billing_layanan ?: $inv->nomor_internet);

                        $notifications[] = [
                            'id' => 'paid_inv_' . $invKey . '_' . md5($tgl),
                            'type' => 'pembayaran',
                            'title' => 'Pembayaran Tagihan Lunas',
                            'message' => "Pembayaran {$inv->kode_billing_layanan} sebesar {$nominalFmt} diterima dari {$nama}",
                            'speech_text' => "Ada pembayaran tagihan masuk sebesar {$nominalFmt} dari {$nama}",
                            'url' => route('finance.billing-layanan', ['search' => $inv->kode_billing_layanan]),
                            'created_at' => $tgl,
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('Poll paid invoices error: ' . $e->getMessage());
                }
            }

            // 3.2 Tagihan Registrasi Pasang Baru yang Lunas (trx_billing_registrasi)
            if ($this->hasTableFast('trx_billing_registrasi')) {
                try {
                    $paidRegs = DB::table('trx_billing_registrasi')
                        ->leftJoin('view_batchjob', 'trx_billing_registrasi.nomor_internet', '=', 'view_batchjob.nomor_internet')
                        ->where('trx_billing_registrasi.status_bill_reg', '15')
                        ->where(function ($q) use ($sinceFormatted) {
                            $q->where('trx_billing_registrasi.payment_paid', '>=', $sinceFormatted)
                              ->orWhere('trx_billing_registrasi.date_update', '>=', $sinceFormatted);
                        })
                        ->select([
                            'trx_billing_registrasi.kode_billing_registrasi',
                            'trx_billing_registrasi.nomor_internet',
                            'trx_billing_registrasi.total_reg',
                            'trx_billing_registrasi.amount_paid',
                            'trx_billing_registrasi.payment_paid',
                            'trx_billing_registrasi.date_update',
                            'view_batchjob.nama_pelanggan',
                        ])
                        ->orderBy('trx_billing_registrasi.date_update', 'desc')
                        ->limit(5)
                        ->get();

                    foreach ($paidRegs as $r) {
                        $nama = $r->nama_pelanggan ?: ($r->nomor_internet ?: 'Pelanggan');
                        $nominal = (float) ($r->amount_paid ?: $r->total_reg ?: 0);
                        $nominalFmt = 'Rp ' . number_format($nominal, 0, ',', '.');
                        $tgl = $r->payment_paid ?: ($r->date_update ?: 'paid');
                        $regBillKey = str_replace(['/', '-', ' '], '_', $r->kode_billing_registrasi ?: $r->nomor_internet);

                        $notifications[] = [
                            'id' => 'paid_reg_' . $regBillKey . '_' . md5($tgl),
                            'type' => 'pembayaran',
                            'title' => 'Pembayaran Biaya Pasang Lunas',
                            'message' => "Biaya registrasi {$r->kode_billing_registrasi} sebesar {$nominalFmt} diterima dari {$nama}",
                            'speech_text' => "Ada pembayaran biaya registrasi pasang baru dari {$nama}",
                            'url' => route('finance.billing-registrasi', ['search' => $r->kode_billing_registrasi]),
                            'created_at' => $tgl,
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('Poll paid registration error: ' . $e->getMessage());
                }
            }

            // 3.3 Tagihan Layanan Menunggu Verifikasi (trx_billing_layanan status_bill_lay = '14')
            if ($this->hasTableFast('trx_billing_layanan')) {
                try {
                    $waitingInvoices = DB::table('trx_billing_layanan')
                        ->leftJoin('view_batchjob', 'trx_billing_layanan.nomor_internet', '=', 'view_batchjob.nomor_internet')
                        ->where('trx_billing_layanan.status_bill_lay', '14') // 14 = Menunggu Verifikasi
                        ->where('trx_billing_layanan.date_update', '>=', $sinceFormatted)
                        ->select([
                            'trx_billing_layanan.kode_billing_layanan',
                            'trx_billing_layanan.nomor_internet',
                            'trx_billing_layanan.total_layanan',
                            'trx_billing_layanan.date_update',
                            'view_batchjob.nama_pelanggan',
                        ])
                        ->orderBy('trx_billing_layanan.date_update', 'desc')
                        ->limit(5)
                        ->get();

                    foreach ($waitingInvoices as $w) {
                        $nama = $w->nama_pelanggan ?: ($w->nomor_internet ?: 'Pelanggan');
                        $nominal = (float) ($w->total_layanan ?: 0);
                        $nominalFmt = $nominal > 0 ? ' (Rp ' . number_format($nominal, 0, ',', '.') . ')' : '';
                        $tgl = $w->date_update ?: 'waiting';
                        $waitBillKey = str_replace(['/', '-', ' '], '_', $w->kode_billing_layanan ?: $w->nomor_internet);

                        $notifications[] = [
                            'id' => 'waiting_verif_' . $waitBillKey . '_' . md5($tgl),
                            'type' => 'pembayaran',
                            'title' => 'Konfirmasi Pembayaran Masuk',
                            'message' => "Bukti transfer {$w->kode_billing_layanan}{$nominalFmt} dari {$nama} menunggu verifikasi",
                            'speech_text' => "Ada konfirmasi pembayaran transfer baru dari {$nama} menunggu verifikasi",
                            'url' => route('finance.billing-layanan', ['search' => $w->kode_billing_layanan]),
                            'created_at' => $tgl,
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('Poll waiting verification billing error: ' . $e->getMessage());
                }
            }

            // 3.4 Konfirmasi Transfer Manual / Bukti Upload Pelanggan (payment_confirmations & ptmsn.payment_confirmations)
            if ($this->hasTableFast('payment_confirmations')) {
                try {
                    $newPayments = DB::table('payment_confirmations')
                        ->where(function ($q) use ($sinceFormatted) {
                            $q->where('created_at', '>=', $sinceFormatted)
                              ->orWhere('updated_at', '>=', $sinceFormatted);
                        })
                        ->where('status', '!=', 'approved')
                        ->where('status', '!=', 'rejected')
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get();

                    foreach ($newPayments as $pay) {
                        $nama = $pay->nama_pelanggan ?? ($pay->nomor_internet ?? 'Pelanggan');
                        $payId = $pay->id ?: ($pay->kode_billing_layanan ?: 'pay');
                        $payDate = $pay->created_at ?: ($pay->updated_at ?: 'pending');

                        $notifications[] = [
                            'id' => 'pay_conf_' . $payId . '_' . md5($payDate),
                            'type' => 'pembayaran',
                            'title' => 'Konfirmasi Pembayaran Masuk',
                            'message' => "Konfirmasi transfer dari {$nama} (" . ($pay->kode_billing_layanan ?? '') . ")",
                            'speech_text' => "Ada konfirmasi pembayaran transfer baru dari {$nama} menunggu verifikasi",
                            'url' => route('finance.billing-layanan', ['search' => $pay->kode_billing_layanan ?? '']),
                            'created_at' => $pay->created_at,
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('Poll payment confirmations error: ' . $e->getMessage());
                }
            }

            // Fallback cross-database ptmsn.payment_confirmations
            try {
                $ptmsnPayments = DB::select("
                    SELECT * FROM ptmsn.payment_confirmations
                    WHERE (created_at >= '{$sinceFormatted}' OR updated_at >= '{$sinceFormatted}')
                      AND status != 'approved' AND status != 'rejected'
                    ORDER BY id DESC LIMIT 5
                ");

                foreach ($ptmsnPayments as $pay) {
                    $nama = $pay->nama_pelanggan ?? ($pay->nomor_internet ?? 'Pelanggan');
                    $payId = $pay->id ?: ($pay->kode_billing_layanan ?: 'ptmsn_pay');
                    $payDate = $pay->created_at ?: ($pay->updated_at ?: 'pending');

                    $notifications[] = [
                        'id' => 'ptmsn_pay_conf_' . $payId . '_' . md5($payDate),
                        'type' => 'pembayaran',
                        'title' => 'Konfirmasi Pembayaran Masuk',
                        'message' => "Konfirmasi transfer dari {$nama} (" . ($pay->kode_billing_layanan ?? '') . ")",
                        'speech_text' => "Ada konfirmasi pembayaran transfer baru dari {$nama} menunggu verifikasi",
                        'url' => route('finance.billing-layanan', ['search' => $pay->kode_billing_layanan ?? '']),
                        'created_at' => $pay->created_at,
                    ];
                }
            } catch (\Throwable $e) {
                // Ignore if ptmsn cross-db table unavailable
            }

            // 3.5 Request Invoice Tagihan Mandiri dari Pelanggan (trx_billing_request)
            if ($this->hasTableFast('trx_billing_request')) {
                try {
                    $newBillingRequests = DB::table('trx_billing_request')
                        ->where('status_request', 'pending')
                        ->where('created_at', '>=', $sinceFormatted)
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get();

                    foreach ($newBillingRequests as $breq) {
                        $nama = $breq->nama_pelanggan ?: 'Pelanggan';
                        $bulanThn = ($breq->bulan_tagihan ?? '') . '/' . ($breq->tahun_tagihan ?? '');
                        $breqId = $breq->id ?: ($breq->nomor_internet ?: 'breq');
                        $breqDate = $breq->created_at ?: 'req';

                        $notifications[] = [
                            'id' => 'breq_' . $breqId . '_' . md5($breqDate),
                            'type' => 'request_invoice',
                            'title' => 'Permintaan Invoice Tagihan',
                            'message' => "Pelanggan {$nama} mengajukan penerbitan invoice ({$bulanThn})",
                            'speech_text' => "Ada permintaan penerbitan invoice tagihan dari pelanggan {$nama}",
                            'url' => route('finance.billing-layanan'),
                            'created_at' => $breq->created_at,
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('Poll billing requests error: ' . $e->getMessage());
                }
            }
        }

        // -------------------------------------------------------------
        // 4. PERMINTAAN UP/DOWNGRADE -> DARI FINANCE UNTUK EKSEKUSI NOC/TEKNIK
        // -------------------------------------------------------------
        if (($isAdminOrDirektur || $isNoc || $isTeknik) && $this->hasTableFast('trx_ubah_layanan')) {
            try {
                $newUpdowns = DB::table('trx_ubah_layanan')
                    ->where('status_ubah_layanan', '11') // 11: Request Baru dari Finance
                    ->where('date_create', '>=', $sinceFormatted)
                    ->orderBy('date_create', 'desc')
                    ->limit(5)
                    ->get();

                foreach ($newUpdowns as $u) {
                    $nama = $u->nama_pelanggan ?? ($u->nomor_internet ?? 'Pelanggan');
                    $uKey = $u->kode_trx_ubah_layanan ?: ($u->nomor_internet ?: 'updown');
                    $uDate = $u->date_create ?: 'req';

                    $notifications[] = [
                        'id' => 'updown_' . $uKey . '_' . md5($uDate),
                        'type' => 'updown',
                        'title' => 'Request Ubah Bandwidth (NOC)',
                        'message' => "Finance mengajukan perubahan paket untuk {$nama}",
                        'speech_text' => "Ada permintaan ubah layanan bandwidth dari Finance untuk {$nama}",
                        'url' => route('teknik.permintaan.up-downgrade'),
                        'created_at' => $u->date_create,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Poll ubah layanan error: ' . $e->getMessage());
            }
        }

        // -------------------------------------------------------------
        // 5. PERMINTAAN SUSPEND (ISOLIR) -> DARI FINANCE UNTUK EKSEKUSI NOC
        // -------------------------------------------------------------
        if (($isAdminOrDirektur || $isNoc || $isTeknik) && $this->hasTableFast('trx_suspend')) {
            try {
                $newSuspends = DB::table('trx_suspend')
                    ->where('status_suspend', '11') // 11: Request Suspend
                    ->where('date_create', '>=', $sinceFormatted)
                    ->orderBy('date_create', 'desc')
                    ->limit(5)
                    ->get();

                foreach ($newSuspends as $s) {
                    $nama = $s->nama_pelanggan ?? ($s->nomor_internet ?? 'Pelanggan');
                    $sKey = $s->kode_suspend ?: ($s->nomor_internet ?: 'suspend');
                    $sDate = $s->date_create ?: 'req';

                    $notifications[] = [
                        'id' => 'suspend_' . $sKey . '_' . md5($sDate),
                        'type' => 'suspend',
                        'title' => 'Request Isolir Jaringan (NOC)',
                        'message' => "Finance mengajukan isolir tagihan untuk {$nama}",
                        'speech_text' => "Ada permintaan isolir tagihan dari Finance untuk {$nama}",
                        'url' => route('teknik.permintaan.suspend'),
                        'created_at' => $s->date_create,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Poll suspend error: ' . $e->getMessage());
            }
        }

        // -------------------------------------------------------------
        // 6. PERMINTAAN TERMINASI -> DARI FINANCE UNTUK LAPANGAN/NOC
        // -------------------------------------------------------------
        if (($isAdminOrDirektur || $isNoc || $isTeknik) && $this->hasTableFast('trx_terminasi')) {
            try {
                $newTerminasis = DB::table('trx_terminasi')
                    ->where('status_terminasi', '11') // 11: Request Terminasi
                    ->where('date_create', '>=', $sinceFormatted)
                    ->orderBy('date_create', 'desc')
                    ->limit(5)
                    ->get();

                foreach ($newTerminasis as $term) {
                    $nama = $term->nama_pelanggan ?? ($term->nomor_internet ?? 'Pelanggan');
                    $termKey = $term->kode_terminasi ?: ($term->nomor_internet ?: 'terminasi');
                    $termDate = $term->date_create ?: 'req';

                    $notifications[] = [
                        'id' => 'terminasi_' . $termKey . '_' . md5($termDate),
                        'type' => 'terminasi',
                        'title' => 'Request Terminasi (NOC/Teknik)',
                        'message' => "Finance mengajukan putus berlangganan & penarikan ONT untuk {$nama}",
                        'speech_text' => "Ada permintaan terminasi layanan dari Finance untuk {$nama}",
                        'url' => route('teknik.permintaan.terminasi'),
                        'created_at' => $term->date_create,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Poll terminasi error: ' . $e->getMessage());
            }
        }

        // -------------------------------------------------------------
        // 7. REPORT INSTALASI DARI TEKNISI (STATUS #18 SELESAI INSTALASI / SIAP AKTIVASI) -> DITUJUKAN UNTUK NOC & ADMIN
        // -------------------------------------------------------------
        if (($isAdminOrDirektur || $isNoc) && $this->hasTableFast('trx_batchjob_register')) {
            try {
                $reportedInstalasi = DB::table('trx_batchjob_register')
                    ->leftJoin('trx_instalasi', 'trx_batchjob_register.nomor_internet', '=', 'trx_instalasi.nomor_internet')
                    ->where('trx_batchjob_register.status_reg', '18') // 18: Selesai Instalasi (Siap Aktivasi NOC)
                    ->where(function ($q) use ($sinceFormatted) {
                        $q->where('trx_batchjob_register.date_update', '>=', $sinceFormatted)
                          ->orWhere('trx_instalasi.date_update', '>=', $sinceFormatted);
                    })
                    ->select([
                        'trx_batchjob_register.nomor_internet',
                        'trx_batchjob_register.nama_pelanggan',
                        'trx_batchjob_register.date_update',
                        'trx_batchjob_register.user_update',
                        'trx_batchjob_register.media_akses',
                        'trx_batchjob_register.olt',
                        'trx_instalasi.instalasi_team',
                        'trx_instalasi.instalasi_note_finish',
                        'trx_instalasi.aktivasi_note',
                    ])
                    ->orderBy('trx_batchjob_register.date_update', 'desc')
                    ->limit(5)
                    ->get();

                foreach ($reportedInstalasi as $inst) {
                    $nama = $inst->nama_pelanggan ?: ($inst->nomor_internet ?: 'Pelanggan');
                    $teknisi = $inst->instalasi_team ?: ($inst->user_update ?: 'Teknisi');
                    $tgl = $inst->date_update ?: 'done';
                    $instKey = $inst->nomor_internet ?: 'inst';
                    
                    $notifications[] = [
                        'id' => 'report_inst_' . $instKey . '_' . md5($tgl),
                        'type' => 'instalasi',
                        'title' => 'Report Instalasi Selesai (NOC)',
                        'message' => "Teknisi ({$teknisi}) telah menyelesaikan instalasi untuk {$nama}. Siap dieksekusi aktivasi di NOC.",
                        'speech_text' => "Ada report instalasi selesai dari teknisi untuk pelanggan {$nama}, siap diaktivasi NOC.",
                        'url' => route('noc.aktivasi', ['status' => 'siap_aktivasi', 'search' => $inst->nomor_internet]),
                        'created_at' => $inst->date_update,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Poll report instalasi error: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'timestamp' => Carbon::now('Asia/Jakarta')->timestamp,
            'notifications' => $notifications,
        ]);
    }
}

