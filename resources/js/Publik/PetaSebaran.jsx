import { useEffect, useMemo, useState } from 'react';
import { CircleMarker, MapContainer, TileLayer, Tooltip } from 'react-leaflet';
import { Loader2, MapPin } from 'lucide-react';
import 'leaflet/dist/leaflet.css';
import { ambilJson } from '@/lib/api';
import { KECAMATAN_GEO, PUSAT_PETA, ZOOM_AWAL, geoWilayah } from '@/lib/geo';

/**
 * GIS Dukcapil — peta sebaran penduduk per kecamatan (`/media/gis`).
 * Port `components/landingpage/peta-demografi-loader.tsx` SIDAKO.
 *
 * Angkanya dari `/api/demografi?kategori=jenis-kelamin` (tingkat kecamatan),
 * dipadankan ke koordinat lewat NAMA wilayah — rekap DKB tidak membawa
 * koordinat, dan kode wilayahnya beda sumber dengan daftar geo.
 *
 * 🔴 Luas lingkaran, bukan jari-jarinya, yang dibuat sebanding dengan jumlah
 * penduduk (`sqrt`). Menskalakan jari-jari langsung membuat kecamatan terbesar
 * tampak berkali-kali lebih besar daripada kenyataannya — mata membaca LUAS.
 */

const angka = (n) => Number(n ?? 0).toLocaleString('id-ID');

/** Jari-jari piksel: 8 px minimum supaya kecamatan terkecil tetap bisa diklik. */
function jariJari(nilai, maks) {
  if (!maks) return 8;
  return 8 + Math.sqrt(nilai / maks) * 26;
}

export default function PetaSebaran() {
  const [items, setItems] = useState(null);
  const [galat, setGalat] = useState(null);

  useEffect(() => {
    ambilJson('/api/demografi?kategori=jenis-kelamin').then((j) => {
      if (j.error?.length) { setGalat(j.error[0]); return; }
      setItems(j.data?.items ?? []);
    });
  }, []);

  const titik = useMemo(() => {
    if (!items) return [];

    const hasil = items
      .map((r) => {
        const geo = geoWilayah(r.wilayah);
        if (!geo) return null;
        // `wilayah` (nama dari DKB) yang dipakai untuk ditampilkan, bukan
        // `geo.nama` — dua kecamatan namanya memang beda antara daftar
        // koordinat dan rekap, dan yang dikenali warga adalah versi rekapnya.
        return { ...geo, wilayah: r.wilayah, jml: Number(r.data?.JML ?? 0), data: r.data };
      })
      .filter(Boolean);

    const maks = Math.max(1, ...hasil.map((t) => t.jml));
    return hasil.map((t) => ({ ...t, r: jariJari(t.jml, maks) }));
  }, [items]);

  const total = titik.reduce((a, b) => a + b.jml, 0);
  // Kecamatan yang punya koordinat tapi tidak ada di data (atau sebaliknya)
  // memang mungkin — sebutkan angkanya daripada diam-diam menghilang.
  const takTerpetakan = items ? items.length - titik.length : 0;

  if (galat) {
    return <p className="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center text-sm text-slate-500">{galat}</p>;
  }

  if (!items) {
    return (
      <div className="flex items-center justify-center rounded-2xl border border-slate-200 bg-white py-24">
        <Loader2 className="h-6 w-6 animate-spin text-brand" />
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div className="h-[26rem] w-full sm:h-[32rem]">
          <MapContainer center={PUSAT_PETA} zoom={ZOOM_AWAL} scrollWheelZoom={false}
                        style={{ height: '100%', width: '100%' }}>
            {/* Ubin OpenStreetMap — lihat catatan di `PetaKantor`: CARTO kini
                mencap ubinnya "API KEY REQUIRED" tanpa memunculkan galat. */}
            <TileLayer url="https://tile.openstreetmap.org/{z}/{x}/{y}.png"
                       attribution="&copy; OpenStreetMap" />
            {titik.map((t) => (
              <CircleMarker key={t.nama} center={[t.lat, t.lng]} radius={t.r}
                            pathOptions={{ color: '#1b4b72', weight: 1.5, fillColor: '#2176bd', fillOpacity: 0.45 }}>
                <Tooltip direction="top" offset={[0, -4]}>
                  <span className="block text-xs font-semibold">{t.wilayah}</span>
                  <span className="block text-xs">{angka(t.jml)} jiwa</span>
                  <span className="block text-[0.68rem] text-slate-500">
                    L {angka(t.data?.L)} · P {angka(t.data?.P)}
                  </span>
                </Tooltip>
              </CircleMarker>
            ))}
          </MapContainer>
        </div>
      </div>

      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {[...titik].sort((a, b) => b.jml - a.jml).map((t) => (
          <div key={t.nama} className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
            <MapPin className="h-4 w-4 flex-none text-brand" />
            <span className="min-w-0 flex-1 truncate text-sm font-medium text-slate-800">{t.wilayah}</span>
            <span className="tabular-nums text-sm font-bold text-slate-900">{angka(t.jml)}</span>
          </div>
        ))}
      </div>

      <p className="text-[0.7rem] text-slate-400">
        Total {angka(total)} jiwa pada {titik.length} kecamatan
        {takTerpetakan > 0 && ` · ${takTerpetakan} wilayah belum punya titik koordinat`}.
        Sumber: rekap Data Kependudukan Bersih (DKB) Disdukcapil Kabupaten Pesisir Barat.
        Posisi titik bersifat indikatif, bukan batas wilayah administratif.
      </p>
    </div>
  );
}
