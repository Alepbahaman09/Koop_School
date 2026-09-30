<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $resetLinkSent = false;
    public string $resetMessage = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        $admin = Auth::user();
        $admin->update([
            'password' => Hash::make($validated['password']),
        ]);

        if ($admin->auth_user_id) {
            try {
                app(\App\Services\SupabaseAuth::class)->updateAdminPasswordByAdminId(
                    $admin->auth_user_id,
                    $validated['password']
                );
            } catch (\Throwable $e) {
                Log::warning('Unable to sync password with Supabase Auth: ' . $e->getMessage());
            }
        }

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }

    /**
     * Send a password reset link to the admin's registered email address.
     */
    public function sendPasswordResetEmail(): void
    {
        $admin = Auth::user();

        if (! $admin || ! $admin->email) {
            return;
        }

        // 1. Generate secure token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $admin->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now(),
            ]
        );

        // 2. Build reset URL
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $admin->email,
        ]);

        // 3. Send email via Gmail SMTP
        try {
            Mail::send('emails.password-reset', [
                'admin' => $admin,
                'resetUrl' => $resetUrl,
            ], function ($message) use ($admin) {
                $message->to($admin->email, $admin->name)
                    ->subject('Reset Your Password - ' . config('app.name', 'Koop School'));
            });

            $this->resetLinkSent = true;
            $this->resetMessage = 'A password reset link has been sent to your registered email (' . $admin->email . '). Please check your inbox!';
        } catch (\Throwable $e) {
            Log::error('Failed to send reset email from settings: ' . $e->getMessage());
            $this->addError('current_password', 'Failed to send reset email: ' . $e->getMessage());
        }
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    @if ($resetLinkSent)
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
            {{ $resetMessage }}
        </div>
    @endif

    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <x-text-input wire:model="current_password" id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <x-text-input wire:model="password" id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <button type="button" wire:click="sendPasswordResetEmail" wire:loading.attr="disabled" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 hover:underline">
                <span wire:loading.remove wire:target="sendPasswordResetEmail">{{ __('Forgot Password?') }}</span>
                <span wire:loading wire:target="sendPasswordResetEmail">{{ __('Sending link...') }}</span>
            </button>

            <x-action-message class="ms-auto me-3" on="password-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
