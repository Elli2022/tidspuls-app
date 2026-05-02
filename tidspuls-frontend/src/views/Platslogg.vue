<template>
    <section class="layout">
        <div class="card">
            <h2>Platslogg</h2>
            <p class="lead">
                Spara din aktuella GPS-position (t.ex. vid platsbesök). Vid in- och utstämpling sparas koordinater
                automatiskt i listan när du tillåter plats i webbläsaren.
            </p>
            <div class="actions">
                <button :disabled="loading" type="button" @click="recordCurrentLocation">Registrera min plats nu</button>
            </div>
            <p v-if="message" class="ok">{{ message }}</p>
            <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
        </div>

        <div class="card">
            <h2>Historik</h2>
            <ul v-if="logs.length" class="log-list">
                <li v-for="log in logs" :key="log.id">
                    <div>
                        <strong>Tid:</strong> {{ formatDate(log.recorded_at) }}<br />
                        <strong>Källa:</strong> {{ formatSource(log.source) }}<br />
                        <strong>GPS:</strong> {{ formatGps(log.latitude, log.longitude) }}
                        <template v-if="log.accuracy != null">
                            <br />
                            <strong>Noggrannhet:</strong> ca {{ Math.round(log.accuracy) }} m
                        </template>
                        <br />
                        <a class="map-link" :href="osmUrl(log.latitude, log.longitude)" target="_blank" rel="noopener"
                            >Öppna på karta</a
                        >
                    </div>
                </li>
            </ul>
            <p v-else>Inga platsregistreringar ännu.</p>
            <button v-if="meta.last_page > meta.current_page" type="button" class="secondary" @click="loadMore">
                Ladda fler
            </button>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import apiClient, { getApiErrorMessage } from '../axios';

type LocationLog = {
    id: number;
    latitude: number;
    longitude: number;
    accuracy: number | null;
    source: string;
    recorded_at: string;
};

type Meta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

const logs = ref<LocationLog[]>([]);
const meta = ref<Meta>({
    current_page: 1,
    last_page: 1,
    per_page: 75,
    total: 0,
});
const loading = ref(false);
const message = ref('');
const errorMessage = ref('');

const formatDate = (value: string) =>
    new Date(value).toLocaleString('sv-SE', { dateStyle: 'short', timeStyle: 'short' });

const formatGps = (lat: number, lng: number) => `${Number(lat).toFixed(5)}, ${Number(lng).toFixed(5)}`;

const formatSource = (source: string) => {
    const map: Record<string, string> = {
        manual: 'Manuell',
        clock_in: 'Instämpling',
        clock_out: 'Utstämpling',
        site_visit: 'Platsbesök',
        check_in: 'Incheckning',
    };

    return map[source] ?? source;
};

const osmUrl = (lat: number, lng: number) =>
    `https://www.openstreetmap.org/?mlat=${encodeURIComponent(lat)}&mlon=${encodeURIComponent(lng)}#map=16/${lat}/${lng}`;

const withGps = async () => {
    if (!navigator.geolocation) {
        return {};
    }

    return new Promise<{ latitude?: number; longitude?: number; accuracy?: number }>((resolve) => {
        navigator.geolocation.getCurrentPosition(
            (position) =>
                resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy:
                        position.coords.accuracy != null && Number.isFinite(position.coords.accuracy)
                            ? position.coords.accuracy
                            : undefined,
                }),
            () => resolve({}),
            { timeout: 8000, enableHighAccuracy: true },
        );
    });
};

const fetchPage = async (page: number, append: boolean) => {
    const response = await apiClient.get('/location-logs', { params: { page } });
    const chunk = response.data.data.location_logs as LocationLog[];
    if (append) {
        logs.value = logs.value.concat(chunk);
    } else {
        logs.value = chunk;
    }

    meta.value = response.data.data.meta as Meta;
};

const loadLogs = async () => {
    await fetchPage(1, false);
};

const loadMore = async () => {
    const next = meta.value.current_page + 1;
    if (next > meta.value.last_page) {
        return;
    }

    await fetchPage(next, true);
};

const recordCurrentLocation = async () => {
    loading.value = true;
    message.value = '';
    errorMessage.value = '';

    try {
        const coords = await withGps();
        if (coords.latitude == null || coords.longitude == null) {
            errorMessage.value =
                'Kunde inte läsa position. Tillåt platsåtkomst för denna webbplats och försök igen.';
            return;
        }

        await apiClient.post('/location-logs', {
            latitude: coords.latitude,
            longitude: coords.longitude,
            accuracy: coords.accuracy,
            source: 'manual',
        });
        message.value = 'Plats sparad.';
        await loadLogs();
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(error, 'Kunde inte spara plats.');
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await loadLogs();
});
</script>

<style scoped>
.layout {
    display: grid;
    gap: 1rem;
}

.card {
    background: #fff;
    border-radius: 12px;
    padding: 1rem;
}

.lead {
    color: #374151;
    font-size: 0.95rem;
    line-height: 1.45;
    margin: 0 0 0.75rem;
}

.actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.log-list {
    list-style: none;
    padding: 0;
    margin: 0 0 0.75rem;
    display: grid;
    gap: 0.75rem;
}

.log-list li {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.75rem;
}

.map-link {
    color: #1c3d5a;
    font-weight: 600;
}

button {
    min-height: 44px;
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.5rem 0.75rem;
    background: #1c3d5a;
    color: #fff;
    border: none;
    cursor: pointer;
}

button.secondary {
    background: #fff;
    color: #1c3d5a;
    border: 1px solid #1c3d5a;
}

.ok {
    color: #136f34;
}

.error {
    color: #bf1b1b;
}
</style>
