<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated admin or user's email address as verified.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        // Find the admin or mobile user corresponding to this verification link
        $user = Admin::find($id) ?? User::find($id);

        if (! $user) {
            abort(404, 'Account not found for verification.');
        }

        abort_unless(hash_equals($hash, sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        // Auto login if verifying an admin account and not currently authenticated
        if ($user instanceof Admin && ! Auth::check()) {
            Auth::login($user);
        }

        session()->flash('success', 'Your email address has been verified successfully!');

        return redirect()->route('dashboard', ['verified' => 1]);
    }
}
