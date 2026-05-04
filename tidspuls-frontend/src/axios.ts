import axios from 'axios';
import { getAuthToken } from './auth';

function collectValidationMessages(details: unknown): string[] {
    if (!details || typeof details !== 'object') {
        return [];
    }

    const messages: string[] = [];

    for (const value of Object.values(details as Record<string, unknown>)) {
        if (!Array.isArray(value)) {
            continue;
        }

        for (const item of value) {
            if (typeof item === 'string') {
                messages.push(item);
            }
        }
    }

    return messages;
}

function translateValidationMessage(message: string): string {
    const m = message.trim();

    if (/password.*at least \d+ characters/i.test(m)) {
        const match = m.match(/at least (\d+) characters/i);

        return match
            ? `Lösenordet måste vara minst ${match[1]} tecken.`
            : 'Lösenordet är för kort.';
    }

    if (/password confirmation does not match/i.test(m)) {
        return 'Lösenorden matchar inte.';
    }

    if (/email.*already been taken/i.test(m)) {
        return 'E-postadressen används redan.';
    }

    if (/personnummer.*already been taken/i.test(m)) {
        return 'Personnumret är redan registrerat.';
    }

    if (/personnummer.*format is invalid/i.test(m) || /The personnummer format is invalid/i.test(m)) {
        return 'Ogiltigt personnummer (kontrollera kontrollsiffra).';
    }

    return m;
}

function resolveApiBaseUrl(): string {
    const raw = import.meta.env.VITE_API_BASE_URL;
    const trimmed = typeof raw === 'string' ? raw.trim() : '';

    if (trimmed !== '') {
        return trimmed.replace(/\/+$/, '');
    }

    if (import.meta.env.DEV) {
        return 'http://localhost:8001/api/v1';
    }

    // Production bundle should never ship without VITE_API_BASE_URL (see scripts/check-vite-api-url.mjs).
    console.error(
        '[tidspuls] VITE_API_BASE_URL saknas i byggd frontend — kontrollera Netlify environment variables och gör om deploy.'
    );

    return '';
}

const apiClient = axios.create({
    baseURL: resolveApiBaseUrl(),
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    timeout: 90_000,
});

apiClient.interceptors.request.use((config) => {
    const token = getAuthToken();

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

export const getApiErrorMessage = (error: unknown, fallback: string): string => {
    if (!axios.isAxiosError(error)) {
        return fallback;
    }

    // No HTTP response: DNS failure, wrong/missing VITE_API_BASE_URL, CORS block, offline, or timeout (Render cold start)
    if (error.response?.status === 429) {
        return 'För många försök just nu. Vänta en liten stund och prova igen.';
    }

    if (!error.response && error.request) {
        const code = error.code;
        let hint = '';

        if (code === 'ECONNABORTED') {
            hint =
                ' Begäran tog för lång tid — gratisplan på Render kan ha varit i viloläge; prova igen om en minut.';
        } else if (code === 'ERR_NETWORK') {
            hint =
                ' Nätverket blockerade anropet (brandvägg, VPN, annonsblockering eller fel API-adress i den byggda sidan).';
        }

        const urlHint =
            typeof apiClient.defaults.baseURL === 'string' && apiClient.defaults.baseURL.length > 0
                ? ` Nuvarande API-bas i klienten: ${apiClient.defaults.baseURL}`
                : ' API-bas i klienten är tom — på Netlify måste VITE_API_BASE_URL sättas vid bygget (tom variabel i UI åsidosätter netlify.toml).';

        return (
            'Kunde inte ansluta till API:t.' +
            hint +
            urlHint +
            ' Redeploy frontend efter du ändrat miljövariabler.'
        );
    }

    const payload = error.response?.data as Record<string, unknown> | undefined;
    const errObj = payload?.error as Record<string, unknown> | undefined;
    const code = typeof errObj?.code === 'string' ? errObj.code : '';

    if (code === 'invalid_credentials') {
        return 'Fel personnummer eller lösenord. Om du inte har konto än, välj «Skapa konto» i menyn.';
    }

    if (code === 'password_reset_throttled') {
        return 'Du har bett om för många återställningslänkar. Vänta en stund innan du försöker igen.';
    }

    if (code === 'invalid_reset_token') {
        return 'Länken är ogiltig eller har gått ut. Begär en ny återställningslänk på inloggningssidan.';
    }

    if (code === 'password_reset_failed') {
        return 'Kunde inte återställa lösenordet. Kontrollera uppgifterna eller begär en ny länk.';
    }

    if (code === 'validation_failed') {
        const rawMessages = collectValidationMessages(errObj?.details);

        if (rawMessages.length > 0) {
            return rawMessages.map(translateValidationMessage).join(' ');
        }

        return 'Några uppgifter är fel. Kontrollera personnummer (giltig kontrollsiffra), e-post och att lösenorden matchar.';
    }

    const message = errObj?.message ?? payload?.message;

    if (typeof message === 'string' && message.trim().length > 0) {
        if (message === 'Invalid credentials.') {
            return 'Fel personnummer eller lösenord. Om du inte har konto än, välj «Skapa konto» i menyn.';
        }

        if (message === 'The given data was invalid.') {
            return 'Några uppgifter är fel. Kontrollera personnummer (giltig kontrollsiffra), e-post och att lösenorden matchar.';
        }

        return message;
    }

    return fallback;
};

export default apiClient;
