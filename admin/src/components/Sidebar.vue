<template>
    <Menu :model="menuItems" class="w-full" />
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { Menu, TieredMenu } from "primevue";
import { useMenuStore } from "../store/menu";

const store = useMenuStore();
const router = useRouter();

const menuItems = computed(() => {
    const items: any[] = [];

    store.menuGroups.forEach((group, index) => {
        // Add items in the group
        group.forEach(item => {
            const menuItem: any = {
                key: item.id,
                label: item.title,
                icon: 'pi pi-fw ' + item.icon,
                command: () => navigateTo(item.slug)
            };

            if (item.submenu && item.submenu.length > 0) {
                menuItem.items = item.submenu.map(sub => ({
                    key: sub.slug,
                    label: sub.title,
                    command: () => navigateTo(sub.slug)
                }));
            }

            items.push(menuItem);
        });

        // Add separator if not the last group
        if (index < store.menuGroups.length - 1) {
            items.push({ separator: true });
        }
    });

    return items;
});

function navigateTo(slug: string) {
    // Remove .php from the path part of the slug if present. Example: edit.php?post_type=page -> edit?post_type=page
    let cleanSlug = slug;

    if (cleanSlug === 'index.php') {
        cleanSlug = '';
    } else {
        const parts = cleanSlug.split('?');
        parts[0] = parts[0].replace(/\.php$/, '');
        cleanSlug = parts.join('?');
    }

    if (!cleanSlug.startsWith('/')) {
        cleanSlug = '/' + cleanSlug;
    }

    router.push(cleanSlug);
}

onMounted(() => {
    store.loadMenuFromState();
});
</script>
