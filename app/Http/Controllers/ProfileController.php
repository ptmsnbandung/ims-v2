<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil pengguna saat ini.
     */
    public function index(): View
    {
        /** @var Pengguna $user */
        $user = Auth::user();
        $user->loadMissing(['level', 'karyawan']);

        return view('profile.index', [
            'user' => $user,
        ]);
    }

    /**
     * Perbarui kata sandi akun pengguna sendiri.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        /** @var Pengguna $user */
        $user = Auth::user();
        $plainCurrent = $request->input('current_password');
        $storedHash = (string) $user->password;

        // Verifikasi kata sandi lama (mendukung MD5, Bcrypt / Hash, dan plain text legacy)
        $isValid = (md5($plainCurrent) === $storedHash)
            || Hash::check($plainCurrent, $storedHash)
            || ($plainCurrent === $storedHash);

        if (!$isValid) {
            return redirect()->back()
                ->withErrors(['current_password' => 'Kata sandi saat ini yang Anda masukkan salah.'])
                ->withInput();
        }

        try {
            $now = now()->format('Y-m-d H:i:s');
            
            DB::table('tb_pengguna')
                ->where('kode_pengguna', $user->kode_pengguna)
                ->update([
                    'password' => md5($request->input('password')),
                    'date_update' => $now,
                    'user_update' => $now,
                ]);

            return redirect()->route('profile')
                ->with('success', 'Kata sandi Anda berhasil diperbarui! Silakan gunakan kata sandi baru untuk login berikutnya.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui kata sandi: ' . $e->getMessage())
                ->withInput();
        }
    }
}
