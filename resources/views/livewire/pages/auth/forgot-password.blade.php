<?php

use App\Models\Admin;
use App\Services\SupabaseAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->email = Str::lower(trim($this->email));
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $adminExists = Admin::query()
            ->whereRaw('LOWER(email) = ?', [$this->email])
            ->exists();

        if ($adminExists) {
            try {
                app(SupabaseAuth::class)->sendPasswordResetLink(
                    $this->email,
                    route('password.reset'),
                );
            } catch (Throwable $error) {
                Log::error('Unable to send an admin password reset email through Supabase.', [
                    'email_hash' => hash('sha256', $this->email),
                    'exception' => $error,
                ]);
            }
        }

        $this->reset('email');
        session()->flash(
            'status',
            __('If an administrator account exists for that email, a password reset link has been sent.'),
        );
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Enter your administrator email and Supabase will send you a secure link to choose a new password.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</div>
