<template>
    <section class="card">
        <h2>Byt lösenord</h2>
        <form class="form" @submit.prevent="changePassword">
            <input v-model="currentPassword" type="password" placeholder="Nuvarande lösenord" required />
            <input v-model="newPassword" type="password" placeholder="Nytt lösenord" required />
            <input v-model="newPasswordConfirmation" type="password" placeholder="Bekräfta nytt lösenord" required />
            <button :disabled="loading" type="submit">{{ loading ? 'Sparar...' : 'Spara' }}</button>
        </form>
        <p v-if="message" class="ok">{{ message }}</p>
        <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
    </section>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import apiClient, { getApiErrorMessage } from '../axios';

const currentPassword = ref('');
const newPassword = ref('');
const newPasswordConfirmation = ref('');
const loading = ref(false);
const message = ref('');
const errorMessage = ref('');

const changePassword = async () => {
    loading.value = true;
    message.value = '';
    errorMessage.value = '';

    try {
        await apiClient.post('/change-password', {
            current_password: currentPassword.value,
            new_password: newPassword.value,
            new_password_confirmation: newPasswordConfirmation.value,
        });
        message.value = 'Lösenord uppdaterat.';
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(
            error,
            'Kunde inte ändra lösenord.'
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

.ok {
    color: #136f34;
}

.error {
    color: #bf1b1b;
}
</style>
