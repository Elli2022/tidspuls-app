# Deployment Guide

## Architecture

- Frontend (`timetjek-frontend`) deploys on Netlify.
- Backend (Laravel API) deploys on Render.
- Database: MySQL/MariaDB (Render MySQL-compatible provider or external managed DB).

## 1) Deploy Backend on Render

1. Sign up at [Render](https://render.com/) with GitHub.
2. Create a **New Web Service** from this repository.
3. Select **Docker** as runtime/language (uses `Dockerfile` in repo root).
4. Set missing environment variables in Render:
   - `APP_URL` (your Render service URL)
   - `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
5. Deploy service.
6. Confirm health endpoint works: `https://<render-url>/up`.
7. Confirm API health endpoint works: `https://<render-url>/api/v1/health`.

## 2) Deploy Frontend on Netlify

1. Sign up at [Netlify](https://www.netlify.com/) with GitHub.
2. Import this repository as a new Netlify site.
3. Netlify uses `netlify.toml` from repo root.
4. **Environment variable priority:** values in the Netlify UI **override** `[build.environment]` in `netlify.toml`. If you ever set `VITE_API_BASE_URL=https://YOUR_RENDER_BACKEND_URL/api/v1` in the UI, the live bundle will keep calling that fake host until you change or delete that variable and redeploy.
5. Set `VITE_API_BASE_URL=https://<render-url>/api/v1` in Netlify **or** rely on the URL committed in `netlify.toml` after removing any conflicting UI variable.
6. Deploy (production builds fail fast if `VITE_API_BASE_URL` still contains `YOUR_RENDER_BACKEND_URL`).

## 3) Post-Deploy checks

- Login with personnummer + password.
- Register a user.
- Clock in/out from mobile browser and verify GPS values are saved.
- Edit and delete time entries.
- Verify overlap validation blocks invalid records.

## Notes on Free Tiers

- Netlify and Render usually provide free tiers, but quotas and sleep behavior can change.
- Always check each provider's current pricing page before production usage.
