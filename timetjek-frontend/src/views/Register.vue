<template>
    <section class="card">
        <h2>Skapa konto</h2>
        <form class="form" @submit.prevent="register">
            <input v-model="name" type="text" placeholder="Namn" required />
            <input v-model="email" type="email" placeholder="E-post" required />
            <input v-model="personnummer" type="text" placeholder="Personnummer (YYMMDD-XXXX)" required />
            <p class="field-hint">Lösenord måste vara minst 8 tecken.</p>
            <input v-model="password" type="password" placeholder="Lösenord (minst 8 tecken)" required />
            <input v-model="passwordConfirmation" type="password" placeholder="Bekräfta lösenord" required />
            <button :disabled="loading" type="submit">{{ loading ? 'Skapar...' : 'Skapa konto' }}</button>
        </form>
        <p class="hint">
            Har du redan konto?
            <router-link to="/login">Logga in</router-link>
        </p>
        <p v-if="message" class="ok">{{ message }}</p>
        <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
    </section>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import apiClient, { getApiErrorMessage } from '../axios';

const name = ref('');
const email = ref('');
const personnummer = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const message = ref('');
const errorMessage = ref('');

const register = async () => {
    loading.value = true;
    message.value = '';
    errorMessage.value = '';

    try {
        await apiClient.post('/register', {
            name: name.value,
            email: email.value,
            personnummer: personnummer.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });

        message.value = 'Konto skapat. Du kan nu logga in.';
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(
            error,
            'Registreringen misslyckades. Kontrollera uppgifterna.'
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

.field-hint {
    margin: -0.35rem 0 0;
    font-size: 0.85rem;
    color: #6c757d;
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

.ok {
    color: #136f34;
}

.error {
    color: #bf1b1b;
}
</style>
