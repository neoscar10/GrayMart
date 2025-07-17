<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return redirect()->intended('/admin/dashboard');
        }

        if ($user->role === 'vendor') {
            return redirect()->intended('/vendor/dashboard');
        }

        return redirect()->intended('/dashboard');
    }
}
