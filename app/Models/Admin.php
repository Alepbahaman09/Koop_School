<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

#[Fillable(['auth_user_id', 'name', 'email', 'password', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class Admin extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Send the email verification notification using our branded email template.
     */
    public function sendEmailVerificationNotification(): void
    {
        $lockKey = 'verify_email_sent_' . $this->id;

        // Prevent duplicate emails from being dispatched within 60 seconds
        if (cache()->has($lockKey)) {
            Log::info('Suppressed duplicate verification email for admin: ' . $this->id);
            return;
        }

        cache()->put($lockKey, true, now()->addSeconds(60));

        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ]
        );

        try {
            Mail::send('emails.verify-email', [
                'admin' => $this,
                'verifyUrl' => $verifyUrl,
            ], function ($message) {
                $message->to($this->email, $this->name)
                    ->subject('Verify Your Email Address - ' . config('app.name', 'KooPik Admin'));
            });
        } catch (\Throwable $e) {
            cache()->forget($lockKey);
            Log::error('Failed to send admin verification email: ' . $e->getMessage(), [
                'admin_id' => $this->id,
                'email' => $this->email,
            ]);
        }
    }
}
