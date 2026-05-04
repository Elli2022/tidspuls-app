/**
 * Fail CI/Netlify builds when VITE_API_BASE_URL is missing or unusable.
 * Netlify UI env vars override netlify.toml — an *empty* variable name still overrides and yields a blank URL at build time.
 */
const raw = process.env.VITE_API_BASE_URL;

if (typeof raw !== 'string' || raw.trim() === '') {
    console.error(
        '\n[tidspuls-frontend] VITE_API_BASE_URL is missing or empty.\n' +
            'Common cause on Netlify: Site configuration → Environment variables contains `VITE_API_BASE_URL` with no value — that overrides netlify.toml.\n' +
            'Fix: set `VITE_API_BASE_URL` to https://<your-render-host>/api/v1 or delete the variable so netlify.toml applies, then redeploy.\n'
    );
    process.exit(1);
}

const normalized = raw.trim().replace(/\/+$/, '');

if (normalized.includes('YOUR_RENDER_BACKEND_URL')) {
    console.error(
        '\n[tidspuls-frontend] VITE_API_BASE_URL contains YOUR_RENDER_BACKEND_URL.\n' +
            'Fix: Netlify → Environment variables → set the real Render URL ending with /api/v1\n' +
            'or remove the variable so netlify.toml applies.\n'
    );
    process.exit(1);
}

const isLocal = /^http:\/\/localhost(?::\d+)?\/api\/v1$/i.test(normalized);
const isHttps = /^https:\/\/.+/i.test(normalized);

if (!isLocal && !isHttps) {
    console.error(
        '\n[tidspuls-frontend] VITE_API_BASE_URL must be https://… for production builds,\n' +
            `or http://localhost:<port>/api/v1 for local production builds.\nGot: ${normalized}\n`
    );
    process.exit(1);
}
