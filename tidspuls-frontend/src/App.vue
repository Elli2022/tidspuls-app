<template>
    <div class="app-shell">
        <header class="topbar">
            <h1>Tidspuls</h1>
            <nav class="actions">
                <template v-if="isAuthenticated">
                    <router-link to="/">Hem</router-link>
                    <router-link to="/platslogg">Platslogg</router-link>
                    <router-link to="/profile">Mina uppgifter</router-link>
                    <router-link to="/change-password">Byt lösenord</router-link>
                    <button type="button" @click="logout">Logga ut</button>
                </template>
                <template v-else>
                    <router-link to="/login">Logga in</router-link>
                    <router-link to="/register">Skapa konto</router-link>
                </template>
            </nav>
        </header>
        <main class="content">
            <router-view />
        </main>
    </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';
import apiClient from './axios';
import { clearAuthToken, isAuthenticated } from './auth';

const router = useRouter();

const logout = async () => {
    try {
        await apiClient.post('/logout');
    } catch {
        // Ignore backend errors and force local logout.
    }

    clearAuthToken();
    await router.push('/login');
};
</script>

<style scoped>
.app-shell {
    min-height: 100vh;
    background: #f5f7fb;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: #1c3d5a;
    color: #fff;
}

.topbar h1 {
    font-size: 1.1rem;
}

.actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.actions a,
.actions button {
    color: #fff;
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    padding: 0.35rem 0.6rem;
    font-size: 0.85rem;
    cursor: pointer;
    text-decoration: none;
}

.content {
    max-width: 900px;
    margin: 0 auto;
    padding: 1rem;
}

@media (max-width: 640px) {
    .topbar {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }

    .actions {
        width: 100%;
        flex-wrap: wrap;
    }
}
</style>
