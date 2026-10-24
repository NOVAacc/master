import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";
export default defineConfig({
    plugins: [
        laravel({ input: ["resources/js/app.tsx", "resources/js/admin-story-preview.ts"], refresh: true }),
        react(),
        tailwindcss(),
    ],
});
