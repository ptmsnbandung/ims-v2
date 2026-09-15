<?php

namespace App\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('legacy_eloquent', function ($app, array $config) {
            return new class($app['hash'], $config['model']) extends EloquentUserProvider {
                /**
                 * Retrieve a user by the given credentials (supports username with or without @ptmsn.co.id domain).
                 */
                public function retrieveByCredentials(array $credentials)
                {
                    $credentials = array_filter(
                        $credentials,
                        fn ($key) => ! str_contains($key, 'password'),
                        ARRAY_FILTER_USE_KEY
                    );

                    if (empty($credentials)) {
                        return null;
                    }

                    $query = $this->newModelQuery();

                    if (isset($credentials['username'])) {
                        $inputUser = trim($credentials['username']);
                        $shortUser = str_contains($inputUser, '@') ? explode('@', $inputUser)[0] : $inputUser;
                        $fullUser = str_contains($inputUser, '@') ? $inputUser : $inputUser . '@ptmsn.co.id';

                        $query->where(function ($q) use ($inputUser, $shortUser, $fullUser) {
                            $q->where('username', $inputUser)
                              ->orWhere('username', $shortUser)
                              ->orWhere('username', $fullUser);
                        });
                    } else {
                        foreach ($credentials as $key => $value) {
                            $query->where($key, $value);
                        }
                    }

                    return $query->first();
                }

                /**
                 * Validate a user against the given credentials.
                 */
                public function validateCredentials(Authenticatable $user, array $credentials)
                {
                    if (empty($credentials['password'])) {
                        return false;
                    }

                    $plain = $credentials['password'];
                    $hash = $user->getAuthPassword();

                    // 1. Check MD5 hash (used in legacy tb_pengguna)
                    if (md5($plain) === $hash) {
                        return true;
                    }

                    // 2. Check Laravel Hash (Bcrypt / Argon2)
                    if ($this->hasher->check($plain, $hash)) {
                        return true;
                    }

                    // 3. Check plain text match if any
                    return $plain === $hash;
                }
            };
        });
    }
}
