<template>
    <section class="layout">
        <div class="card">
            <h2>Attest — väntande stämplingar</h2>
            <p v-if="loading">Laddar…</p>
            <template v-else-if="!canAttest">
                <p class="hint">
                    Endast administratör eller chef kan attestera kollegornas tid. Din roll är
                    <strong>{{ roleLabel(me?.role ?? '') }}</strong
                    >.
                </p>
            </template>
            <template v-else>
                <p v-if="message" class="ok">{{ message }}</p>
                <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
                <ul v-if="entries.length" class="entry-list">
                    <li v-for="entry in entries" :key="entry.id">
                        <div class="entry-main">
                            <strong>{{ entry.user?.name ?? '—' }}</strong>
                            <span class="meta">{{ entry.user?.email ?? '' }}</span>
                            <span class="times">
                                <strong>In:</strong> {{ formatDate(entry.clocked_in_at) }} ·
                                <strong>Ut:</strong>
                                {{ entry.clocked_out_at ? formatDate(entry.clocked_out_at) : '—' }}
                            </span>
                            <template v-if="entry.note">
                                <span class="meta"><strong>Anteckning:</strong> {{ entry.note }}</span>
                            </template>
                            <label class="reject-label">
                                Motivering vid avslag (valfritt)
                                <textarea
                                    v-model="rejectReasons[entry.id]"
                                    rows="2"
                                    maxlength="1000"
                                    placeholder="T.ex. justera tider och skicka in igen."
                                />
                            </label>
                        </div>
                        <div class="row-actions">
                            <button type="button" :disabled="busyId === entry.id" @click="approve(entry.id)">
                                Godkänn
                            </button>
                            <button
                                type="button"
                                class="btn-secondary"
                                :disabled="busyId === entry.id"
                                @click="reject(entry.id)"
                            >
                                Avslå
                            </button>
                        </div>
                    </li>
                </ul>
                <p v-else class="hint">Inga stämplingar väntar på attest.</p>
            </template>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import apiClient, { getApiErrorMessage } from '../axios';

type MeUser = {
    role: string;
};

type PendingEntry = {
    id: number;
    clocked_in_at: string;
    clocked_out_at: string | null;
    note: string | null;
    user?: { id: number; name: string; email: string };
};

const loading = ref(true);
const me = ref<MeUser | null>(null);
const entries = ref<PendingEntry[]>([]);
const rejectReasons = reactive<Record<number, string>>({});
const busyId = ref<number | null>(null);
const message = ref('');
const errorMessage = ref('');

const canAttest = computed(() => {
    const r = me.value?.role;
    return r === 'admin' || r === 'manager';
});

const roleLabel = (role: string) => {
    switch (role) {
        case 'admin':
            return 'Administratör';
        case 'manager':
            return 'Chef';
        case 'employee':
            return 'Medarbetare';
        default:
            return role || '—';
    }
};

const formatDate = (value: string) =>
    new Date(value).toLocaleString('sv-SE', { dateStyle: 'short', timeStyle: 'short' });

const load = async () => {
    loading.value = true;
    errorMessage.value = '';
    message.value = '';

    try {
        const meRes = await apiClient.get('/me');
        me.value = meRes.data.data.user as MeUser;

        if (!canAttest.value) {
            entries.value = [];
            return;
        }

        const listRes = await apiClient.get('/time-entries/pending-review');
        entries.value = listRes.data.data.time_entries as PendingEntry[];
        for (const e of entries.value) {
            if (rejectReasons[e.id] === undefined) {
                rejectReasons[e.id] = '';
            }
        }
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(error, 'Kunde inte ladda attestlistan.');
    } finally {
        loading.value = false;
    }
};

const approve = async (id: number) => {
    busyId.value = id;
    errorMessage.value = '';
    message.value = '';

    try {
        await apiClient.post(`/time-entries/${id}/approve`);
        message.value = 'Stämpling godkänd.';
        await load();
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(error, 'Kunde inte godkänna.');
    } finally {
        busyId.value = null;
    }
};

const reject = async (id: number) => {
    busyId.value = id;
    errorMessage.value = '';
    message.value = '';

    try {
        const reason = rejectReasons[id]?.trim();
        await apiClient.post(`/time-entries/${id}/reject`, {
            reason: reason === '' ? null : reason,
        });
        message.value = 'Stämpling avslagen — medarbetaren kan justera och skicka in på nytt.';
        rejectReasons[id] = '';
        await load();
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(error, 'Kunde inte avslå.');
    } finally {
        busyId.value = null;
    }
};

onMounted(load);
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

.entry-list {
    list-style: none;
    padding: 0;
    margin: 1rem 0 0;
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

.entry-main {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    flex: 1;
    min-width: 0;
}

.meta {
    font-size: 0.85rem;
    color: #6c757d;
}

.times {
    font-size: 0.9rem;
}

.reject-label {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.85rem;
    margin-top: 0.35rem;
}

textarea {
    width: 100%;
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.5rem 0.75rem;
    font: inherit;
    resize: vertical;
}

.row-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex-shrink: 0;
}

button {
    min-height: 44px;
    border-radius: 8px;
    border: none;
    padding: 0.5rem 0.75rem;
    background: #1c3d5a;
    color: #fff;
    cursor: pointer;
}

.btn-secondary {
    background: #fff;
    color: #1c3d5a;
    border: 1px solid #1c3d5a;
}

.hint {
    color: #495057;
    margin: 0.5rem 0 0;
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

    .row-actions {
        flex-direction: row;
        flex-wrap: wrap;
    }
}
</style>
