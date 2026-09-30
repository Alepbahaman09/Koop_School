<?php

use App\Models\Admin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided registered email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->email = Str::lower(trim($this->email));
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $admin = Admin::query()
            ->whereRaw('LOWER(email) = ?', [$this->email])
            ->first();

        if ($admin) {
            // 1. Generate a secure reset token and store in password_reset_tokens
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $admin->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => Carbon::now(),
                ]
            );

            // 2. Build the password reset URL
            $resetUrl = route('password.reset', [
                'token' => $token,
                'email' => $admin->email,
            ]);

            // 3. Send the password reset email directly via Gmail SMTP
            try {
                Mail::send('emails.password-reset', [
                    'admin' => $admin,
                    'resetUrl' => $resetUrl,
                ], function ($message) use ($admin) {
                    $message->to($admin->email, $admin->name)
                        ->subject('Reset Password Notification - ' . config('app.name', 'Koop School'));
                });
            } catch (\Throwable $error) {
                Log::error('Unable to send password reset email via SMTP.', [
                    'email' => $admin->email,
                    'error' => $error->getMessage(),
                ]);
            }
        }

        $this->reset('email');
        session()->flash(
            'status',
            __('If an administrator account exists for that email, a password reset link has been sent to your email inbox.'),
        );
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Enter your registered administrator email address and we will email you a secure link to reset your password.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Registered Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus placeholder="admin@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-5">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}" wire:navigate>
                {{ __('Back to login') }}
            </a>

            <x-primary-button>
                {{ __('Send Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</div>
