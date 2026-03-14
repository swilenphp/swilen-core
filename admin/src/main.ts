import { createApp } from "vue";
import { createPinia } from "pinia";
import { createRouter, createWebHistory } from "vue-router";
import PrimeVue from "primevue/config";
import Aura from "@primeuix/themes/aura";
import "primeicons/primeicons.css";
import App from "./App.vue";
import "./assets/main.css";
import "./bus";

const app = createApp(App);

const pinia = createPinia();
const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: "/",
            alias: ["/index.php", "/index"],
            component: () => import("./views/Dashboard.vue"),
        },
        {
            path: "/edit",
            alias: ["/edit.php"],
            component: () => import("./views/Posts.vue"),
        },
        {
            path: "/upload",
            alias: ["/upload.php"],
            component: () => import("./views/Media.vue"),
        },
        {
            path: "/:pathMatch(.*)*",
            component: () => import("./views/Generic.vue"),
        },
    ],
});

import { definePreset } from "@primeuix/themes";

const MyPreset = definePreset(Aura, {
    semantic: {
        primary: {
            50: "{teal.50}",
            100: "{teal.100}",
            200: "{teal.200}",
            300: "{teal.300}",
            400: "{teal.400}",
            500: "{teal.500}",
            600: "{teal.600}",
            700: "{teal.700}",
            800: "{teal.800}",
            900: "{teal.900}",
            950: "{teal.950}",
        },
    },
});

app.use(pinia);
app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: MyPreset,
        options: {
            darkModeSelector: "system",
            cssLayer: false,
        },
    },
});

app.mount("#admin-app");
