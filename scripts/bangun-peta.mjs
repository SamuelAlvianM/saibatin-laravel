/**
 * Bangun geometri peta statistik kependudukan (halaman GIS + PDF), DUA
 * keluaran dari satu penyederhanaan — bentuknya dijamin identik:
 *   - resources/js/lib/peta-wilayah.js  → GeoJSON bujur/lintang untuk Highcharts Maps (island)
 *   - resources/peta/peta-wilayah.json  → path SVG untuk PDF dompdf (dibaca PHP)
 *
 *   node scripts/bangun-peta.mjs <folder-geojson>
 *
 * Sumber: batas BPS 2015 dari github.com/ArrayAccess/Indonesia-Postal-And-Area
 * (lisensi MIT), berkas `data/geojson/62/…`. Unduh berkas kecamatan & tetangga
 * yang disebut blok WILAYAH ke satu folder (nama berkas = kode BPS).
 *
 * 🔴 HASILNYA DI-COMMIT, SKRIP INI JARANG DIJALANKAN. README repo sumber
 * mengumumkan datanya akan dihapus pemiliknya — jangan memuat dari GitHub
 * saat runtime.
 *
 * ⚠️ DARATAN TETANGGA IKUT DIGAMBAR supaya wilayah tidak tampak melayang di
 * latar kosong (peta "bolong").
 *
 * 🔴 Kecamatan dicocokkan lewat NAMA, bukan kode: geometri memakai kode BPS,
 * basis data memakai kode Kemendagri — dua penomoran berbeda.
 */
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';

// ── WILAYAH: Kabupaten Pesisir Barat (BPS 1813) ─────────────────────────────
const BUJUR_MIN = 103.55;
const BUJUR_MAKS = 104.75;
const LINTANG_MIN = -6.0;
const LINTANG_MAKS = -4.8;

const KECAMATAN = [
    { bps: '1813010', nama: 'LEMONG' },
    { bps: '1813020', nama: 'PESISIR UTARA' },
    { bps: '1813030', nama: 'PULAU PISANG' },
    { bps: '1813040', nama: 'KARYA PENGGAWA' },
    { bps: '1813050', nama: 'WAY KRUI' },
    { bps: '1813060', nama: 'PESISIR TENGAH' },
    { bps: '1813070', nama: 'KRUI SELATAN' },
    { bps: '1813080', nama: 'PESISIR SELATAN' },
    { bps: '1813090', nama: 'NGAMBUR' },
    { bps: '1813100', nama: 'NGARAS' },
    { bps: '1813110', nama: 'BANGKUNAT' },
];

const TETANGGA = [
    { kode: '1704', nama: 'Kaur' },
    { kode: '1801', nama: 'Lampung Barat' },
    { kode: '1802', nama: 'Tanggamus' },
];
// ────────────────────────────────────────────────────────────────────────────

const LEBAR = 1000;
const SKALA = LEBAR / (BUJUR_MAKS - BUJUR_MIN);
const TINGGI = Math.round((LINTANG_MAKS - LINTANG_MIN) * SKALA);

const proyeksi = ([bujur, lintang]) => [(bujur - BUJUR_MIN) * SKALA, (LINTANG_MAKS - lintang) * SKALA];
const balik = ([x, y]) => [
    Math.round((BUJUR_MIN + x / SKALA) * 1e5) / 1e5,
    Math.round((LINTANG_MAKS - y / SKALA) * 1e5) / 1e5,
];

/** Douglas–Peucker, toleransi dalam satuan viewBox. */
function sederhanakan(c, tol) {
    if (c.length < 4) return c;
    const simpan = new Uint8Array(c.length);
    simpan[0] = simpan[c.length - 1] = 1;
    const tumpuk = [[0, c.length - 1]];
    while (tumpuk.length) {
        const [a, b] = tumpuk.pop();
        const [ax, ay] = c[a];
        const [bx, by] = c[b];
        const dx = bx - ax;
        const dy = by - ay;
        const pj = Math.hypot(dx, dy) || 1;
        let jauh = -1;
        let idx = -1;
        for (let i = a + 1; i < b; i++) {
            const d = Math.abs(dy * c[i][0] - dx * c[i][1] + bx * ay - by * ax) / pj;
            if (d > jauh) {
                jauh = d;
                idx = i;
            }
        }
        if (jauh > tol) {
            simpan[idx] = 1;
            tumpuk.push([a, idx], [idx, b]);
        }
    }
    return c.filter((_, i) => simpan[i]);
}

/** Sutherland–Hodgman: potong cincin ke persegi [x0,y0,x1,y1]. */
function potong(c, [x0, y0, x1, y1]) {
    const sisi = [
        [(p) => p[0] >= x0, (a, b) => [x0, a[1] + ((b[1] - a[1]) * (x0 - a[0])) / (b[0] - a[0])]],
        [(p) => p[0] <= x1, (a, b) => [x1, a[1] + ((b[1] - a[1]) * (x1 - a[0])) / (b[0] - a[0])]],
        [(p) => p[1] >= y0, (a, b) => [a[0] + ((b[0] - a[0]) * (y0 - a[1])) / (b[1] - a[1]), y0]],
        [(p) => p[1] <= y1, (a, b) => [a[0] + ((b[0] - a[0]) * (y1 - a[1])) / (b[1] - a[1]), y1]],
    ];
    let hasil = c;
    for (const [dalam, titik] of sisi) {
        const masuk = hasil;
        hasil = [];
        for (let i = 0; i < masuk.length; i++) {
            const kini = masuk[i];
            const sebelum = masuk[(i + masuk.length - 1) % masuk.length];
            if (dalam(kini)) {
                if (!dalam(sebelum)) hasil.push(titik(sebelum, kini));
                hasil.push(kini);
            } else if (dalam(sebelum)) {
                hasil.push(titik(sebelum, kini));
            }
        }
        if (!hasil.length) break;
    }
    return hasil;
}

function luas(c) {
    let s = 0;
    for (let i = 0; i < c.length; i++) {
        const [x1, y1] = c[i];
        const [x2, y2] = c[(i + 1) % c.length];
        s += x1 * y2 - x2 * y1;
    }
    return s / 2;
}

function titikBerat(c) {
    let a = 0;
    let cx = 0;
    let cy = 0;
    for (let i = 0; i < c.length; i++) {
        const [x1, y1] = c[i];
        const [x2, y2] = c[(i + 1) % c.length];
        const f = x1 * y2 - x2 * y1;
        a += f;
        cx += (x1 + x2) * f;
        cy += (y1 + y2) * f;
    }
    return [cx / (3 * a), cy / (3 * a)];
}

function cincinDari(berkas) {
    const j = JSON.parse(readFileSync(berkas, 'utf8'));
    const semua = [];
    for (const f of j.features ?? [j]) {
        const g = f.geometry;
        const poligon = g.type === 'MultiPolygon' ? g.coordinates : [g.coordinates];
        for (const p of poligon) for (const r of p) semua.push(r);
    }
    return semua;
}

/**
 * `lebih` = cakupan di luar kotak tampilan (satuan viewBox). Daratan tetangga
 * dipotong jauh di luar kotak: peta web bertinggi tetap bisa lebih lebar dari
 * rasionya, dan potongan dekat tampak sebagai garis pantai lurus buatan.
 */
function olah(berkas, tol, minLuas, lebih = [20, 20]) {
    const [px, py] = lebih;
    const kotak = [-px, -py, LEBAR + px, TINGGI + py];
    const hasil = [];
    for (const mentah of cincinDari(berkas)) {
        // Cincin GeoJSON tertutup; Douglas–Peucker pada garis berujung-pangkal
        // sama membuang SEMUA titik, jadi titik penutupnya dilepas dulu.
        const c = potong(mentah.slice(0, -1).map(proyeksi), kotak);
        if (c.length < 3) continue;
        const s = sederhanakan(c, tol);
        if (s.length < 3 || Math.abs(luas(s)) < minLuas) continue;
        hasil.push(s);
    }
    return hasil;
}

const keJalur = (cincin) =>
    cincin.map((c) => 'M' + c.map(([x, y]) => `${x.toFixed(1)},${y.toFixed(1)}`).join('L') + 'Z').join('');

const keGeometri = (cincin) => ({
    type: 'MultiPolygon',
    coordinates: cincin.map((c) => {
        const r = c.map(balik);
        return [[...r, r[0]]];
    }),
});

const folder = process.argv[2];
if (!folder) {
    console.error('Pakai: node scripts/bangun-peta.mjs <folder-geojson>');
    process.exit(1);
}

const kecamatan = KECAMATAN.map(({ bps, nama }) => {
    // Pulau Pisang hanya ±1,5 km — minLuas kecil supaya pulaunya tidak terbuang.
    const cincin = olah(join(folder, `${bps}.json`), 0.5, 0.3);
    const terbesar = cincin.reduce((a, b) => (Math.abs(luas(b)) > Math.abs(luas(a)) ? b : a));
    const [x, y] = titikBerat(terbesar);
    return { nama, bps, d: keJalur(cincin), pusat: [Math.round(x), Math.round(y)], geometri: keGeometri(cincin) };
});

/*
 * 🔴 DUA potongan untuk tetangga. Web (Highcharts) memotong sendiri ke kotak
 * tampilannya, jadi GeoJSON-nya boleh lebar. PDF tidak: php-svg-lib di dompdf
 * MENGABAIKAN viewBox sebagai batas gambar — path di luar kotak ikut tergambar
 * dan daratan abu-abu meluber menutupi judul halaman. Path SVG untuk PDF
 * karenanya dipotong pas di tepi kotak.
 */
const tetangga = TETANGGA.map(({ kode, nama }) => {
    const lebar = olah(join(folder, `${kode}.json`), 0.9, 3, [500, 150]);
    const pas = olah(join(folder, `${kode}.json`), 0.9, 3, [0, 0]);
    return { nama, d: keJalur(pas), geometri: keGeometri(lebar) };
}).filter((t) => t.d);

const atribusi =
    'Batas kecamatan BPS 2015, disederhanakan. Sumber: github.com/ArrayAccess/Indonesia-Postal-And-Area (lisensi MIT). Batas bersifat indikatif, bukan acuan batas wilayah resmi.';

// 1) Untuk PHP (PDF dompdf).
const jalurJson = join(process.cwd(), 'resources', 'peta', 'peta-wilayah.json');
mkdirSync(dirname(jalurJson), { recursive: true });
writeFileSync(
    jalurJson,
    JSON.stringify({
        _catatan: 'DIBANGKITKAN oleh scripts/bangun-peta.mjs — jangan disunting tangan. ' + atribusi,
        lebar: LEBAR,
        tinggi: TINGGI,
        kecamatan: kecamatan.map(({ geometri: _g, ...k }) => k),
        tetangga: tetangga.map(({ geometri: _g, ...t }) => t),
    }),
);

// 2) Untuk island Highcharts.
const fitur = (props, geometry) => ({ type: 'Feature', properties: props, geometry });
const jalurJs = join(process.cwd(), 'resources', 'js', 'lib', 'peta-wilayah.js');
writeFileSync(
    jalurJs,
    `/**
 * Geometri peta kecamatan — DIBANGKITKAN oleh scripts/bangun-peta.mjs, jangan
 * disunting tangan. Identik dengan resources/peta/peta-wilayah.json (PDF).
 *
 * ${atribusi}
 */

/** Satuan viewBox kotak tampilan (dipakai posisi label). */
export const PETA_LEBAR = ${LEBAR};
export const PETA_TINGGI = ${TINGGI};

/** Kotak tampilan awal [bujur barat, lintang selatan, bujur timur, lintang utara]. */
export const BATAS_TAMPILAN = [${BUJUR_MIN}, ${LINTANG_MIN}, ${BUJUR_MAKS}, ${LINTANG_MAKS}];

/** Satuan viewBox → [bujur, lintang]. */
export const keBujurLintang = (x, y) => [${BUJUR_MIN} + x / ${SKALA}, ${LINTANG_MAKS} - y / ${SKALA}];

/** Nama (persis kolom \`wilayah\` DB), kode BPS, titik berat poligon terbesar (viewBox). */
export const KECAMATAN_PETA = ${JSON.stringify(kecamatan.map(({ nama, bps, pusat }) => ({ nama, bps, pusat })))};

export const GEOJSON_KECAMATAN = ${JSON.stringify({
        type: 'FeatureCollection',
        features: kecamatan.map((k) => fitur({ nama: k.nama, bps: k.bps }, k.geometri)),
    })};

export const GEOJSON_TETANGGA = ${JSON.stringify({
        type: 'FeatureCollection',
        features: tetangga.map((t) => fitur({ nama: t.nama }, t.geometri)),
    })};
`,
);

console.log(
    `✓ viewBox ${LEBAR}×${TINGGI}, ${kecamatan.length} kecamatan, ${tetangga.length} tetangga\n` +
        kecamatan.map((k) => `  ${k.nama.padEnd(16)} pusat ${k.pusat.join(',')}`).join('\n'),
);
