import { useMemo, useState } from 'react';
import { CalendarDays, Download, Loader2, MapPin } from 'lucide-react';
import { PilihanDropdown } from '@/Components/PilihanDropdown';
import { namaCantik } from '@/lib/peta-label';

/**
 * Island filter periode × wilayah halaman `/media/gis`. Mengganti pilihan
 * langsung berpindah halaman (query string) — setiap tampilan bisa dibagikan
 * dan di-bookmark, dan tombol Unduh PDF selalu mengikuti yang sedang dilihat.
 */
export default function FilterStatistik({ periode, daftarPeriode = [], wilayah, daftarWilayah = [], hrefPdf }) {
  const [sibuk, setSibuk] = useState(false);

  function ganti(kunci, nilai) {
    const q = new URLSearchParams(window.location.search);
    q.set(kunci, nilai);
    setSibuk(true);
    window.location.search = q.toString();
  }

  const grupWilayah = useMemo(() => {
    const kab = daftarWilayah.filter((w) => w.tingkat === 'kabupaten');
    const kec = daftarWilayah.filter((w) => w.tingkat === 'kecamatan');
    const namaKec = new Map(kec.map((k) => [k.kode, namaCantik(k.nama)]));
    return [
      { opsi: kab.map((w) => ({ nilai: w.kode, label: `${w.nama} (seluruh wilayah)` })) },
      { judul: 'Kecamatan', opsi: kec.map((w) => ({ nilai: w.kode, label: `Kec. ${namaCantik(w.nama)}` })) },
      ...kec
        .map((k) => ({
          judul: `Desa/kelurahan · Kec. ${namaKec.get(k.kode)}`,
          opsi: daftarWilayah
            .filter((w) => w.tingkat === 'desa' && w.induk === k.kode)
            .map((w) => ({ nilai: w.kode, label: namaCantik(w.nama), keterangan: `Kec. ${namaKec.get(k.kode)}` })),
        }))
        .filter((g) => g.opsi.length),
    ];
  }, [daftarWilayah]);

  return (
    <div className="grid gap-3 sm:flex sm:flex-wrap sm:items-end lg:flex-nowrap">
      <div className="sm:w-56">
        <label htmlFor="filter-periode" className="mb-1.5 block text-xs font-medium text-slate-500">
          Periode data
        </label>
        <PilihanDropdown
          id="filter-periode"
          nilai={periode}
          grup={[{ opsi: daftarPeriode.map((p) => ({ nilai: p.kunci, label: p.label })) }]}
          onPilih={(v) => ganti('periode', v)}
          ikon={<CalendarDays className="h-4 w-4" />}
          disabled={sibuk}
        />
      </div>

      <div className="sm:w-80">
        <label htmlFor="filter-wilayah" className="mb-1.5 block text-xs font-medium text-slate-500">
          Wilayah
        </label>
        <PilihanDropdown
          id="filter-wilayah"
          nilai={wilayah}
          grup={grupWilayah}
          onPilih={(v) => ganti('wilayah', v)}
          ikon={<MapPin className="h-4 w-4" />}
          cari
          placeholderCari="Cari kecamatan atau desa…"
          disabled={sibuk}
        />
      </div>

      {sibuk && <Loader2 className="mb-3.5 h-5 w-5 animate-spin text-slate-400" aria-label="Memuat" />}

      <a
        href={hrefPdf}
        className="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-brand-dark px-5 text-sm font-medium text-white shadow-sm transition hover:bg-brand sm:ml-auto"
      >
        <Download className="h-4 w-4" />
        Unduh PDF
      </a>
    </div>
  );
}
