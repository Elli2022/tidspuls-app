<template>
    <section class="card">
        <h2>Mina uppgifter</h2>
        <p v-if="loading">Laddar...</p>
        <template v-else-if="user">
            <dl class="grid">
                <dt>Organisation</dt>
                <dd>{{ user.organization?.name ?? '—' }}</dd>
                <dt>Roll</dt>
                <dd>{{ roleLabel(user.role) }}</dd>
                <dt>Namn</dt>
                <dd>{{ user.name }}</dd>
                <dt>E-post</dt>
                <dd>{{ user.email }}</dd>
                <dt>Personnummer</dt>
                <dd>{{ user.personnummer }}</dd>
            </dl>
        </template>
        <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
    </section>

    <section v-if="user && canSeeMembers" class="card">
        <h2>På din arbetsplats</h2>
        <p v-if="membersLoading">Laddar lista…</p>
        <p v-else-if="membersError" class="error">{{ membersError }}</p>
        <ul v-else-if="members.length" class="member-list">
            <li v-for="m in members" :key="m.id">
                <strong>{{ m.name }}</strong>
                <span class="meta">{{ m.email }} · {{ roleLabel(m.role) }}</span>
            </li>
        </ul>
        <p v-else>Inga kollegor listade.</p>
    </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import apiClient, { getApiErrorMessage } from '../axios';

type MeUser = {
    id: number;
    name: string;
    email: string;
    personnummer: string;
    role: string;
    organization: { id: number; name: string } | null;
};

type MemberRow = {
    id: number;
    name: string;
    email: string;
    role: string;
};

const user = ref<MeUser | null>(null);
const loading = ref(true);
const errorMessage = ref('');
const members = ref<MemberRow[]>([]);
const membersLoading = ref(false);
const membersError = ref('');

const canSeeMembers = computed(() => {
    const r = user.value?.role;
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
            return role;
    }
};

const loadMembers = async () => {
    if (!canSeeMembers.value) {
        members.value = [];
        return;
    }

    membersLoading.value = true;
    membersError.value = '';

    try {
        const response = await apiClient.get('/organization/members');
        members.value = response.data.data.members as MemberRow[];
    } catch (error: unknown) {
        membersError.value = getApiErrorMessage(error, 'Kunde inte hämta kollegalistan.');
    } finally {
        membersLoading.value = false;
    }
};

onMounted(async () => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiClient.get('/me');
        user.value = response.data.data.user as MeUser;
        if (canSeeMembers.value) {
            await loadMembers();
        }
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(error, 'Kunde inte hämta profilen.');
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.card {
    background: #fff;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.grid {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 0.5rem 1rem;
    margin: 0;
}

dt {
    font-weight: 600;
    color: #495057;
}

dd {
    margin: 0;
}

.member-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 0.75rem;
}

.member-list li {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.meta {
    font-size: 0.85rem;
    color: #6c757d;
}

.error {
    color: #bf1b1b;
    margin-top: 0.75rem;
}
</style>
