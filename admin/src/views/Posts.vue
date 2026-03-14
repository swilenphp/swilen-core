<template>
    <div class="posts-view">
        <div class="flex justify-between items-center mb-4">
            <h1 class="m-0 text-2xl font-semibold">Posts</h1>
            <Button label="Add New Post" icon="pi pi-plus" outlined />
        </div>

        <DataTable
            :value="posts"
            :paginator="true"
            :rows="10"
            tableStyle="min-width: 50rem"
        >
            <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
            <Column field="title" header="Title" sortable style="width: 40%">
                <template #body="slotProps">
                    <div class="flex align-items-center gap-2 font-medium">
                        {{ slotProps.data.title }}
                        <span
                            v-if="slotProps.data.status === 'Draft'"
                            class="text-500 text-sm"
                        >
                            — Draft</span
                        >
                    </div>
                    <div
                        class="flex gap-2 text-sm mt-1 actions-row opacity-0 transition-opacity-duration-200"
                    >
                        <a
                            href="#"
                            class="text-primary no-underline hover:underline"
                            >Edit</a
                        >
                        |
                        <a
                            href="#"
                            class="text-primary no-underline hover:underline"
                            >Quick Edit</a
                        >
                        |
                        <a
                            href="#"
                            class="text-red-400 no-underline hover:underline"
                            >Trash</a
                        >
                        |
                        <a
                            href="#"
                            class="text-primary no-underline hover:underline"
                            >View</a
                        >
                    </div>
                </template>
            </Column>
            <Column field="author" header="Author" sortable></Column>
            <Column field="categories" header="Categories">
                <template #body="slotProps">
                    {{ slotProps.data.categories.join(", ") }}
                </template>
            </Column>
            <Column field="tags" header="Tags">
                <template #body="slotProps">
                    {{ slotProps.data.tags.join(", ") || "—" }}
                </template>
            </Column>
            <Column field="date" header="Date" sortable>
                <template #body="slotProps">
                    <div class="line-height-3">
                        {{
                            slotProps.data.status === "Published"
                                ? "Published"
                                : "Last Modified"
                        }}<br />
                        {{ slotProps.data.date }}
                    </div>
                </template>
            </Column>
            <Column headerStyle="width: 4rem" bodyStyle="text-align: center">
                <template #body>
                    <Button icon="pi pi-ellipsis-v" text rounded />
                </template>
            </Column>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";

const posts = ref([
    {
        id: 1,
        title: "Hello World",
        author: "admin",
        categories: ["Uncategorized"],
        tags: [],
        date: "2023/10/25 at 10:30 am",
        status: "Published",
    },
    {
        id: 2,
        title: "Welcome to FluxPress",
        author: "admin",
        categories: ["News", "Announcements"],
        tags: ["fluxpress", "welcome"],
        date: "2023/10/26 at 2:15 pm",
        status: "Published",
    },
    {
        id: 3,
        title: "Top 10 Vue Tricks",
        author: "editor",
        categories: ["Development"],
        tags: ["vue", "frontend"],
        date: "2023/10/27 at 9:00 am",
        status: "Draft",
    },
    {
        id: 4,
        title: "Understanding Headless CMS",
        author: "admin",
        categories: ["Architecture"],
        tags: ["headless", "api"],
        date: "2023/10/28 at 4:45 pm",
        status: "Published",
    },
    {
        id: 5,
        title: "Customizing PrimeVue Themes",
        author: "designer",
        categories: ["Design"],
        tags: ["css", "primevue"],
        date: "2023/10/29 at 11:20 am",
        status: "Published",
    },
]);

onMounted(() => {
    // In a real app we would load from an API here
});
</script>

<style scoped>
.posts-view h1 {
    color: var(--p-text-color);
}
.surface-card {
    background-color: var(--p-surface-900);
}
:deep(tr:hover) .actions-row {
    opacity: 1 !important;
}
</style>
