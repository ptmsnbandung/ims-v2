<?php

namespace App\Http\Middleware;

use App\Models\Pengguna;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var Pengguna|null $user */
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isActive()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.',
            ]);
        }

        if (empty($roles)) {
            return $next($request);
        }

        if (! $user->hasRole($roles)) {
            abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
        }

        // Strict read-only guard for CEO: prevent any state mutation
        if ($user->isCeo() && !in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            if (!$request->routeIs('logout') && !$request->routeIs('profile.password')) {
                if ($request->expectsJson() || $request->isXmlHttpRequest()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Akses Ditolak: Akun CEO hanya memiliki hak akses pemantauan (Read-Only) dan tidak dapat melakukan perubahan data.',
                    ], 403);
                }

                abort(403, 'Akses Ditolak: Akun CEO hanya memiliki hak akses pemantauan (Read-Only) dan tidak dapat melakukan perubahan data.');
            }
        }

        return $next($request);
    }
}
