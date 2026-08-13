import '../css/app.css';

import { createRoot } from 'react-dom/client';

/**
 * Titik masuk halaman PUBLIK — bukan Inertia.
 *
 * Halaman publik dirender Blade di server supaya isinya terbaca mesin pencari
 * (port ini sengaja tanpa Inertia SSR — lihat `app.jsx`). Bagian yang memang
 * butuh interaksi dipasang di atas HTML itu sebagai **island**: sepotong React
 * yang hidup di dalam satu elemen, bukan aplikasi yang mengambil alih halaman.
 *
 * Cara memakainya dari Blade:
 *
 *     <div data-island="Navbar" data-props='@json($props)'></div>
 *
 * `data-island` adalah nama berkas di `resources/js/Publik/`. Island dimuat
 * MALAS (dynamic import) dan hanya kalau elemennya benar-benar ada di halaman,
 * jadi halaman berita tidak ikut mengunduh kode peta demografi.
 */
const pulau = import.meta.glob('./Publik/*.jsx');

function bacaProps(el) {
    const mentah = el.dataset.props;
    if (!mentah) return {};
    try {
        return JSON.parse(mentah);
    } catch (e) {
        // Island yang props-nya rusak lebih baik tetap tampil kosong daripada
        // menjatuhkan seluruh halaman — sisanya masih HTML biasa yang berguna.
        console.error(`Props island "${el.dataset.island}" bukan JSON yang sah`, e);
        return {};
    }
}

function pasang(el) {
    const nama = el.dataset.island;
    const muat = pulau[`./Publik/${nama}.jsx`];

    if (!muat) {
        console.error(`Island tidak ditemukan: ${nama}`);
        return;
    }

    muat().then((mod) => {
        const Komponen = mod.default;
        createRoot(el).render(<Komponen {...bacaProps(el)} />);
    });
}

document.querySelectorAll('[data-island]').forEach(pasang);
