<?php

use App\Livewire\Actions\Logout;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Check verification status on load.
     */
    public function mount(): void
    {
        /** @var Admin|null $user */
        $user = Auth::user();

        if ($user === null) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }
    }

    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        /** @var Admin|null $user */
        $user = Auth::user();

        if ($user === null) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Thanks for signing up! Click the button below to receive your verification email, then click the link inside to activate your administrator account.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('A verification link has been sent to your email address.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <x-primary-button wire:click="sendVerification">
            {{ __('Send Verification Email') }}
        </x-primary-button>

        <button wire:click="logout" type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            {{ __('Log Out') }}
        </button>
    </div>
</div>
