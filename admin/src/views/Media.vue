<template>
    <div class="media-view p-4">
        <div class="flex align-items-center justify-content-between mb-4">
            <h1 class="m-0 text-2xl font-semibold">Media Library</h1>
            <Button label="Add New Media File" icon="pi pi-plus" outlined />
        </div>

        <div class="p-4">
            <div class="grid grid-cols-6 gap-4">
                <div v-for="item in mediaItems" :key="item.id">
                    <div class="media-item rounded overflow-hidden cursor-pointer relative hover:border-primary transition-colors transition-duration-200">
                        <img :src="item.url" :alt="item.title" class="w-full h-full object-cover block" style="aspect-ratio: 1;" />
                        <div class="media-overlay absolute bottom-0 left-0 w-full p-2 text-sm text-white bg-black-alpha-60 opacity-0 transition-opacity transition-duration-200">
                            {{ item.title }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import Button from 'primevue/button';

const mediaItems = ref([
    { id: 1, title: 'Hero Background', url: 'https://images.unsplash.com/photo-1497215728101-856f4ea42174?w=500&auto=format&fit=crop&q=60' },
    { id: 2, title: 'Office Space', url: 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=500&auto=format&fit=crop&q=60' },
    { id: 3, title: 'Team Meeting', url: 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=500&auto=format&fit=crop&q=60' },
    { id: 4, title: 'Product Shot 1', url: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&auto=format&fit=crop&q=60' },
    { id: 5, title: 'Product Shot 2', url: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&auto=format&fit=crop&q=60' },
    { id: 6, title: 'Abstract Art', url: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=500&auto=format&fit=crop&q=60' },
    { id: 7, title: 'Nature Texture', url: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=500&auto=format&fit=crop&q=60' },
    { id: 8, title: 'City Skyline', url: 'https://images.unsplash.com/photo-1449844908441-8829872d2607?w=500&auto=format&fit=crop&q=60' },
    { id: 9, title: 'Coffee Mugs', url: 'https://images.unsplash.com/photo-1511920170033-f8396924c648?w=500&auto=format&fit=crop&q=60' },
    { id: 10, title: 'Desktop Setup', url: 'https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?w=500&auto=format&fit=crop&q=60' },
    { id: 11, title: 'Vintage Camera', url: 'https://images.unsplash.com/photo-1516961642265-531546e84af2?w=500&auto=format&fit=crop&q=60' },
    { id: 12, title: 'Code Screen', url: 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=500&auto=format&fit=crop&q=60' }
]);

onMounted(() => {
    // In a real app we would load from an API here
});
</script>

<style scoped>
.media-view h1 {
    color: var(--p-text-color);
}
.surface-card {
    background-color: var(--p-surface-900);
}
.media-item:hover .media-overlay {
    opacity: 1;
}
</style>
