import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import path from 'node:path';

export default defineConfig({
    resolve: {
        // `@/…` menunjuk ke resources/js — pola yang sama dipakai portal Next.js,
        // supaya komponen yang dipindahkan tidak perlu ditulis ulang import-nya.
        alias: { '@': path.resolve(import.meta.dirname, 'resources/js') },
    },
    plugins: [
        laravel({
            // Dua titik masuk yang sengaja dipisah: `app.jsx` untuk dashboard &
            // formulir (Inertia), `publik.jsx` untuk situs publik (Blade +
            // island). Warga yang membuka beranda tidak ikut mengunduh runtime
            // Inertia beserta seluruh halaman dashboard petugas.
            input: ['resources/css/app.css', 'resources/js/app.jsx', 'resources/js/publik.jsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    // Build ini menghasilkan berkas STATIS di public/build — tidak ada Node yang
    // berjalan di server. Yang diunggah ke hosting adalah hasilnya, bukan
    // toolchain-nya.
    //
    // ⚠️ JANGAN menambahkan `build: { manifest: true }` di sini. Sejak Vite 5,
    // opsi itu menaruh manifest di `public/build/.vite/manifest.json`, sedangkan
    // Laravel mencarinya di `public/build/manifest.json` → setiap halaman 500
    // dengan ViteManifestNotFoundException. `laravel-vite-plugin` sudah mengatur
    // lokasinya dengan benar; biarkan saja.
});
