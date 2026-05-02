<template>
    <section class="card">
        <h2>Mina uppgifter</h2>
        <p v-if="loading">Laddar...</p>
        <template v-else-if="user">
            <dl class="grid">
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
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import apiClient, { getApiErrorMessage } from '../axios';

type MeUser = {
    id: number;
    name: string;
    email: string;
    personnummer: string;
};

const user = ref<MeUser | null>(null);
const loading = ref(true);
const errorMessage = ref('');

onMounted(async () => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiClient.get('/me');
        user.value = response.data.data.user as MeUser;
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

.error {
    color: #bf1b1b;
    margin-top: 0.75rem;
}
</style>
