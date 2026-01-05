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
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('echarts')) {
                            return 'vendor-echarts';
                        }
                        if (id.includes('@heroicons') || id.includes('lodash')) {
                            return 'vendor-utils';
                        }
                        return 'vendor';
                    }
                }
            }
        },
        chunkSizeWarningLimit: 600,
    }
});
