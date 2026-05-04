import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/pages/dashboard.js",
                "resources/js/pages/tasks-index.js",
                "resources/js/pages/recurring-tasks-index.js",
                "resources/js/pages/categories-index.js",
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
