<template>
    <div class="generic-view p-4">
        <h2>Generic View</h2>
        <p>
            Loading API data for path: <strong>{{ route.path }}</strong>
        </p>
        <div v-if="loading" class="mt-4">
            <i class="pi pi-spin pi-spinner text-2xl"></i> Loading...
        </div>
        <div v-else class="mt-4 surface-card p-4 border-round">
            <pre>{{ mockData }}</pre>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();
const loading = ref(true);
const mockData = ref("");

async function loadData() {
    loading.value = true;
    // Simulate API call based on route
    setTimeout(() => {
        mockData.value = JSON.stringify(
            {
                endpoint: route.path,
                query: route.query,
                message: "Simulated API Response for " + route.path,
            },
            null,
            2,
        );
        loading.value = false;
    }, 500);
}

onMounted(() => {
    loadData();
});

watch(
    () => route.fullPath,
    () => {
        loadData();
    },
);
</script>

<style scoped>
.generic-view h2 {
    color: var(--p-primary-color);
    margin-top: 0;
}
.surface-card {
    background-color: var(--p-surface-800);
}
</style>
