<template>
    <section class="layout">
        <div class="card">
            <h2>Tidsregistrering</h2>
            <div class="actions">
                <button :disabled="loading" @click="clockIn">Stämpla in</button>
                <button :disabled="loading" @click="clockOut">Stämpla ut</button>
            </div>
            <p v-if="message" class="ok">{{ message }}</p>
            <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
        </div>

        <div class="card">
            <h2>Sparade stämplingar</h2>
            <ul v-if="entries.length" class="entry-list">
                <li v-for="entry in entries" :key="entry.id">
                    <div>
                        <strong>In:</strong> {{ formatDate(entry.clocked_in_at) }}<br />
                        <strong>Ut:</strong> {{ entry.clocked_out_at ? formatDate(entry.clocked_out_at) : 'Pågående' }}
                    </div>
                    <div class="row-actions">
                        <button @click="startEdit(entry)">Redigera</button>
                        <button @click="removeEntry(entry.id)">Ta bort</button>
                    </div>
                </li>
            </ul>
            <p v-else>Inga stämplingar ännu.</p>
        </div>

        <div v-if="editingEntry" class="card">
            <h2>Redigera stämpling</h2>
            <form class="form" @submit.prevent="saveEdit">
                <label>
                    In
                    <input v-model="editClockedInAt" type="datetime-local" required />
                </label>
                <label>
                    Ut
                    <input v-model="editClockedOutAt" type="datetime-local" />
                </label>
                <button type="submit">Spara ändring</button>
                <button type="button" @click="cancelEdit">Avbryt</button>
            </form>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import apiClient, { getApiErrorMessage } from '../axios';

type TimeEntry = {
    id: number;
    clocked_in_at: string;
    clocked_out_at: string | null;
};

const entries = ref<TimeEntry[]>([]);
const loading = ref(false);
const message = ref('');
const errorMessage = ref('');
const editingEntry = ref<TimeEntry | null>(null);
const editClockedInAt = ref('');
const editClockedOutAt = ref('');

const formatDate = (value: string) =>
    new Date(value).toLocaleString('sv-SE', { dateStyle: 'short', timeStyle: 'short' });

const dateTimeLocalValue = (value: string | null) => {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const tzOffsetMs = date.getTimezoneOffset() * 60000;
    return new Date(date.getTime() - tzOffsetMs).toISOString().slice(0, 16);
};

const toIsoDate = (value: string) => new Date(value).toISOString();

const withGps = async () => {
    if (!navigator.geolocation) {
        return {};
    }

    return new Promise<{ latitude?: number; longitude?: number }>((resolve) => {
        navigator.geolocation.getCurrentPosition(
            (position) => resolve({
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
            }),
            () => resolve({}),
            { timeout: 4000 }
        );
    });
};

const loadEntries = async () => {
    const response = await apiClient.get('/time-entries');
    entries.value = response.data.data.time_entries;
};

const clockIn = async () => {
    loading.value = true;
    message.value = '';
    errorMessage.value = '';

    try {
        const coords = await withGps();
        await apiClient.post('/time-entries/clock-in', coords);
        await loadEntries();
        message.value = 'Instämpling registrerad.';
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(error, 'Kunde inte stampla in.');
    } finally {
        loading.value = false;
    }
};

const clockOut = async () => {
    loading.value = true;
    message.value = '';
    errorMessage.value = '';

    try {
        const coords = await withGps();
        await apiClient.post('/time-entries/clock-out', coords);
        await loadEntries();
        message.value = 'Utstämpling registrerad.';
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(error, 'Kunde inte stampla ut.');
    } finally {
        loading.value = false;
    }
};

const startEdit = (entry: TimeEntry) => {
    editingEntry.value = entry;
    editClockedInAt.value = dateTimeLocalValue(entry.clocked_in_at);
    editClockedOutAt.value = dateTimeLocalValue(entry.clocked_out_at);
};

const cancelEdit = () => {
    editingEntry.value = null;
    editClockedInAt.value = '';
    editClockedOutAt.value = '';
};

const saveEdit = async () => {
    if (!editingEntry.value) {
        return;
    }

    try {
        await apiClient.put(`/time-entries/${editingEntry.value.id}`, {
            clocked_in_at: toIsoDate(editClockedInAt.value),
            clocked_out_at: editClockedOutAt.value ? toIsoDate(editClockedOutAt.value) : null,
        });
        await loadEntries();
        cancelEdit();
        message.value = 'Stämpling uppdaterad.';
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(error, 'Kunde inte uppdatera stamplingen.');
    }
};

const removeEntry = async (id: number) => {
    try {
        await apiClient.delete(`/time-entries/${id}`);
        await loadEntries();
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(error, 'Kunde inte ta bort stamplingen.');
    }
};

onMounted(async () => {
    await loadEntries();
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

.actions,
.row-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.entry-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 0.75rem;
}

.entry-list li {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.75rem;
}

.form {
    display: grid;
    gap: 0.75rem;
}

input,
button {
    min-height: 44px;
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.5rem 0.75rem;
}

button {
    background: #1c3d5a;
    color: #fff;
    border: none;
}

.ok {
    color: #136f34;
}

.error {
    color: #bf1b1b;
}

@media (max-width: 640px) {
    .entry-list li {
        flex-direction: column;
    }
}
</style>

