/**
 * OCR KTP/KK — **berjalan di BROWSER**, bukan di server.
 *
 * 🔴 Ini perbedaan yang disengaja dari portal Next.js (`app/api/ocr/ktp/route.ts`).
 * Keputusan user saat port dimulai: hosting tujuan adalah cPanel yang hanya
 * menjalankan PHP — tidak ada daemon Node, tidak ada binary `tesseract`, dan
 * jatah prosesnya kecil (`ulimit -u` = 35). Menjalankan OCR di sana mustahil.
 * Di browser, biayanya ditanggung perangkat warga dan servernya cuma menyajikan
 * berkas statis.
 *
 * Ikutan yang HILANG bersama pindahnya, dan memang tidak lagi diperlukan:
 * pembatas laju per-IP, penghitung `MAKS_BERSAMAAN`, dan batas unggah 8 MB —
 * ketiganya melindungi CPU server dari penyalahgunaan. Tidak ada CPU server yang
 * dipakai lagi. Yang TETAP dibawa: timeout (tanpa itu wasm yang gagal muat
 * membuat promise tidak pernah selesai — pernah terjadi di produksi SIDAKO &
 * TIDORE 11 Agu 2026), percobaan 4 orientasi, dan seluruh logika penguraian
 * teksnya, supaya hasilnya identik dengan portal lama.
 *
 * Mesin & data bahasa dilayani dari `public/ocr/` — TIDAK mengunduh dari CDN.
 * Selain karena kantor dinas sering di balik jaringan yang ketat, mengunduh
 * dari CDN berarti foto KTP diproses oleh kode yang sumbernya tidak kita kuasai.
 */

/** Batas penyiapan mesin OCR (muat wasm + data bahasa). */
const OCR_INIT_TIMEOUT = 30_000;
/** Batas satu kali pembacaan gambar. */
const OCR_TIMEOUT = 45_000;
/** Lebar kerja pra-proses — sama dengan yang dipakai sharp di portal lama. */
const LEBAR_KERJA = 1600;

const BASIS = '/ocr';

/**
 * Worker dipakai ulang (singleton) — muat wasm + data bahasa hanya SEKALI per
 * tab. Foto pertama menyiapkan mesin (beberapa detik), berikutnya langsung baca.
 */
let janjiWorker = null;

async function ambilWorker() {
  if (!janjiWorker) {
    // Dimuat dinamis: berkas mesinnya besar, dan kebanyakan pengunjung halaman
    // pendaftaran tidak pernah sampai memilih foto KTP.
    janjiWorker = import('tesseract.js')
      .then(({ createWorker, OEM }) =>
        createWorker('ind', OEM.LSTM_ONLY, {
          workerPath: `${BASIS}/worker.min.js`,
          corePath: BASIS,
          langPath: BASIS,
          gzip: true,
          cacheMethod: 'none',
        }),
      )
      .catch((e) => {
        janjiWorker = null; // izinkan percobaan berikutnya menyiapkan ulang
        throw e;
      });
  }

  return janjiWorker;
}

function denganTimeout(janji, ms) {
  return Promise.race([
    janji,
    new Promise((_, tolak) => setTimeout(() => tolak(new Error('timeout')), ms)),
  ]);
}

// ── Pra-proses gambar ────────────────────────────────────────────────────────

/** Muat berkas jadi <img> yang sudah siap digambar ke canvas. */
function muatGambar(file) {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file);
    const img = new window.Image();
    img.onload = () => {
      URL.revokeObjectURL(url);
      resolve(img);
    };
    img.onerror = () => {
      URL.revokeObjectURL(url);
      reject(new Error('gambar tidak terbaca'));
    };
    img.src = url;
  });
}

/**
 * Perbesar ke lebar kerja, jadikan abu-abu, lalu regangkan kontrasnya.
 *
 * Padanan `grayscale().normalize().sharpen()` milik sharp. Peregangan kontras
 * memakai persentil 2–98 supaya satu titik putih/hitam ekstrem (kilatan blitz
 * pada laminasi KTP itu biasa) tidak membuat seluruh gambar jadi datar.
 */
function praProses(img, sudut) {
  const skala = LEBAR_KERJA / img.width;
  const w = Math.round(img.width * skala);
  const h = Math.round(img.height * skala);

  const canvas = document.createElement('canvas');
  const tegak = sudut === 90 || sudut === 270;
  canvas.width = tegak ? h : w;
  canvas.height = tegak ? w : h;

  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.translate(canvas.width / 2, canvas.height / 2);
  if (sudut) ctx.rotate((sudut * Math.PI) / 180);
  ctx.drawImage(img, -w / 2, -h / 2, w, h);

  const data = ctx.getImageData(0, 0, canvas.width, canvas.height);
  const px = data.data;
  const histogram = new Uint32Array(256);

  for (let i = 0; i < px.length; i += 4) {
    const abu = (px[i] * 299 + px[i + 1] * 587 + px[i + 2] * 114) / 1000;
    px[i] = px[i + 1] = px[i + 2] = abu;
    histogram[abu | 0]++;
  }

  const total = px.length / 4;
  const bawah = persentil(histogram, total * 0.02);
  const atas = persentil(histogram, total * 0.98);
  const rentang = Math.max(1, atas - bawah);

  for (let i = 0; i < px.length; i += 4) {
    const v = Math.min(255, Math.max(0, ((px[i] - bawah) * 255) / rentang));
    px[i] = px[i + 1] = px[i + 2] = v;
  }

  ctx.putImageData(data, 0, 0);

  return canvas;
}

function persentil(histogram, ambang) {
  let kumulatif = 0;
  for (let i = 0; i < 256; i++) {
    kumulatif += histogram[i];
    if (kumulatif >= ambang) return i;
  }

  return 255;
}

// ── Penguraian teks (identik dengan portal Next.js) ──────────────────────────

/** Salah baca OCR yang umum pada digit → angka. */
function normalkanDigit(teks) {
  return teks
    .replace(/[OoQ]/g, '0')
    .replace(/[Il|!]/g, '1')
    .replace(/[Ss]/g, '5')
    .replace(/[B]/g, '8')
    .replace(/[Zz]/g, '2');
}

/**
 * Validasi struktur NIK 16 digit: 6 digit kode wilayah + tgl lahir (2, +40 utk
 * perempuan) + bulan (2) + tahun (2) + urut (4). Tanggal & bulan dicek agar
 * hasil ngawur ditolak.
 */
function nikSah(nik) {
  if (!/^\d{16}$/.test(nik)) return false;
  let hari = parseInt(nik.slice(6, 8), 10);
  const bulan = parseInt(nik.slice(8, 10), 10);
  if (hari > 40) hari -= 40; // perempuan

  return hari >= 1 && hari <= 31 && bulan >= 1 && bulan <= 12;
}

/**
 * Dari deretan digit, ambil jendela 16-digit yang valid sebagai NIK. Menangani
 * kasus OCR menyisipkan 1 digit liar (deret jadi 17+ digit).
 */
function pilihNik(digit) {
  if (digit.length < 16) return undefined;
  for (let i = 0; i + 16 <= digit.length; i++) {
    const calon = digit.slice(i, i + 16);
    if (nikSah(calon)) return calon;
  }

  return digit.length === 16 ? digit : undefined;
}

/** Skor keyakinan hasil urai — dipakai memilih orientasi terbaik. */
function skor(p) {
  return (p.nikBerlabel ? 3 : p.nik ? 1 : 0) + (p.nama ? 1 : 0) + (p.nokk ? 2 : 0);
}

/** Ekstrak NIK / No.KK (16 digit) dan Nama dari teks OCR KTP/KK. */
export function uraikanTeksKtp(mentah) {
  const hasil = {};
  const baris = String(mentah ?? '')
    .split('\n')
    .map((l) => l.trim())
    .filter(Boolean);

  for (const l of baris) {
    const digit = normalkanDigit(l).replace(/[^0-9]/g, '');
    const m16 = digit.match(/\d{16}/);

    if (!hasil.nokk && /(no\.?\s*kk|kartu\s*keluarga|nomor\s*kk)/i.test(l)) {
      // Ambil digit setelah ":" agar "No"/"KK" (huruf) tak jadi digit palsu.
      const ekor = l.includes(':') ? l.slice(l.lastIndexOf(':') + 1) : l;
      const digitEkor = normalkanDigit(ekor).replace(/[^0-9]/g, '');
      const mk = digitEkor.match(/\d{16}/);
      if (mk) {
        hasil.nokk = mk[0];
        continue;
      }
    }
    if (!hasil.nik && m16 && (/nik/i.test(l) || digit.length <= 20)) {
      const nik = pilihNik(digit);
      if (nik) {
        hasil.nik = nik;
        hasil.nikBerlabel = /nik/i.test(l);
      }
    }
    if (!hasil.nama && /nama/i.test(l) && !/keluarga/i.test(l)) {
      const sesudah = l.split(/[:∶]/)[1];
      if (sesudah) {
        const nama = sesudah.replace(/[^A-Za-z.,'\s-]/g, '').trim();
        if (nama.length >= 3) hasil.nama = nama;
      }
    }
  }

  if (!hasil.nik) {
    for (const deret of normalkanDigit(String(mentah ?? '')).match(/\d{16,}/g) ?? []) {
      const nik = pilihNik(deret);
      if (nik) {
        hasil.nik = nik;
        break;
      }
    }
  }

  return hasil;
}

// ── Muka umum ───────────────────────────────────────────────────────────────

export class OcrGagal extends Error {}

/**
 * Baca foto KTP/KK → `{ nik?, nokk?, nama? }`.
 *
 * Melempar `OcrGagal` dengan pesan siap-tampil bila mesinnya tak bisa disiapkan,
 * kehabisan waktu, atau tidak ada satu pun data yang terbaca. Pemanggilnya
 * memperlakukan kegagalan sebagai "isi manual saja", bukan sebagai penghalang.
 */
export async function bacaKtp(file) {
  let img;
  try {
    img = await muatGambar(file);
  } catch {
    throw new OcrGagal('Gambar tidak dapat diproses.');
  }

  let worker;
  try {
    worker = await denganTimeout(ambilWorker(), OCR_INIT_TIMEOUT);
  } catch {
    throw new OcrGagal('Pemindai tidak siap. Silakan isi datanya manual.');
  }

  // Banyak foto KTP/KK terpotret miring/terputar. Coba beberapa orientasi lalu
  // pakai hasil terbaik. Berhenti lebih awal begitu NIK berlabel atau No.KK
  // ditemukan — keduanya sudah tervalidasi struktur, jadi cukup meyakinkan.
  let terbaik = {};
  let skorTerbaik = -1;

  try {
    for (const sudut of [0, 90, 270, 180]) {
      const canvas = praProses(img, sudut);
      const { data } = await denganTimeout(worker.recognize(canvas), OCR_TIMEOUT);
      const urai = uraikanTeksKtp(data?.text ?? '');
      const nilai = skor(urai);
      if (nilai > skorTerbaik) {
        terbaik = urai;
        skorTerbaik = nilai;
      }
      if (urai.nikBerlabel || urai.nokk) break;
    }
  } catch (e) {
    throw new OcrGagal(
      e?.message === 'timeout'
        ? 'Pemindaian tidak selesai tepat waktu. Silakan isi datanya manual.'
        : 'Pemindaian gagal. Silakan isi data secara manual.',
    );
  }

  delete terbaik.nikBerlabel;

  if (!terbaik.nik && !terbaik.nama && !terbaik.nokk) {
    throw new OcrGagal(
      'Teks KTP/KK tidak terbaca. Coba foto ulang dengan pencahayaan lebih baik.',
    );
  }

  return terbaik;
}
