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

const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8001/api/v1',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
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

    // No HTTP response: DNS failure, wrong VITE_API_BASE_URL, CORS block, or offline
    if (!error.response && error.request) {
        return 'Kunde inte ansluta till servern. Kontrollera nätverket och att frontend är byggd med rätt API-adress (VITE_API_BASE_URL).';
    }

    const payload = error.response?.data as Record<string, unknown> | undefined;
    const errObj = payload?.error as Record<string, unknown> | undefined;
    const code = typeof errObj?.code === 'string' ? errObj.code : '';

    if (code === 'invalid_credentials') {
        return 'Fel personnummer eller lösenord. Om du inte har konto än, välj «Skapa konto» i menyn.';
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
