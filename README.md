# Tasks

A personal task management app: add tasks, schedule them, track progress on
them in real time, mark them done, and set them up to recur automatically.
Tasks can be tagged with colored **flags** for filtering, and every task
keeps a history of past occurrences when it recurs.

## What it does

- **Tasks** — quick-add a task, schedule it, edit its label/description
  inline, mark it complete, delete it. The task list auto-saves as you type
  (debounced) and shows a live "Saved!" confirmation.
- **Flags** — create colored tags (e.g. "Work", "Urgent") and attach several
  to a task; filter the task list by one or more flags.
- **Recurrence** — attach a recurrence rule to a task so a new occurrence is
  automatically scheduled after the current one is completed; each task
  shows its upcoming and previous occurrences.
- **Progress tracking** — start/stop a live "in progress" timer on a task
  (`TasksProgression`), and see a completion progress bar for the day.
- **Completed tasks view** — browse completed tasks by day/week/month.
- **Export** — export the currently visible (filtered) task list as plain
  text, copied to the clipboard.
- **Teams & auth** — built on Laravel Jetstream: registration/login, email
  verification, two-factor auth, teams with invitations, profile & API
  token management, GitHub/Google social login.

## How it's built

- **Backend**: [Laravel 12](https://laravel.com) (PHP 8.4), with
  [Jetstream](https://jetstream.laravel.com) (Livewire-free / Inertia
  stack) for auth, teams and account management, and
  [Sanctum](https://laravel.com/docs/sanctum) for API session auth.
- **Frontend**: [Inertia.js](https://inertiajs.com) + [Vue 3](https://vuejs.org)
  (`<script setup>` SFCs) — server-routed pages, no separate REST/SPA
  boundary for page navigation. The task list itself talks to a small JSON
  API (`routes/api.php`) via `axios` for live editing without full page
  reloads.
- **Styling**: [Tailwind CSS](https://tailwindcss.com) with
  [daisyUI](https://daisyui.com) components, a small brand color system
  defined in `tailwind.config.js` (navy + green accent), and shared Vue
  components in `resources/js/Components`.
- **Build tooling**: [Vite](https://vitejs.dev) (`laravel-vite-plugin`),
  including an SSR build for Inertia server-side rendering.
- **Realtime**: [Laravel Reverb](https://reverb.laravel.com) (WebSocket
  server) + `laravel-echo`/`pusher-js` on the frontend for broadcast
  events.
- **Database**: MySQL by default via Docker (see `docker-compose.yml`), or
  SQLite for a zero-dependency local setup (see below). Key models:
  `Task`, `Flag`, `Recurrence`, `TasksProgression`, plus Jetstream's
  `Team`/`Membership`/`TeamInvitation`/`User`.
- **Tests**: PHPUnit (`tests/Feature`, `tests/Unit`).

### Project layout

```
app/                  Models, Http controllers (web + api/), services, jobs
resources/js/
  Pages/               Inertia pages (Tasks, Flags, Dashboard, Auth, Teams, ...)
  Pages/Tasks/Partials/ Task row, flag filter, modals (delete/reschedule/complete)
  Components/          Shared UI building blocks (buttons, inputs, modals, ...)
  Layouts/             AppLayout (authenticated shell), GuestLayout
routes/
  web.php              Inertia page routes
  api.php              JSON API used by the Vue pages (axios)
database/migrations/   Schema
```

## Running it locally

You need `APP_KEY` set and a database migrated either way. Copy the env
file first if you don't have one:

```bash
cp .env.example .env
```

### Option A — Docker (Laravel Sail, recommended)

This matches `docker-compose.yml`: a PHP 8.4 app container + MySQL, with
ports for the app, Vite dev server, and Reverb already mapped.

```bash
# install PHP deps via a throwaway container, then bring the stack up
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
    laravelsail/php84-composer:latest composer install --ignore-platform-reqs

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

In `.env`, set `DB_CONNECTION=mysql` with `DB_HOST=mysql`, `DB_DATABASE`,
`DB_USERNAME`, `DB_PASSWORD` matching `docker-compose.yml`. The app is then
served at **http://localhost**.

### Option B — Native PHP/Node (SQLite, no Docker)

Fastest way to get a local copy running without containers.

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite   # DB_CONNECTION=sqlite is the .env.example default
php artisan migrate --seed

composer dev
```

`composer dev` runs the app server, queue worker, log tailer (`pail`) and
the Vite dev server together. Equivalent to running these separately:

```bash
php artisan serve
php artisan queue:listen --tries=1
npm run dev
```

The app is then served at **http://localhost:8000** (or wherever
`php artisan serve` binds).

### Useful commands

```bash
php artisan test        # run the PHPUnit test suite
npm run build            # production build (client + SSR bundles)
php artisan migrate:fresh --seed   # reset the database
```

The seeder creates a test user: `test@example.com` / `password`.
