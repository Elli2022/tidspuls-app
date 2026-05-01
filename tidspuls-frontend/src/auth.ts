import { computed, ref } from 'vue';

const authToken = ref<string | null>(localStorage.getItem('authToken'));

export const isAuthenticated = computed(() => Boolean(authToken.value));

export const getAuthToken = (): string | null => authToken.value;

export const setAuthToken = (token: string): void => {
    authToken.value = token;
    localStorage.setItem('authToken', token);
};

export const clearAuthToken = (): void => {
    authToken.value = null;
    localStorage.removeItem('authToken');
};
