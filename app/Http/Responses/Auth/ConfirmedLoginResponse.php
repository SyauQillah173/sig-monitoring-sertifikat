<?php

namespace App\Http\Responses\Auth;

use Illuminate\Support\Facades\Date;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class ConfirmedLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->hasSession()) {
            $request->session()->put('auth.password_confirmed_at', Date::now()->unix());
        }

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(Fortify::redirects('login'));
    }
}
