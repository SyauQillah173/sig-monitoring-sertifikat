<x-layouts::auth :title="__('Email verification')">
    <div class="mt-4 flex flex-col gap-6">
        <div class="ui-panel-muted p-5 text-center text-sm leading-6 text-slate-600">
            {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
        </div>

        @if (session('status') == 'verification-link-sent')
            <p class="ui-flash border-emerald-200 bg-emerald-50 text-center font-medium text-emerald-700">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </p>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full !rounded-full !bg-slate-950 !py-3.5 !text-sm !font-semibold hover:!bg-teal-700">
                    {{ __('Resend verification email') }}
                </flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button variant="ghost" type="submit" class="cursor-pointer !rounded-full !px-5 !py-2.5 !text-sm !font-semibold !text-slate-600 hover:!bg-slate-100 hover:!text-slate-950" data-test="logout-button">
                    {{ __('Log out') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>
