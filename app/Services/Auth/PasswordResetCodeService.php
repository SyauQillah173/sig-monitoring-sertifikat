<?php

namespace App\Services\Auth;

use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetCodeService
{
    public function send(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ],
        );

        Mail::to($user->email)->send(new PasswordResetCodeMail(
            $user,
            $code,
            $this->expiresInMinutes(),
        ));
    }

    public function verify(string $email, string $code): bool
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record || ! Hash::check($code, $record->token)) {
            return false;
        }

        return Carbon::parse($record->created_at)->addMinutes($this->expiresInMinutes())->isFuture();
    }

    public function delete(string $email): void
    {
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }

    public function expiresInMinutes(): int
    {
        return (int) config('auth.passwords.users.expire', 60);
    }
}
