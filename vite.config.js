import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Plugin custom: remove referencias a TTF e WOFF do CSS final,
// e impede que Vite gere esses arquivos no bundle.
// Browsers modernos (>2018) suportam WOFF2 nativo - TTF/WOFF sao redundantes.
// Resultado: bundle de fontes vai de ~4MB para ~457KB (-88%).
function woff2Only() {
    return {
        name: 'woff2-only',
        // Remove o assetFileNames das fontes TTF/WOFF (assim Vite nao copia)
        generateBundle(_, bundle) {
            for (const fileName of Object.keys(bundle)) {
                if (fileName.match(/\.(ttf|woff)(\?.*)?$/i)) {
                    delete bundle[fileName];
                }
            }
        },
        // Modifica o CSS pra remover as referencias url(...ttf) e url(...woff)
        renderChunk(code, chunk) {
            // Nao se aplica a chunks JS
            return null;
        },
        // Hook que roda no CSS: remove url() de ttf/woff do @font-face
        transform(code, id) {
            if (id.includes('tabler-icons') && id.endsWith('.css')) {
                // Remove ",url(...ttf...) format('truetype')" e ",url(...woff...) format('woff')"
                code = code.replace(/,url\([^)]*\.ttf[^)]*\)\s*format\(["']?truetype["']?\)/gi, '');
                code = code.replace(/,url\([^)]*\.woff[^)]*\)\s*format\(["']?woff["']?\)/gi, '');
                return { code, map: null };
            }
            return null;
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        woff2Only(),
    ],
    build: {
        minify: 'esbuild',
        cssCodeSplit: true,
        chunkSizeWarningLimit: 500,
        rollupOptions: {
            output: {
                entryFileNames: 'assets/[name]-[hash].js',
                chunkFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
    },
});
