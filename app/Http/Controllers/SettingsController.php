<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index()
    {
        $admin = auth()->user();
        $settingsData = Cache::remember('app_settings.current', 60, fn () => AppSetting::current()->toArray());
        $settings = (object) $settingsData;
        $preferences = array_replace(
            AppSetting::defaultNotificationPreferences(),
            $settingsData['notification_preferences'] ?? [],
        );

        return view('settings', compact('admin', 'settings', 'preferences'));
    }

    public function update(Request $request)
    {
        $admin = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(Admin::class)->ignore($admin->id)],
            'store_name' => ['required', 'string', 'max:255'],
            'store_email' => ['nullable', 'email', 'max:255'],
            'store_phone' => ['nullable', 'string', 'max:50'],
            'store_address' => ['nullable', 'string', 'max:1000'],
            'currency' => ['required', 'in:MYR'],
            'notification_preferences' => ['array'],
            'notification_preferences.email_notifications' => ['nullable', 'boolean'],
            'notification_preferences.new_order_alerts' => ['nullable', 'boolean'],
            'notification_preferences.payment_alerts' => ['nullable', 'boolean'],
            'notification_preferences.low_stock_alerts' => ['nullable', 'boolean'],
            'current_password' => ['nullable', 'required_with:password'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if (! empty($validated['password'])) {
            if (! Hash::check((string) $validated['current_password'], $admin->password)) {
                return back()->withErrors(['current_password' => 'The current password is incorrect.'])->withInput();
            }

            $admin->password = $validated['password'];
        }

        $admin->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ])->save();

        $preferences = [];
        foreach (array_keys(AppSetting::defaultNotificationPreferences()) as $key) {
            $preferences[$key] = (bool) ($validated['notification_preferences'][$key] ?? false);
        }

        AppSetting::current()->update([
            'store_name' => $validated['store_name'],
            'store_email' => $validated['store_email'] ?? null,
            'store_phone' => $validated['store_phone'] ?? null,
            'store_address' => $validated['store_address'] ?? null,
            'currency' => $validated['currency'],
            'notification_preferences' => $preferences,
        ]);
        Cache::forget('app_settings.current');

        return back()->with('success', 'Settings updated successfully.');
    }
}
