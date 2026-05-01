<template>
    <section class="card">
        <h2>Logga in</h2>
        <form class="form" @submit.prevent="login">
            <input v-model="personnummer" type="text" placeholder="Personnummer" required />
            <input v-model="password" type="password" placeholder="Lösenord" required />
            <button :disabled="loading" type="submit">{{ loading ? 'Loggar in...' : 'Logga in' }}</button>
        </form>
        <p class="hint">
            Har du inget konto?
            <router-link to="/register">Skapa konto</router-link>
        </p>
        <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
    </section>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import apiClient, { getApiErrorMessage } from '../axios';
import { setAuthToken } from '../auth';

const router = useRouter();
const personnummer = ref('');
const password = ref('');
const loading = ref(false);
const errorMessage = ref('');

const login = async () => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await apiClient.post('/login', {
            personnummer: personnummer.value,
            password: password.value,
            device_name: 'web-app',
        });

        setAuthToken(response.data.data.access_token);
        await router.push('/');
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(
            error,
            'Kunde inte logga in. Kontrollera personnummer/lösenord.'
        );
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.card {
    background: #fff;
    border-radius: 12px;
    padding: 1rem;
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

.hint {
    margin-top: 0.75rem;
    font-size: 0.9rem;
    color: #495057;
}

.hint a {
    color: #1c3d5a;
    font-weight: 600;
}

.error {
    color: #bf1b1b;
    margin-top: 0.75rem;
}
</style>
