<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Services\SupabaseAuth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {--email=} {--name=} {--password=}';

    protected $description = 'Create or promote a PostgreSQL-backed administrator account';

    public function handle(SupabaseAuth $supabaseAuth): int
    {
        $data = [
            'email' => strtolower(trim((string) ($this->option('email') ?: $this->ask('Email')))),
            'name' => trim((string) ($this->option('name') ?: $this->ask('Name'))),
            'password' => (string) ($this->option('password') ?: $this->secret('Password')),
        ];

        $validator = Validator::make($data, [
            'email' => ['required', 'email'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        try {
            $authUserId = $supabaseAuth->upsertAdminIdentity(
                $data['email'],
                $data['password'],
                $data['name'],
            );

            $admin = Admin::firstOrNew(['email' => $data['email']]);
            $admin->fill([
                'auth_user_id' => $authUserId,
                'name' => $data['name'],
                'password' => $data['password'],
                'email_verified_at' => $admin->email_verified_at ?: now(),
            ])->save();
        } catch (\Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }

        $this->info("Administrator {$admin->email} is ready.");

        return self::SUCCESS;
    }
}
