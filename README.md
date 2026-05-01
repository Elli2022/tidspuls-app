# Tidspuls App

Fullstack monorepo for a time-tracking demo project.

- Backend: Laravel 11 (PHP 8.3), REST API, Sanctum auth.
- Frontend: Vue 3 + TypeScript + Vite.
- Database: MySQL/MariaDB compatible schema.

## Repository Structure

- `app`, `routes`, `database`, `config`, ...: Laravel backend.
- `timetjek-frontend/`: standalone Vue frontend app.
- `.github/workflows/ci.yml`: CI for tests, formatting, static analysis.
- `DEPLOYMENT.md`: Netlify + Render deployment guide.

## Features Implemented

- Login with personnummer and password.
- Password change for authenticated user.
- Clock in / clock out with optional GPS coordinates.
- List, edit and delete time entries.
- Overlap validation for time entries.
- Mobile-friendly frontend screens.

## Local Development

### Backend (Laravel)

1. Install dependencies: `composer install`
2. Configure environment: `cp .env.example .env`
3. Generate app key: `php artisan key:generate`
4. Run migrations: `php artisan migrate`
5. Start API server: `php artisan serve --port=8001`

### Frontend (Vue)

1. Go to frontend folder: `cd timetjek-frontend`
2. Install dependencies: `npm install`
3. Set env var `VITE_API_BASE_URL=http://localhost:8001/api/v1`
4. Start dev server: `npm run dev`

## Quality Checks

- Backend tests: `php artisan test`
- Backend format check: `vendor/bin/pint --test`
- Backend static analysis: `vendor/bin/phpstan analyse`
- Frontend build/typecheck: `cd timetjek-frontend && npm run build`

## Deployment

See `DEPLOYMENT.md` for:

- Backend deploy on Render
- Frontend deploy on Netlify
