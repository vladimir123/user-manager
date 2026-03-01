# User Manager

A full-stack Laravel 12 + Vue 3 application to import, store, and manage users from the [randomuser.me](https://randomuser.me) API.

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3, Laravel 12 |
| Database | PostgreSQL 16 |
| Frontend | Vue 3 + Inertia.js + Vuetify 3 |
| Build tool | Vite (HMR in Docker) |
| Container | Docker + Docker Compose |

## Features

- 📥 Import 50 users from [randomuser.me API v1.4](https://randomuser.me/api/?results=50)
- 🗄️ Stores data in 3 related tables: `users`, `contacts`, `addresses`
- ✏️ Full CRUD (with contact & address in a tabbed form)
- ☑️ Bulk select & delete users
- 🔍 Live search + pagination
- 🔄 Re-import from UI (upserts by email — safe to run multiple times)
- 🌙 Dark / Light theme toggle
- 🐳 Docker dev environment with fully automated setup + Vite HMR

---

## Quick Start — Docker (recommended)

### 1. Clone and configure

```bash
git clone <repo-url>
cd user-manager
cp .env.example .env      # copy the config template
```

> **Optionally** edit `.env` to change credentials or ports before first launch.

### 2. Start

```bash
docker compose -f docker-compose.dev.yml up
```

On first start the container automatically:
1. Installs Composer & npm dependencies
2. Creates `.env` from `.env.example` if it doesn't exist
3. Patches `.env` with Docker-specific values (`DB_HOST=postgres`, file-based cache/sessions)
4. Generates `APP_KEY` (skipped if already set)
5. Waits for PostgreSQL to be ready
6. Runs `php artisan migrate`
7. Starts **Vite dev server** (HMR on port 5173)
8. Starts **Laravel** on port 8000

Open **[http://localhost:8000](http://localhost:8000)**

### 3. Import users

Click **Import** in the top bar, or via CLI:

```bash
docker compose -f docker-compose.dev.yml exec app php artisan users:import
```

---

## Configuration (`.env`)

All Docker settings are driven by a single `.env` file — no separate docker-specific config needed.

| Variable | Default | Description |
|---|---|---|
| `DB_HOST` | `postgres` | Docker service name — **don't change** |
| `DB_PORT` | `5432` | Also used as the host-side port for pgAdmin |
| `DB_DATABASE` | `user_manager` | PostgreSQL database name |
| `DB_USERNAME` | `postgres` | PostgreSQL user |
| `DB_PASSWORD` | `postgres` | PostgreSQL password |

> **pgAdmin / TablePlus:** connect to `localhost:5432` (or whatever `DB_PORT` is set to).
>
> If you have a local PostgreSQL already running on port 5432, change `DB_PORT=5433` in `.env` before starting.

---

## Local Setup (without Docker)

**Requirements:** PHP 8.3 + `pgsql` extension, PostgreSQL, Node.js 18+

```bash
git clone <repo-url>
cd user-manager

composer install
npm install

cp .env.example .env
# Edit .env: set DB_HOST=127.0.0.1, DB_DATABASE, DB_USERNAME, DB_PASSWORD

php artisan key:generate
php artisan migrate
```

**Run (two terminals):**
```bash
# Terminal 1
npm run dev

# Terminal 2
php artisan serve
```

Open **[http://localhost:8000](http://localhost:8000)**

---

## Database Schema

```
users
  ├── id, external_id (login.uuid from API), email, password
  ├── name, first_name, last_name, username, gender
  ├── date_of_birth, nationality
  └── picture_large, picture_thumbnail

contacts (user_id FK)
  ├── phone
  └── cell

addresses (user_id FK)
  ├── street_number, street_name, city, state
  ├── postcode, country
  └── latitude, longitude
```

---

## Project Structure

```
user-manager/
├── app/
│   ├── Console/Commands/ImportUsersCommand.php   # php artisan users:import
│   ├── Http/Controllers/
│   │   ├── UserController.php                    # CRUD + bulk delete
│   │   └── ImportController.php                  # POST /import
│   ├── Models/                                   # User, Contact, Address
│   └── Services/RandomUserService.php            # API fetch + upsert logic
├── database/migrations/
├── resources/js/
│   ├── app.js                                    # Vue + Vuetify + Inertia + Ziggy
│   ├── Layouts/AppLayout.vue
│   ├── Pages/Users/                              # Index (with bulk select), Create, Edit
│   └── Components/ImportModal.vue
├── routes/web.php
├── Dockerfile.dev                                # Dev image
├── docker-compose.dev.yml                        # Dev stack
├── .env.example                                  # Config template (Docker-ready defaults)
└── docker/
    └── dev-entrypoint.sh                         # Auto-setup script
```

---

## Docker Commands

```bash
# Start (foreground — shows logs)
docker compose -f docker-compose.dev.yml up

# Start in background
docker compose -f docker-compose.dev.yml up -d

# View logs
docker compose -f docker-compose.dev.yml logs -f app

# Import users
docker compose -f docker-compose.dev.yml exec app php artisan users:import

# Run any artisan command
docker compose -f docker-compose.dev.yml exec app php artisan <command>

# Rebuild image (required after changes to Dockerfile.dev or docker/dev-entrypoint.sh)
docker compose -f docker-compose.dev.yml build app

# Stop
docker compose -f docker-compose.dev.yml down

# Reset database (destroys all data)
docker compose -f docker-compose.dev.yml down -v
```
