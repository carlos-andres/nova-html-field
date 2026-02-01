import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import cssInjectedByJsPlugin from 'vite-plugin-css-injected-by-js'
import path from 'path'

export default defineConfig({
    plugins: [vue(), cssInjectedByJsPlugin()],
    define: {
        'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV || 'production'),
    },
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        lib: {
            entry: path.resolve(__dirname, 'resources/js/field.js'),
            name: 'NovaHtmlField',
            formats: ['iife'],
            fileName: () => 'js/field.js',
        },
        rollupOptions: {
            external: ['vue', 'laravel-nova'],
            output: {
                globals: {
                    vue: 'Vue',
                    'laravel-nova': 'LaravelNova',
                },
                assetFileNames: 'css/[name][extname]',
            },
        },
        sourcemap: false,
        minify: 'esbuild',
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
})
