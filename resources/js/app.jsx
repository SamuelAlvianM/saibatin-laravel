import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

import Toaster from '@/Components/ui/toast';

/**
 * Titik masuk sisi klien.
 *
 * Berkas ini dan seluruh komponen di Pages/ dikompilasi Vite menjadi berkas
 * statis di `public/build`. Di server hanya PHP yang berjalan — tidak ada Node,
 * tidak ada proses yang harus dijaga hidup.
 *
 * 🔴 SENGAJA TANPA SSR. Inertia SSR membutuhkan daemon Node yang tidak tersedia
 * di hosting target. Konsekuensinya halaman Inertia tidak ramah mesin pencari —
 * karena itu halaman publik (beranda, berita, PPID, produk) TIDAK dibangun di
 * sini, melainkan sebagai Blade yang dirender server.
 */
createInertiaApp({
    title: (title) => (title ? `${title} — SAIBATIN` : 'SAIBATIN'),

    // Setiap halaman jadi chunk sendiri (glob TANPA `eager`), bukan satu bundel
    // raksasa. Dengan 16+ halaman dashboard plus situs publik, memuat semuanya
    // di muka berarti warga yang cuma membuka /login ikut mengunduh seluruh
    // dashboard petugas. Vite menyusun chunk-nya, Laravel menyajikannya statis —
    // tetap tanpa Node di server.
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx');
        const muat = pages[`./Pages/${name}.jsx`];
        if (!muat) throw new Error(`Halaman Inertia tidak ditemukan: ${name}`);
        return muat();
    },

    // `Toaster` sengaja BERSANDING dengan `App`, bukan di dalamnya: ia membaca
    // `flash` lewat `router.on('success')`, bukan `usePage()`, jadi tidak perlu
    // konteks Inertia — dan cukup dipasang sekali di sini untuk seluruh halaman.
    setup({ el, App, props }) {
        createRoot(el).render(
            <>
                <App {...props} />
                <Toaster awal={props.initialPage.props.flash} />
            </>,
        );
    },

    progress: { color: '#2176bd' },
});
