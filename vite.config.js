import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        host: '0.0.0.0',
        watch: {
            usePolling: false,
            ignored: [
                '**/node_modules/**',
                '**/vendor/**',
                '**/storage/**',
                '**/bootstrap/**',
                '**/database/**'
            ]
        }
    },
    build: {
        sourcemap: false,
        rollupOptions: {
            maxParallelFileOps: 2,
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('echarts') || id.includes('zrender')) {
                            return 'vendor-echarts';
                        }
                        return 'vendor';
                    }
                }
            }
        },
        chunkSizeWarningLimit: 1000,
    }
});
