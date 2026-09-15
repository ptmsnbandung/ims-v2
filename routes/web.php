<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Noc\NocController;
use App\Http\Controllers\Teknik\TeknikController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
    Route::get('/login', [LoginController::class, 'showLoginForm']);
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Routes Role Master Admin & Direktur
    Route::middleware('role:admin,direktur')->prefix('admin')->name('admin.')->group(function () {
        // 1. User Management & Access Rights
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::match(['POST', 'PUT'], '/users/{kode_pengguna}/update', [AdminController::class, 'updateUser'])->name('users.update');
        Route::match(['POST', 'PUT'], '/users/{kode_pengguna}', [AdminController::class, 'updateUser']);
        Route::match(['POST', 'PATCH'], '/users/{kode_pengguna}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('users.toggle-status');
        Route::match(['POST', 'DELETE'], '/users/{kode_pengguna}/delete', [AdminController::class, 'deleteUser'])->name('users.delete');
        Route::match(['POST', 'DELETE'], '/users/{kode_pengguna}', [AdminController::class, 'deleteUser']);

        // 2. Master Paket Internet & Layanan Bandwidth
        Route::get('/paket', [AdminController::class, 'paket'])->name('paket');
        Route::post('/paket/store', [AdminController::class, 'storePaket'])->name('paket.store');
        Route::match(['POST', 'DELETE'], '/paket/{kode_bandwith}/delete', [AdminController::class, 'deletePaket'])->name('paket.delete')->where('kode_bandwith', '.*');
    });

    // Routes Role Teknik & NOC & Direktur
    Route::middleware('role:teknik,noc,direktur')->prefix('teknik')->name('teknik.')->group(function () {
        Route::get('/tiket', [TeknikController::class, 'tiket'])->name('tiket');
        
        // Pendaftaran Pelanggan Baru & Edit Pendaftaran
        Route::get('/pendaftaran', [TeknikController::class, 'pendaftaran'])->name('pendaftaran');
        Route::get('/pendaftaran/export', [TeknikController::class, 'exportPendaftaran'])->name('pendaftaran.export');
        Route::post('/pendaftaran', [TeknikController::class, 'storePendaftaran'])->name('pendaftaran.store');
        Route::post('/pendaftaran/update', [TeknikController::class, 'updatePendaftaran'])->name('pendaftaran.update');
        Route::post('/pendaftaran/batal-pasang', [TeknikController::class, 'batalPasang'])->name('pendaftaran.batal-pasang');
        
        // Survey & Instalasi Workflow Routes
        Route::post('/pendaftaran/schedule-survey', [TeknikController::class, 'storeScheduleSurvey'])->name('pendaftaran.schedule-survey');
        Route::post('/pendaftaran/report-survey', [TeknikController::class, 'storeReportSurvey'])->name('pendaftaran.report-survey');
        Route::post('/pendaftaran/schedule-instalasi', [TeknikController::class, 'storeScheduleInstalasi'])->name('pendaftaran.schedule-instalasi');
        Route::post('/pendaftaran/report-instalasi', [TeknikController::class, 'storeReportInstalasi'])->name('pendaftaran.report-instalasi');
        Route::post('/pendaftaran/request-aktivasi', [TeknikController::class, 'requestAktivasiNoc'])->name('pendaftaran.request-aktivasi');

        // AJAX API Dropdowns & Detail Pelanggan & Billing & Survey/Instalasi
        Route::get('/api/billing/{nomor_internet}', [TeknikController::class, 'getBillingDetail'])->name('api.billing.detail');
        Route::get('/api/pendaftaran/{nomor_internet}', [TeknikController::class, 'getPendaftaranDetail'])->name('api.pendaftaran.detail');
        Route::get('/api/survey-instalasi/{nomor_internet}', [TeknikController::class, 'getSurveyInstalasiDetail'])->name('api.survey-instalasi.detail');
        Route::get('/api/paket/{kategori}', [TeknikController::class, 'getPaket'])->name('api.paket');
        Route::get('/api/wilayah/kota/{provinsi}', [TeknikController::class, 'getKota'])->name('api.kota');
        Route::get('/api/wilayah/kecamatan/{kota}', [TeknikController::class, 'getKecamatan'])->name('api.kecamatan');
        Route::get('/api/wilayah/kelurahan/{kecamatan}', [TeknikController::class, 'getKelurahan'])->name('api.kelurahan');

        // Group Permintaan
        Route::prefix('permintaan')->name('permintaan.')->group(function () {
            Route::get('/up-downgrade', [TeknikController::class, 'upDowngrade'])->name('up-downgrade');
            Route::post('/up-downgrade/{kode_trx}/schedule', [TeknikController::class, 'scheduleUpDowngrade'])->name('up-downgrade.schedule');
            Route::post('/up-downgrade/{kode_trx}/cancel', [TeknikController::class, 'cancelUpDowngrade'])->name('up-downgrade.cancel');
            Route::get('/terminasi', [TeknikController::class, 'terminasi'])->name('terminasi');
            Route::get('/suspend', [TeknikController::class, 'suspend'])->name('suspend');
        });

        // Data Pelanggan & Profile & Export Excel
        Route::get('/pelanggan', [TeknikController::class, 'pelanggan'])->name('pelanggan');
        Route::get('/pelanggan/export', [TeknikController::class, 'exportPelanggan'])->name('pelanggan.export');
        Route::get('/pelanggan/{nomor_internet}', [TeknikController::class, 'profilePelanggan'])->name('pelanggan.profile');
        Route::get('/dokumen/langganan/{nomor_internet}', [TeknikController::class, 'dokumenLangganan'])->name('dokumen.langganan');
        Route::get('/dokumen/survey/{nomor_internet}', [TeknikController::class, 'dokumenSurvey'])->name('dokumen.survey');
        Route::get('/dokumen/instalasi/{nomor_internet}', [TeknikController::class, 'dokumenInstalasi'])->name('dokumen.instalasi');
        Route::post('/pelanggan/{nomor_internet}/upload-doc', [TeknikController::class, 'uploadDocArsip'])->name('pelanggan.upload-doc');
        Route::post('/pelanggan/{nomor_internet}/perangkat', [TeknikController::class, 'storePerangkat'])->name('pelanggan.perangkat.store');
        Route::post('/pelanggan/{nomor_internet}/perangkat/{kode_inst_barang}/delete', [TeknikController::class, 'deletePerangkat'])->name('pelanggan.perangkat.delete');
        Route::post('/pelanggan/{nomor_internet}/update-pppoe', [TeknikController::class, 'updatePppoe'])->name('pelanggan.update-pppoe');
        // Peta Jaringan & Jalur FTTH (GIS Network Builder)
        Route::get('/peta-jaringan', [TeknikController::class, 'petaJaringan'])->name('peta-jaringan');
        Route::post('/peta-jaringan/project', [TeknikController::class, 'storeGisProject'])->name('peta-jaringan.project.store');
        Route::post('/peta-jaringan/project/{id}/delete', [TeknikController::class, 'deleteGisProject'])->name('peta-jaringan.project.delete');
        Route::post('/peta-jaringan/element', [TeknikController::class, 'saveGisElement'])->name('peta-jaringan.element.save');
        Route::post('/peta-jaringan/element/{id}/delete', [TeknikController::class, 'deleteGisElement'])->name('peta-jaringan.element.delete');
        Route::post('/peta-jaringan/upload-photo', [TeknikController::class, 'uploadGisPhoto'])->name('peta-jaringan.upload-photo');
        Route::post('/peta-jaringan/import-kmz', [TeknikController::class, 'importKmzKml'])->name('peta-jaringan.import-kmz');
        Route::get('/peta-jaringan/export-kml/{projectId?}', [TeknikController::class, 'exportKml'])->name('peta-jaringan.export-kml');

        // Cek Coverage Lokasi ke ODP Terdekat (GIS Dropcore Routing)
        Route::get('/coverage', [TeknikController::class, 'coverage'])->name('coverage');
        Route::post('/coverage/update-ticket-status', [TeknikController::class, 'updateCoverageTicketStatus'])->name('coverage.update-status');
    });

    Route::middleware('role:noc,direktur')->prefix('noc')->name('noc.')->group(function () {
        // 1. Dashboard NOC Command Center
        Route::get('/', [NocController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard', [NocController::class, 'dashboard'])->name('dashboard.index');

        // 2. Infrastruktur: OLT, GPON, & Port PON
        Route::get('/olt', [NocController::class, 'olt'])->name('olt');
        Route::get('/olt/create', [NocController::class, 'oltCreate'])->name('olt.create');
        Route::get('/olt/{kode_olt}/edit', [NocController::class, 'oltEdit'])->name('olt.edit');
        Route::post('/olt/store', [NocController::class, 'storeOlt'])->name('olt.store');
        Route::post('/olt/{kode_olt}/delete', [NocController::class, 'deleteOlt'])->name('olt.delete');
        Route::post('/olt/test-connection', [NocController::class, 'testOltConnection'])->name('olt.test-connection');
        Route::post('/olt/sync-live', [NocController::class, 'syncLiveGpon'])->name('olt.sync-live');
        Route::post('/olt/scan-uncfg', [NocController::class, 'scanUncfgOnu'])->name('olt.scan-uncfg');
        Route::get('/gpon', [NocController::class, 'gponTopology'])->name('gpon');
        Route::post('/pon/store', [NocController::class, 'storePon'])->name('pon.store');

        // 3. Infrastruktur: ODP (Optical Distribution Point)
        Route::get('/odp', [NocController::class, 'odp'])->name('odp');
        Route::post('/odp/store', [NocController::class, 'storeOdp'])->name('odp.store');

        // 4. Infrastruktur: POP (Point of Presence)
        Route::get('/pop', [NocController::class, 'pop'])->name('pop');
        Route::post('/pop/store', [NocController::class, 'storePop'])->name('pop.store');

        // 5. Infrastruktur: Wilayah Perangkat Jaringan
        Route::get('/wilayah', [NocController::class, 'wilayahPerangkat'])->name('wilayah');
        Route::post('/wilayah/store', [NocController::class, 'storeWilayahPerangkat'])->name('wilayah.store');

        // 5. Provisioning & Aktivasi Jaringan
        Route::get('/aktivasi', [NocController::class, 'aktivasi'])->name('aktivasi');
        Route::post('/aktivasi/{nomor_internet}/schedule', [NocController::class, 'storeScheduleAktivasi'])->name('aktivasi.schedule');
        Route::post('/aktivasi/{nomor_internet}/report', [NocController::class, 'storeReportAktivasi'])->name('aktivasi.report');
        Route::post('/aktivasi/{nomor_internet}/proses', [NocController::class, 'doAktivasi'])->name('aktivasi.proses');
        Route::post('/aktivasi/{nomor_internet}/pppoe-activate', [NocController::class, 'doAktivasiPPPoE'])->name('aktivasi.pppoe');

        // 6. Isolir / Suspend Layanan
        Route::get('/suspend', [NocController::class, 'suspend'])->name('suspend');
        Route::post('/suspend/{kode_suspend}/approve', [NocController::class, 'approveSuspend'])->name('suspend.approve');
        Route::post('/suspend/{kode_suspend}/cancel', [NocController::class, 'cancelSuspend'])->name('suspend.cancel');

        // 7. Terminasi Layanan
        Route::get('/terminasi', [NocController::class, 'terminasi'])->name('terminasi');
        Route::post('/terminasi/{kode_trx}/schedule', [NocController::class, 'scheduleCollect'])->name('terminasi.schedule');
        Route::post('/terminasi/{kode_trx}/cancel', [NocController::class, 'cancelTerminasi'])->name('terminasi.cancel');

        // 8. Inventaris Perangkat & Asset Jaringan
        Route::get('/perangkat', [NocController::class, 'perangkat'])->name('perangkat');
    });

    // Routes Role Finance & Direktur & Admin
    Route::middleware('role:finance,direktur,admin')->prefix('finance')->name('finance.')->group(function () {
        // 1. Billing Layanan (Recurring Monthly Invoicing)
        Route::get('/', [FinanceController::class, 'billingLayanan'])->name('index');
        Route::get('/billing-layanan', [FinanceController::class, 'billingLayanan'])->name('billing-layanan');
        Route::post('/billing-layanan/generate', [FinanceController::class, 'generateInvoice'])->name('billing-layanan.generate');
        Route::post('/billing-layanan/publish', [FinanceController::class, 'publishBillingLayanan'])->name('billing-layanan.publish.post');
        Route::post('/billing-layanan/{kode_billing}/publish', [FinanceController::class, 'publishBillingLayanan'])->name('billing-layanan.publish')->where('kode_billing', '.*');
        Route::post('/billing-layanan/generate-midtrans', [FinanceController::class, 'generateMidtransLayanan'])->name('billing-layanan.generate-midtrans.post');
        Route::post('/billing-layanan/renew-midtrans', [FinanceController::class, 'renewMidtransLayanan'])->name('billing-layanan.renew-midtrans.post');
        Route::post('/billing-layanan/konfirmasi-bayar', [FinanceController::class, 'konfirmasiBayarLayanan'])->name('billing-layanan.konfirmasi-bayar.post');
        Route::post('/billing-layanan/{kode_billing}/konfirmasi-bayar', [FinanceController::class, 'konfirmasiBayarLayanan'])->name('billing-layanan.konfirmasi-bayar')->where('kode_billing', '.*');
        Route::post('/billing-layanan/adjust', [FinanceController::class, 'adjustBillingLayanan'])->name('billing-layanan.adjust.post');
        Route::post('/billing-layanan/{kode_billing}/adjust', [FinanceController::class, 'adjustBillingLayanan'])->name('billing-layanan.adjust')->where('kode_billing', '.*');
        Route::post('/billing-layanan/rollback', [FinanceController::class, 'rollbackBillingLayanan'])->name('billing-layanan.rollback.post');
        Route::post('/billing-layanan/{kode_billing}/rollback', [FinanceController::class, 'rollbackBillingLayanan'])->name('billing-layanan.rollback')->where('kode_billing', '.*');
        Route::post('/billing-layanan/change-payment-method', [FinanceController::class, 'changePaymentMethodLayanan'])->name('billing-layanan.change-payment-method.post');
        Route::post('/billing-layanan/{kode_billing}/change-payment-method', [FinanceController::class, 'changePaymentMethodLayanan'])->name('billing-layanan.change-payment-method')->where('kode_billing', '.*');
        Route::get('/billing-layanan/export', [FinanceController::class, 'exportBillingLayanan'])->name('billing-layanan.export');
        Route::get('/api/billing-layanan-detail', [FinanceController::class, 'getBillingLayananDetail'])->name('billing-layanan.detail.query');
        Route::get('/api/billing-layanan/{kode_billing}', [FinanceController::class, 'getBillingLayananDetail'])->name('billing-layanan.detail')->where('kode_billing', '.*');

        // 2. Billing Registrasi (Tagihan Pasang Baru)
        Route::get('/billing-registrasi', [FinanceController::class, 'billingRegistrasi'])->name('billing-registrasi');
        Route::post('/billing-registrasi/publish', [FinanceController::class, 'publishBillingRegistrasi'])->name('billing-registrasi.publish.post');
        Route::post('/billing-registrasi/{kode_billing}/publish', [FinanceController::class, 'publishBillingRegistrasi'])->name('billing-registrasi.publish')->where('kode_billing', '.*');
        Route::post('/billing-registrasi/generate-midtrans', [FinanceController::class, 'generateMidtransRegistrasi'])->name('billing-registrasi.generate-midtrans.post');
        Route::post('/billing-registrasi/renew-midtrans', [FinanceController::class, 'renewMidtransRegistrasi'])->name('billing-registrasi.renew-midtrans.post');
        Route::post('/billing-registrasi/konfirmasi-bayar', [FinanceController::class, 'konfirmasiBayarRegistrasi'])->name('billing-registrasi.konfirmasi-bayar.post');
        Route::post('/billing-registrasi/{kode_billing}/konfirmasi-bayar', [FinanceController::class, 'konfirmasiBayarRegistrasi'])->name('billing-registrasi.konfirmasi-bayar')->where('kode_billing', '.*');
        Route::post('/billing-registrasi/change-payment-method', [FinanceController::class, 'changePaymentMethodRegistrasi'])->name('billing-registrasi.change-payment-method.post');
        Route::post('/billing-registrasi/{kode_billing}/change-payment-method', [FinanceController::class, 'changePaymentMethodRegistrasi'])->name('billing-registrasi.change-payment-method')->where('kode_billing', '.*');
        Route::get('/billing-registrasi/export', [FinanceController::class, 'exportBillingRegistrasi'])->name('billing-registrasi.export');
        Route::get('/api/billing-registrasi-detail', [FinanceController::class, 'getBillingRegistrasiDetail'])->name('billing-registrasi.detail.query');

        // 3. Master Paket Internet & Layanan Bandwidth (Redirect to Admin)
        Route::get('/paket', fn() => redirect()->route('admin.paket'))->name('paket');
        Route::post('/paket/store', [AdminController::class, 'storePaket'])->name('paket.store');
        Route::post('/paket/{kode_bandwith}/delete', [AdminController::class, 'deletePaket'])->name('paket.delete')->where('kode_bandwith', '.*');

        // 4. Permintaan ke NOC: UP / Downgrade Bandwidth Layanan
        Route::get('/permintaan/up-downgrade', [FinanceController::class, 'upDowngrade'])->name('permintaan.up-downgrade');
        Route::post('/permintaan/up-downgrade', [FinanceController::class, 'storeUpDowngrade'])->name('permintaan.up-downgrade.store');
        Route::post('/permintaan/up-downgrade/{kode_trx}/cancel', [FinanceController::class, 'cancelUpDowngrade'])->name('permintaan.up-downgrade.cancel')->where('kode_trx', '.*');

        // 4. Permintaan ke NOC: Suspend Layanan (Jatuh Tempo / Tunggakan)
        Route::get('/permintaan/suspend', [FinanceController::class, 'suspend'])->name('permintaan.suspend');
        Route::post('/permintaan/suspend', [FinanceController::class, 'storeSuspend'])->name('permintaan.suspend.store');
        Route::post('/permintaan/suspend/{kode_suspend}/unsuspend', [FinanceController::class, 'requestUnsuspend'])->name('permintaan.suspend.unsuspend')->where('kode_suspend', '.*');
        Route::post('/permintaan/suspend/{kode_suspend}/cancel', [FinanceController::class, 'cancelSuspend'])->name('permintaan.suspend.cancel')->where('kode_suspend', '.*');

        // 5. Permintaan ke NOC: Terminasi Layanan (Berhenti Berlangganan)
        Route::get('/permintaan/terminasi', [FinanceController::class, 'terminasi'])->name('permintaan.terminasi');
        Route::post('/permintaan/terminasi', [FinanceController::class, 'storeTerminasi'])->name('permintaan.terminasi.store');
        Route::post('/permintaan/terminasi/{kode_trx}/cancel', [FinanceController::class, 'cancelTerminasi'])->name('permintaan.terminasi.cancel')->where('kode_trx', '.*');

        // 6. API Helpers
        Route::get('/api/pelanggan-search', [FinanceController::class, 'apiPelangganSearch'])->name('api.pelanggan-search');
    });
});

