# Deployment Guide

## Architecture

- Frontend (`tidspuls-frontend`) deploys on Netlify.
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

## Felsökning: «Kunde inte ansluta till API:t» (Netlify + Render)

1. **Tom `VITE_API_BASE_URL` i Netlify:** Under *Site configuration → Environment variables*, om variabeln finns men värdet är tomt **åsidosätter den** värdet från `netlify.toml`. Ta bort variabeln helt eller sätt den till `https://<din-render-backend>/api/v1`, sedan **Trigger deploy** (ny build krävs — Vite bakar in URL vid bygget).
2. **Fel backend-URL:** Bekräfta att Render-tjänsten svarar: öppna `https://<render-host>/api/v1/health` i webbläsaren (ska ge JSON med `"success": true`).
3. **Render viloläge:** Första anrop efter paus kan ta lång tid; frontend väntar nu upp till 90 s. Prova «Logga in» igen efter en minut om det timeout:ar.
4. **Nätverk:** VPN, företags-proxy eller annonsblockering kan blockera `*.onrender.com`.

## 3) Post-Deploy checks

- Login with personnummer + password.
- Register a user.
- Clock in/out from mobile browser and verify GPS values are saved.
- Edit and delete time entries.
- Verify overlap validation blocks invalid records.

## Notes on Free Tiers

- Netlify and Render usually provide free tiers, but quotas and sleep behavior can change.
- Always check each provider's current pricing page before production usage.

## Separat backend för Tidspuls (eget Render‑API + egen DB)

Använd detta när du vill ha **en helt egen** backend för `tidspuls-app`, utan att dela databas eller tjänst med den gamla Timetjek‑deployen.

### 1) Skapa databas på Render

1. På Render: **New +** → **PostgreSQL** (enklast med nuvarande `Dockerfile`, som har `pdo_pgsql`).
2. Välj region/plan och skapa instansen.
3. När den är klar: öppna databasen och notera **Internal Database URL** eller värdena för host, port, databas, användare, lösenord.

Sätt dessa på webbtjänsten i nästa steg (Laravel):

- `DB_CONNECTION=pgsql`
- `DB_HOST` = host från Render (ofta intern hostname om API och DB ligger i samma region)
- `DB_PORT=5432`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

*(Om du hellre vill köra MariaDB/MySQL: skapa en MySQL‑kompatibel instans hos Render eller annan leverantör och sätt `DB_CONNECTION=mysql` och motsvarande variabler. Se till att migreringarna körs mot den databasen.)*

### 2) Skapa ny Web Service (API) från repot `tidspuls-app`

1. **New +** → **Web Service** → koppla GitHub‑repot **`Elli2022/tidspuls-app`** (eller ditt klonade repo).
2. **Runtime:** välj **Docker** (bygger från `Dockerfile` i root).
3. **Namn:** t.ex. `tidspuls-backend` → ger ofta publik URL  
   `https://tidspuls-backend.onrender.com`  
   (om namnet är upptaget får du ett suffix — använd då den **faktiska** URL:en överallt nedan).
4. Under **Environment** lägg minst till:

| Variabel | Kommentar |
|----------|-----------|
| `APP_NAME` | T.ex. `Tidspuls` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://<din-web-service>.onrender.com` |
| `APP_KEY` | Generera lokalt: `php artisan key:generate --show` och klistra in värdet |
| `DB_*` | Enligt PostgreSQL‑instansen ovan |

Lämna `SESSION_DRIVER`/`CACHE_STORE`/`QUEUE_CONNECTION` som i Laravel‑standard om migreringarna skapat nödvändiga tabeller (körs vid containerstart: `php artisan migrate --force` i `Dockerfile`).

5. **Deploy.** När bygget är klart, testa:

   - `https://<din-service>.onrender.com/up`
   - `https://<din-service>.onrender.com/api/v1/health`

### 3) Koppla Netlify (frontend) till den nya backenden

1. Öppna din Tidspuls‑site på Netlify → **Site configuration** → **Environment variables**.
2. Sätt **`VITE_API_BASE_URL`** till:

   `https://<din-service>.onrender.com/api/v1`

   (samma host som `APP_URL`, med `/api/v1` sist.)

3. Trigga **Deploy** (eller “Clear cache and deploy”) så att frontend byggs om med rätt API‑bas‑URL.

Repots `netlify.toml` har förhandsfyllt `https://tidspuls-backend.onrender.com/api/v1` — det funkar bara om din Render‑tjänst verkligen får den URL:en; annars **ska Netlify‑variabeln eller filen** uppdateras till den riktiga hosten.

### 4) Verifiering

- Registrera användare eller skapa via databas — kontrollera att data hamnar i **den nya** PostgreSQL‑instansen, inte i gamla projektets databas.
- Logga in, stämpla in/ut, lista poster — ska gå mot den nya API‑bas‑URL:en (kolla **Network** i webbläsaren).
