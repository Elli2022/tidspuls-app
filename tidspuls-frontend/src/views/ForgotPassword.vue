<template>
    <section class="card">
        <h2>Glömt lösenord</h2>
        <p class="intro">
            Ange den e-postadress du registrerade kontot med. Om den finns hos oss skickar vi en länk för att välja nytt
            lösenord.
        </p>
        <form class="form" @submit.prevent="submit">
            <input v-model="email" type="email" autocomplete="email" placeholder="E-post" required />
            <button :disabled="loading" type="submit">{{ loading ? 'Skickar…' : 'Skicka återställningslänk' }}</button>
        </form>
        <p class="hint">
            <router-link to="/login">Tillbaka till inloggning</router-link>
        </p>
        <p v-if="message" class="ok">{{ message }}</p>
        <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
    </section>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import apiClient, { getApiErrorMessage } from '../axios';

const email = ref('');
const loading = ref(false);
const message = ref('');
const errorMessage = ref('');

const submit = async () => {
    loading.value = true;
    message.value = '';
    errorMessage.value = '';

    try {
        const response = await apiClient.post('/forgot-password', {
            email: email.value.trim(),
        });
        message.value =
            (response.data?.data?.message as string) ??
            'Om det finns ett konto för den här e-postadressen har vi skickat en länk.';
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(error, 'Kunde inte skicka återställningslänk.');
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

.intro {
    font-size: 0.95rem;
    color: #495057;
    margin-top: 0;
}

.form {
    display: grid;
    gap: 0.75rem;
    margin-top: 1rem;
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
}

.hint a {
    color: #1c3d5a;
    font-weight: 600;
}

.ok {
    color: #136f34;
    margin-top: 0.75rem;
}

.error {
    color: #bf1b1b;
    margin-top: 0.75rem;
}
</style>
