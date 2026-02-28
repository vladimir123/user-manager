# User Manager

A full-stack Laravel 12 + Vue 3 application to import, store, and manage users from the [randomuser.me](https://randomuser.me) API.

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3, Laravel 12 |
| Database | PostgreSQL 16 |
| Frontend | Vue 3 + Inertia.js + Vuetify 3 |
| Build tool | Vite |
| Container | Docker + Docker Compose |

## Features

- 📥 Import 50 users from [randomuser.me API v0.8](https://randomuser.me/api/0.8/?results=50)
- 🗄️ Stores data in 3 related tables: `users`, `contacts`, `addresses`
- ✏️ Full CRUD for users (with contact & address managed in tabbed form)
- 🔍 Live search + pagination on the users table
- 🔄 Re-import button in the UI (Import modal)
- 🌙 Dark / Light theme toggle
- 🐳 Docker dev environment with auto-setup

---

## Quick Start

### Option A — Docker (recommended, no manual installs)

```bash
git clone <repo-url>
cd user-manager

docker compose -f docker-compose.dev.yml up
```

On first start the container automatically:
1. Installs Composer dependencies
2. Installs npm packages
3. Generates `APP_KEY` in `.env`
4. Waits for PostgreSQL to be healthy
5. Runs `php artisan migrate`
6. Builds frontend assets (`npm run build`)
7. Starts `php artisan serve` on **port 8000**

Open **http://localhost:8000** then import data:

```bash
docker compose -f docker-compose.dev.yml exec app php artisan users:import
```

> PostgreSQL in this stack runs on port **5433** to avoid conflicts with any local postgres on 5432.

### Environment Variables in Docker

`docker-compose.dev.yml` reads variables directly from your `.env` file — no need to duplicate them:

```yaml
env_file:
  - .env          # All variables from .env are passed into the container
environment:
  DB_HOST: postgres   # Only the host is overridden (docker service name instead of 127.0.0.1)
```

The postgres container also uses your `.env` values:
```yaml
POSTGRES_DB:       ${DB_DATABASE:-user_manager}
POSTGRES_USER:     ${DB_USERNAME:-laravel}
POSTGRES_PASSWORD: ${DB_PASSWORD:-secret}
```

So just edit your `.env` once — Docker picks it up automatically.

---

### Option B — Local (requires PHP 8.3 + PostgreSQL + Node.js)

**Prerequisites:**
```bash
# PHP pgsql extension (if not installed)
sudo apt-get install php8.3-pgsql
```

**Setup:**
```bash
git clone <repo-url>
cd user-manager

composer install
npm install

cp .env.example .env
# Edit .env — set DB_CONNECTION=pgsql, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

php artisan key:generate
php artisan migrate
```

**Run (two terminals):**
```bash
# Terminal 1 — Vite dev server
npm run dev

# Terminal 2 — Laravel dev server
php artisan serve
```

Open **http://localhost:8000**

---

## Data Import

### Via Artisan command (CLI)
```bash
# Import 50 users (default)
php artisan users:import

# Import a custom number
php artisan users:import --count=100
```

### Via UI
Click the **Import** button in the top bar → confirm in the modal.

Re-importing is safe — existing users are matched by email and updated, new ones are created.

---

## Database Schema

```
users
  ├── id, external_id, name, email, password
  ├── first_name, last_name, username, gender
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
│   │   ├── UserController.php                    # CRUD
│   │   └── ImportController.php                  # POST /import
│   ├── Models/                                   # User, Contact, Address
│   └── Services/RandomUserService.php            # API fetch + upsert logic
├── database/migrations/                          # 5 migration files
├── resources/js/
│   ├── app.js                                    # Vue + Vuetify + Inertia + Ziggy
│   ├── Layouts/AppLayout.vue                     # Nav drawer + theme toggle
│   ├── Pages/Users/                              # Index, Create, Edit
│   └── Components/ImportModal.vue
├── routes/web.php
├── Dockerfile                                    # Production image (PHP-FPM + Nginx)
├── Dockerfile.dev                                # Dev image (php artisan serve)
├── docker-compose.yml                            # Production stack
├── docker-compose.dev.yml                        # Development stack (auto-setup)
└── docker/
    ├── nginx/default.conf
    └── dev-entrypoint.sh                         # Auto-install + migrate + serve
```

---

## Docker Commands

```bash
# Start dev environment
docker compose -f docker-compose.dev.yml up

# Start in background
docker compose -f docker-compose.dev.yml up -d

# Import users
docker compose -f docker-compose.dev.yml exec app php artisan users:import

# Run any artisan command
docker compose -f docker-compose.dev.yml exec app php artisan <command>

# Stop
docker compose -f docker-compose.dev.yml down

# Reset database (destroy volumes)
docker compose -f docker-compose.dev.yml down -v
```
