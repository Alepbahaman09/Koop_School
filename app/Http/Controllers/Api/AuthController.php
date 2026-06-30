<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $this->normalizeEmail($request);
        $data = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', PasswordRule::min(8)->letters()->numbers()],
            'username' => 'required|string|max:255|unique:users,username',
            'phone_number' => 'nullable|string|max:32|unique:users,phone_number',
            'device_name' => 'nullable|string|max:100',
        ]);
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['username'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'username' => $data['username'],
                'phone_number' => $data['phone_number'] ?? null,
            ]);
            $user->mobileProfile()->create(['profile' => ['pinEnabled' => false]]);
            Customer::create([
                'student_id' => 'APP-'.$user->id,
                'parent_name' => $user->name,
                'student_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number ?? '-',
                'class' => '-',
                'address' => '-',
            ]);

            return $user;
        });
        $verificationSent = $this->sendVerification($user);

        return response()->json(array_merge(
            $this->tokenResponse($user, $data['device_name'] ?? 'mobile'),
            ['verificationSent' => $verificationSent],
        ), 201);
    }

    public function login(Request $request)
    {
        $this->normalizeEmail($request);
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);
        $user = User::where('email', strtolower($data['email']))->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        return response()->json($this->tokenResponse($user, $data['device_name'] ?? 'mobile'));
    }

    public function me(Request $request)
    {
        return response()->json(['success' => true, 'user' => $this->serialize($request->user())]);
    }

    public function logout(Request $request)
    {
        $request->attributes->get('api_token')?->delete();

        return response()->json(['success' => true]);
    }

    public function reauthenticate(Request $request)
    {
        $this->normalizeEmail($request);
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = $request->user();
        if (strtolower($data['email']) !== strtolower($user->email) || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => ['Invalid password.']]);
        }

        return response()->json(['success' => true]);
    }

    public function resendEmailVerification(Request $request)
    {
        if (! $request->user()->hasVerifiedEmail() && ! $this->sendVerification($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Verification email could not be sent. Please try again later.',
            ], 503);
        }

        return response()->json(['success' => true]);
    }

    public function requestEmailChange(Request $request)
    {
        $this->normalizeEmail($request);
        $data = $request->validate([
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($request->user()->id)],
        ]);
        if ($data['email'] === $request->user()->email) {
            return response()->json(['success' => true, 'user' => $this->serialize($request->user())]);
        }

        DB::transaction(function () use ($request, $data) {
            $user = $request->user();
            Customer::where('email', $user->email)->update(['email' => strtolower($data['email'])]);
            $user->update(['email' => strtolower($data['email']), 'email_verified_at' => null]);
        });
        $this->sendVerification($request->user()->refresh());

        return response()->json(['success' => true, 'user' => $this->serialize($request->user()->refresh())]);
    }

    public function destroy(Request $request)
    {
        DB::transaction(function () use ($request) {
            $user = $request->user();
            $user->apiTokens()->delete();
            $user->cards()->delete();
            Customer::where('email', $user->email)->delete();
            $user->delete();
        });

        return response()->json(['success' => true]);
    }

    public function passwordReset(Request $request)
    {
        $this->normalizeEmail($request);
        $data = $request->validate(['email' => 'required|email']);
        try {
            Password::broker('users')->sendResetLink($data);
        } catch (\Throwable $error) {
            Log::error('Unable to send password reset email.', [
                'email_hash' => hash('sha256', $data['email']),
                'exception' => $error,
            ]);
        }

        return response()->json(['success' => true]);
    }

    private function tokenResponse(User $user, string $deviceName): array
    {
        $token = Str::random(80);
        $user->apiTokens()->where('expires_at', '<', now())->delete();
        $user->apiTokens()->create([
            'name' => $deviceName,
            'token_hash' => hash('sha256', $token),
            'last_used_at' => now(),
            'expires_at' => now()->addDays((int) config('auth.api_token_lifetime_days', 30)),
        ]);

        return ['success' => true, 'token' => $token, 'user' => $this->serialize($user)];
    }

    private function serialize(User $user): array
    {
        return [
            'uid' => (string) $user->id,
            'email' => $user->email,
            'emailVerified' => $user->email_verified_at !== null,
            'username' => $user->username,
            'phoneNumber' => $user->phone_number,
        ];
    }

    private function sendVerification(User $user): bool
    {
        try {
            $user->sendEmailVerificationNotification();

            return true;
        } catch (\Throwable $error) {
            Log::error('Unable to send account verification email.', [
                'user_id' => $user->id,
                'exception' => $error,
            ]);

            return false;
        }
    }

    private function normalizeEmail(Request $request): void
    {
        if ($request->filled('email')) {
            $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        }
    }
}
