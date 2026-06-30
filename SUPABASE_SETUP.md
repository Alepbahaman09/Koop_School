# Supabase Setup

Fill these values in `.env` with the Nyan Supabase project credentials.

## Database

Use the connection string from Supabase Dashboard > Project Settings > Database.

```env
DB_CONNECTION=pgsql
DB_URL=
DB_HOST=db.your-project-ref.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-database-password
DB_SSLMODE=require
```

If your network needs the Supabase transaction pooler instead, use the pooler host, port, and username shown by Supabase. The username is usually shaped like `postgres.your-project-ref`.

## REST API

Use the project URL and API keys from Supabase Dashboard > Project Settings > API.

```env
SUPABASE_URL=https://your-project-ref.supabase.co
SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key
SUPABASE_DEFAULT_SCHEMA=public
```

Keep `SUPABASE_SERVICE_ROLE_KEY` server-only. Do not expose it to browser JavaScript or mobile apps.

## Check The Connection

After filling `.env`, run:

```bash
php artisan supabase:check
```

To test the REST API with the anon key:

```bash
php artisan supabase:check --anon
```
