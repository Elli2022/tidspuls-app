/**
 * Fail CI/Netlify builds when VITE_API_BASE_URL still contains the repo placeholder.
 * Netlify UI env vars override netlify.toml — a leftover YOUR_RENDER_BACKEND_URL breaks production.
 */
const raw = process.env.VITE_API_BASE_URL;

if (typeof raw === 'string' && raw.includes('YOUR_RENDER_BACKEND_URL')) {
    console.error(
        '\n[tidspuls-frontend] VITE_API_BASE_URL contains YOUR_RENDER_BACKEND_URL.\n' +
            'Fix: Netlify → Site configuration → Environment variables → set VITE_API_BASE_URL to https://<your-render-host>/api/v1\n' +
            'or remove the variable so netlify.toml applies.\n'
    );
    process.exit(1);
}
