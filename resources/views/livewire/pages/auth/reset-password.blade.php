<?php

use App\Models\Admin;
use App\Services\SupabaseAuth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $token = '';
    public string $email = '';
    public string $accessToken = '';
    public string $linkError = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->token = (string) request()->query('token', '');
        $this->email = Str::lower(trim((string) request()->query('email', '')));
    }

    public function resetPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // Scenario A: Reset via local SMTP email token
        if ($this->token !== '' && $this->email !== '') {
            $record = DB::table('password_reset_tokens')
                ->where('email', $this->email)
                ->first();

            if (! $record || ! Hash::check($this->token, $record->token) || Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
                $this->addError('password', __('This password reset link is invalid or has expired. Please request a new one.'));
                return;
            }

            $admin = Admin::query()
                ->whereRaw('LOWER(email) = ?', [$this->email])
                ->first();

            if (! $admin) {
                $this->addError('password', __('No administrator account found for this email address.'));
                return;
            }

            // Update local password
            $admin->password = Hash::make($this->password);
            $admin->email_verified_at = $admin->email_verified_at ?? now();
            $admin->remember_token = Str::random(60);
            $admin->save();

            // Sync password to Supabase Auth if linked
            if ($admin->auth_user_id) {
                try {
                    app(SupabaseAuth::class)->updateAdminPasswordByAdminId($admin->auth_user_id, $this->password);
                } catch (\Throwable $e) {
                    Log::warning('Supabase Auth password sync on reset warning: ' . $e->getMessage());
                }
            }

            // Clean up used token
            DB::table('password_reset_tokens')->where('email', $this->email)->delete();

            Auth::guard('web')->logout();
            session()->invalidate();
            session()->regenerateToken();

            session()->flash('status', __('Your password has been successfully reset! You can now log in with your new password.'));
            $this->redirectRoute('login', navigate: true);
            return;
        }

        // Scenario B: Reset via Supabase access token hash
        if ($this->accessToken !== '') {
            try {
                $supabase = app(SupabaseAuth::class);
                $identity = $supabase->recoveryUser($this->accessToken);
                $authUserId = (string) ($identity['id'] ?? '');
                $email = Str::lower(trim((string) ($identity['email'] ?? '')));

                $admin = Admin::query()
                    ->where('auth_user_id', $authUserId)
                    ->orWhereRaw('LOWER(email) = ?', [$email])
                    ->first();

                if (! $admin || $authUserId === '' || $email === '') {
                    throw new RuntimeException('This reset link does not belong to an administrator account.');
                }

                $supabase->updatePassword($this->accessToken, $this->password);

                $admin->forceFill([
                    'auth_user_id' => $authUserId,
                    'password' => Hash::make($this->password),
                    'email_verified_at' => $admin->email_verified_at ?? now(),
                    'remember_token' => Str::random(60),
                ])->save();

                Auth::guard('web')->logout();
                session()->invalidate();
                session()->regenerateToken();

                session()->flash('status', __('Your password has been reset. You can now log in.'));
                $this->redirectRoute('login', navigate: true);
                return;
            } catch (\Throwable $error) {
                report($error);
                $this->addError('password', __($error->getMessage()));
                return;
            }
        }

        $this->addError('password', __('This password reset link is invalid or has expired. Please request a new one.'));
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Choose a new password for your administrator account.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($linkError)
        <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
            {{ $linkError }}
        </div>
    @endif

    <form wire:submit="resetPassword">
        @if ($email)
            <div class="mb-4">
                <x-input-label for="email" :value="__('Account Email')" />
                <x-text-input wire:model="email" id="email" class="block mt-1 w-full bg-slate-50 text-slate-600" type="email" name="email" readonly />
            </div>
        @endif

        <div>
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input wire:model="password" id="password" class="mt-1 block w-full"
                          type="password" required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="mt-1 block w-full"
                          type="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-5 flex items-center justify-between">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}" wire:navigate>
                {{ __('Cancel') }}
            </a>

            <x-primary-button wire:loading.attr="disabled">
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>

    @script
    <script>
        const values = new URLSearchParams(window.location.hash.slice(1));
        const accessToken = values.get('access_token');
        const errorMessage = values.get('error_description');

        if (accessToken) {
            $wire.set('accessToken', accessToken);
            window.history.replaceState({}, document.title, window.location.pathname + window.location.search);
        } else if (errorMessage) {
            $wire.set('linkError', errorMessage);
        }
    </script>
    @endscript
</div>
