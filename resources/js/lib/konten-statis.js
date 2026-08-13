import { useEffect, useState } from 'react';

/**
 * Blok konten yang bisa disunting petugas dari dashboard — port
 * `lib/static-content-registry.ts` portal Next.js.
 *
 * `BAWAAN` adalah isi default tiap blok. Gunanya bukan sekadar contoh: selama
 * blok itu belum pernah disunting, dialah yang tampil, sehingga situs publik
 * tidak perlu di-seed dan tidak pernah tampil kosong. Halaman merender bawaan
 * dulu, lalu menggantinya begitu jawaban `/api/static-content` tiba — supaya
 * tidak ada teks yang berkedip masuk dan menggeser tata letak.
 *
 * 🔴 Berkas ini akan TERUS BERTAMBAH seiring halaman publik dikerjakan
 * (Fase 7). Registry aslinya memuat 12 blok; yang sudah dipindahkan ke sini
 * hanya yang halamannya sudah ada.
 */
export const BAWAAN = {
  'beranda.hero': {
    heading: 'Layanan Kependudukan Kabupaten Pesisir Barat',
    subheading:
      'Urus akta kelahiran, KTP-el, Kartu Keluarga, dan layanan kependudukan lainnya secara online — cepat, mudah, dan gratis.',
    searchPlaceholder: 'Mau mengurus apa hari ini?',
  },
  'beranda.carousel': {
    slides: [],
  },
};

export function bawaan(kunci) {
  return BAWAAN[kunci] ?? {};
}

/**
 * Ambil beberapa blok konten sekaligus.
 *
 * Render pertama memakai `BAWAAN` (tanpa layout shift), lalu ditimpa nilai DB.
 * Gagal mengambil TIDAK memunculkan galat di layar — halaman tetap tampil
 * dengan teks bawaannya, yang jauh lebih berguna bagi warga daripada pesan
 * kesalahan.
 */
export function useKontenStatis(kunci) {
  const gabung = kunci.join(',');

  const [data, setData] = useState(() =>
    Object.fromEntries(kunci.map((k) => [k, bawaan(k)])),
  );

  useEffect(() => {
    let aktif = true;
    fetch(`/api/static-content?keys=${encodeURIComponent(gabung)}`)
      .then((r) => r.json())
      .then((j) => {
        if (!aktif || !j?.data?.items) return;
        // Blok yang ada di DB tapi isinya kosong tetap jatuh ke bawaan —
        // petugas yang mengosongkan satu kolom tidak boleh membuat bagian
        // halaman itu hilang sama sekali.
        setData(
          Object.fromEntries(
            kunci.map((k) => [k, { ...bawaan(k), ...(j.data.items[k] ?? {}) }]),
          ),
        );
      })
      .catch(() => {});
    return () => {
      aktif = false;
    };
  }, [gabung]);

  return data;
}
