import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import liveReload from "vite-plugin-live-reload";
import vueDevTools from "vite-plugin-vue-devtools";
import legacy from "@vitejs/plugin-legacy";
import fs from "node:fs/promises";
import { resolve } from "path";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        vue(),
        vueDevTools(),
        liveReload([
            __dirname + "/(templates|views)/**/*.php",
            // __dirname + "/(templates|views)/**/*.twig",
        ]),
        legacy({
            targets: ["defaults", "not IE 11"],
            additionalLegacyPolyfills: ["regenerator-runtime/runtime"],
            modernPolyfills: true,
        }),
        {
            name: "copy-file",
            async buildStart(config) {
                if (process.env.NODE_ENV === "development") {
                    const path = __dirname + "/dist";
                    await fs.rm(path, { recursive: true, force: true });

                    const outDir = resolve(
                        __dirname,
                        "../public/wp-admin/assets",
                    );
                    await fs.rm(outDir, { recursive: true, force: true });
                }
            },
        },
        tailwindcss(),
    ],
    resolve: {
        alias: {
            "@": resolve(__dirname, "src"),
        },
    },
    optimizeDeps: {
        include: ["vue", "pinia", "axios", "vue-router"],
        exclude: ["jquery"],
    },
    build: {
        outDir: "../public/wp-admin/assets",
        emptyOutDir: true,
        manifest: true,
        minify: true,
        cssCodeSplit: true,
        rollupOptions: {
            input: resolve(__dirname, "src/main.ts"),
            output: {
                assetFileNames: "[hash].[ext]",
                entryFileNames: "[name].[hash].js",
                chunkFileNames: "[hash].js",
                globals: {
                    jquery: "window.$",
                },
            },
        },
    },
    server: {
        port: 9010,
        strictPort: true,
        cors: true,
        origin: "http://localhost:9010",
        hmr: {
            protocol: "ws",
            host: "localhost",
        },
    },
    define: {
        __DEV__: process.env.NODE_ENV === "development",
    },
});
