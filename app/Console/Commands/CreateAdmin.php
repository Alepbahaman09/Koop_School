<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create or update an administrator account';

    public function handle(): int
    {
        $name = $this->ask('Name', 'Koop School Admin');
        $email = strtolower(trim($this->ask('Email')));
        $password = $this->secret('Password');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen((string) $password) < 8) {
            $this->error('A valid email and password of at least 8 characters are required.');

            return self::FAILURE;
        }

        Admin::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $this->info("Administrator account is ready: {$email}");

        return self::SUCCESS;
    }
}
