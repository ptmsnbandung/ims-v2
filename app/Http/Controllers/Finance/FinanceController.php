<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\MidtransService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        // Default or Filtered Month & Year
        $selectedBulan = $request->input('bulan', '');
        $selectedTahun = $request->input('tahun', '');

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
            $query->where('status_bill_lay', $request->input('status_bayar'));
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

        $tahunList = Schema::hasTable('trx_billing_layanan')
            ? DB::table('trx_billing_layanan')
                ->select('tahun_tagihan')
                ->distinct()
                ->whereNotNull('tahun_tagihan')
                ->orderBy('tahun_tagihan', 'desc')
                ->limit(5)
                ->pluck('tahun_tagihan')
                ->toArray()
            : [];

        if (empty($tahunList)) {
            $tahunList = [(string) date('Y'), (string) (date('Y') - 1), (string) (date('Y') - 2)];
        }

        $layananList = Schema::hasTable('m_bandwith_kategori')
            ? DB::table('m_bandwith_kategori')->pluck('nama_kategori_bandwith')->filter()->unique()->toArray()
            : ['BROADBAND', 'DEDICATED', 'SOHO', 'CORPORATE'];

        $wilayahList = Schema::hasTable('m_wilayah_perangkat')
            ? DB::table('m_wilayah_perangkat')->pluck('name_w')->filter()->unique()->toArray()
            : [];

        if (empty($wilayahList) && Schema::hasTable('m_wilayah')) {
            $wilayahList = DB::table('m_wilayah')->limit(50)->pluck('nama_kota')->filter()->unique()->toArray();
        }

        $statusBillList = Schema::hasTable('m_status_bill_lay')
            ? DB::table('m_status_bill_lay')->where('hide', '0')->get()
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
            'bulan' => 'required|string|size:2',
            'tahun' => 'required|numeric|min:2020|max:2035',
        ]);

        $bulan = str_pad($request->input('bulan'), 2, '0', STR_PAD_LEFT);
        $tahun = (string) $request->input('tahun');
        $namaBulanShort = Carbon::createFromDate((int)$tahun, (int)$bulan, 1)->format('M');
        $periodeTagihan = "{$namaBulanShort} {$tahun}";
        $userCreator = Auth::user()?->nama ?? 'FINANCE';

        DB::beginTransaction();
        try {
            // Ambil semua pelanggan aktif (status_reg = 20 atau pelanggan live)
            $pelanggans = DB::table('view_batchjob')
                ->whereIn('status_reg', ['20', '21', '22']) // Aktif, Suspend, Req Suspend
                ->where('hide', '0')
                ->get();

            $createdCount = 0;
            $skippedCount = 0;

            foreach ($pelanggans as $p) {
                $kodeBilling = "INV/{$p->nomor_internet}/{$bulan}/{$tahun}";

                // Cek apakah invoice periode ini sudah pernah digenerate
                $exists = DB::table('trx_billing_layanan')
                    ->where('kode_billing_layanan', $kodeBilling)
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                $hargaBandwith = (float) ($p->harga_bandwith ?? 0);
                $potongan = (float) ($p->potongan ?? 0);
                $totalLayanan = max(0, $hargaBandwith - $potongan);

                // Buat record di trx_billing_layanan
                DB::table('trx_billing_layanan')->insert([
                    'kode_billing_layanan' => $kodeBilling,
                    'nomor_internet' => $p->nomor_internet,
                    'kode_bandwith' => $p->kode_bandwith,
                    'nominal_bandwith' => $p->nominal_bandwith ?? '0',
                    'bulan_tagihan' => $bulan,
                    'tahun_tagihan' => $tahun,
                    'periode_tagihan' => $periodeTagihan,
                    'potongan' => (string) $potongan,
                    'desc_potongan' => $p->potongan_note ?? '-',
                    'ppn' => $p->ppn_nom ?? '0.11',
                    'tax' => $p->ppn ?? '2',
                    'voucher' => '-',
                    'total_layanan' => (string) $totalLayanan,
                    'notif_mail' => '0',
                    'notif_wa' => '0',
                    'status_bill_lay' => '12', // Generating... Auto Publish
                    'denda' => null,
                    'invoice_file' => null,
                    'payment_type' => '1', // Default Midtrans
                    'payment_post' => '-',
                    'date_create' => Carbon::now()->toDateTimeString(),
                    'user_create' => $userCreator,
                    'date_update' => Carbon::now()->toDateTimeString(),
                    'user_update' => $userCreator,
                    'hide' => '0',
                    'islock' => null,
                ]);

                // Buat detail item layanan
                DB::table('trx_billing_layanan_detail')->insert([
                    'kode_billing_lay_detail' => "D-{$p->nomor_internet}-{$bulan}/{$tahun}-T11",
                    'kode_billing_layanan' => $kodeBilling,
                    'kode_item' => 'T11',
                    'komponen' => 'LAYANAN ' . ($p->nama_kategori_bandwith ?? 'INTERNET') . ' ' . ($p->nominal_bandwith ?? '') . ' Mbps',
                    'qty' => 1,
                    'biaya' => (string) $hargaBandwith,
                    'date_create' => Carbon::now()->toDateTimeString(),
                    'user_create' => $userCreator,
                    'hide' => null,
                ]);

                // Buat log
                DB::table('trx_billing_layanan_log')->insert([
                    'kode_billing_lay_log' => "LOG-{$p->nomor_internet}-{$bulan}/{$tahun}-" . time() . "-{$createdCount}",
                    'kode_billing_layanan' => $kodeBilling,
                    'status_bill_lay' => '12',
                    'note_billing_lay' => "Batch Invoice Generated by {$userCreator}",
                    'date_create' => Carbon::now()->toDateTimeString(),
                    'user_create' => $userCreator,
                    'hide' => '0',
                ]);

                $createdCount++;
            }

            DB::commit();

            return redirect()->route('finance.billing-layanan', [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ])->with('success', "Berhasil men-generate {$createdCount} invoice untuk periode {$periodeTagihan}. ({$skippedCount} invoice sudah ada sebelumnya).");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal men-generate invoice: ' . $e->getMessage());
        }
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
            'catatan' => 'nullable|string',
        ]);

        $user = Auth::user()?->nama ?? 'FINANCE';
        $decodedKode = $request->input('kode_billing') ? trim($request->input('kode_billing')) : urldecode($kodeBilling ?? '');
        $nominal = $request->input('nominal_bayar');
        $bank = $request->input('bank_tujuan', 'Manual Transfer');
        $catatan = $request->input('catatan', 'Pembayaran Tagihan Bulanan Terverifikasi');

        try {
            DB::table('trx_billing_layanan')
                ->where('kode_billing_layanan', $decodedKode)
                ->update([
                    'status_bill_lay' => '15', // Paid
                    'payment_paid' => Carbon::now()->toDateTimeString(),
                    'amount_paid' => (string) $nominal,
                    'merchant_type' => $bank,
                    'payment_type' => $request->input('metode_bayar') === 'cash' ? '3' : '2',
                    'date_update' => Carbon::now()->toDateTimeString(),
                    'user_update' => $user,
                ]);

            DB::table('trx_billing_layanan_log')->insert([
                'kode_billing_lay_log' => 'LOG-' . uniqid(),
                'kode_billing_layanan' => $decodedKode,
                'status_bill_lay' => '15',
                'note_billing_lay' => "Payment verified ({$bank} - Rp " . number_format($nominal, 0, ',', '.') . "): {$catatan} by {$user}",
                'date_create' => Carbon::now()->toDateTimeString(),
                'user_create' => $user,
                'hide' => '0',
            ]);

            return redirect()->back()->with('success', "Pembayaran invoice {$decodedKode} sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil diverifikasi.");
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
                'kode_billing_registrasi' => $decodedKode,
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

        $items = DB::table('trx_billing_layanan_detail')
            ->where('kode_billing_layanan', $decodedKode)
            ->get();

        $logs = DB::table('trx_billing_layanan_log')
            ->where('kode_billing_layanan', $decodedKode)
            ->orderBy('date_create', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'invoice' => $invoice,
            'items' => $items,
            'logs' => $logs,
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
                    $inv->desc_payment_type ?? ($inv->payment_type == 1 ? 'Midtrans' : 'Manual Transfer'),
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
            $query->where('status_bill_reg', $request->input('status_bayar'));
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
            ? DB::table('m_status_bill_reg')->where('hide', '0')->get()
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
            'catatan' => 'nullable|string',
        ]);

        $user = Auth::user()?->nama ?? 'FINANCE';
        $decodedKode = $request->input('kode_billing') ? trim($request->input('kode_billing')) : urldecode($kodeBilling ?? '');
        $nominal = $request->input('nominal_bayar');
        $bank = $request->input('bank_tujuan', 'Manual Transfer');
        $catatan = $request->input('catatan', 'Pembayaran Registrasi Terverifikasi');

        try {
            DB::table('trx_billing_registrasi')
                ->where('kode_billing_registrasi', $decodedKode)
                ->update([
                    'status_bill_reg' => '14', // Paid
                    'payment_paid' => Carbon::now()->toDateTimeString(),
                    'amount_paid' => (string) $nominal,
                    'merchant_type' => $bank,
                    'payment_type' => $request->input('metode_bayar') === 'cash' ? '3' : '2',
                    'date_update' => Carbon::now()->toDateTimeString(),
                    'user_update' => $user,
                ]);

            DB::table('trx_billing_registrasi_log')->insert([
                'kode_billing_regis_log' => 'LOG-' . uniqid(),
                'kode_billing_registrasi' => $decodedKode,
                'status_bill_reg' => '14',
                'note_billing_reg' => "Registration payment verified ({$bank} - Rp " . number_format($nominal, 0, ',', '.') . "): {$catatan} by {$user}",
                'date_create' => Carbon::now()->toDateTimeString(),
                'user_create' => $user,
                'hide' => '0',
            ]);

            return redirect()->back()->with('success', "Pembayaran Registrasi {$decodedKode} sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil diverifikasi.");
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
            $query->where(function($q) use ($search) {
                $q->where('b.kode_bandwith', 'like', "%{$search}%")
                  ->orWhere('b.nama_bandwith', 'like', "%{$search}%")
                  ->orWhere('k.nama_kategori_bandwith', 'like', "%{$search}%")
                  ->orWhere('b.peruntukan_bangunan', 'like', "%{$search}%")
                  ->orWhere('b.nominal_bandwith', 'like', "%{$search}%");
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
}

