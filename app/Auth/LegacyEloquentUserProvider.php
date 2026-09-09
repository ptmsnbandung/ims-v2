<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class LegacyEloquentUserProvider extends EloquentUserProvider
{
    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
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
}
