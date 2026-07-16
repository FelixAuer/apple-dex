# Apple-Dex

A personal "Pokedex for apples" — a small, self-hosted web app for logging apple varieties you've eaten. Built with Laravel, Livewire/Volt, Tailwind CSS, and `spatie/laravel-medialibrary`. No AI, no social features, no paid third-party services.

See [`apple-dex-spec.md`](./apple-dex-spec.md) for the full product spec.

## Local setup (Laravel Sail)

Requires Docker Desktop. No local PHP/Composer/Node install needed — everything runs in containers.

```bash
# 1. Install PHP dependencies (one-off, via a throwaway Composer container)
docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html composer:2 install

# 2. Copy the environment file and generate an app key
cp .env.example .env

# 3. Start the stack (PHP 8.5 app container + MySQL 8.4)
docker compose up -d

# 4. Finish setup inside the app container
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate --seed
docker compose exec laravel.test php artisan storage:link
docker compose exec laravel.test npm install
docker compose exec laravel.test npm run build
```

The app is now available at **http://localhost**. Log in with the seeded local test account: `test@example.com` / `password`.

> **Note:** on native Windows (outside WSL2), the `./vendor/bin/sail` wrapper script refuses to run (it only supports macOS/Linux/WSL2). Use `docker compose` directly as shown above — it drives the exact same containers Sail generates in `compose.yaml`. On macOS/Linux/WSL2 you can use `./vendor/bin/sail` in place of `docker compose exec laravel.test` / `docker compose up -d` if preferred.

### Everyday commands

```bash
docker compose up -d                              # start containers
docker compose down                                # stop containers
docker compose exec laravel.test php artisan ...   # run artisan commands
docker compose exec laravel.test npm run dev       # Vite dev server (HMR)
docker compose exec laravel.test ./vendor/bin/pint # code style
```

Re-seeding is always safe — `VarietySeeder` uses `updateOrCreate` keyed on variety name, so it never creates duplicates.

## Deployment (Laravel Forge)

Standard zero-downtime Forge deploy script:

```bash
cd /home/forge/your-site.com
git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

if [ -f artisan ]; then
    $FORGE_PHP artisan migrate --force
    $FORGE_PHP artisan db:seed --class=VarietySeeder --force
    $FORGE_PHP artisan storage:link
    $FORGE_PHP artisan config:cache
    $FORGE_PHP artisan route:cache
    $FORGE_PHP artisan view:cache
fi

npm install && npm run build
```

Notes:
- `db:seed --class=VarietySeeder --force` is safe to run on every deploy — the seeder is idempotent.
- `storage:link` is safe to re-run; Forge/artisan skip it if the symlink already exists.
- No queue worker is required — the app has no queued jobs.
- Populate every key in `.env.example` on the server, in particular `APP_KEY` (generate once with `php artisan key:generate` and never regenerate on redeploy — it invalidates existing sessions and any encrypted data), `DB_*`, and `MAIL_*`.
- Serve over HTTPS (Forge handles Let's Encrypt certificates) — required for both PWA installability and the browser Geolocation API.
- No external paid services or API keys are required anywhere in this app.
