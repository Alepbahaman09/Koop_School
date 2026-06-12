<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MobileDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'username' => 'required|string|max:255|unique:users,username',
            'phone_number' => 'nullable|string|max:32|unique:users,phone_number',
        ]);
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['username'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'username' => $data['username'],
                'phone_number' => $data['phone_number'] ?? null,
                'mobile_profile' => ['pinEnabled' => false],
                'is_admin' => false,
            ]);
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
        $user->sendEmailVerificationNotification();

        return response()->json($this->tokenResponse($user), 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = User::where('email', strtolower($data['email']))->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid email or password.']]);
        }

        return response()->json($this->tokenResponse($user));
    }

    public function me(Request $request)
    {
        return response()->json(['success' => true, 'user' => $this->serialize($request->user())]);
    }

    public function logout(Request $request)
    {
        $request->user()->update(['api_token_hash' => null]);

        return response()->json(['success' => true]);
    }

    public function reauthenticate(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = $request->user();
        if (strtolower($data['email']) !== strtolower($user->email) || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => ['Invalid password.']]);
        }

        return response()->json(['success' => true]);
    }

    public function resendEmailVerification(Request $request)
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return response()->json(['success' => true]);
    }

    public function requestEmailChange(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|unique:users,email']);
        DB::transaction(function () use ($request, $data) {
            $user = $request->user();
            Customer::where('email', $user->email)->update(['email' => strtolower($data['email'])]);
            $user->update(['email' => strtolower($data['email']), 'email_verified_at' => null]);
            $user->sendEmailVerificationNotification();
        });

        return response()->json(['success' => true, 'user' => $this->serialize($request->user()->refresh())]);
    }

    public function destroy(Request $request)
    {
        DB::transaction(function () use ($request) {
            $user = $request->user();
            MobileDocument::where('path', 'like', "users/{$user->id}/%")->delete();
            Customer::where('email', $user->email)->delete();
            $user->delete();
        });

        return response()->json(['success' => true]);
    }

    public function passwordReset(Request $request)
    {
        Password::sendResetLink($request->validate(['email' => 'required|email']));

        return response()->json(['success' => true]);
    }

    private function tokenResponse(User $user): array
    {
        $token = Str::random(80);
        $user->update(['api_token_hash' => hash('sha256', $token)]);

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
}
