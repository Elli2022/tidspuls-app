import { createRouter, createWebHistory } from 'vue-router';
import Home from './views/Home.vue';
import Login from './views/Login.vue';
import Register from './views/Register.vue';
import ChangePassword from './views/ChangePassword.vue';
import Profile from './views/Profile.vue';
import { getAuthToken } from './auth';

const routes = [
    { path: '/', component: Home, meta: { requiresAuth: true } },
    { path: '/login', component: Login, meta: { guestOnly: true } },
    { path: '/register', component: Register, meta: { guestOnly: true } },
    { path: '/profile', component: Profile, meta: { requiresAuth: true } },
    { path: '/change-password', component: ChangePassword, meta: { requiresAuth: true } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    const token = getAuthToken();

    if (to.meta.requiresAuth && !token) {
        return '/login';
    }

    if (to.meta.guestOnly && token) {
        return '/';
    }
});

export default router;
