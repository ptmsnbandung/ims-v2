<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\MidtransService;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    /**
     * Menu Utama: Billing Layanan (Recurring Monthly Invoices)
     */
    public function billingLayanan(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        // Default or Filtered Month & Year (Default to current month and year to avoid massive full-history scan timeouts)
        $selectedBulan = $request->has('bulan') ? (string)$request->input('bulan') : date('m');
        $selectedTahun = $request->has('tahun') ? (string)$request->input('tahun') : (string) date('Y');

        // Query view_billing_layanan
        $query = DB::table('view_billing_layanan');

        // Apply Filters
        if ($selectedBulan !== '' && $selectedBulan !== null) {
            $query->where('bulan_tagihan', str_pad($selectedBulan, 2, '0', STR_PAD_LEFT));
        }
        if ($selectedTahun !== '' && $selectedTahun !== null) {
            $query->where('tahun_tagihan', $selectedTahun);
        }
        if ($request->filled('layanan')) {
            $query->where('nama_kategori_bandwith', $request->input('layanan'));
        }
        if ($request->filled('status_user')) {
            $query->where('status_reg', $request->input('status_user'));
        }
        if ($request->filled('status_bayar')) {
            $statusBayar = trim((string)$request->input('status_bayar'));
            if ($statusBayar === 'menunggu_verifikasi' || $statusBayar === 'waiting_verification') {
                $pendingKodes = collect();
                if (Schema::hasTable('payment_confirmations')) {
                    $pendingKodes = DB::table('payment_confirmations')
                        ->where('status', '!=', 'approved')
                        ->where('status', '!=', 'rejected')
                        ->pluck('kode_billing_layanan')
                        ->filter()
                        ->toArray();
                }

                $expandedKodes = [];
                foreach ($pendingKodes as $k) {
                    $expandedKodes[] = $k;
                    $expandedKodes[] = str_replace('/', '-', $k);
                    $expandedKodes[] = str_replace('-', '/', $k);
                }

                try {
                    $ptmsnPending = DB::select("SELECT kode_billing_layanan FROM ptmsn.payment_confirmations WHERE status != 'approved' AND status != 'rejected'");
                    foreach ($ptmsnPending as $p) {
                        if (!empty($p->kode_billing_layanan)) {
                            $expandedKodes[] = $p->kode_billing_layanan;
                            $expandedKodes[] = str_replace('/', '-', $p->kode_billing_layanan);
                            $expandedKodes[] = str_replace('-', '/', $p->kode_billing_layanan);
                        }
                    }
                } catch (\Throwable $e) {}

                $expandedKodes = array_unique(array_filter($expandedKodes));

                $query->where(function ($q) use ($expandedKodes) {
                    $q->where('status_bill_lay', '!=', '15');
                    if (!empty($expandedKodes)) {
                        $q->whereIn('kode_billing_layanan', $expandedKodes);
                    } else {
                        $q->where('status_bill_lay', '14')
                          ->where(function ($sub) {
                              $sub->where('payment_type', '2')
                                  ->orWhere('merchant_type', 'like', '%transfer%')
                                  ->orWhere('merchant_type', 'like', '%bca%')
                                  ->orWhere('merchant_type', 'like', '%mandiri%')
                                  ->orWhere('merchant_type', 'like', '%bri%')
                                  ->orWhere('merchant_type', 'like', '%bni%')
                                  ->orWhere('merchant_type', 'like', '%bsi%')
                                  ->orWhere('merchant_type', 'like', '%permata%');
                          });
                    }
                });
            } else {
                $query->where('status_bill_lay', $statusBayar);
            }
        }
        if ($request->filled('wilayah')) {
            $query->where('nama_kota_pasang', $request->input('wilayah'));
        }
        if ($request->filled('metode_bayar')) {
            $query->where('payment_type', $request->input('metode_bayar'));
        }
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('kode_billing_layanan', 'LIKE', "%{$search}%")
                  ->orWhere('nomor_internet', 'LIKE', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'LIKE', "%{$search}%")
                  ->orWhere('invoice_file', 'LIKE', "%{$search}%");
            });
        }

        // HIGH PERFORMANCE: 1 SINGLE AGGREGATED QUERY instead of 8 separate full-view scans
        $kpiQuery = DB::table('trx_billing_layanan')
            ->selectRaw("
                COUNT(CASE WHEN status_bill_lay IN ('11', '12') THEN 1 END) as generating_count,
                COALESCE(SUM(CASE WHEN status_bill_lay IN ('11', '12') THEN CAST(total_layanan AS DECIMAL(15,2)) ELSE 0 END), 0) as generating_amount,
                COUNT(CASE WHEN status_bill_lay = '13' THEN 1 END) as publish_count,
                COALESCE(SUM(CASE WHEN status_bill_lay = '13' THEN CAST(total_layanan AS DECIMAL(15,2)) ELSE 0 END), 0) as publish_amount,
                COUNT(CASE WHEN status_bill_lay = '14' THEN 1 END) as waiting_count,
                COALESCE(SUM(CASE WHEN status_bill_lay = '14' THEN CAST(total_layanan AS DECIMAL(15,2)) ELSE 0 END), 0) as waiting_amount,
                COUNT(CASE WHEN status_bill_lay = '15' THEN 1 END) as paid_count,
                COALESCE(SUM(CASE WHEN status_bill_lay = '15' THEN CAST(total_layanan AS DECIMAL(15,2)) ELSE 0 END), 0) as paid_amount
            ");

        if ($selectedBulan !== '' && $selectedBulan !== null) {
            $kpiQuery->where('bulan_tagihan', str_pad($selectedBulan, 2, '0', STR_PAD_LEFT));
        }
        if ($selectedTahun !== '' && $selectedTahun !== null) {
            $kpiQuery->where('tahun_tagihan', $selectedTahun);
        }

        $kpi = $kpiQuery->first();

        $generatingCount = (int) ($kpi->generating_count ?? 0);
        $generatingAmount = (float) ($kpi->generating_amount ?? 0);
        $publishCount = (int) ($kpi->publish_count ?? 0);
        $publishAmount = (float) ($kpi->publish_amount ?? 0);
        $waitingCount = (int) ($kpi->waiting_count ?? 0);
        $waitingAmount = (float) ($kpi->waiting_amount ?? 0);
        $paidCount = (int) ($kpi->paid_count ?? 0);
        $paidAmount = (float) ($kpi->paid_amount ?? 0);

        // Fetch Paginated Invoices (Efficient Indexed Pagination)
        $invoices = $query->orderBy('date_create', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Ambil Data Konfirmasi Pembayaran & merchant_type / payment_type langsung dari database
        $kodeBillings = collect($invoices->items())->pluck('kode_billing_layanan')->filter()->toArray();
        $expandedKodeBillings = [];
        foreach ($kodeBillings as $kb) {
            $expandedKodeBillings[] = $kb;
            $expandedKodeBillings[] = str_replace('/', '-', $kb);
            $expandedKodeBillings[] = str_replace('-', '/', $kb);
        }
        $expandedKodeBillings = array_unique(array_filter($expandedKodeBillings));

        $trxBillings = collect();
        $confirmationsByKode = collect();

        if (!empty($kodeBillings)) {
            try {
                if (Schema::hasTable('trx_billing_layanan')) {
                    $trxSelect = ['kode_billing_layanan', 'merchant_type', 'payment_type'];
                    if (Schema::hasColumn('trx_billing_layanan', 'destination_bank')) {
                        $trxSelect[] = 'destination_bank';
                    }
                    $trxBillings = DB::table('trx_billing_layanan')
                        ->whereIn('kode_billing_layanan', $kodeBillings)
                        ->select($trxSelect)
                        ->get()
                        ->keyBy('kode_billing_layanan');
                }
            } catch (\Throwable $e) {
                Log::info('Query trx_billing_layanan notice: ' . $e->getMessage());
            }

            $records = collect();

            // 1. Coba ambil dari tabel payment_confirmations di database aktif (ims_v3)
            try {
                if (Schema::hasTable('payment_confirmations') && !empty($expandedKodeBillings)) {
                    $localRecords = DB::table('payment_confirmations')
                        ->whereIn('kode_billing_layanan', $expandedKodeBillings)
                        ->orderBy('id', 'desc')
                        ->get();

                    if ($localRecords->isNotEmpty()) {
                        $records = $records->merge($localRecords);
                    }
                }
            } catch (\Throwable $e) {
                Log::info('Query local payment_confirmations notice: ' . $e->getMessage());
            }

            // 2. Fallback cross-database ke database ptmsn.payment_confirmations (seperti di bayar_transfer.php)
            try {
                if (!empty($expandedKodeBillings)) {
                    $escapedKodes = "'" . implode("','", array_map('addslashes', $expandedKodeBillings)) . "'";
                    $ptmsnRecords = DB::select("
                        SELECT * FROM ptmsn.payment_confirmations 
                        WHERE kode_billing_layanan IN ({$escapedKodes}) 
                        ORDER BY id DESC
                    ");

                    if (!empty($ptmsnRecords)) {
                        $records = $records->merge(collect($ptmsnRecords));
                    }
                }
            } catch (\Throwable $e) {
                // Ignore jika ptmsn bukan database lokal
            }

            if ($records->isNotEmpty()) {
                foreach ($records as $rec) {
                    if (!empty($rec->kode_billing_layanan)) {
                        $k = $rec->kode_billing_layanan;
                        if (!$confirmationsByKode->has($k)) {
                            $confirmationsByKode->put($k, $rec);
                        }
                        $kDash = str_replace('/', '-', $k);
                        if (!$confirmationsByKode->has($kDash)) {
                            $confirmationsByKode->put($kDash, $rec);
                        }
                        $kSlash = str_replace('-', '/', $k);
                        if (!$confirmationsByKode->has($kSlash)) {
                            $confirmationsByKode->put($kSlash, $rec);
                        }
                    }
                }
            }
        }

        foreach ($invoices as $inv) {
            $trx = $trxBillings->get($inv->kode_billing_layanan);
            if ($trx) {
                if (!empty($trx->merchant_type)) {
                    $inv->merchant_type = $trx->merchant_type;
                }
                if (isset($trx->payment_type) && $trx->payment_type !== null && $trx->payment_type !== '') {
                    $inv->payment_type = (string) $trx->payment_type;
                }
                if (isset($trx->destination_bank) && !empty($trx->destination_bank)) {
                    $inv->destination_bank = $trx->destination_bank;
                }
            }

            $kode = $inv->kode_billing_layanan;
            $altKode1 = str_replace('/', '-', $kode);
            $altKode2 = str_replace('-', '/', $kode);

            $confirmation = $confirmationsByKode->get($kode)
                ?? $confirmationsByKode->get($altKode1)
                ?? $confirmationsByKode->get($altKode2)
                ?? null;

            $inv->payment_confirmation = $confirmation;

            // Pastikan destination_bank terisi dari kolom tabel atau confirmation jika ada
            if (empty($inv->destination_bank)) {
                $inv->destination_bank = $confirmation?->destination_bank 
                    ?? $confirmation?->bank_name 
                    ?? null;
            }

            // Jika invoice memiliki bukti transfer terdaftar, tandai payment_type menjadi transfer manual (2)
            if ($confirmation && !empty($confirmation->proof_file)) {
                $inv->has_manual_transfer_proof = true;
                if (empty($inv->payment_type) || $inv->payment_type == '1') {
                    $inv->payment_type = '2';
                }
            } else {
                $inv->has_manual_transfer_proof = false;
            }
        }

        // Master Dropdown Data (Optimized to fast master tables)
        $bulanList = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];

        $currentYear = (int) date('Y');
        $tahunList = [(string) ($currentYear + 1), (string) $currentYear, (string) ($currentYear - 1), (string) ($currentYear - 2), (string) ($currentYear - 3)];

        $bandwithKategoriList = Schema::hasTable('m_bandwith_kategori')
            ? DB::table('m_bandwith_kategori')->where('disable', 0)->orderBy('nama_kategori_bandwith', 'asc')->get()
            : collect();

        $layananList = Schema::hasTable('m_bandwith_kategori')
            ? DB::table('m_bandwith_kategori')->where('disable', 0)->pluck('nama_kategori_bandwith')->filter()->unique()->values()->toArray()
            : ['BROADBAND', 'DEDICATED', 'SOHO', 'CORPORATE'];

        $wilayahList = Schema::hasTable('m_wilayah_perangkat')
            ? DB::table('m_wilayah_perangkat')->pluck('name_w')->filter()->unique()->toArray()
            : [];

        if (empty($wilayahList) && Schema::hasTable('m_wilayah')) {
            $wilayahList = DB::table('m_wilayah')->limit(50)->pluck('nama_kota')->filter()->unique()->toArray();
        }

        $statusBillList = Schema::hasTable('m_status_bill_lay')
            ? DB::table('m_status_bill_lay')
                ->where('hide', '0')
                ->whereNotIn('status_bill_lay', ['17', '18'])
                ->where('desc_bill_lay', 'NOT LIKE', '%cancel midtrans%')
                ->where('desc_bill_lay', 'NOT LIKE', '%expire midtrans%')
                ->get()
            : collect();

        $statusUserList = [
            '20' => 'User Aktif',
            '21' => 'User Suspend',
            '22' => 'Req. Suspend',
            '23' => 'Terminasi',
            '18' => 'Selesai Instalasi',
        ];

        return view('finance.billing-layanan', [
            'user' => $request->user(),
            'invoices' => $invoices,
            'kpis' => [
                'generating' => ['count' => $generatingCount, 'amount' => $generatingAmount],
                'publish' => ['count' => $publishCount, 'amount' => $publishAmount],
                'waiting' => ['count' => $waitingCount, 'amount' => $waitingAmount],
                'paid' => ['count' => $paidCount, 'amount' => $paidAmount],
            ],
            'bulanList' => $bulanList,
            'tahunList' => $tahunList,
            'bandwithKategoriList' => $bandwithKategoriList,
            'layananList' => $layananList,
            'wilayahList' => $wilayahList,
            'statusBillList' => $statusBillList,
            'statusUserList' => $statusUserList,
            'selectedBulan' => $selectedBulan,
            'selectedTahun' => $selectedTahun,
        ]);
    }

    /**
     * Batch Generate Monthly Invoice Massal
     */
    public function generateInvoice(Request $request): RedirectResponse
    {
        $request->validate([
            'bulan' => 'required',
            'tahun' => 'required',
        ]);

        $bulan = str_pad($request->input('bulan'), 2, '0', STR_PAD_LEFT);
        $tahun = (string) $request->input('tahun');
        $userCreator = Auth::user()?->nama ?? 'FINANCE';
        $jenisGenerate = $request->input('jenis_generate', 'single');
        $nomorInternet = $request->input('nomor_internet');
        $autoPublish = $request->input('auto_publish', 'yes');
        $ppnSetting = $request->input('ppn', 'default');
        $kirimWa = $request->has('kirim_wa') || $request->input('kirim_wa') == '1' || $request->input('kirim_wa') === 'true';
        $kirimEmail = $request->has('kirim_email') || $request->input('kirim_email') == '1' || $request->input('kirim_email') === 'true';

        if ($jenisGenerate === 'single' && $nomorInternet) {
            $p = DB::table('view_batchjob')
                ->where('nomor_internet', $nomorInternet)
                ->first();

            if (!$p) {
                return redirect()->back()->with('error', "Pelanggan dengan nomor internet {$nomorInternet} tidak ditemukan.");
            }

            $kodeBilling = "INV/{$p->nomor_internet}/{$bulan}/{$tahun}";
            $namaBulanShort = Carbon::createFromDate((int)$tahun, (int)$bulan, 1)->format('M');
            $periodeTagihan = "{$namaBulanShort} {$tahun}";

            $exists = DB::table('trx_billing_layanan')->where('kode_billing_layanan', $kodeBilling)->exists();
            if ($exists) {
                return redirect()->back()->with('info', "Invoice {$kodeBilling} sudah pernah diterbitkan sebelumnya.");
            }

            $hargaBandwith = (float) ($p->harga_bandwith ?? $p->nominal_bandwith ?? 0);
            $potongan = (float) ($p->potongan ?? 0);
            $totalLayanan = max(0, $hargaBandwith - $potongan);
            $statusBill = ($autoPublish === 'yes') ? '13' : '12';
            $now = Carbon::now()->toDateTimeString();

            DB::table('trx_billing_layanan')->insert([
                'kode_billing_layanan' => $kodeBilling,
                'nomor_internet' => $p->nomor_internet,
                'kode_bandwith' => $p->kode_bandwith ?? null,
                'nominal_bandwith' => $p->nominal_bandwith ?? '0',
                'bulan_tagihan' => $bulan,
                'tahun_tagihan' => $tahun,
                'periode_tagihan' => $periodeTagihan,
                'potongan' => (string) $potongan,
                'desc_potongan' => $p->potongan_note ?? '-',
                'ppn' => ($ppnSetting === 'exclude' ? '0' : ($p->ppn_nom ?? '0.11')),
                'tax' => ($ppnSetting === 'exclude' ? '0' : ($p->ppn ?? '2')),
                'voucher' => '-',
                'total_layanan' => (string) $totalLayanan,
                'notif_mail' => $kirimEmail ? '1' : '0',
                'notif_wa' => $kirimWa ? '1' : '0',
                'status_bill_lay' => $statusBill,
                'denda' => null,
                'invoice_file' => null,
                'payment_type' => '1',
                'payment_post' => '-',
                'payment_publish' => ($autoPublish === 'yes') ? $now : null,
                'date_create' => $now,
                'user_create' => $userCreator,
                'date_update' => $now,
                'user_update' => $userCreator,
                'hide' => '0',
                'islock' => null,
            ]);

            return redirect()->route('finance.billing-layanan', [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ])->with('success', "Invoice {$kodeBilling} berhasil dibuat.");
        }

        $result = BillingService::generateMonthlyInvoices($bulan, $tahun, $userCreator);

        if ($result['success']) {
            return redirect()->route('finance.billing-layanan', [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ])->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Publish Single Invoice Bulanan (Status 11/12 -> 13)
     */
    public function publishBillingLayanan(Request $request, ?string $kodeBilling = null): RedirectResponse
    {
        $user = Auth::user()?->nama ?? 'FINANCE';
        $decodedKode = $request->input('kode_billing') ? trim($request->input('kode_billing')) : urldecode($kodeBilling ?? '');

        try {
            $inv = DB::table('trx_billing_layanan')->where('kode_billing_layanan', $decodedKode)->first();
            if (!$inv) {
                return redirect()->back()->with('error', 'Invoice tidak ditemukan.');
            }

            $updateData = [
                'status_bill_lay' => '13', // Publish
                'payment_publish' => Carbon::now()->toDateTimeString(),
                'date_update' => Carbon::now()->toDateTimeString(),
                'user_update' => $user,
            ];

            // If payment_type is Midtrans (1) and link not yet generated or empty, generate Snap link
            if ($inv->payment_type == 1 && empty($inv->payment_respond_post)) {
                $customer = DB::table('view_batchjob')->where('nomor_internet', $inv->nomor_internet)->first();
                $customerDetails = [
                    'nama' => $customer->nama_pelanggan ?? 'Pelanggan',
                    'email' => $customer->email ?? 'billing@ims-router.net',
                    'nomor_hp' => $customer->nomor_hp ?? '08123456789',
                ];
                $amount = (float) ($inv->total_layanan ?? $inv->nominal_bandwith ?? 0);
                $midtrans = app(MidtransService::class)->generateSnapLink($decodedKode, $amount, $customerDetails);
                $updateData['payment_post'] = $midtrans['payment_post'];
                $updateData['payment_respond_post'] = $midtrans['payment_respond_post'];
                $updateData['expiry'] = $midtrans['expiry'];
            }

            DB::table('trx_billing_layanan')
                ->where('kode_billing_layanan', $decodedKode)
                ->update($updateData);

            DB::table('trx_billing_layanan_log')->insert([
                'kode_billing_lay_log' => 'LOG-' . uniqid(),
                'kode_billing_layanan' => $decodedKode,
                'status_bill_lay' => '13',
                'note_billing_lay' => "Invoice published by {$user}" . ($inv->payment_type == 1 ? " (Midtrans Link Generated)" : ""),
                'date_create' => Carbon::now()->toDateTimeString(),
                'user_create' => $user,
                'hide' => '0',
            ]);

            return redirect()->back()->with('success', "Invoice {$decodedKode} berhasil di-publish.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal publish invoice: ' . $e->getMessage());
        }
    }

    /**
     * Generate Link Midtrans on-demand untuk Billing Layanan
     */
    public function generateMidtransLayanan(Request $request): RedirectResponse
    {
        $user = Auth::user()?->nama ?? 'FINANCE';
        $kodeBilling = trim($request->input('kode_billing', ''));

        $inv = DB::table('trx_billing_layanan')->where('kode_billing_layanan', $kodeBilling)->first();
        if (!$inv) {
            return redirect()->back()->with('error', 'Invoice tidak ditemukan.');
        }

        try {
            $customer = DB::table('view_batchjob')->where('nomor_internet', $inv->nomor_internet)->first();
            $customerDetails = [
                'nama' => $customer->nama_pelanggan ?? 'Pelanggan',
                'email' => $customer->email ?? 'billing@ims-router.net',
                'nomor_hp' => $customer->nomor_hp ?? '08123456789',
            ];
            $amount = (float) ($inv->total_layanan ?? $inv->nominal_bandwith ?? 0);
            $midtrans = app(MidtransService::class)->generateSnapLink($kodeBilling, $amount, $customerDetails);

            DB::table('trx_billing_layanan')
                ->where('kode_billing_layanan', $kodeBilling)
                ->update([
                    'payment_type' => '1',
                    'payment_post' => $midtrans['payment_post'],
                    'payment_respond_post' => $midtrans['payment_respond_post'],
                    'expiry' => $midtrans['expiry'],
                    'date_update' => Carbon::now()->toDateTimeString(),
                    'user_update' => $user,
                ]);

            DB::table('trx_billing_layanan_log')->insert([
                'kode_billing_lay_log' => 'LOG-' . uniqid(),
                'kode_billing_layanan' => $kodeBilling,
                'status_bill_lay' => $inv->status_bill_lay,
                'note_billing_lay' => "Link Pembayaran Midtrans berhasil di-generate oleh {$user} (Berlaku s/d {$midtrans['expiry']})",
                'date_create' => Carbon::now()->toDateTimeString(),
                'user_create' => $user,
                'hide' => '0',
            ]);

            return redirect()->back()->with('success', "Link Pembayaran Midtrans untuk invoice {$kodeBilling} berhasil dibuat!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal generate link Midtrans: ' . $e->getMessage());
        }
    }

    /**
     * Renew Expired Link Midtrans untuk Billing Layanan
     */
    public function renewMidtransLayanan(Request $request): RedirectResponse
    {
        $user = Auth::user()?->nama ?? 'FINANCE';
        $kodeBilling = trim($request->input('kode_billing', ''));

        $inv = DB::table('trx_billing_layanan')->where('kode_billing_layanan', $kodeBilling)->first();
        if (!$inv) {
            return redirect()->back()->with('error', 'Invoice tidak ditemukan.');
        }

        try {
            $customer = DB::table('view_batchjob')->where('nomor_internet', $inv->nomor_internet)->first();
            $customerDetails = [
                'nama' => $customer->nama_pelanggan ?? 'Pelanggan',
                'email' => $customer->email ?? 'billing@ims-router.net',
                'nomor_hp' => $customer->nomor_hp ?? '08123456789',
            ];
            $amount = (float) ($inv->total_layanan ?? $inv->nominal_bandwith ?? 0);
            $rePublishCount = ((int) ($inv->re_publish ?? 0)) + 1;

            $midtrans = app(MidtransService::class)->renewSnapLink($kodeBilling, $amount, $customerDetails, [], $rePublishCount);

            DB::table('trx_billing_layanan')
                ->where('kode_billing_layanan', $kodeBilling)
                ->update([
                    'payment_type' => '1',
                    'payment_post' => $midtrans['payment_post'],
                    'payment_respond_post' => $midtrans['payment_respond_post'],
                    'expiry' => $midtrans['expiry'],
                    're_publish' => (string) $rePublishCount,
                    'status_bill_lay' => in_array($inv->status_bill_lay, ['11', '12']) ? '13' : $inv->status_bill_lay,
                    'date_update' => Carbon::now()->toDateTimeString(),
                    'user_update' => $user,
                ]);

            DB::table('trx_billing_layanan_log')->insert([
                'kode_billing_lay_log' => 'LOG-' . uniqid(),
                'kode_billing_layanan' => $kodeBilling,
                'status_bill_lay' => $inv->status_bill_lay,
                'note_billing_lay' => "Link Midtrans di-renew ke Order ID {$midtrans['order_id']} oleh {$user} (Berlaku s/d {$midtrans['expiry']})",
                'date_create' => Carbon::now()->toDateTimeString(),
                'user_create' => $user,
                'hide' => '0',
            ]);

            return redirect()->back()->with('success', "Link Midtrans untuk invoice {$kodeBilling} berhasil di-renew! Berlaku hingga " . Carbon::parse($midtrans['expiry'])->translatedFormat('d M Y H:i'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal renew link Midtrans: ' . $e->getMessage());
        }
    }

    /**
     * Konfirmasi Pembayaran Manual Invoice Bulanan
     */
    public function konfirmasiBayarLayanan(Request $request, ?string $kodeBilling = null): RedirectResponse
    {
        $request->validate([
            'nominal_bayar' => 'required|numeric|min:1',
            'metode_bayar' => 'required|string',
            'bank_tujuan' => 'nullable|string',
            'nama_kolektor' => 'nullable|string',
            'no_kwitansi' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        $user = Auth::user()?->nama ?? Auth::user()?->name ?? 'FINANCE';
        $userUpdate = mb_substr($user, 0, 50);
        $decodedKode = $request->input('kode_billing') ? trim($request->input('kode_billing')) : urldecode($kodeBilling ?? '');
        $nominal = $request->input('nominal_bayar');
        $metodeBayar = $request->input('metode_bayar');
        $namaKolektor = trim((string) $request->input('nama_kolektor', ''));
        $noKwitansi = trim((string) $request->input('no_kwitansi', ''));

        if ($metodeBayar === 'cash' || $metodeBayar === '3') {
            $paymentType = '3';
            $penerima = $request->input('bank_tujuan') ?: 'Cash To Collector';
            $bank = $namaKolektor !== '' ? "{$penerima} ({$namaKolektor})" : $penerima;
            $catatanDefault = "Pembayaran Cash to Collector Terverifikasi" . ($noKwitansi !== '' ? " [Kwitansi: {$noKwitansi}]" : "");
        } else {
            $paymentType = '2';
            $bank = $request->input('bank_tujuan') ?: 'BCA';
            $catatanDefault = "Pembayaran Transfer Bank Terverifikasi" . ($noKwitansi !== '' ? " [Ref: {$noKwitansi}]" : "");
        }

        $catatan = $request->input('catatan') ?: $catatanDefault;

        try {
            $inv = DB::table('trx_billing_layanan')->where('kode_billing_layanan', $decodedKode)->first();
            $nomorInternet = $inv?->nomor_internet;

            $updateData = [
                'status_bill_lay' => '15', // Paid
                'payment_paid' => Carbon::now()->toDateTimeString(),
                'amount_paid' => (string) $nominal,
                'merchant_type' => $bank,
                'payment_type' => $paymentType,
                'date_update' => Carbon::now()->toDateTimeString(),
                'user_update' => $userUpdate,
            ];
            if (Schema::hasColumn('trx_billing_layanan', 'destination_bank')) {
                $updateData['destination_bank'] = $bank;
            }

            DB::table('trx_billing_layanan')
                ->where('kode_billing_layanan', $decodedKode)
                ->update($updateData);

            DB::table('trx_billing_layanan_log')->insert([
                'kode_billing_lay_log' => 'LOG-' . uniqid(),
                'kode_billing_layanan' => $decodedKode,
                'status_bill_lay' => '15',
                'note_billing_lay' => "Payment verified ({$bank} - Rp " . number_format($nominal, 0, ',', '.') . "): {$catatan} by {$userUpdate}",
                'date_create' => Carbon::now()->toDateTimeString(),
                'user_create' => $userUpdate,
                'hide' => '0',
            ]);

            // Handle upload foto bukti transfer (jika diupload via modal / kamera)
            if ($request->hasFile('foto_bukti')) {
                try {
                    $file = $request->file('foto_bukti');
                    if ($file->isValid()) {
                        $ext = $file->getClientOriginalExtension() ?: 'jpg';
                        $filename = 'proof_' . preg_replace('/[^a-zA-Z0-9]/', '_', $decodedKode) . '_' . time() . '.' . $ext;
                        $uploadPath = public_path('uploads/bukti_transfer');
                        if (!file_exists($uploadPath)) {
                            mkdir($uploadPath, 0755, true);
                        }
                        $file->move($uploadPath, $filename);
                        $proofUrl = '/uploads/bukti_transfer/' . $filename;

                        if (Schema::hasTable('payment_confirmations')) {
                            $existPc = DB::table('payment_confirmations')
                                ->where('kode_billing_layanan', $decodedKode)
                                ->orWhere('kode_billing_layanan', str_replace('/', '-', $decodedKode))
                                ->orWhere('kode_billing_layanan', str_replace('-', '/', $decodedKode))
                                ->first();

                            if ($existPc) {
                                DB::table('payment_confirmations')
                                    ->where('id', $existPc->id)
                                    ->update([
                                        'proof_file' => $proofUrl,
                                        'status' => 'approved',
                                        'verified_at' => Carbon::now()->toDateTimeString(),
                                        'admin_notes' => "Diverifikasi oleh {$userUpdate} via Approval Billing Layanan",
                                        'updated_at' => Carbon::now()->toDateTimeString(),
                                    ]);
                            } else {
                                DB::table('payment_confirmations')->insert([
                                    'kode_billing_layanan' => $decodedKode,
                                    'customer_id' => $nomorInternet,
                                    'proof_file' => $proofUrl,
                                    'status' => 'approved',
                                    'verified_at' => Carbon::now()->toDateTimeString(),
                                    'notes' => $catatan,
                                    'admin_notes' => "Diupload & diverifikasi oleh {$userUpdate}",
                                    'created_at' => Carbon::now()->toDateTimeString(),
                                    'updated_at' => Carbon::now()->toDateTimeString(),
                                ]);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Upload foto bukti notice: ' . $e->getMessage());
                }
            }

            // Sinkronisasi status di tabel payment_confirmations menjadi approved
            try {
                if (Schema::hasTable('payment_confirmations')) {
                    DB::table('payment_confirmations')
                        ->where('kode_billing_layanan', $decodedKode)
                        ->orWhere('kode_billing_layanan', str_replace('/', '-', $decodedKode))
                        ->orWhere('kode_billing_layanan', str_replace('-', '/', $decodedKode))
                        ->update([
                            'status' => 'approved',
                            'verified_at' => Carbon::now()->toDateTimeString(),
                            'admin_notes' => "Diverifikasi oleh {$userUpdate} via Approval Billing Layanan",
                            'updated_at' => Carbon::now()->toDateTimeString(),
                        ]);
                }
            } catch (\Throwable $e) {
                Log::info('Update local payment_confirmations note: ' . $e->getMessage());
            }

            // Cross-database update ke database ptmsn jika ada
            try {
                $escapedKode = addslashes($decodedKode);
                $escapedAlt1 = addslashes(str_replace('/', '-', $decodedKode));
                $escapedAlt2 = addslashes(str_replace('-', '/', $decodedKode));
                $nowStr = Carbon::now()->toDateTimeString();
                $adminNoteStr = addslashes("Diverifikasi oleh {$userUpdate} via Approval Billing Layanan");

                DB::statement("
                    UPDATE ptmsn.payment_confirmations 
                    SET status = 'approved',
                        verified_at = '{$nowStr}',
                        admin_notes = '{$adminNoteStr}',
                        updated_at = '{$nowStr}'
                    WHERE kode_billing_layanan IN ('{$escapedKode}', '{$escapedAlt1}', '{$escapedAlt2}')
                ");
            } catch (\Throwable $e) {
                // Ignore jika ptmsn bukan database lokal
            }

            // Auto Req Unsuspend ke NOC jika pelanggan sedang dalam status Suspend/Terisolir
            $unsuspendInfo = '';
            if ($nomorInternet && Schema::hasTable('trx_suspend')) {
                $activeSuspend = DB::table('trx_suspend')
                    ->where('nomor_internet', $nomorInternet)
                    ->whereIn('status_suspend', ['11', '12'])
                    ->orderBy('date_create', 'desc')
                    ->first();

                $customerReg = DB::table('trx_batchjob_register')
                    ->where('nomor_internet', $nomorInternet)
                    ->first();

                if ($activeSuspend) {
                    DB::table('trx_suspend')
                        ->where('kode_suspend', $activeSuspend->kode_suspend)
                        ->update([
                            'status_suspend' => '18', // 18: Request Unsuspend ke NOC
                            'desc_suspend_cancel' => "Otomatis diajukan buka isolir: Pelanggan telah membayar lunas tagihan {$decodedKode} ({$bank})",
                            'date_update' => Carbon::now()->toDateTimeString(),
                            'user_update' => $userUpdate,
                        ]);
                    $unsuspendInfo = " serta Permintaan Buka Isolir (Req Unsuspend) otomatis dikirimkan ke tim NOC.";
                } elseif ($customerReg && $customerReg->is_suspend == '1') {
                    $kodeSuspend = $nomorInternet . '-' . rand(1000000, 9999999);
                    DB::table('trx_suspend')->insert([
                        'kode_suspend' => $kodeSuspend,
                        'nomor_internet' => $nomorInternet,
                        'suspend_start' => now()->format('Y-m-d'),
                        'status_suspend' => '18', // 18: Request Unsuspend ke NOC
                        'desc_suspend' => "Otomatis Req Unsuspend: Pelanggan telah melunasi tagihan {$decodedKode} ({$bank})",
                        'date_create' => Carbon::now()->toDateTimeString(),
                        'user_create' => $userUpdate,
                        'hide' => '0',
                    ]);
                    $unsuspendInfo = " serta Permintaan Buka Isolir (Req Unsuspend) otomatis dikirimkan ke tim NOC.";
                }
            }

            return redirect()->back()->with('success', "Pembayaran invoice {$decodedKode} sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil diverifikasi{$unsuspendInfo}");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal konfirmasi pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Adjustment Diskon Potongan / Denda Invoice Bulanan
     */
    public function adjustBillingLayanan(Request $request, ?string $kodeBilling = null): RedirectResponse
    {
        $request->validate([
            'potongan' => 'nullable|numeric|min:0',
            'denda' => 'nullable|numeric|min:0',
            'desc_potongan' => 'nullable|string',
            'note_adjustment' => 'nullable|string',
        ]);

        $user = Auth::user()?->nama ?? 'FINANCE';
        $decodedKode = $request->input('kode_billing') ? trim($request->input('kode_billing')) : urldecode($kodeBilling ?? '');

        $inv = DB::table('trx_billing_layanan')->where('kode_billing_layanan', $decodedKode)->first();
        if (!$inv) {
            return redirect()->back()->with('error', 'Invoice tidak ditemukan.');
        }

        $potongan = (float) $request->input('potongan', $inv->potongan ?? 0);
        $denda = (float) $request->input('denda', $inv->denda ?? 0);
        $descPotongan = $request->input('desc_potongan', $inv->desc_potongan);
        $noteAdj = $request->input('note_adjustment', 'Penyesuaian tagihan oleh ' . $user);

        // Hitung total baru dari detail pokok
        $subtotal = DB::table('trx_billing_layanan_detail')
            ->where('kode_billing_layanan', $decodedKode)
            ->where('kode_item', 'T11')
            ->sum('biaya');

        if ($subtotal <= 0) {
            $subtotal = (float) $inv->total_layanan;
        }

        $totalBaru = max(0, $subtotal - $potongan + $denda);

        try {
            DB::table('trx_billing_layanan')
                ->where('kode_billing_layanan', $decodedKode)
                ->update([
                    'potongan' => (string) $potongan,
                    'desc_potongan' => $descPotongan,
                    'denda' => $denda > 0 ? (string) $denda : null,
                    'total_layanan' => (string) $totalBaru,
                    'note_adjusment' => $noteAdj,
                    'date_update' => Carbon::now()->toDateTimeString(),
                    'user_update' => $user,
                ]);

            DB::table('trx_billing_layanan_log')->insert([
                'kode_billing_lay_log' => 'LOG-' . uniqid(),
                'kode_billing_layanan' => $decodedKode,
                'status_bill_lay' => $inv->status_bill_lay,
                'note_billing_lay' => "Adjustment: Diskon Rp " . number_format($potongan, 0, ',', '.') . ", Denda Rp " . number_format($denda, 0, ',', '.') . " -> Total Rp " . number_format($totalBaru, 0, ',', '.') . " ({$noteAdj})",
                'date_create' => Carbon::now()->toDateTimeString(),
                'user_create' => $user,
                'hide' => '0',
            ]);

            return redirect()->back()->with('success', "Penyesuaian invoice {$decodedKode} berhasil disimpan. Total baru: Rp " . number_format($totalBaru, 0, ',', '.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal melakukan penyesuaian tagihan: ' . $e->getMessage());
        }
    }

    /**
     * Rollback Status Tagihan ke Draft / Generating
     */
    public function rollbackBillingLayanan(Request $request, ?string $kodeBilling = null): RedirectResponse
    {
        $user = Auth::user()?->nama ?? 'FINANCE';
        $decodedKode = $request->input('kode_billing') ? trim($request->input('kode_billing')) : urldecode($kodeBilling ?? '');

        try {
            DB::table('trx_billing_layanan')
                ->where('kode_billing_layanan', $decodedKode)
                ->update([
                    'status_bill_lay' => '12', // Auto publish / draft
                    'payment_paid' => null,
                    'amount_paid' => null,
                    'date_update' => Carbon::now()->toDateTimeString(),
                    'user_update' => $user,
                ]);

            DB::table('trx_billing_layanan_log')->insert([
                'kode_billing_lay_log' => 'LOG-' . uniqid(),
                'kode_billing_layanan' => $decodedKode,
                'status_bill_lay' => '12',
                'note_billing_lay' => "Invoice status rolled back to Draft by {$user}",
                'date_create' => Carbon::now()->toDateTimeString(),
                'user_create' => $user,
                'hide' => '0',
            ]);

            return redirect()->back()->with('success', "Status invoice {$decodedKode} berhasil di-rollback.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal rollback invoice: ' . $e->getMessage());
        }
    }

    /**
     * Hapus Tagihan Billing Layanan
     */
    public function deleteBillingLayanan(Request $request, ?string $kodeBilling = null): RedirectResponse
    {
        $user = Auth::user()?->nama ?? 'FINANCE';
        $decodedKode = $request->input('kode_billing') ? trim($request->input('kode_billing')) : urldecode($kodeBilling ?? '');

        try {
            if (empty($decodedKode)) {
                return redirect()->back()->with('error', 'Kode billing tidak valid.');
            }

            if (Schema::hasTable('trx_billing_layanan_detail')) {
                DB::table('trx_billing_layanan_detail')->where('kode_billing_layanan', $decodedKode)->delete();
            }

            if (Schema::hasTable('trx_billing_layanan_log')) {
                DB::table('trx_billing_layanan_log')->where('kode_billing_layanan', $decodedKode)->delete();
            }

            DB::table('trx_billing_layanan')->where('kode_billing_layanan', $decodedKode)->delete();

            return redirect()->back()->with('success', "Invoice tagihan {$decodedKode} berhasil dihapus!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus tagihan: ' . $e->getMessage());
        }
    }

    /**
     * Change Payment Method for Billing Layanan (1: Midtrans, 2: Manual Transfer, 3: Cash To Collector)
     */
    public function changePaymentMethodLayanan(Request $request, ?string $kodeBilling = null): RedirectResponse
    {
        $request->validate([
            'payment_type' => 'required|in:1,2,3',
        ]);

        $user = Auth::user()?->nama ?? 'FINANCE';
        $decodedKode = $request->input('kode_billing') ? trim($request->input('kode_billing')) : urldecode($kodeBilling ?? '');
        $paymentType = (string) $request->input('payment_type');
        $paymentTypeName = $paymentType == '1' ? 'Midtrans' : ($paymentType == '2' ? 'Manual Transfer' : 'Cash To Collector');

        try {
            DB::table('trx_billing_layanan')
                ->where('kode_billing_layanan', $decodedKode)
                ->update([
                    'payment_type' => $paymentType,
                    'status_bill_lay' => '20', // Change Payment Method
                    'date_update' => Carbon::now()->toDateTimeString(),
                    'user_update' => $user,
                ]);

            DB::table('trx_billing_layanan_log')->insert([
                'kode_billing_lay_log' => 'LOG-' . uniqid(),
                'kode_billing_layanan' => $decodedKode,
                'status_bill_lay' => '20',
                'note_billing_lay' => "Metode pembayaran diubah ke {$paymentTypeName} oleh {$user}",
                'date_create' => Carbon::now()->toDateTimeString(),
                'user_create' => $user,
                'hide' => '0',
            ]);

            return redirect()->back()->with('success', "Metode pembayaran invoice {$decodedKode} berhasil diubah ke {$paymentTypeName}!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengubah metode pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Change Payment Method for Billing Registrasi (1: Midtrans, 2: Manual Transfer, 3: Cash To Collector)
     */
    public function changePaymentMethodRegistrasi(Request $request, ?string $kodeBilling = null): RedirectResponse
    {
        $request->validate([
            'payment_type' => 'required|in:1,2,3',
        ]);

        $user = Auth::user()?->nama ?? 'FINANCE';
        $decodedKode = $request->input('kode_billing') ? trim($request->input('kode_billing')) : urldecode($kodeBilling ?? '');
        $paymentType = (string) $request->input('payment_type');
        $paymentTypeName = $paymentType == '1' ? 'Midtrans' : ($paymentType == '2' ? 'Manual Transfer' : 'Cash To Collector');

        try {
            DB::table('trx_billing_registrasi')
                ->where('kode_billing_registrasi', $decodedKode)
                ->update([
                    'payment_type' => $paymentType,
                    'status_bill_reg' => '19', // Change Payment Method
                    'date_update' => Carbon::now()->toDateTimeString(),
                    'user_update' => $user,
                ]);

            DB::table('trx_billing_registrasi_log')->insert([
                'kode_billing_regis_log' => 'LOG-' . uniqid(),
                'kode_billing_regis_log' => $decodedKode,
                'status_bill_reg' => '19',
                'note_billing_reg' => "Metode pembayaran registrasi diubah ke {$paymentTypeName} oleh {$user}",
                'date_create' => Carbon::now()->toDateTimeString(),
                'user_create' => $user,
                'hide' => '0',
            ]);

            return redirect()->back()->with('success', "Metode pembayaran registrasi {$decodedKode} berhasil diubah ke {$paymentTypeName}!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengubah metode pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * AJAX Endpoint: Get Detail Breakdown Invoice
     */
    public function getBillingLayananDetail(Request $request, ?string $kodeBilling = null): JsonResponse
    {
        $decodedKode = $request->input('kode_billing') ?: ($request->input('kode') ?: urldecode($kodeBilling ?? ''));

        $invoice = DB::table('view_billing_layanan')
            ->where('kode_billing_layanan', $decodedKode)
            ->first();

        if (!$invoice) {
            return response()->json(['error' => 'Invoice tidak ditemukan'], 404);
        }

        if (Schema::hasTable('trx_billing_layanan')) {
            $trx = DB::table('trx_billing_layanan')->where('kode_billing_layanan', $decodedKode)->first();
            if ($trx) {
                if (!empty($trx->merchant_type)) {
                    $invoice->merchant_type = $trx->merchant_type;
                }
                if (isset($trx->payment_type) && $trx->payment_type !== null && $trx->payment_type !== '') {
                    $invoice->payment_type = (string) $trx->payment_type;
                }
            }
        }

        $items = DB::table('trx_billing_layanan_detail')
            ->where('kode_billing_layanan', $decodedKode)
            ->get();

        $logs = DB::table('trx_billing_layanan_log')
            ->where('kode_billing_layanan', $decodedKode)
            ->orderBy('date_create', 'desc')
            ->limit(10)
            ->get();

        $confirmation = null;
        if (Schema::hasTable('payment_confirmations')) {
            $confirmation = DB::table('payment_confirmations')
                ->where('kode_billing_layanan', $decodedKode)
                ->orWhere('kode_billing_layanan', str_replace('/', '-', $decodedKode))
                ->orWhere('kode_billing_layanan', str_replace('-', '/', $decodedKode))
                ->orderBy('id', 'desc')
                ->first();
        }

        if (!$confirmation) {
            try {
                $escapedKode = addslashes($decodedKode);
                $escapedAlt1 = addslashes(str_replace('/', '-', $decodedKode));
                $escapedAlt2 = addslashes(str_replace('-', '/', $decodedKode));
                $ptmsnConf = DB::selectOne("
                    SELECT * FROM ptmsn.payment_confirmations 
                    WHERE kode_billing_layanan IN ('{$escapedKode}', '{$escapedAlt1}', '{$escapedAlt2}')
                    ORDER BY id DESC
                    LIMIT 1
                ");
                if ($ptmsnConf) {
                    $confirmation = $ptmsnConf;
                }
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'invoice' => $invoice,
            'items' => $items,
            'logs' => $logs,
            'confirmation' => $confirmation,
        ]);
    }

    /**
     * Export Data Invoice Bulanan to CSV
     */
    public function exportBillingLayanan(Request $request): StreamedResponse
    {
        $query = DB::table('view_billing_layanan');

        if ($request->filled('bulan')) {
            $query->where('bulan_tagihan', str_pad($request->input('bulan'), 2, '0', STR_PAD_LEFT));
        }
        if ($request->filled('tahun')) {
            $query->where('tahun_tagihan', $request->input('tahun'));
        }
        if ($request->filled('layanan')) {
            $query->where('nama_kategori_bandwith', $request->input('layanan'));
        }
        if ($request->filled('status_bayar')) {
            $query->where('status_bill_lay', $request->input('status_bayar'));
        }
        if ($request->filled('wilayah')) {
            $query->where('nama_kota_pasang', $request->input('wilayah'));
        }
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('kode_billing_layanan', 'LIKE', "%{$search}%")
                  ->orWhere('nomor_internet', 'LIKE', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'LIKE', "%{$search}%");
            });
        }

        $invoices = $query->orderBy('date_create', 'desc')->get();
        $filename = 'rekap_billing_layanan_' . date('Ymd_His') . '.csv';

        return response()->stream(function () use ($invoices) {
            $handle = fopen('php://output', 'w');
            // Add BOM for UTF-8 Excel support
            fputs($handle, "\xEF\xBB\xBF");

            // Header
            fputcsv($handle, [
                'No Invoice',
                'No Layanan / Internet',
                'Nama Pelanggan',
                'Kategori Layanan',
                'Bandwidth (Mbps)',
                'Periode',
                'Tagihan (Rp)',
                'Potongan (Rp)',
                'Denda (Rp)',
                'Total Bayar (Rp)',
                'Jumlah Dibayar (Rp)',
                'Status Tagihan',
                'Metode Pembayaran',
                'Tanggal Terbit',
                'Tanggal Bayar',
                'Wilayah Pasang',
            ]);

            foreach ($invoices as $inv) {
                $methodStr = $inv->merchant_type ?? ($inv->desc_payment_type ?? ($inv->payment_type == 1 ? 'Midtrans' : ($inv->payment_type == 3 ? 'Cash To Collector' : 'Manual Transfer')));
                fputcsv($handle, [
                    $inv->kode_billing_layanan ?? '',
                    $inv->nomor_internet ?? '',
                    $inv->nama_pelanggan ?? '',
                    $inv->nama_kategori_bandwith ?? '',
                    $inv->nominal_bandwith ?? '',
                    $inv->periode_tagihan ?? '',
                    $inv->harga_bandwith ?? $inv->total_layanan ?? 0,
                    $inv->potongan ?? 0,
                    $inv->denda ?? 0,
                    $inv->total_layanan ?? 0,
                    $inv->amount_paid ?? 0,
                    $inv->desc_bill_lay ?? '',
                    $methodStr,
                    $inv->payment_publish ?? $inv->date_create ?? '',
                    $inv->payment_paid ?? '',
                    $inv->nama_kota_pasang ?? '',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // =========================================================================
    // BILLING REGISTRASI (TAGIHAN PASANG BARU)
    // =========================================================================

    /**
     * Menu Billing Registrasi (Tagihan Pendaftaran / Pasang Baru)
     */
    public function billingRegistrasi(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $query = DB::table('view_billing_reg');

        // Filters
        if ($request->filled('layanan')) {
            $query->where('nama_kategori_bandwith', $request->input('layanan'));
        }
        if ($request->filled('status_bayar')) {
            $statusBayar = trim((string)$request->input('status_bayar'));
            if ($statusBayar === 'menunggu_verifikasi' || $statusBayar === 'waiting_verification') {
                $pendingKodes = collect();
                if (Schema::hasTable('payment_confirmations')) {
                    $pendingKodes = DB::table('payment_confirmations')
                        ->where('status', '!=', 'approved')
                        ->where('status', '!=', 'rejected')
                        ->pluck('kode_billing_layanan')
                        ->filter()
                        ->toArray();
                }

                $expandedKodes = [];
                foreach ($pendingKodes as $k) {
                    $expandedKodes[] = $k;
                    $expandedKodes[] = str_replace('/', '-', $k);
                    $expandedKodes[] = str_replace('-', '/', $k);
                }
                $expandedKodes = array_unique(array_filter($expandedKodes));

                $query->where(function ($q) use ($expandedKodes) {
                    $q->where('status_bill_reg', '!=', '14');
                    if (!empty($expandedKodes)) {
                        $q->whereIn('kode_billing_registrasi', $expandedKodes);
                    } else {
                        $q->where('payment_type', '2');
                    }
                });
            } else {
                $query->where('status_bill_reg', $statusBayar);
            }
        }
        if ($request->filled('wilayah')) {
            $query->where('alamat_p', 'LIKE', '%' . $request->input('wilayah') . '%');
        }
        if ($request->filled('metode_bayar')) {
            $query->where('payment_type', $request->input('metode_bayar'));
        }
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('kode_billing_registrasi', 'LIKE', "%{$search}%")
                  ->orWhere('nomor_internet', 'LIKE', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'LIKE', "%{$search}%");
            });
        }

        // HIGH PERFORMANCE: 1 SINGLE AGGREGATED QUERY for Billing Registrasi KPIs
        $kpi = DB::table('trx_billing_registrasi')
            ->selectRaw("
                COUNT(*) as total_count,
                COUNT(CASE WHEN (status_bill_reg IN ('11', '11.1') OR status_bill_reg IS NULL) AND payment_publish IS NULL THEN 1 END) as draft_count,
                COUNT(CASE WHEN status_bill_reg = '12' OR payment_publish IS NOT NULL THEN 1 END) as published_count,
                COUNT(CASE WHEN payment_type = '1' THEN 1 END) as midtrans_count,
                COUNT(CASE WHEN payment_type IN ('2', '3') THEN 1 END) as manual_count
            ")
            ->first();

        $kpiTotal = (int) ($kpi->total_count ?? 0);
        $kpiDraft = (int) ($kpi->draft_count ?? 0);
        $kpiPublished = (int) ($kpi->published_count ?? 0);
        $kpiMidtrans = (int) ($kpi->midtrans_count ?? 0);
        $kpiManual = (int) ($kpi->manual_count ?? 0);

        $registrations = $query->orderBy('date_create', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $layananList = Schema::hasTable('m_bandwith_kategori')
            ? DB::table('m_bandwith_kategori')->pluck('nama_kategori_bandwith')->filter()->unique()->toArray()
            : ['BROADBAND', 'DEDICATED', 'SOHO', 'CORPORATE'];

        $wilayahList = Schema::hasTable('m_wilayah_perangkat')
            ? DB::table('m_wilayah_perangkat')->pluck('name_w')->filter()->unique()->toArray()
            : [];

        if (empty($wilayahList) && Schema::hasTable('m_wilayah')) {
            $wilayahList = DB::table('m_wilayah')->limit(50)->pluck('nama_kota')->filter()->unique()->toArray();
        }

        $statusBillRegList = Schema::hasTable('m_status_bill_reg')
            ? DB::table('m_status_bill_reg')
                ->where('hide', '0')
                ->whereNotIn('status_bill_reg', ['17', '18'])
                ->where('desc_bill_reg', 'NOT LIKE', '%cancel midtrans%')
                ->where('desc_bill_reg', 'NOT LIKE', '%expire midtrans%')
                ->get()
            : collect();

        return view('finance.billing-registrasi', [
            'user' => $request->user(),
            'registrations' => $registrations,
            'kpis' => [
                'total' => $kpiTotal,
                'draft' => $kpiDraft,
                'published' => $kpiPublished,
                'midtrans' => $kpiMidtrans,
                'manual' => $kpiManual,
            ],
            'layananList' => $layananList,
            'wilayahList' => $wilayahList,
            'statusBillRegList' => $statusBillRegList,
        ]);
    }

    /**
     * Publish Billing Registrasi
     */
    public function publishBillingRegistrasi(Request $request, ?string $kodeBilling = null): RedirectResponse
    {
        $user = Auth::user()?->nama ?? 'FINANCE';
        $decodedKode = $request->input('kode_billing') ? trim($request->input('kode_billing')) : urldecode($kodeBilling ?? '');

        try {
            $reg = DB::table('trx_billing_registrasi')->where('kode_billing_registrasi', $decodedKode)->first();
            if (!$reg) {
                return redirect()->back()->with('error', 'Tagihan registrasi tidak ditemukan.');
            }

            $updateData = [
                'status_bill_reg' => '12', // Publish
                'payment_publish' => Carbon::now()->toDateTimeString(),
                'date_update' => Carbon::now()->toDateTimeString(),
                'user_update' => $user,
            ];

            // If payment_type is Midtrans (1) and link not yet generated or empty, generate Snap link
            if ($reg->payment_type == 1 && empty($reg->payment_respond_post)) {
                $customer = DB::table('view_batchjob')->where('nomor_internet', $reg->nomor_internet)->first();
                $customerDetails = [
                    'nama' => $customer->nama_pelanggan ?? 'Pelanggan',
                    'email' => $customer->email ?? 'billing@ims-router.net',
                    'nomor_hp' => $customer->nomor_hp ?? '08123456789',
                ];
                $amount = (float) ($reg->total_reg ?? 0);
                $midtrans = app(MidtransService::class)->generateSnapLink($decodedKode, $amount, $customerDetails);
                $updateData['payment_post'] = $midtrans['payment_post'];
                $updateData['payment_respond_post'] = $midtrans['payment_respond_post'];
                $updateData['expiry'] = $midtrans['expiry'];
            }

            DB::table('trx_billing_registrasi')
                ->where('kode_billing_registrasi', $decodedKode)
                ->update($updateData);

            DB::table('trx_billing_registrasi_log')->insert([
                'kode_billing_regis_log' => 'LOG-' . uniqid(),
                'kode_billing_registrasi' => $decodedKode,
                'status_bill_reg' => '12',
                'note_billing_reg' => "Billing Registrasi published by {$user}" . ($reg->payment_type == 1 ? " (Midtrans Link Generated)" : ""),
                'date_create' => Carbon::now()->toDateTimeString(),
                'user_create' => $user,
                'hide' => '0',
            ]);

            return redirect()->back()->with('success', "Tagihan Registrasi {$decodedKode} berhasil di-publish.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal publish billing registrasi: ' . $e->getMessage());
        }
    }

    /**
     * Generate Link Midtrans on-demand untuk Billing Registrasi
     */
    public function generateMidtransRegistrasi(Request $request): RedirectResponse
    {
        $user = Auth::user()?->nama ?? 'FINANCE';
        $kodeBilling = trim($request->input('kode_billing', ''));

        $reg = DB::table('trx_billing_registrasi')->where('kode_billing_registrasi', $kodeBilling)->first();
        if (!$reg) {
            return redirect()->back()->with('error', 'Tagihan registrasi tidak ditemukan.');
        }

        try {
            $customer = DB::table('view_batchjob')->where('nomor_internet', $reg->nomor_internet)->first();
            $customerDetails = [
                'nama' => $customer->nama_pelanggan ?? 'Pelanggan',
                'email' => $customer->email ?? 'billing@ims-router.net',
                'nomor_hp' => $customer->nomor_hp ?? '08123456789',
            ];
            $amount = (float) ($reg->total_reg ?? 0);
            $midtrans = app(MidtransService::class)->generateSnapLink($kodeBilling, $amount, $customerDetails);

            DB::table('trx_billing_registrasi')
                ->where('kode_billing_registrasi', $kodeBilling)
                ->update([
                    'payment_type' => '1',
                    'payment_post' => $midtrans['payment_post'],
                    'payment_respond_post' => $midtrans['payment_respond_post'],
                    'expiry' => $midtrans['expiry'],
                    'date_update' => Carbon::now()->toDateTimeString(),
                    'user_update' => $user,
                ]);

            DB::table('trx_billing_registrasi_log')->insert([
                'kode_billing_regis_log' => 'LOG-' . uniqid(),
                'kode_billing_registrasi' => $kodeBilling,
                'status_bill_reg' => $reg->status_bill_reg,
                'note_billing_reg' => "Link Pembayaran Midtrans berhasil di-generate oleh {$user} (Berlaku s/d {$midtrans['expiry']})",
                'date_create' => Carbon::now()->toDateTimeString(),
                'user_create' => $user,
                'hide' => '0',
            ]);

            return redirect()->back()->with('success', "Link Pembayaran Midtrans untuk registrasi {$kodeBilling} berhasil dibuat!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal generate link Midtrans registrasi: ' . $e->getMessage());
        }
    }

    /**
     * Renew Expired Link Midtrans untuk Billing Registrasi
     */
    public function renewMidtransRegistrasi(Request $request): RedirectResponse
    {
        $user = Auth::user()?->nama ?? 'FINANCE';
        $kodeBilling = trim($request->input('kode_billing', ''));

        $reg = DB::table('trx_billing_registrasi')->where('kode_billing_registrasi', $kodeBilling)->first();
        if (!$reg) {
            return redirect()->back()->with('error', 'Tagihan registrasi tidak ditemukan.');
        }

        try {
            $customer = DB::table('view_batchjob')->where('nomor_internet', $reg->nomor_internet)->first();
            $customerDetails = [
                'nama' => $customer->nama_pelanggan ?? 'Pelanggan',
                'email' => $customer->email ?? 'billing@ims-router.net',
                'nomor_hp' => $customer->nomor_hp ?? '08123456789',
            ];
            $amount = (float) ($reg->total_reg ?? 0);

            $midtrans = app(MidtransService::class)->renewSnapLink($kodeBilling, $amount, $customerDetails);

            DB::table('trx_billing_registrasi')
                ->where('kode_billing_registrasi', $kodeBilling)
                ->update([
                    'payment_type' => '1',
                    'payment_post' => $midtrans['payment_post'],
                    'payment_respond_post' => $midtrans['payment_respond_post'],
                    'expiry' => $midtrans['expiry'],
                    'status_bill_reg' => '12', // Published
                    'date_update' => Carbon::now()->toDateTimeString(),
                    'user_update' => $user,
                ]);

            DB::table('trx_billing_registrasi_log')->insert([
                'kode_billing_regis_log' => 'LOG-' . uniqid(),
                'kode_billing_registrasi' => $kodeBilling,
                'status_bill_reg' => '12',
                'note_billing_reg' => "Link Midtrans Registrasi di-renew ke Order ID {$midtrans['order_id']} oleh {$user} (Berlaku s/d {$midtrans['expiry']})",
                'date_create' => Carbon::now()->toDateTimeString(),
                'user_create' => $user,
                'hide' => '0',
            ]);

            return redirect()->back()->with('success', "Link Midtrans untuk registrasi {$kodeBilling} berhasil di-renew! Berlaku hingga " . Carbon::parse($midtrans['expiry'])->translatedFormat('d M Y H:i'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal renew link Midtrans registrasi: ' . $e->getMessage());
        }
    }

    /**
     * Konfirmasi Pembayaran Manual Billing Registrasi
     */
    public function konfirmasiBayarRegistrasi(Request $request, ?string $kodeBilling = null): RedirectResponse
    {
        $request->validate([
            'nominal_bayar' => 'required|numeric|min:1',
            'metode_bayar' => 'required|string',
            'bank_tujuan' => 'nullable|string',
            'nama_kolektor' => 'nullable|string',
            'no_kwitansi' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        $user = Auth::user()?->nama ?? Auth::user()?->name ?? 'FINANCE';
        $userUpdate = mb_substr($user, 0, 50);
        $decodedKode = $request->input('kode_billing') ? trim($request->input('kode_billing')) : urldecode($kodeBilling ?? '');
        $nominal = $request->input('nominal_bayar');
        $metodeBayar = $request->input('metode_bayar');
        $namaKolektor = trim((string) $request->input('nama_kolektor', ''));
        $noKwitansi = trim((string) $request->input('no_kwitansi', ''));

        if ($metodeBayar === 'cash' || $metodeBayar === '3') {
            $paymentType = '3';
            $penerima = $request->input('bank_tujuan') ?: 'Cash To Collector';
            $bank = $namaKolektor !== '' ? "{$penerima} ({$namaKolektor})" : $penerima;
            $catatanDefault = "Pembayaran Registrasi Cash to Collector Terverifikasi" . ($noKwitansi !== '' ? " [Kwitansi: {$noKwitansi}]" : "");
        } else {
            $paymentType = '2';
            $bank = $request->input('bank_tujuan') ?: 'BCA';
            $catatanDefault = "Pembayaran Registrasi Transfer Bank Terverifikasi" . ($noKwitansi !== '' ? " [Ref: {$noKwitansi}]" : "");
        }

        $catatan = $request->input('catatan') ?: $catatanDefault;

        try {
            $reg = DB::table('trx_billing_registrasi')->where('kode_billing_registrasi', $decodedKode)->first();
            $nomorInternet = $reg?->nomor_internet;

            DB::table('trx_billing_registrasi')
                ->where('kode_billing_registrasi', $decodedKode)
                ->update([
                    'status_bill_reg' => '14', // Paid
                    'payment_paid' => Carbon::now()->toDateTimeString(),
                    'amount_paid' => (string) $nominal,
                    'merchant_type' => $bank,
                    'payment_type' => $paymentType,
                    'date_update' => Carbon::now()->toDateTimeString(),
                    'user_update' => $userUpdate,
                ]);

            DB::table('trx_billing_registrasi_log')->insert([
                'kode_billing_regis_log' => 'LOG-' . uniqid(),
                'kode_billing_registrasi' => $decodedKode,
                'status_bill_reg' => '14',
                'note_billing_reg' => "Registration payment verified ({$bank} - Rp " . number_format($nominal, 0, ',', '.') . "): {$catatan} by {$userUpdate}",
                'date_create' => Carbon::now()->toDateTimeString(),
                'user_create' => $userUpdate,
                'hide' => '0',
            ]);

            // Auto Req Unsuspend ke NOC jika pelanggan sedang dalam status Suspend/Terisolir
            $unsuspendInfo = '';
            if ($nomorInternet && Schema::hasTable('trx_suspend')) {
                $activeSuspend = DB::table('trx_suspend')
                    ->where('nomor_internet', $nomorInternet)
                    ->whereIn('status_suspend', ['11', '12'])
                    ->orderBy('date_create', 'desc')
                    ->first();

                if ($activeSuspend) {
                    DB::table('trx_suspend')
                        ->where('kode_suspend', $activeSuspend->kode_suspend)
                        ->update([
                            'status_suspend' => '18', // 18: Request Unsuspend ke NOC
                            'desc_suspend_cancel' => "Otomatis diajukan buka isolir: Pelanggan telah membayar lunas registrasi {$decodedKode} ({$bank})",
                            'date_update' => Carbon::now()->toDateTimeString(),
                            'user_update' => $userUpdate,
                        ]);
                    $unsuspendInfo = " serta Permintaan Buka Isolir (Req Unsuspend) otomatis dikirimkan ke tim NOC.";
                }
            }

            return redirect()->back()->with('success', "Pembayaran Registrasi {$decodedKode} sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil diverifikasi{$unsuspendInfo}");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal konfirmasi pembayaran registrasi: ' . $e->getMessage());
        }
    }

    /**
     * AJAX Endpoint: Get Detail Breakdown Billing Registrasi
     */
    public function getBillingRegistrasiDetail(Request $request, ?string $kodeBilling = null): JsonResponse
    {
        $decodedKode = $request->input('kode_billing') ?: ($request->input('kode') ?: urldecode($kodeBilling ?? ''));

        $reg = DB::table('view_billing_reg')
            ->where('kode_billing_registrasi', $decodedKode)
            ->first();

        if (!$reg) {
            return response()->json(['error' => 'Data registrasi tidak ditemukan'], 404);
        }

        $items = DB::table('trx_billing_registrasi_detail')
            ->where('kode_billing_registrasi', $decodedKode)
            ->get();

        $logs = DB::table('trx_billing_registrasi_log')
            ->where('kode_billing_registrasi', $decodedKode)
            ->orderBy('date_create', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'billing' => $reg,
            'items' => $items,
            'logs' => $logs,
        ]);
    }

    /**
     * Export Data Billing Registrasi to CSV
     */
    public function exportBillingRegistrasi(Request $request): StreamedResponse
    {
        $query = DB::table('view_billing_reg');

        if ($request->filled('layanan')) {
            $query->where('nama_kategori_bandwith', $request->input('layanan'));
        }
        if ($request->filled('status_bayar')) {
            $query->where('status_bill_reg', $request->input('status_bayar'));
        }
        if ($request->filled('wilayah')) {
            $query->where('alamat_p', 'LIKE', '%' . $request->input('wilayah') . '%');
        }
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('kode_billing_registrasi', 'LIKE', "%{$search}%")
                  ->orWhere('nomor_internet', 'LIKE', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'LIKE', "%{$search}%");
            });
        }

        $regs = $query->orderBy('date_create', 'desc')->get();
        $filename = 'rekap_billing_registrasi_' . date('Ymd_His') . '.csv';

        return response()->stream(function () use ($regs) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Kode Registrasi',
                'No Layanan / Internet',
                'Nama Pelanggan',
                'Kategori Layanan',
                'Bandwidth',
                'Biaya Pasang (Rp)',
                'Potongan (Rp)',
                'Total Tagihan (Rp)',
                'Jumlah Dibayar (Rp)',
                'Status Bayar',
                'Metode Pembayaran',
                'Tanggal Publish',
                'Tanggal Bayar',
                'Wilayah Pasang',
            ]);

            foreach ($regs as $r) {
                fputcsv($handle, [
                    $r->kode_billing_registrasi ?? '',
                    $r->nomor_internet ?? '',
                    $r->nama_pelanggan ?? '',
                    $r->nama_kategori_bandwith ?? '',
                    $r->nominal_bandwith ?? '',
                    $r->biaya_reg ?? 0,
                    $r->potongan ?? 0,
                    $r->total_reg ?? 0,
                    $r->amount_paid ?? 0,
                    $r->desc_bill_reg ?? '',
                    $r->desc_payment_type ?? ($r->payment_type == 1 ? 'Midtrans' : 'Manual Transfer'),
                    $r->payment_publish ?? $r->date_create ?? '',
                    $r->payment_paid ?? '',
                    $r->alamat_p ?? $r->alamat_pasang ?? '',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // =========================================================================
    // MODUL PERMINTAAN KE NOC (UP/DOWNGRADE, SUSPEND, TERMINASI)
    // =========================================================================

    /**
     * Permintaan: UP / Downgrade Bandwidth Layanan
     */
    public function upDowngrade(Request $request): View
    {
        $search = $request->query('search');
        $layanan = $request->query('layanan');
        $wilayah = $request->query('wilayah');
        $status = $request->query('status');

        $query = DB::table('view_ubah_layanan as u')
            ->leftJoin('view_batchjob as b', 'u.nomor_internet', '=', 'b.nomor_internet')
            ->select(
                'u.*',
                'b.alamat_p',
                'b.alamat_pasang',
                'b.jenis_bangunan',
                'b.jenis_kelamin',
                'b.status_reg as status_pelanggan'
            );

        if ($layanan) {
            $query->where(function($q) use ($layanan) {
                $q->where('u.nama_kategori_bandwith_baru', $layanan)
                  ->orWhere('u.nama_kategori_bandwith_lama', $layanan);
            });
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('u.kode_trx_ubah_layanan', 'like', "%{$search}%")
                  ->orWhere('u.nomor_internet', 'like', "%{$search}%")
                  ->orWhere('u.nama_pelanggan', 'like', "%{$search}%");
            });
        }

        if ($wilayah) {
            $query->where('b.alamat_p', 'like', "%{$wilayah}%");
        }

        if ($status) {
            $query->where('u.status_ubah_layanan', $status);
        }

        $ubahLayanans = $query->orderBy('u.date_create', 'desc')->paginate(10)->withQueryString();

        // HIGH PERFORMANCE: Single aggregated query for UP/Downgrade KPIs
        $counts = DB::table('trx_ubah_layanan')
            ->selectRaw("
                COUNT(CASE WHEN status_ubah_layanan = '11' THEN 1 END) as c11,
                COUNT(CASE WHEN status_ubah_layanan = '12' THEN 1 END) as c12,
                COUNT(CASE WHEN status_ubah_layanan = '13' THEN 1 END) as c13,
                COUNT(CASE WHEN status_ubah_layanan = '14' THEN 1 END) as c14
            ")
            ->first();

        $count11 = (int) ($counts->c11 ?? 0);
        $count12 = (int) ($counts->c12 ?? 0);
        $count13 = (int) ($counts->c13 ?? 0);
        $count14 = (int) ($counts->c14 ?? 0);

        $layananList = DB::table('m_bandwith_kategori')->pluck('nama_kategori_bandwith')->filter()->unique();

        // List Paket Bandwidth untuk modal create
        $paketList = DB::table('m_bandwith as b')
            ->join('m_bandwith_kategori as k', 'b.kode_kategori_bandwith', '=', 'k.kode_kategori_bandwith')
            ->select('b.kode_bandwith', 'b.nominal_bandwith', 'b.harga_bandwith', 'k.nama_kategori_bandwith')
            ->where('b.hide', '0')
            ->orderBy('k.nama_kategori_bandwith')
            ->orderBy('b.nominal_bandwith')
            ->get();

        return view('finance.permintaan.up-downgrade', [
            'user' => $request->user(),
            'ubahLayanans' => $ubahLayanans,
            'search' => $search,
            'layanan' => $layanan,
            'wilayah' => $wilayah,
            'status' => $status,
            'count11' => $count11,
            'count12' => $count12,
            'count13' => $count13,
            'count14' => $count14,
            'layananList' => $layananList,
            'paketList' => $paketList,
        ]);
    }

    /**
     * Store Request UP / Downgrade ke NOC
     */
    public function storeUpDowngrade(Request $request): RedirectResponse
    {
        $request->validate([
            'nomor_internet' => 'required|string',
            'kode_bandwith_baru' => 'required|string',
            'date_schedule' => 'required|date',
            'note_request' => 'nullable|string',
        ]);

        $customer = DB::table('view_batchjob')->where('nomor_internet', $request->nomor_internet)->first();
        if (!$customer) {
            return redirect()->back()->with('error', "Pelanggan dengan No. Layanan {$request->nomor_internet} tidak ditemukan.");
        }

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'Finance';
        $kodeTrx = 'UB-' . $request->nomor_internet . rand(1000, 9999);

        DB::table('trx_ubah_layanan')->insert([
            'kode_trx_ubah_layanan' => $kodeTrx,
            'nomor_internet' => $request->nomor_internet,
            'kode_bandwith_lama' => $customer->kode_bandwith ?? null,
            'kode_bandwith_baru' => $request->kode_bandwith_baru,
            'status_ubah_layanan' => '11', // 11: Request Baru ke NOC
            'date_request' => now()->format('Y-m-d'),
            'note_request' => $request->note_request ?? 'Pengajuan ubah kecepatan/paket oleh Finance',
            'date_schedule' => $request->date_schedule,
            'note_schedule' => $request->note_request ?? '-',
            'date_create' => $now,
            'user_create' => $currentUser,
            'hide' => '0',
        ]);

        return redirect()->back()->with('success', "Request Ubah Layanan {$kodeTrx} berhasil dibuat dan dikirim ke tim NOC!");
    }

    /**
     * Cancel Permintaan UP / Downgrade
     */
    public function cancelUpDowngrade(Request $request, string $kodeTrx): RedirectResponse
    {
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'Finance';

        DB::table('trx_ubah_layanan')->where('kode_trx_ubah_layanan', $kodeTrx)->update([
            'status_ubah_layanan' => '14', // Canceled
            'date_cancel' => now()->format('Y-m-d'),
            'note_cancel' => $request->note_cancel ?? 'Dibatalkan oleh Finance',
            'date_update' => $now,
            'user_update' => $currentUser,
        ]);

        return redirect()->back()->with('success', "Permintaan ubah layanan {$kodeTrx} berhasil dibatalkan!");
    }

    /**
     * Permintaan: Suspend Layanan (Pelanggan Menunggak / Jatuh Tempo)
     */
    public function suspend(Request $request): View
    {
        $search = $request->query('search');
        $layanan = $request->query('layanan');
        $wilayah = $request->query('wilayah');
        $status = $request->query('status');

        $query = DB::table('view_suspend');

        if ($layanan) {
            $query->where('nama_kategori_bandwith', $layanan);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('desc_suspend', 'like', "%{$search}%");
            });
        }

        if ($wilayah) {
            $query->where('alamat_p', 'like', "%{$wilayah}%");
        }

        if ($status) {
            $query->where('status_suspend', $status);
        }

        $suspends = $query->orderBy('date_create', 'desc')->paginate(10)->withQueryString();

        // HIGH PERFORMANCE: Single aggregated query for Suspend KPIs
        $counts = DB::table('trx_suspend')
            ->selectRaw("
                COUNT(CASE WHEN status_suspend = '11' THEN 1 END) as cRequest,
                COUNT(CASE WHEN status_suspend = '12' THEN 1 END) as cSuspend,
                COUNT(CASE WHEN status_suspend = '18' THEN 1 END) as cReqUnsuspend,
                COUNT(CASE WHEN status_suspend = '13' THEN 1 END) as cUnsuspend
            ")
            ->first();

        $countRequest = (int) ($counts->cRequest ?? 0);
        $countSuspend = (int) ($counts->cSuspend ?? 0);
        $countReqUnsuspend = (int) ($counts->cReqUnsuspend ?? 0);
        $countUnsuspend = (int) ($counts->cUnsuspend ?? 0);

        $layananList = DB::table('m_bandwith_kategori')->pluck('nama_kategori_bandwith')->filter()->unique();

        return view('finance.permintaan.suspend', [
            'user' => $request->user(),
            'suspends' => $suspends,
            'search' => $search,
            'layanan' => $layanan,
            'wilayah' => $wilayah,
            'status' => $status,
            'countRequest' => $countRequest,
            'countSuspend' => $countSuspend,
            'countReqUnsuspend' => $countReqUnsuspend,
            'countUnsuspend' => $countUnsuspend,
            'layananList' => $layananList,
        ]);
    }

    /**
     * Store Request Suspend ke NOC
     */
    public function storeSuspend(Request $request): RedirectResponse
    {
        $request->validate([
            'nomor_internet' => 'required|string',
            'suspend_start' => 'required|date',
            'desc_suspend' => 'required|string',
        ]);

        $customer = DB::table('view_batchjob')->where('nomor_internet', $request->nomor_internet)->first();
        if (!$customer) {
            return redirect()->back()->with('error', "Pelanggan dengan No. Layanan {$request->nomor_internet} tidak ditemukan.");
        }

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'Finance';
        $kodeSuspend = $request->nomor_internet . '-' . rand(1000000, 9999999);

        DB::table('trx_suspend')->insert([
            'kode_suspend' => $kodeSuspend,
            'nomor_internet' => $request->nomor_internet,
            'suspend_start' => $request->suspend_start,
            'status_suspend' => '11', // 11: Request Suspend Baru ke NOC
            'desc_suspend' => $request->desc_suspend,
            'date_create' => $now,
            'user_create' => $currentUser,
            'hide' => '0',
        ]);

        return redirect()->back()->with('success', "Request Suspend {$kodeSuspend} berhasil dibuat dan diteruskan ke tim NOC!");
    }

    /**
     * Trigger Manual Process Auto Request Suspend ke NOC untuk Pelanggan Belum Bayar (Jatuh Tempo Tgl 25)
     */
    public function autoSuspendUnpaid(Request $request): RedirectResponse
    {
        $currentUser = auth()->user()->nama ?? 'Finance';

        Artisan::call('suspend:unpaid-customers', [
            '--user' => $currentUser . ' (Manual Trigger)',
        ]);

        return redirect()->back()->with('success', "Proses Auto Request Suspend Jatuh Tempo Tanggal 25 berhasil dijalankan ke tim NOC!");
    }

    /**
     * Request Unsuspend (Pelanggan Telah Melunasi Tagihan)
     */
    public function requestUnsuspend(Request $request, string $kodeSuspend): RedirectResponse
    {
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'Finance';

        DB::table('trx_suspend')->where('kode_suspend', $kodeSuspend)->update([
            'status_suspend' => '18', // 18: Request Unsuspend ke NOC
            'date_update' => $now,
            'user_update' => $currentUser,
        ]);

        return redirect()->back()->with('success', "Pengajuan Buka Isolir (Req Unsuspend) untuk {$kodeSuspend} berhasil dikirim ke tim NOC!");
    }

    /**
     * Cancel Permintaan Suspend
     */
    public function cancelSuspend(Request $request, string $kodeSuspend): RedirectResponse
    {
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'Finance';

        DB::table('trx_suspend')->where('kode_suspend', $kodeSuspend)->update([
            'status_suspend' => '14', // 14: Cancel Suspend
            'desc_suspend_cancel' => $request->desc_cancel ?? 'Dibatalkan oleh Finance',
            'date_update' => $now,
            'user_update' => $currentUser,
        ]);

        return redirect()->back()->with('success', "Permintaan suspend {$kodeSuspend} berhasil dibatalkan!");
    }

    /**
     * Permintaan: Terminasi Layanan (Pelanggan Berhenti Berlangganan)
     */
    public function terminasi(Request $request): View
    {
        $search = $request->query('search');
        $layanan = $request->query('layanan');
        $wilayah = $request->query('wilayah');
        $status = $request->query('status');
        $bulan = $request->query('bulan');
        $tahun = $request->query('tahun');

        $query = DB::table('view_terminasi');

        if ($layanan) {
            $query->where('nama_kategori_bandwith', $layanan);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('kode_trx_terminasi', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%");
            });
        }

        if ($wilayah) {
            $query->where('alamat_p', 'like', "%{$wilayah}%");
        }

        if ($status) {
            $query->where('status_terminasi', $status);
        }

        if ($bulan) {
            $query->whereMonth('date_create', $bulan);
        }

        if ($tahun) {
            $query->whereYear('date_create', $tahun);
        }

        $terminasis = $query->orderBy('date_create', 'desc')->paginate(10)->withQueryString();

        // HIGH PERFORMANCE: Single aggregated query for Terminasi KPIs
        $counts = DB::table('trx_terminasi')
            ->selectRaw("
                COUNT(CASE WHEN status_terminasi = '11' THEN 1 END) as c11,
                COUNT(CASE WHEN status_terminasi IN ('12', '12.1') THEN 1 END) as c12,
                COUNT(CASE WHEN status_terminasi = '13' THEN 1 END) as c13,
                COUNT(CASE WHEN status_terminasi = '16' THEN 1 END) as c16
            ")
            ->first();

        $count11 = (int) ($counts->c11 ?? 0);
        $count12 = (int) ($counts->c12 ?? 0);
        $count13 = (int) ($counts->c13 ?? 0);
        $count16 = (int) ($counts->c16 ?? 0);

        $layananList = DB::table('m_bandwith_kategori')->pluck('nama_kategori_bandwith')->filter()->unique();

        return view('finance.permintaan.terminasi', [
            'user' => $request->user(),
            'terminasis' => $terminasis,
            'search' => $search,
            'layanan' => $layanan,
            'wilayah' => $wilayah,
            'status' => $status,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'count11' => $count11,
            'count12' => $count12,
            'count13' => $count13,
            'count16' => $count16,
            'layananList' => $layananList,
        ]);
    }

    /**
     * Store Request Terminasi ke NOC
     */
    public function storeTerminasi(Request $request): RedirectResponse
    {
        $request->validate([
            'nomor_internet' => 'required|string',
            'note_termin' => 'required|string',
        ]);

        $customer = DB::table('view_batchjob')->where('nomor_internet', $request->nomor_internet)->first();
        if (!$customer) {
            return redirect()->back()->with('error', "Pelanggan dengan No. Layanan {$request->nomor_internet} tidak ditemukan.");
        }

        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'Finance';
        $kodeTrx = 'TR-' . $request->nomor_internet . rand(1000, 9999);

        DB::table('trx_terminasi')->insert([
            'kode_trx_terminasi' => $kodeTrx,
            'nomor_internet' => $request->nomor_internet,
            'note_termin' => $request->note_termin,
            'status_terminasi' => '11', // 11: Request Baru ke NOC
            'collect_perangkat' => '0',
            'collect_payment' => '0',
            'date_create' => $now,
            'user_create' => $currentUser,
            'date_update' => $now,
            'user_update' => $currentUser,
            'hide' => '0',
        ]);

        return redirect()->back()->with('success', "Request Terminasi {$kodeTrx} berhasil dibuat dan dikirim ke tim NOC!");
    }

    /**
     * Cancel Permintaan Terminasi
     */
    public function cancelTerminasi(Request $request, string $kodeTrx): RedirectResponse
    {
        $now = now()->format('Y-m-d H:i:s');
        $currentUser = auth()->user()->nama ?? 'Finance';

        DB::table('trx_terminasi')->where('kode_trx_terminasi', $kodeTrx)->update([
            'status_terminasi' => '16', // Cancel Terminasi
            'note_termin_cancel' => $request->note_cancel ?? 'Dibatalkan oleh Finance',
            'date_update' => $now,
            'user_update' => $currentUser,
        ]);

        return redirect()->back()->with('success', "Permintaan terminasi {$kodeTrx} berhasil dibatalkan!");
    }

    /**
     * Memastikan struktur tabel m_bandwith dan m_bandwith_kategori tersedia di database
     */
    protected function ensurePaketTableColumns(): void
    {
        try {
            if (!Schema::hasTable('m_bandwith_kategori')) {
                Schema::create('m_bandwith_kategori', function (Blueprint $table) {
                    $table->string('kode_kategori_bandwith', 50)->primary();
                    $table->string('nama_kategori_bandwith', 100);
                    $table->string('alias_nama_kategori', 100)->nullable();
                    $table->decimal('biaya_reg', 15, 2)->default(0);
                    $table->tinyInteger('disable')->default(0);
                    $table->timestamps();
                });

                // Seed Kategori Awal
                DB::table('m_bandwith_kategori')->insert([
                    ['kode_kategori_bandwith' => 'KB01', 'nama_kategori_bandwith' => 'CORPORATE', 'alias_nama_kategori' => 'Corporate Dedicated', 'biaya_reg' => 500000, 'disable' => 0],
                    ['kode_kategori_bandwith' => 'KB02', 'nama_kategori_bandwith' => 'LAST MILE', 'alias_nama_kategori' => 'Last Mile FTTH', 'biaya_reg' => 250000, 'disable' => 0],
                    ['kode_kategori_bandwith' => 'KB03', 'nama_kategori_bandwith' => 'BROADBAND', 'alias_nama_kategori' => 'Broadband Internet', 'biaya_reg' => 150000, 'disable' => 0],
                    ['kode_kategori_bandwith' => 'KB04', 'nama_kategori_bandwith' => 'HOME', 'alias_nama_kategori' => 'Home Fiber', 'biaya_reg' => 100000, 'disable' => 0],
                    ['kode_kategori_bandwith' => 'KB05', 'nama_kategori_bandwith' => 'DEDICATED', 'alias_nama_kategori' => '1:1 Symmetrical Dedicated', 'biaya_reg' => 1000000, 'disable' => 0],
                    ['kode_kategori_bandwith' => 'KB06', 'nama_kategori_bandwith' => 'BUSINESS', 'alias_nama_kategori' => 'SOHO & Business', 'biaya_reg' => 300000, 'disable' => 0],
                    ['kode_kategori_bandwith' => 'KB07', 'nama_kategori_bandwith' => 'CUSTOM', 'alias_nama_kategori' => 'Custom SLA Bandwidth', 'biaya_reg' => 500000, 'disable' => 0],
                    ['kode_kategori_bandwith' => 'KB08', 'nama_kategori_bandwith' => 'EVENT', 'alias_nama_kategori' => 'Temporary / Event Bandwidth', 'biaya_reg' => 750000, 'disable' => 0],
                ]);
            }

            if (!Schema::hasTable('m_bandwith')) {
                Schema::create('m_bandwith', function (Blueprint $table) {
                    $table->string('kode_bandwith', 50)->primary();
                    $table->string('nama_bandwith', 150)->nullable();
                    $table->string('kode_kategori_bandwith', 50)->nullable();
                    $table->integer('nominal_bandwith')->default(0);
                    $table->decimal('harga_bandwith', 15, 2)->default(0);
                    $table->string('peruntukan_bangunan', 255)->nullable();
                    $table->string('kategori_bangunan', 100)->nullable();
                    $table->tinyInteger('disable')->default(0);
                    $table->char('hide', 1)->default('0');
                    $table->dateTime('date_create')->nullable();
                    $table->string('user_create', 100)->nullable();
                    $table->dateTime('date_update')->nullable();
                    $table->string('user_update', 100)->nullable();
                    $table->timestamps();
                });
            } else {
                Schema::table('m_bandwith', function (Blueprint $table) {
                    if (!Schema::hasColumn('m_bandwith', 'nama_bandwith')) {
                        $table->string('nama_bandwith', 150)->nullable();
                    }
                    if (!Schema::hasColumn('m_bandwith', 'peruntukan_bangunan')) {
                        $table->string('peruntukan_bangunan', 255)->nullable();
                    }
                    if (!Schema::hasColumn('m_bandwith', 'kategori_bangunan')) {
                        $table->string('kategori_bangunan', 100)->nullable();
                    }
                    if (!Schema::hasColumn('m_bandwith', 'disable')) {
                        $table->tinyInteger('disable')->default(0);
                    }
                    if (!Schema::hasColumn('m_bandwith', 'hide')) {
                        $table->char('hide', 1)->default('0');
                    }
                });
            }

            // Auto-populate peruntukan_bangunan for existing packages if empty
            if (Schema::hasTable('m_bandwith')) {
                $emptyPackages = DB::table('m_bandwith')
                    ->where(function($q) {
                        $q->whereNull('peruntukan_bangunan')
                          ->orWhere('peruntukan_bangunan', '')
                          ->orWhere('peruntukan_bangunan', 'RUMAH-KANTOR'); // re-evaluate default fallback
                    })
                    ->get();

                if ($emptyPackages->isNotEmpty()) {
                    // Check actual building usage in customer registers
                    $regBuildings = [];
                    if (Schema::hasTable('trx_batchjob_register') && Schema::hasColumn('trx_batchjob_register', 'jenis_bangunan')) {
                        $usage = DB::table('trx_batchjob_register')
                            ->select('kode_bandwith', 'jenis_bangunan')
                            ->whereNotNull('jenis_bangunan')
                            ->where('jenis_bangunan', '!=', '')
                            ->distinct()
                            ->get();

                        foreach ($usage as $u) {
                            $regBuildings[$u->kode_bandwith][] = strtoupper(trim($u->jenis_bangunan));
                        }
                    }

                    foreach ($emptyPackages as $pkg) {
                        $assigned = [];
                        if (!empty($regBuildings[$pkg->kode_bandwith])) {
                            $assigned = array_values(array_unique($regBuildings[$pkg->kode_bandwith]));
                        } else {
                            // Smart assignment based on category and nominal speed
                            $kat = strtoupper($pkg->kode_kategori_bandwith ?? '');
                            $speed = (int) ($pkg->nominal_bandwith ?? 0);

                            if (str_contains($kat, 'KB02') || str_contains($kat, 'LAST') || $speed >= 1000) {
                                $assigned = ['RUMAH-KANTOR', 'GEDUNG', 'OUTDOOR/EVENT'];
                            } elseif (str_contains($kat, 'KB01') || str_contains($kat, 'CORP') || str_contains($kat, 'DEDICATED')) {
                                if ($speed >= 100) {
                                    $assigned = ['RUMAH-KANTOR', 'GEDUNG'];
                                } elseif ($speed >= 50) {
                                    $assigned = ['RUMAH-KANTOR', 'RUKO'];
                                } else {
                                    $assigned = ['RUMAH-KANTOR'];
                                }
                            } elseif (str_contains($kat, 'KB04') || str_contains($kat, 'HOME')) {
                                $assigned = ['RUMAH-PRIBADI', 'KOS-KOSAN'];
                            } elseif (str_contains($kat, 'KB03') || str_contains($kat, 'BROAD')) {
                                if ($speed <= 30) {
                                    $assigned = ['RUMAH-PRIBADI', 'KOS-KOSAN'];
                                } else {
                                    $assigned = ['RUMAH-PRIBADI', 'APARTEMEN'];
                                }
                            } elseif (str_contains($kat, 'KB06') || str_contains($kat, 'BUSI')) {
                                $assigned = ['RUMAH-KANTOR', 'RUKO', 'GEDUNG'];
                            } elseif (str_contains($kat, 'KB08') || str_contains($kat, 'EVENT')) {
                                $assigned = ['OUTDOOR/EVENT', 'GEDUNG'];
                            } else {
                                if ($speed >= 100) {
                                    $assigned = ['RUMAH-KANTOR', 'GEDUNG'];
                                } elseif ($speed >= 50) {
                                    $assigned = ['RUMAH-PRIBADI', 'RUMAH-KANTOR'];
                                } else {
                                    $assigned = ['RUMAH-PRIBADI', 'KOS-KOSAN'];
                                }
                            }
                        }

                        $cleanName = $pkg->nama_bandwith ?: ("Paket " . ($pkg->nominal_bandwith ?: 50) . " Mbps");

                        DB::table('m_bandwith')
                            ->where('kode_bandwith', $pkg->kode_bandwith)
                            ->update([
                                'peruntukan_bangunan' => implode(',', $assigned),
                                'kategori_bangunan' => $assigned[0] ?? 'RUMAH-KANTOR',
                                'nama_bandwith' => $cleanName,
                            ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Auto-ensure m_bandwith columns: " . $e->getMessage());
        }
    }

    /**
     * Master Data Paket Internet & Layanan Bandwidth (Finance)
     */
    public function paket(Request $request): View
    {
        $this->ensurePaketTableColumns();

        $search = $request->query('search');
        $selectedBangunan = $request->query('bangunan', 'all');
        $selectedKategori = $request->query('kategori', 'all');

        $query = DB::table('m_bandwith as b')
            ->leftJoin('m_bandwith_kategori as k', 'b.kode_kategori_bandwith', '=', 'k.kode_kategori_bandwith')
            ->select(
                'b.*',
                'k.nama_kategori_bandwith',
                'k.alias_nama_kategori'
            )
            ->where('b.hide', '0');

        if ($search) {
            $hasNamaBandwith = Schema::hasColumn('m_bandwith', 'nama_bandwith');
            $hasPeruntukan = Schema::hasColumn('m_bandwith', 'peruntukan_bangunan');
            $query->where(function($q) use ($search, $hasNamaBandwith, $hasPeruntukan) {
                $q->where('b.kode_bandwith', 'like', "%{$search}%");
                if ($hasNamaBandwith) {
                    $q->orWhere('b.nama_bandwith', 'like', "%{$search}%");
                }
                $q->orWhere('k.nama_kategori_bandwith', 'like', "%{$search}%");
                if ($hasPeruntukan) {
                    $q->orWhere('b.peruntukan_bangunan', 'like', "%{$search}%");
                }
                $q->orWhere('b.nominal_bandwith', 'like', "%{$search}%");
            });
        }

        if ($selectedBangunan !== 'all' && !empty($selectedBangunan)) {
            $query->where(function($q) use ($selectedBangunan) {
                $q->where('b.peruntukan_bangunan', 'like', "%{$selectedBangunan}%")
                  ->orWhere('b.kategori_bangunan', 'like', "%{$selectedBangunan}%");
            });
        }

        if ($selectedKategori !== 'all' && !empty($selectedKategori)) {
            $query->where(function($q) use ($selectedKategori) {
                $q->where('b.kode_kategori_bandwith', $selectedKategori)
                  ->orWhere('k.nama_kategori_bandwith', $selectedKategori);
            });
        }

        $pakets = $query->orderBy('b.nominal_bandwith', 'asc')
            ->orderBy('b.kode_bandwith', 'asc')
            ->paginate(15)
            ->withQueryString();

        // Attach default name_bandwith jika masih kosong & cast data types
        foreach ($pakets as $p) {
            $p->harga_bandwith = (float) ($p->harga_bandwith ?? 0);
            $p->nominal_bandwith = (int) ($p->nominal_bandwith ?? 0);
            if (empty($p->nama_bandwith)) {
                $p->nama_bandwith = "Paket {$p->nominal_bandwith} Mbps";
            }
        }

        // List Kategori untuk dropdown
        $kategoriList = DB::table('m_bandwith_kategori')->where('disable', 0)->get();

        // 1. KPI Counters
        $totalPaket = DB::table('m_bandwith')->where('hide', '0')->count();
        $totalAktif = DB::table('m_bandwith')->where('hide', '0')->where('disable', 0)->count();
        $totalKategori = DB::table('m_bandwith_kategori')->where('disable', 0)->count();
        $minSpeed = DB::table('m_bandwith')->where('hide', '0')->min('nominal_bandwith') ?: 1;
        $maxSpeed = DB::table('m_bandwith')->where('hide', '0')->max('nominal_bandwith') ?: 1000;

        // 2. Count per Building Types
        $buildingTypes = [
            'KOS-KOSAN' => 'KOS-KOSAN',
            'RUMAH-PRIBADI' => 'RUMAH-PRIBADI',
            'RUMAH-KANTOR' => 'RUMAH-KANTOR',
            'RUKO' => 'RUKO',
            'APARTEMEN' => 'APARTEMEN',
            'GEDUNG' => 'GEDUNG',
            'OUTDOOR/EVENT' => 'OUTDOOR/EVENT',
        ];

        // Merge extra types from database if exists
        if (Schema::hasTable('m_jns_bangunan')) {
            $dbBangunan = DB::table('m_jns_bangunan')->where('hide', '0')->pluck('jenis_bangunan')->filter();
            foreach ($dbBangunan as $b) {
                $bUpper = strtoupper(trim($b));
                if (!empty($bUpper) && !isset($buildingTypes[$bUpper])) {
                    $buildingTypes[$bUpper] = $bUpper;
                }
            }
        }

        $buildingCounts = [];
        foreach ($buildingTypes as $key => $label) {
            $buildingCounts[$key] = DB::table('m_bandwith')
                ->where('hide', '0')
                ->where(function($q) use ($key) {
                    $q->where('peruntukan_bangunan', 'like', "%{$key}%")
                      ->orWhere('kategori_bangunan', 'like', "%{$key}%");
                })
                ->count();
        }

        return view('finance.paket', [
            'user' => $request->user(),
            'pakets' => $pakets,
            'kategoriList' => $kategoriList,
            'totalPaket' => $totalPaket,
            'totalAktif' => $totalAktif,
            'totalKategori' => $totalKategori,
            'minSpeed' => $minSpeed,
            'maxSpeed' => $maxSpeed,
            'buildingTypes' => $buildingTypes,
            'buildingCounts' => $buildingCounts,
            'selectedBangunan' => $selectedBangunan,
            'selectedKategori' => $selectedKategori,
            'search' => $search,
        ]);
    }

    /**
     * Store / Update Master Paket Bandwidth
     * Otomatis mengupdate data tarif pelanggan aktif dan tagihan billing berjalan
     */
    public function storePaket(Request $request): RedirectResponse
    {
        $this->ensurePaketTableColumns();

        $request->validate([
            'kode_bandwith' => 'required|string|max:50',
            'nama_bandwith' => 'required|string|max:150',
            'kode_kategori_bandwith' => 'required|string|max:50',
            'nominal_bandwith' => 'required|numeric|min:1',
            'harga_bandwith' => 'required|numeric|min:0',
            'peruntukan_bangunan' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $now = Carbon::now()->toDateTimeString();
        $currentUser = substr(Auth::user()?->username ?? (Auth::user()?->nama ?? 'FINANCE'), 0, 20);
        $kodeBandwith = trim($request->kode_bandwith);

        $peruntukanString = 'RUMAH-KANTOR';
        if ($request->has('peruntukan_bangunan') && is_array($request->peruntukan_bangunan)) {
            $peruntukanString = implode(', ', $request->peruntukan_bangunan);
        } elseif ($request->filled('peruntukan_bangunan_text')) {
            $peruntukanString = $request->peruntukan_bangunan_text;
        }

        $disable = $request->has('is_active') && $request->is_active ? 0 : 0;
        if ($request->has('status_aktif')) {
            $disable = $request->status_aktif == '1' ? 0 : 1;
        }

        $newHarga = (float) $request->harga_bandwith;
        $newSpeed = (int) $request->nominal_bandwith;

        $existing = DB::table('m_bandwith')->where('kode_bandwith', $kodeBandwith)->first();

        DB::beginTransaction();
        try {
            if ($existing) {
                // UPDATE Paket
                DB::table('m_bandwith')->where('kode_bandwith', $kodeBandwith)->update([
                    'nama_bandwith' => $request->nama_bandwith,
                    'kode_kategori_bandwith' => $request->kode_kategori_bandwith,
                    'nominal_bandwith' => $newSpeed,
                    'harga_bandwith' => $newHarga,
                    'peruntukan_bangunan' => $peruntukanString,
                    'kategori_bangunan' => is_array($request->peruntukan_bangunan) ? ($request->peruntukan_bangunan[0] ?? 'RUMAH-KANTOR') : $peruntukanString,
                    'disable' => $disable,
                    'hide' => '0',
                    'date_update' => $now,
                    'user_update' => $currentUser,
                ]);
            } else {
                // INSERT Paket Baru
                DB::table('m_bandwith')->insert([
                    'kode_bandwith' => $kodeBandwith,
                    'nama_bandwith' => $request->nama_bandwith,
                    'kode_kategori_bandwith' => $request->kode_kategori_bandwith,
                    'nominal_bandwith' => $newSpeed,
                    'harga_bandwith' => $newHarga,
                    'peruntukan_bangunan' => $peruntukanString,
                    'kategori_bangunan' => is_array($request->peruntukan_bangunan) ? ($request->peruntukan_bangunan[0] ?? 'RUMAH-KANTOR') : $peruntukanString,
                    'disable' => $disable,
                    'hide' => '0',
                    'date_create' => $now,
                    'user_create' => $currentUser,
                    'date_update' => $now,
                    'user_update' => $currentUser,
                ]);
            }

            // ===================================================================
            // CASCADING AUTO-UPDATE: Update Pelanggan & Billing Terkait
            // ===================================================================
            $updatedCustomerCount = 0;
            $updatedBillingCount = 0;

            // 1. Update data master tarif di trx_batchjob_register (Pelanggan)
            if (Schema::hasTable('trx_batchjob_register')) {
                $custUpdate = [
                    'user_update' => $currentUser,
                    'date_update' => $now,
                ];
                if (Schema::hasColumn('trx_batchjob_register', 'harga_bandwith')) {
                    $custUpdate['harga_bandwith'] = (string) $newHarga;
                }
                if (Schema::hasColumn('trx_batchjob_register', 'nominal_bandwith')) {
                    $custUpdate['nominal_bandwith'] = (string) $newSpeed;
                }
                $updatedCustomerCount = DB::table('trx_batchjob_register')
                    ->where('kode_bandwith', $kodeBandwith)
                    ->update($custUpdate);
            }

            // 2. Update tagihan billing berjalan yang belum lunas (status 11, 12, 13, 14)
            if (Schema::hasTable('trx_billing_layanan')) {
                $unpaidBillings = DB::table('trx_billing_layanan')
                    ->where('kode_bandwith', $kodeBandwith)
                    ->whereIn('status_bill_lay', ['11', '12', '13', '14']) // Draft, Published Unpaid, Overdue, Partial
                    ->get();

                foreach ($unpaidBillings as $bill) {
                    $potongan = (float) ($bill->potongan ?? 0);
                    $totalLayanan = max(0, $newHarga - $potongan);

                    DB::table('trx_billing_layanan')
                        ->where('kode_billing_layanan', $bill->kode_billing_layanan)
                        ->update([
                            'nominal_bandwith' => (string) $newSpeed,
                            'total_layanan' => (string) $totalLayanan,
                            'user_update' => $currentUser,
                            'date_update' => $now,
                        ]);

                    // Update detail invoice item T11
                    if (Schema::hasTable('trx_billing_layanan_detail')) {
                        DB::table('trx_billing_layanan_detail')
                            ->where('kode_billing_layanan', $bill->kode_billing_layanan)
                            ->where('kode_item', 'T11')
                            ->update([
                                'biaya' => (string) $newHarga,
                                'user_update' => $currentUser,
                            ]);
                    }

                    // Log audit trail
                    if (Schema::hasTable('trx_billing_layanan_log')) {
                        DB::table('trx_billing_layanan_log')->insert([
                            'kode_billing_lay_log' => "LOG-TARIFF-" . substr(md5($bill->kode_billing_layanan . microtime()), 0, 16),
                            'kode_billing_layanan' => $bill->kode_billing_layanan,
                            'status_bill_lay' => $bill->status_bill_lay,
                            'note_billing_lay' => "Tarif paket diubah otomatis dari Master Paket menjadi Rp " . number_format($newHarga, 0, ',', '.') . " oleh {$currentUser}",
                            'date_create' => $now,
                            'user_create' => $currentUser,
                            'hide' => '0',
                        ]);
                    }

                    $updatedBillingCount++;
                }
            }

            DB::commit();

            $msg = "Paket {$request->nama_bandwith} ({$kodeBandwith}) berhasil disimpan!";
            if ($updatedCustomerCount > 0 || $updatedBillingCount > 0) {
                $msg .= " Otomatis memperbarui {$updatedCustomerCount} data pelanggan aktif dan {$updatedBillingCount} invoice tagihan berjalan.";
            }

            return redirect()->route('finance.paket')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', "Gagal menyimpan paket: " . $e->getMessage());
        }
    }

    /**
     * Hapus Master Paket Bandwidth
     */
    public function deletePaket(Request $request, string $kode_bandwith): RedirectResponse
    {
        // Cek jika ada pelanggan aktif yang masih berlangganan paket ini
        $activeCustomerCount = 0;
        if (Schema::hasTable('trx_batchjob_register')) {
            $activeCustomerCount = DB::table('trx_batchjob_register')
                ->where('kode_bandwith', $kode_bandwith)
                ->whereNotIn('status_reg', ['23', '23.1', '15']) // Bukan terminasi
                ->count();
        }

        if ($activeCustomerCount > 0) {
            return redirect()->route('finance.paket')->with('error', "Gagal menghapus paket {$kode_bandwith}. Terdapat {$activeCustomerCount} pelanggan aktif yang masih menggunakan paket ini.");
        }

        DB::table('m_bandwith')->where('kode_bandwith', $kode_bandwith)->delete();

        return redirect()->route('finance.paket')->with('success', "Paket {$kode_bandwith} berhasil dihapus.");
    }

    /**
     * API Search Pelanggan Aktif (Autocomplete untuk Modal Permintaan)
     */
    public function apiPelangganSearch(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $customers = DB::table('view_batchjob')
            ->select('nomor_internet', 'nama_pelanggan', 'nama_kategori_bandwith', 'nominal_bandwith', 'harga_bandwith', 'kode_bandwith', 'alamat_p', 'status_reg', 'desc_registrasi')
            ->where(function($query) use ($q) {
                $query->where('nomor_internet', 'like', "%{$q}%")
                      ->orWhere('nama_pelanggan', 'like', "%{$q}%")
                      ->orWhere('alamat_p', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get();

        return response()->json($customers);
    }

    /**
     * Tampilan Dokumen Resmi Form Invoice / Tagihan Pelanggan (PDF, Word, Cetak)
     */
    public function dokumenInvoice(Request $request, string $kodeBilling): View
    {
        $decodedKode = urldecode($kodeBilling);

        // 1. Ambil invoice dari view_billing_layanan atau trx_billing_layanan
        $invoice = null;
        if (Schema::hasTable('view_billing_layanan')) {
            $invoice = DB::table('view_billing_layanan')
                ->where('kode_billing_layanan', $decodedKode)
                ->orWhere('kode_billing_layanan', str_replace('-', '/', $decodedKode))
                ->orWhere('kode_billing_layanan', str_replace('/', '-', $decodedKode))
                ->orWhere('invoice_file', $decodedKode)
                ->orWhere('nomor_internet', $decodedKode)
                ->first();
        }

        if (!$invoice && Schema::hasTable('trx_billing_layanan')) {
            $invoice = DB::table('trx_billing_layanan')
                ->where('kode_billing_layanan', $decodedKode)
                ->orWhere('kode_billing_layanan', str_replace('-', '/', $decodedKode))
                ->orWhere('kode_billing_layanan', str_replace('/', '-', $decodedKode))
                ->orWhere('invoice_file', $decodedKode)
                ->orWhere('nomor_internet', $decodedKode)
                ->first();
        }

        if (!$invoice) {
            abort(404, "Dokumen Invoice [{$decodedKode}] tidak ditemukan.");
        }

        // Ambil data pelanggan lengkap (alamat, nama, no internet)
        $customer = null;
        if (Schema::hasTable('view_batchjob')) {
            $customer = DB::table('view_batchjob')
                ->where('nomor_internet', $invoice->nomor_internet)
                ->first();
        }
        if (!$customer && Schema::hasTable('trx_batchjob_register')) {
            $katTable = Schema::hasTable('m_bandwith_kategori') ? 'm_bandwith_kategori' : 'm_kategori_bandwith';
            $custQuery = DB::table('trx_batchjob_register');
            if (Schema::hasTable('m_pelanggan')) {
                $custQuery->leftJoin('m_pelanggan', 'trx_batchjob_register.nik_penduduk', '=', 'm_pelanggan.nik_penduduk');
            }
            if (Schema::hasTable('m_bandwith')) {
                $custQuery->leftJoin('m_bandwith', 'trx_batchjob_register.kode_bandwith', '=', 'm_bandwith.kode_bandwith');
                if (Schema::hasTable($katTable)) {
                    $custQuery->leftJoin($katTable, 'm_bandwith.kode_kategori_bandwith', '=', "{$katTable}.kode_kategori_bandwith");
                }
            }
            $customer = $custQuery->where('trx_batchjob_register.nomor_internet', $invoice->nomor_internet)
                ->select(
                    'trx_batchjob_register.*',
                    'm_pelanggan.nama_penduduk',
                    'm_pelanggan.alamat_ktp',
                    'm_pelanggan.rt_ktp',
                    'm_pelanggan.rw_ktp',
                    'm_bandwith.nominal_bandwith',
                    'm_bandwith.harga_bandwith',
                    "{$katTable}.nama_kategori_bandwith"
                )
                ->first();
        }

        // Ambil rincian item jika ada
        $items = collect();
        if (Schema::hasTable('trx_billing_layanan_detail')) {
            $items = DB::table('trx_billing_layanan_detail')
                ->where('kode_billing_layanan', $invoice->kode_billing_layanan)
                ->get();
        }

        // Format data
        $customerName = $invoice->nama_pelanggan ?? ($customer->nama_pelanggan ?? ($customer->nama_penduduk ?? 'Pelanggan'));
        $nomorInternet = $invoice->nomor_internet ?? ($customer->nomor_internet ?? '-');
        $noInvoice = $invoice->kode_billing_layanan ?? $decodedKode;
        
        // Alamat lengkap
        $alamat = $invoice->alamat_pasang ?? ($customer->alamat_pasang ?? ($customer->alamat_p ?? ($customer->alamat_ktp ?? '-')));
        if (!empty($customer->rt_pasang) || !empty($customer->rw_pasang)) {
            $alamat .= " RT." . ($customer->rt_pasang ?? '00') . " / RW." . ($customer->rw_pasang ?? '00');
        }
        if (!empty($customer->nama_kel_pasang)) {
            $alamat .= ", Kel. " . $customer->nama_kel_pasang;
        }
        if (!empty($customer->nama_kec_pasang)) {
            $alamat .= ", Kec. " . $customer->nama_kec_pasang;
        }
        if (!empty($customer->nama_kota_pasang)) {
            $alamat .= ", " . $customer->nama_kota_pasang;
        }

        // Periode & Jatuh tempo
        $bulanTagihan = $invoice->bulan_tagihan ?? date('m');
        $tahunTagihan = $invoice->tahun_tagihan ?? date('Y');
        
        $monthName = date('M', mktime(0, 0, 0, (int)$bulanTagihan, 1, (int)$tahunTagihan));
        $periodeTagihan = $invoice->periode_tagihan ?: ($monthName . ' ' . $tahunTagihan);
        
        $jatuhTempo = !empty($invoice->expiry) 
            ? Carbon::parse($invoice->expiry)->locale('id')->isoFormat('D MMMM Y')
            : 'Tanggal 20 Setiap Bulan';

        // Layanan & Nominal
        $kategoriBandwith = $invoice->nama_kategori_bandwith ?? ($customer->nama_kategori_bandwith ?? 'BROADBAND');
        $nominalBandwith = $invoice->nominal_bandwith ?? ($customer->nominal_bandwith ?? '');
        $namaLayanan = "LAYANAN INTERNET {$kategoriBandwith}" . ($nominalBandwith ? " {$nominalBandwith} MBps" : '');

        $subtotal = (float) ($invoice->harga_bandwith ?? ($customer->harga_bandwith ?? ($invoice->total_layanan ?? 0)));
        $potongan = (float) ($invoice->potongan ?? 0);
        $ppn = (float) ($invoice->ppn ?? 0);
        $total = (float) ($invoice->total_layanan ?? max(0, $subtotal - $potongan + $ppn));

        $terbilangText = $this->terbilangRupiah($total);

        // Payment Link (Midtrans redirect / QR)
        $paymentUrl = null;
        if (!empty($invoice->payment_respond_post)) {
            $resp = json_decode($invoice->payment_respond_post, true);
            $paymentUrl = $resp['redirect_url'] ?? null;
        }
        if (!$paymentUrl) {
            $paymentUrl = route('finance.billing-layanan', ['search' => $noInvoice]);
        }

        // Encode Base64 Kop and Stamp for seamless offline/PDF/doc export
        $headerPath = public_path('assets/images/invoice_kop_header.png');
        if (!file_exists($headerPath)) {
            $headerPath = public_path('assets/images/kop_header.png');
        }
        $footerPath = public_path('assets/images/invoice_kop_footer.png');
        if (!file_exists($footerPath)) {
            $footerPath = public_path('assets/images/kop_footer.png');
        }
        $stampPath = public_path('assets/images/invoice_stempel_keuangan.png');

        $headerBase64 = file_exists($headerPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($headerPath)) : asset('assets/images/kop_header.png');
        $footerBase64 = file_exists($footerPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($footerPath)) : asset('assets/images/kop_footer.png');
        $stampBase64 = file_exists($stampPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($stampPath)) : null;

        return view('finance.dokumen.invoice', compact(
            'invoice',
            'customer',
            'items',
            'customerName',
            'nomorInternet',
            'noInvoice',
            'alamat',
            'periodeTagihan',
            'jatuhTempo',
            'namaLayanan',
            'subtotal',
            'potongan',
            'ppn',
            'total',
            'terbilangText',
            'paymentUrl',
            'headerBase64',
            'footerBase64',
            'stampBase64'
        ));
    }

    /**
     * API JSON Pencarian Invoice untuk Cetak Massal (Modal Print)
     */
    public function searchBatchInvoiceJson(Request $request): JsonResponse
    {
        $bulan = $request->query('bulan', '');
        $tahun = $request->query('tahun', '');
        $search = trim($request->query('search', ''));
        $statusBayar = $request->query('status_bayar', '');

        $statusDescriptions = [
            '11' => 'Draft',
            '12' => 'Auto Publish',
            '13' => 'Published (Belum Bayar)',
            '14' => 'Menunggu Verifikasi',
            '15' => 'Lunas (Paid)',
            '16' => 'Dibatalkan',
            '17' => 'Expired',
            '18' => 'Kadaluarsa',
            '1' => 'Published',
            '2' => 'Lunas',
        ];

        $invoices = collect();
        try {
            $query = DB::table('view_billing_layanan');

            if ($bulan !== '' && $bulan !== null) {
                $bulanPad = str_pad((string)$bulan, 2, '0', STR_PAD_LEFT);
                $bulanInt = (int)$bulan;
                $query->where(function($q) use ($bulanPad, $bulanInt) {
                    $q->where('bulan_tagihan', $bulanPad)
                      ->orWhere('bulan_tagihan', (string)$bulanInt);
                });
            }
            if ($tahun !== '' && $tahun !== null) {
                $query->where('tahun_tagihan', (string)$tahun);
            }
            if ($statusBayar !== '' && $statusBayar !== null) {
                $query->where('status_bill_lay', $statusBayar);
            }
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nama_pelanggan', 'like', "%{$search}%")
                      ->orWhere('nomor_internet', 'like', "%{$search}%")
                      ->orWhere('kode_billing_layanan', 'like', "%{$search}%");
                });
            }

            $invoices = $query->orderBy('nama_pelanggan', 'asc')->get();
        } catch (\Throwable $e) {
            Log::info('searchBatchInvoiceJson view_billing_layanan query fallback: ' . $e->getMessage());
            try {
                $query = DB::table('trx_billing_layanan')
                    ->leftJoin('view_batchjob', 'trx_billing_layanan.nomor_internet', '=', 'view_batchjob.nomor_internet');

                if ($bulan !== '' && $bulan !== null) {
                    $bulanPad = str_pad((string)$bulan, 2, '0', STR_PAD_LEFT);
                    $query->where('trx_billing_layanan.bulan_tagihan', $bulanPad);
                }
                if ($tahun !== '' && $tahun !== null) {
                    $query->where('trx_billing_layanan.tahun_tagihan', (string)$tahun);
                }
                if ($statusBayar !== '' && $statusBayar !== null) {
                    $query->where('trx_billing_layanan.status_bill_lay', $statusBayar);
                }
                if ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('view_batchjob.nama_pelanggan', 'like', "%{$search}%")
                          ->orWhere('trx_billing_layanan.nomor_internet', 'like', "%{$search}%")
                          ->orWhere('trx_billing_layanan.kode_billing_layanan', 'like', "%{$search}%");
                    });
                }

                $invoices = $query->select(
                    'trx_billing_layanan.*',
                    'view_batchjob.nama_pelanggan'
                )->get();
            } catch (\Throwable $e2) {
                Log::error('searchBatchInvoiceJson fallback failed: ' . $e2->getMessage());
                $invoices = DB::table('trx_billing_layanan')->limit(100)->get();
            }
        }

        $data = $invoices->map(function($inv) use ($statusDescriptions) {
            $statusKey = (string)($inv->status_bill_lay ?? '');
            $statusDesc = $inv->desc_bill_lay ?? ($statusDescriptions[$statusKey] ?? 'Draft');

            return [
                'kode_billing_layanan' => $inv->kode_billing_layanan ?? '',
                'nomor_internet' => $inv->nomor_internet ?? '-',
                'nama_pelanggan' => $inv->nama_pelanggan ?? 'Pelanggan',
                'nama_kategori_bandwith' => $inv->nama_kategori_bandwith ?? '',
                'nominal_bandwith' => $inv->nominal_bandwith ?? '',
                'total_layanan' => (float)($inv->total_layanan ?? $inv->harga_bandwith ?? 0),
                'bulan_tagihan' => $inv->bulan_tagihan ?? '',
                'tahun_tagihan' => $inv->tahun_tagihan ?? '',
                'periode_tagihan' => $inv->periode_tagihan ?? (($inv->bulan_tagihan ?? '') . '/' . ($inv->tahun_tagihan ?? '')),
                'status_bill_lay' => $statusKey,
                'status_desc' => $statusDesc,
            ];
        });

        return response()->json([
            'success' => true,
            'total' => $data->count(),
            'data' => $data
        ]);
    }

    /**
     * API JSON Kandidat Pelanggan untuk Form Generate Invoice
     */
    public function getGenerateCandidatesJson(Request $request): JsonResponse
    {
        $bulan = $request->query('bulan', date('m'));
        $tahun = $request->query('tahun', date('Y'));
        $search = trim($request->query('search', ''));
        $layanan = trim($request->query('layanan', ''));

        $bulanPad = str_pad((string)$bulan, 2, '0', STR_PAD_LEFT);
        $namaBulanShort = Carbon::createFromDate((int)$tahun, (int)$bulanPad, 1)->format('M');
        $periodeStr = "{$namaBulanShort} {$tahun}";

        $query = DB::table('view_batchjob')
            ->whereIn('status_reg', ['20', '21', '21.1', '22'])
            ->where('hide', '0')
            ->whereNotNull('nomor_internet')
            ->where('nomor_internet', '!=', '');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('nomor_internet', 'like', "%{$search}%");
            });
        }

        if ($layanan && $layanan !== 'Semua Layanan') {
            $query->where('nama_kategori_bandwith', 'like', "%{$layanan}%");
        }

        $pelanggans = $query->select(
            'nomor_internet',
            'nama_pelanggan',
            'nama_kategori_bandwith',
            'nominal_bandwith',
            'harga_bandwith',
            'potongan',
            'status_reg'
        )->orderBy('nama_pelanggan', 'asc')->get()->unique('nomor_internet');

        // Check which ones already have invoices generated
        $existingInvoices = DB::table('trx_billing_layanan')
            ->where('bulan_tagihan', $bulanPad)
            ->where('tahun_tagihan', (string)$tahun)
            ->whereIn('nomor_internet', $pelanggans->pluck('nomor_internet'))
            ->pluck('status_bill_lay', 'nomor_internet');

        $data = $pelanggans->map(function($p) use ($existingInvoices, $periodeStr) {
            $hasInv = $existingInvoices->has($p->nomor_internet);
            $harga = (float)($p->harga_bandwith ?? $p->nominal_bandwith ?? 0);
            $pot = (float)($p->potongan ?? 0);
            $pendingNominal = max(0, $harga - $pot);

            return [
                'nomor_internet' => $p->nomor_internet,
                'nama_pelanggan' => $p->nama_pelanggan,
                'layanan' => $p->nama_kategori_bandwith ?? '-',
                'periode' => $periodeStr,
                'pending_nominal' => $pendingNominal,
                'pending_formatted' => 'Rp ' . number_format($pendingNominal, 0, ',', '.'),
                'is_generated' => $hasInv,
                'status_bill_lay' => $existingInvoices[$p->nomor_internet] ?? null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'total' => $data->count(),
            'data' => $data
        ]);
    }

    /**
     * Cetak Banyak Invoice Sekaligus (Batch Print Invoices)
     */
    public function batchDokumenInvoice(Request $request): View
    {
        $kodes = $request->input('kodes', $request->query('kodes'));
        if (is_string($kodes)) {
            $kodes = explode(',', $kodes);
        }
        $kodes = array_filter((array)$kodes);

        $bulan = $request->input('bulan', $request->query('bulan'));
        $tahun = $request->input('tahun', $request->query('tahun'));

        $rawInvoices = collect();
        try {
            $query = DB::table('view_billing_layanan');
            if (!empty($kodes)) {
                $query->whereIn('kode_billing_layanan', $kodes);
            } else {
                if ($bulan !== '' && $bulan !== null) {
                    $bulanPad = str_pad((string)$bulan, 2, '0', STR_PAD_LEFT);
                    $bulanInt = (int)$bulan;
                    $query->where(function($q) use ($bulanPad, $bulanInt) {
                        $q->where('bulan_tagihan', $bulanPad)
                          ->orWhere('bulan_tagihan', (string)$bulanInt);
                    });
                }
                if ($tahun) {
                    $query->where('tahun_tagihan', (string)$tahun);
                }
            }
            $rawInvoices = $query->orderBy('nama_pelanggan', 'asc')->get();
        } catch (\Throwable $e) {
            $query = DB::table('trx_billing_layanan')
                ->leftJoin('view_batchjob', 'trx_billing_layanan.nomor_internet', '=', 'view_batchjob.nomor_internet');
            if (!empty($kodes)) {
                $query->whereIn('trx_billing_layanan.kode_billing_layanan', $kodes);
            } else {
                if ($bulan !== '' && $bulan !== null) {
                    $bulanPad = str_pad((string)$bulan, 2, '0', STR_PAD_LEFT);
                    $query->where('trx_billing_layanan.bulan_tagihan', $bulanPad);
                }
                if ($tahun) {
                    $query->where('trx_billing_layanan.tahun_tagihan', (string)$tahun);
                }
            }
            $rawInvoices = $query->select('trx_billing_layanan.*', 'view_batchjob.nama_pelanggan', 'view_batchjob.alamat_pasang')->get();
        }

        if ($rawInvoices->isEmpty()) {
            abort(404, "Tidak ada data invoice yang ditemukan untuk dicetak.");
        }

        $headerPath = public_path('assets/images/invoice_kop_header.png');
        if (!file_exists($headerPath)) $headerPath = public_path('assets/images/kop_header.png');
        $footerPath = public_path('assets/images/invoice_kop_footer.png');
        if (!file_exists($footerPath)) $footerPath = public_path('assets/images/kop_footer.png');

        $headerBase64 = file_exists($headerPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($headerPath)) : asset('assets/images/kop_header.png');
        $footerBase64 = file_exists($footerPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($footerPath)) : asset('assets/images/kop_footer.png');

        $invoicesData = [];
        foreach ($rawInvoices as $invoice) {
            $customer = null;
            if (Schema::hasTable('view_batchjob')) {
                $customer = DB::table('view_batchjob')->where('nomor_internet', $invoice->nomor_internet)->first();
            }

            $customerName = $invoice->nama_pelanggan ?? ($customer->nama_pelanggan ?? ($customer->nama_penduduk ?? 'Pelanggan'));
            $nomorInternet = $invoice->nomor_internet ?? ($customer->nomor_internet ?? '-');
            $noInvoice = $invoice->kode_billing_layanan;

            $alamat = $invoice->alamat_pasang ?? ($customer->alamat_pasang ?? ($customer->alamat_p ?? ($customer->alamat_ktp ?? '-')));
            if (!empty($customer->rt_pasang) || !empty($customer->rw_pasang)) {
                $alamat .= " RT." . ($customer->rt_pasang ?? '00') . " / RW." . ($customer->rw_pasang ?? '00');
            }
            if (!empty($customer->nama_kel_pasang)) $alamat .= ", Kel. " . $customer->nama_kel_pasang;
            if (!empty($customer->nama_kec_pasang)) $alamat .= ", Kec. " . $customer->nama_kec_pasang;
            if (!empty($customer->nama_kota_pasang)) $alamat .= ", " . $customer->nama_kota_pasang;

            $bTagihan = $invoice->bulan_tagihan ?? date('m');
            $tTagihan = $invoice->tahun_tagihan ?? date('Y');
            $monthName = date('M', mktime(0, 0, 0, (int)$bTagihan, 1, (int)$tTagihan));
            $periodeTagihan = $invoice->periode_tagihan ?: ($monthName . ' ' . $tTagihan);

            $jatuhTempo = !empty($invoice->expiry) 
                ? Carbon::parse($invoice->expiry)->locale('id')->isoFormat('D MMMM Y')
                : 'Tanggal 20 Setiap Bulan';

            $kategoriBandwith = $invoice->nama_kategori_bandwith ?? ($customer->nama_kategori_bandwith ?? 'BROADBAND');
            $nominalBandwith = $invoice->nominal_bandwith ?? ($customer->nominal_bandwith ?? '');
            $namaLayanan = "LAYANAN INTERNET {$kategoriBandwith}" . ($nominalBandwith ? " {$nominalBandwith} MBps" : '');

            $subtotal = (float) ($invoice->harga_bandwith ?? ($customer->harga_bandwith ?? ($invoice->total_layanan ?? 0)));
            $potongan = (float) ($invoice->potongan ?? 0);
            $ppn = (float) ($invoice->ppn ?? 0);
            $total = (float) ($invoice->total_layanan ?? max(0, $subtotal - $potongan + $ppn));

            $terbilangText = $this->terbilangRupiah($total);

            $paymentUrl = null;
            if (!empty($invoice->payment_respond_post)) {
                $resp = json_decode($invoice->payment_respond_post, true);
                $paymentUrl = $resp['redirect_url'] ?? null;
            }
            if (!$paymentUrl) {
                $paymentUrl = route('finance.billing-layanan', ['search' => $noInvoice]);
            }

            $invoicesData[] = [
                'customerName' => $customerName,
                'nomorInternet' => $nomorInternet,
                'noInvoice' => $noInvoice,
                'alamat' => $alamat,
                'periodeTagihan' => $periodeTagihan,
                'jatuhTempo' => $jatuhTempo,
                'namaLayanan' => $namaLayanan,
                'subtotal' => $subtotal,
                'potongan' => $potongan,
                'ppn' => $ppn,
                'total' => $total,
                'terbilangText' => $terbilangText,
                'paymentUrl' => $paymentUrl
            ];
        }

        return view('finance.dokumen.batch_invoice', compact(
            'invoicesData',
            'headerBase64',
            'footerBase64',
            'bulan',
            'tahun'
        ));
    }

    /**
     * Helper Terbilang Bahasa Indonesia
     */
    private function terbilangRupiah(float $angka): string
    {
        $angka = abs((float) $angka);
        if ($angka == 0) {
            return 'Nol Rupiah';
        }

        $terbilang = $this->penyebut($angka);
        return trim(preg_replace('/\s+/', ' ', $terbilang)) . ' Rupiah';
    }

    private function penyebut(float $nilai): string
    {
        $nilai = abs((float)$nilai);
        $huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        $temp = '';
        if ($nilai < 12) {
            $temp = ' ' . $huruf[(int)$nilai];
        } elseif ($nilai < 20) {
            $temp = $this->penyebut($nilai - 10) . ' Belas';
        } elseif ($nilai < 100) {
            $temp = $this->penyebut((int)($nilai / 10)) . ' Puluh' . $this->penyebut(fmod($nilai, 10));
        } elseif ($nilai < 200) {
            $temp = ' Seratus' . $this->penyebut($nilai - 100);
        } elseif ($nilai < 1000) {
            $temp = $this->penyebut((int)($nilai / 100)) . ' Ratus' . $this->penyebut(fmod($nilai, 100));
        } elseif ($nilai < 2000) {
            $temp = ' Seribu' . $this->penyebut($nilai - 1000);
        } elseif ($nilai < 1000000) {
            $temp = $this->penyebut((int)($nilai / 1000)) . ' Ribu' . $this->penyebut(fmod($nilai, 1000));
        } elseif ($nilai < 1000000000) {
            $temp = $this->penyebut((int)($nilai / 1000000)) . ' Juta' . $this->penyebut(fmod($nilai, 1000000));
        } elseif ($nilai < 1000000000000) {
            $temp = $this->penyebut((int)($nilai / 1000000000)) . ' Milyar' . $this->penyebut(fmod($nilai, 1000000000));
        } elseif ($nilai < 1000000000000000) {
            $temp = $this->penyebut((int)($nilai / 1000000000000)) . ' Trilyun' . $this->penyebut(fmod($nilai, 1000000000000));
        }
        return $temp;
    }
}
