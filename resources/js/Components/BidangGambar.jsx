import { useState } from 'react';
import { ImagePlus, Pencil, X } from 'lucide-react';
import PemilihMedia from '@/Components/PemilihMedia';

/**
 * Kontrol gambar tunggal: pratinjau SEKALIGUS tombol.
 * Port `components/media/image-picker-field.tsx`.
 *
 * - Klik ubin → buka pustaka media / unggah.
 * - Sudah ada gambar → hover memunculkan "Ganti" + tombol hapus di pojok.
 * - Belum ada → zona putus-putus "Pilih {label}".
 */
export default function BidangGambar({
  nilai, onUbah, label = 'Gambar', judul, rasio, kelas, gaya, onGalat,
}) {
  const [buka, setBuka] = useState(false);

  return (
    <>
      <button type="button" onClick={() => setBuka(true)} style={gaya}
              title={nilai ? 'Ganti gambar' : `Pilih ${label}`}
              className={`group relative flex items-center justify-center overflow-hidden rounded-lg border bg-slate-50 transition-colors ${
                nilai ? 'border-slate-200' : 'border-dashed border-slate-300 hover:border-brand/60 hover:bg-brand/5'
              } ${kelas ?? 'aspect-video w-full'}`}>
        {nilai ? (
          <>
            <img src={nilai} alt={label} className="h-full w-full object-cover" />
            <span className="absolute inset-0 flex items-center justify-center gap-1.5 bg-slate-900/0 text-transparent transition-colors group-hover:bg-slate-900/50 group-hover:text-white">
              <Pencil className="h-4 w-4" />
              <span className="text-xs font-medium">Ganti</span>
            </span>
            <span role="button" tabIndex={-1} title="Hapus gambar"
                  onClick={(e) => { e.stopPropagation(); onUbah(''); }}
                  className="absolute right-1 top-1 z-10 flex h-6 w-6 items-center justify-center rounded-md bg-rose-600 text-white opacity-0 shadow transition-opacity group-hover:opacity-100">
              <X className="h-3.5 w-3.5" />
            </span>
          </>
        ) : (
          <span className="flex flex-col items-center justify-center gap-1 p-2 text-center text-slate-400">
            <ImagePlus className="h-5 w-5" />
            <span className="text-[11px] font-medium leading-tight">Pilih {label}</span>
          </span>
        )}
      </button>

      {buka && (
        <PemilihMedia
          judul={judul ?? `Pilih ${label}`}
          rasio={rasio}
          onGalat={onGalat}
          onPilih={(m) => onUbah(m.url)}
          onTutup={() => setBuka(false)}
        />
      )}
    </>
  );
}
