import { defineStore } from "pinia";
import { ref } from "vue";

export interface MenuItem {
    id: string;
    title: string;
    slug: string;
    icon: string;
    classes: string;
    submenu: {
        title: string;
        slug: string;
    }[];
}

export type MenuStateGroup = MenuItem[];

export const useMenuStore = defineStore("menu", () => {
    const menuGroups = ref<MenuStateGroup[]>([]);

    function loadMenuFromState() {
        try {
            const scriptTag = document.getElementById("swilen-state");
            if (scriptTag) {
                const state = JSON.parse(scriptTag.textContent || "{}");
                if (state && state.menu) {
                    menuGroups.value = state.menu;
                }
            }
        } catch (error) {
            console.error("Failed to parse Swilen state:", error);
        }
    }

    return {
        menuGroups,
        loadMenuFromState,
    };
});
