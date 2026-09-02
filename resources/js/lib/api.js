/**
 * Pemanggil endpoint JSON portal (di luar jalur form Inertia).
 *
 * 🔴 SOAL CSRF — dua header, dua isi yang BERBEDA. Tertukar = 419 tanpa
 * penjelasan apa pun di pesannya:
 *
 *   X-CSRF-TOKEN   ← token MENTAH (dari <meta name="csrf-token">)
 *   X-XSRF-TOKEN   ← nilai cookie XSRF-TOKEN, yang TERENKRIPSI; Laravel
 *                    mendekripsinya lebih dulu
 *
 * Yang dipakai di sini adalah **cookie**, bukan meta. Alasannya penting:
 * `<meta>` hanya dirender saat pemuatan halaman penuh, sedangkan Inertia tidak
 * pernah merender ulang <head>. Begitu sesi diregenerasi — dan itu terjadi
 * setiap kali orang login — token di meta jadi basi sementara halamannya masih
 * hidup, sehingga SEMUA POST sesudah login gagal 419. Cookie XSRF-TOKEN
 * disegarkan di setiap respons, jadi ia selalu ikut.
 *
 * Meta dipakai hanya sebagai cadangan bila cookie tidak terbaca.
 */

/**
 * Baca badan JSON sebuah balasan, dan **ratakan dua bentuk galat jadi satu**.
 *
 * 🔴 Server ini memakai DUA bentuk yang berbeda, sementara seluruh pemanggil
 * hanya mengenal yang pertama:
 *
 *   Balasan::gagal()      → 4xx  { error: ["Info: …"] }
 *   $request->validate()  → 422  { message, errors: { medan: ["…"] } }
 *
 * Lima controller memakai `validate()` — `ProfilController`, `AspirasiController`,
 * `LoginController`, `RegisterController`, `SandiController` — dan balasan 422
 * mereka LOLOS dari `if (j.error?.length)` di setiap pemanggil, lalu jatuh ke
 * cabang sukses. Permintaan yang DITOLAK terbaca sebagai BERHASIL: menyimpan
 * Profil dengan No. HP tidak valid menampilkan "Profil diperbarui" padahal tidak
 * ada yang tersimpan, dan `router.reload()` sesudahnya menutupi jejaknya dengan
 * mengembalikan nilai lama seolah itu memang hasilnya. Tidak ada galat, tidak
 * ada peringatan konsol, tidak ada yang terlihat rusak.
 *
 * Diratakan di SATU tempat supaya seluruh pemanggil ikut sembuh sekaligus,
 * bukan ditambal layar demi layar. `medan` ikut dibawa agar formulir bisa
 * menandai isian yang bersangkutan, bukan cuma menampilkan pesan umum.
 *
 * ⚠️ Menambah endpoint baru? Pakai `Balasan::gagal` ATAU `validate()` —
 * keduanya kini aman. Jangan menambah bentuk ketiga.
 */
async function bacaJson(res, pesanGagal) {
  let j;

  try {
    j = await res.json();
  } catch {
    return { error: [pesanGagal], success: [], data: null };
  }

  if (! res.ok && ! j?.error?.length && j?.errors && typeof j.errors === 'object') {
    return {
      ...j,
      error: Object.values(j.errors).flat(),
      medan: Object.fromEntries(
        Object.entries(j.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)]),
      ),
      success: [],
      data: null,
    };
  }

  return j;
}

function tokenCsrf() {
  const cookie = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

  if (cookie) {
    return { 'X-XSRF-TOKEN': decodeURIComponent(cookie[1]) };
  }

  const meta = document.querySelector('meta[name="csrf-token"]')?.content;
  return meta ? { 'X-CSRF-TOKEN': meta } : {};
}

export async function kirimJson(url, body, metode = 'POST') {
  const res = await fetch(url, {
    method: metode,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...tokenCsrf(),
    },
    body: body === undefined ? undefined : JSON.stringify(body),
  });

  // 419 dibedakan dari galat validasi biasa: yang dibutuhkan pengguna bukan
  // "coba lagi", melainkan memuat ulang halaman.
  if (res.status === 419) {
    return {
      error: ['Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.'],
      success: [], data: null,
    };
  }

  return bacaJson(res, 'Gagal menghubungi server. Coba lagi.');
}

/**
 * Unggah berkas (multipart).
 *
 * Content-Type SENGAJA tidak diset — peramban harus menyusunnya sendiri
 * lengkap dengan `boundary`. Mengisinya manual membuat berkas tidak terbaca
 * di sisi server.
 */
export async function kirimBerkas(url, formData) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...tokenCsrf() },
    body: formData,
  });

  if (res.status === 419) {
    return { error: ['Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.'], success: [], data: null };
  }

  return bacaJson(res, 'Gagal mengunggah berkas. Coba lagi.');
}

/** GET JSON — tidak perlu token CSRF. */
export async function ambilJson(url) {
  const res = await fetch(url, {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  });

  return bacaJson(res, 'Gagal menghubungi server. Coba lagi.');
}
