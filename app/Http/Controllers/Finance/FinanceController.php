<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\MidtransService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Clone query for KPI statistics based on current month/year filter
        $kpiQuery = DB::table('view_billing_layanan');
        if ($selectedBulan !== '' && $selectedBulan !== null) {
            $kpiQuery->where('bulan_tagihan', str_pad($selectedBulan, 2, '0', STR_PAD_LEFT));
        }
        if ($selectedTahun !== '' && $selectedTahun !== null) {
            $kpiQuery->where('tahun_tagihan', $selectedTahun);
        }

        // 4 KPI Summary Cards
        // 1. Generating... Auto Publish (11 Draft / 12 Generating)
        $kpiGenerating = (clone $kpiQuery)->whereIn('status_bill_lay', ['11', '12']);
        $generatingCount = $kpiGenerating->count();
        $generatingAmount = $kpiGenerating->sum('total_layanan');

        // 2. Publish Billing (13)
        $kpiPublish = (clone $kpiQuery)->where('status_bill_lay', '13');
        $publishCount = $kpiPublish->count();
        $publishAmount = $kpiPublish->sum('total_layanan');

        // 3. Waiting Payment (14)
        $kpiWaiting = (clone $kpiQuery)->where('status_bill_lay', '14');
        $waitingCount = $kpiWaiting->count();
        $waitingAmount = $kpiWaiting->sum('total_layanan');

        // 4. Paid (15)
        $kpiPaid = (clone $kpiQuery)->where('status_bill_lay', '15');
        $paidCount = $kpiPaid->count();
        $paidAmount = $kpiPaid->sum('total_layanan');

        // Fetch Paginated Invoices
        $invoices = $query->orderBy('date_create', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Master Dropdown Data
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

        $tahunList = DB::table('view_billing_layanan')
            ->select('tahun_tagihan')
            ->distinct()
            ->whereNotNull('tahun_tagihan')
            ->orderBy('tahun_tagihan', 'desc')
            ->pluck('tahun_tagihan')
            ->toArray();

        if (empty($tahunList)) {
            $tahunList = [(string) date('Y'), (string) (date('Y') - 1)];
        }

        $layananList = DB::table('view_billing_layanan')
            ->select('nama_kategori_bandwith')
            ->distinct()
            ->whereNotNull('nama_kategori_bandwith')
            ->pluck('nama_kategori_bandwith')
            ->toArray();

        $wilayahList = DB::table('view_billing_layanan')
            ->select('nama_kota_pasang')
            ->distinct()
            ->whereNotNull('nama_kota_pasang')
            ->pluck('nama_kota_pasang')
            ->toArray();

        $statusBillList = DB::table('m_status_bill_lay')
            ->where('hide', '0')
            ->get();

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

        // Summary Counts for Billing Registrasi
        $kpiTotal = (clone $query)->count();
        $kpiDraft = (clone $query)->where(function($q) {
            $q->whereIn('status_bill_reg', ['11', '11.1'])->orWhereNull('status_bill_reg');
        })->whereNull('payment_publish')->count();
        $kpiPublished = (clone $query)->where(function($q) {
            $q->where('status_bill_reg', '12')->orWhereNotNull('payment_publish');
        })->count();
        $kpiMidtrans = (clone $query)->where('payment_type', '1')->count();
        $kpiManual = (clone $query)->whereIn('payment_type', ['2', '3'])->count();

        $registrations = $query->orderBy('date_create', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $layananList = DB::table('view_billing_reg')
            ->select('nama_kategori_bandwith')
            ->distinct()
            ->whereNotNull('nama_kategori_bandwith')
            ->pluck('nama_kategori_bandwith')
            ->toArray();

        $wilayahList = DB::table('view_batchjob')
            ->select('nama_kota_pasang')
            ->distinct()
            ->whereNotNull('nama_kota_pasang')
            ->pluck('nama_kota_pasang')
            ->toArray();

        $statusBillRegList = DB::table('m_status_bill_reg')
            ->where('hide', '0')
            ->get();

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

        // 4 KPI Counters
        $count11 = DB::table('view_ubah_layanan')->where('status_ubah_layanan', '11')->count();
        $count12 = DB::table('view_ubah_layanan')->where('status_ubah_layanan', '12')->count();
        $count13 = DB::table('view_ubah_layanan')->where('status_ubah_layanan', '13')->count();
        $count14 = DB::table('view_ubah_layanan')->where('status_ubah_layanan', '14')->count();

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

        // 4 KPI Counters
        $countRequest = DB::table('view_suspend')->where('status_suspend', '11')->count();
        $countSuspend = DB::table('view_suspend')->where('status_suspend', '12')->count();
        $countReqUnsuspend = DB::table('view_suspend')->where('status_suspend', '18')->count();
        $countUnsuspend = DB::table('view_suspend')->where('status_suspend', '13')->count();

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

        // 4 Key KPI Counters
        $count11 = DB::table('view_terminasi')->where('status_terminasi', '11')->count();
        $count12 = DB::table('view_terminasi')->whereIn('status_terminasi', ['12', '12.1'])->count();
        $count13 = DB::table('view_terminasi')->where('status_terminasi', '13')->count();
        $count16 = DB::table('view_terminasi')->where('status_terminasi', '16')->count();

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

