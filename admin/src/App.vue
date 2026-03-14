<template>
    <teleport to="#sidebar">
        <Sidebar />
    </teleport>

    <div class="admin-shell">
        <router-view />
    </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import bus from "./bus";
import Sidebar from "./components/Sidebar.vue";

onMounted(() => {
    console.log("Vue Admin Shell Loaded");

    // Example listener for PHP events
    bus.on("php-event", (data) => {
        console.log("Received event from PHP:", data);
    });
});
</script>

<style>
/* Basic layout for isolated Vue app */
.admin-shell {
    padding: 20px;
}
</style>
