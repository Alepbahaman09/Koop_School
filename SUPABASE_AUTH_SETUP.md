# Supabase authentication setup

The Flutter and Laravel code is ready, but Supabase email recovery requires a
few project-specific values that cannot be committed to source control.

## 1. Apply the database changes

Run the Laravel migration:

```bash
php artisan migrate
```

Apply the Flutter project's Supabase migrations through the Supabase CLI or SQL
Editor. The latest migration is:

```text
C:\Koopik-app\supabase\migrations\202607150017_keep_admin_identities_out_of_mobile_profiles.sql
```

## 2. Configure email password recovery

In **Supabase Dashboard > Authentication > URL Configuration**:

- Set the Site URL to the deployed admin URL.
- Add `koopik://reset-password` to Redirect URLs.
- Add `https://your-admin-domain/reset-password` to Redirect URLs.
- For local admin testing, add `http://localhost:8000/reset-password`.

In **Authentication > Email Templates > Reset password**, keep the standard
Supabase confirmation link. If the template builds its own link, use
`{{ .RedirectTo }}` instead of `{{ .SiteURL }}` so mobile and admin requests
return to the correct client.

Email/password authentication must remain enabled under
**Authentication > Providers > Email**. Configure custom SMTP before production
if reset emails should come from the school's domain.

Run the app with the recovery redirect supplied as a Dart define:

```bash
flutter run \
  --dart-define=SUPABASE_URL=https://your-project.supabase.co \
  --dart-define=SUPABASE_ANON_KEY=your-publishable-key \
  --dart-define=PASSWORD_RECOVERY_REDIRECT_URL=koopik://reset-password
```

## 3. Link administrator accounts to Supabase

Admin password recovery requires the admin to exist in both `public.admins` and
Supabase Auth. Create or update an administrator with the included command:

```bash
php artisan admin:create
```

The command now creates the Supabase Auth identity and the Laravel admin record
with the same credentials. It requires `SUPABASE_URL`, `SUPABASE_ANON_KEY`, and
`SUPABASE_SERVICE_ROLE_KEY` in the Laravel `.env` file. Never expose the service
role key to the Flutter app or frontend JavaScript.

For an existing admin, run `php artisan admin:create` again with the same email.
The command links the account through its Supabase Auth UUID and synchronizes
the chosen password.

## References

- [Supabase redirect URL configuration](https://supabase.com/docs/guides/auth/redirect-urls)
