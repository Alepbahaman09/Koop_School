<?php

use App\Models\Admin;
use App\Services\SupabaseAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $accessToken = '';
    public string $linkError = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function resetPassword(): void
    {
        $this->validate([
            'accessToken' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'accessToken.required' => __('This password reset link is invalid or has expired.'),
        ]);

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
                'password' => $this->password,
                'email_verified_at' => $admin->email_verified_at ?? now(),
                'remember_token' => Str::random(60),
            ])->save();

            // Do not let a stale authenticated session redirect the browser
            // away from the login page after the password has changed.
            Auth::guard('web')->logout();
            session()->invalidate();
            session()->regenerateToken();
        } catch (Throwable $error) {
            report($error);
            $this->addError('accessToken', __($error->getMessage()));

            return;
        }

        session()->flash('status', __('Your password has been reset. You can now log in.'));
        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Choose a new password for your administrator account.') }}
    </div>

    @if ($linkError)
        <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
            {{ $linkError }}
        </div>
    @endif

    <form wire:submit="resetPassword">
        <x-input-error :messages="$errors->get('accessToken')" class="mb-4" />

        <div>
            <x-input-label for="password" :value="__('New password')" />
            <x-text-input wire:model="password" id="password" class="mt-1 block w-full"
                          type="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm new password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="mt-1 block w-full"
                          type="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-4 flex items-center justify-end">
            <x-primary-button wire:loading.attr="disabled">
                {{ __('Reset password') }}
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
            window.history.replaceState({}, document.title, window.location.pathname);
        } else {
            $wire.set(
                'linkError',
                errorMessage || 'This password reset link is invalid or has expired.',
            );
        }
    </script>
    @endscript
</div>
