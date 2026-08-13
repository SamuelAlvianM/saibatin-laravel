/**
 * Salin mesin OCR (tesseract.js) dari node_modules ke `public/ocr/`.
 *
 * 🔴 Kenapa disalin, bukan diambil dari CDN: foto KTP warga tidak boleh
 * diproses kode yang sumbernya tidak kita kuasai, dan jaringan kantor dinas
 * sering memblokir CDN. Kenapa tidak ikut di-git: 12 MB berkas biner yang bisa
 * dibuat ulang dari `node_modules` — sama alasannya dengan `public/build`.
 *
 * Hanya varian **LSTM** yang disalin; OEM yang dipakai `LSTM_ONLY`, jadi varian
 * lain hanya menggandakan ukuran unggahan ke cPanel tanpa pernah terpakai.
 * Peramban memilih SATU di antara ketiganya sesuai dukungan SIMD-nya.
 *
 * Jalankan: `npm run ocr:aset` (sudah otomatis lewat `npm run build`).
 */
import { copyFileSync, existsSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const akar = join(dirname(fileURLToPath(import.meta.url)), '..');
const tujuan = join(akar, 'public', 'ocr');

const berkas = [
  ['node_modules/tesseract.js/dist/worker.min.js', 'worker.min.js'],
  ['node_modules/tesseract.js-core/tesseract-core-lstm.wasm.js', 'tesseract-core-lstm.wasm.js'],
  ['node_modules/tesseract.js-core/tesseract-core-simd-lstm.wasm.js', 'tesseract-core-simd-lstm.wasm.js'],
  ['node_modules/tesseract.js-core/tesseract-core-relaxedsimd-lstm.wasm.js', 'tesseract-core-relaxedsimd-lstm.wasm.js'],
];

// Data bahasa Indonesia TIDAK ada di node_modules — tesseract.js mengunduhnya
// dari CDN saat runtime, yang justru ingin dihindari. Berkasnya diambil dari
// portal Next.js yang sudah membundelnya, dan ikut di-git di `tessdata/`.
const tessdata = ['tessdata/ind.traineddata.gz', 'ind.traineddata.gz'];

mkdirSync(tujuan, { recursive: true });

let disalin = 0;
let hilang = [];

for (const [asal, nama] of [...berkas, tessdata]) {
  const sumber = join(akar, asal);
  if (!existsSync(sumber)) {
    hilang.push(asal);
    continue;
  }
  copyFileSync(sumber, join(tujuan, nama));
  disalin++;
}

if (hilang.length > 0) {
  console.warn(
    `⚠️  Aset OCR tidak lengkap — ${hilang.length} berkas tidak ditemukan:\n   ` +
    hilang.join('\n   ') +
    '\n   Pembacaan foto KTP akan gagal dengan "Pemindai tidak siap".',
  );
}

console.log(`Aset OCR: ${disalin} berkas disalin ke public/ocr/`);
