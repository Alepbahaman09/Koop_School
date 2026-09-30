<?php

use App\Models\Admin;
use App\Services\SupabaseAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming admin registration request.
     */
    public function register(): void
    {
        $this->name = trim($this->name);
        $this->email = Str::lower(trim($this->email));

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $disallowedDomains = ['example.com', 'example.org', 'test.com', 'koop.school'];
        $domain = substr(strrchr($this->email, '@'), 1);
        if (in_array(strtolower($domain), $disallowedDomains, true)) {
            $this->addError('email', __('Please enter a valid, active email address.'));
            return;
        }

        // 1. Sync identity with Supabase Auth
        $authUserId = null;
        try {
            $supabase = app(SupabaseAuth::class);
            $authUserId = $supabase->upsertAdminIdentity($this->email, $this->password, $this->name);
        } catch (\Throwable $e) {
            Log::warning('Supabase admin identity creation notice: ' . $e->getMessage());
        }

        // 2. Create the Admin record in PostgreSQL
        $admin = Admin::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'auth_user_id' => $authUserId,
        ]);

        // 3. Log in the newly registered admin
        Auth::login($admin);

        // 5. Redirect to email verification notice page
        $this->redirect(route('verification.notice', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold text-gray-900">Create Admin Account</h2>
        <p class="text-sm text-gray-600 mt-1">Register as a new administrator for KooPik</p>
    </div>

    <form wire:submit="register">
        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered? Log in') }}
            </a>

            <x-primary-button>
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</div>
