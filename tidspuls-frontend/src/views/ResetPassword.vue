<template>
    <section class="card">
        <h2>Nytt lösenord</h2>
        <p v-if="!tokenFromLink || !emailFromLink" class="error">
            Länken saknar token eller e-post. Öppna länken från mejlet eller
            <router-link to="/forgot-password">begär en ny återställningslänk</router-link>.
        </p>
        <template v-else>
            <p class="intro">E-post: <strong>{{ emailFromLink }}</strong></p>
            <form class="form" @submit.prevent="submit">
                <input v-model="password" type="password" autocomplete="new-password" placeholder="Nytt lösenord" required />
                <input
                    v-model="passwordConfirmation"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Bekräfta lösenord"
                    required
                />
                <button :disabled="loading" type="submit">{{ loading ? 'Sparar…' : 'Spara nytt lösenord' }}</button>
            </form>
        </template>
        <p class="hint">
            <router-link to="/login">Till inloggning</router-link>
        </p>
        <p v-if="message" class="ok">{{ message }}</p>
        <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
    </section>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient, { getApiErrorMessage } from '../axios';

const route = useRoute();
const router = useRouter();

const tokenFromLink = computed(() => {
    const t = route.query.token;
    return typeof t === 'string' ? t : '';
});

const emailFromLink = computed(() => {
    const e = route.query.email;
    return typeof e === 'string' ? decodeURIComponent(e) : '';
});

const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const message = ref('');
const errorMessage = ref('');

const submit = async () => {
    loading.value = true;
    message.value = '';
    errorMessage.value = '';

    try {
        await apiClient.post('/reset-password', {
            token: tokenFromLink.value,
            email: emailFromLink.value.trim(),
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        message.value = 'Lösenordet är uppdaterat. Du omdirigeras till inloggning…';
        setTimeout(() => {
            void router.push('/login');
        }, 1500);
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(error, 'Kunde inte återställa lösenordet.');
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
}

.form {
    display: grid;
    gap: 0.75rem;
    margin-top: 0.75rem;
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

.hint a,
.intro :deep(a) {
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

.error :deep(a) {
    color: #1c3d5a;
}
</style>
