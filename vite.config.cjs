const { defineConfig } = require('vite');
const laravelPlugin = require('laravel-vite-plugin');
const laravel = laravelPlugin.default ?? laravelPlugin;

module.exports = defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard.js'],
            refresh: true,
        }),
    ],
});
