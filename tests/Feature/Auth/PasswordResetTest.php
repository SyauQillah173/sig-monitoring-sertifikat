<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_code_request_screen_can_be_rendered(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_reset_password_code_entry_screen_has_visible_code_field(): void
    {
        $this->get(route('password.code', ['email' => 'admin@example.com']))
            ->assertOk()
            ->assertSee('Kode 6 digit dari email')
            ->assertSee('data-reset-code-input', false);
    }

    public function test_reset_password_code_can_be_requested_for_registered_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'registered@example.com']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.code', ['email' => $user->email]))
            ->assertSessionHas('status');

        Mail::assertSent(PasswordResetCodeMail::class, fn (PasswordResetCodeMail $mail) => $mail->user->is($user)
            && strlen($mail->code) === 6);
    }

    public function test_reset_password_code_rejects_unregistered_email(): void
    {
        Mail::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_reset_password_screen_requires_verified_code(): void
    {
        $this->get(route('password.reset', ['email' => 'registered@example.com']))
            ->assertRedirect(route('password.code', ['email' => 'registered@example.com']));
    }

    public function test_password_can_be_reset_with_valid_code(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'resetme@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $this->post(route('password.email'), ['email' => $user->email]);

        Mail::assertSent(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) use ($user) {
            $this->post(route('password.code.verify'), [
                'email' => $user->email,
                'code' => $mail->code,
            ])->assertRedirect(route('password.reset', ['email' => $user->email]));

            $this->get(route('password.reset', ['email' => $user->email]))
                ->assertOk()
                ->assertSee('Buat password baru');

            $this->post(route('password.update'), [
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_invalid_reset_code_is_rejected(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'resetme@example.com']);

        $this->post(route('password.email'), ['email' => $user->email]);

        $this->from(route('password.code', ['email' => $user->email]))
            ->post(route('password.code.verify'), [
                'email' => $user->email,
                'code' => '000000',
            ])
            ->assertRedirect(route('password.code', ['email' => $user->email]))
            ->assertSessionHasErrors('code');
    }
}
