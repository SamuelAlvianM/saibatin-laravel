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

  try {
    return await res.json();
  } catch {
    return { error: ['Gagal menghubungi server. Coba lagi.'], success: [], data: null };
  }
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

  try {
    return await res.json();
  } catch {
    return { error: ['Gagal mengunggah berkas. Coba lagi.'], success: [], data: null };
  }
}

/** GET JSON — tidak perlu token CSRF. */
export async function ambilJson(url) {
  const res = await fetch(url, {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  });

  try {
    return await res.json();
  } catch {
    return { error: ['Gagal menghubungi server. Coba lagi.'], success: [], data: null };
  }
}
