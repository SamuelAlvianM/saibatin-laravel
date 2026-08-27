import { useRef, useState } from 'react';
import { ImagePlus, Loader2, X } from 'lucide-react';
import PenampilGambar from '@/Components/PenampilGambar';

/** Hanya tiga format ini yang diterima (permintaan user). */
const TIPE_DITERIMA = ['image/jpeg', 'image/jpg', 'image/png'];
const ACCEPT = '.jpg,.jpeg,.png,image/jpeg,image/png';
/** Batas berkas MENTAH sebelum dikecilkan di browser. */
const MAKS_BYTE_MENTAH = 10 * 1024 * 1024;
/** Sisi terpanjang setelah dikecilkan — cukup untuk KTP tetap terbaca. */
const SISI_MAKS = 1600;

/**
 * Pemilih SATU gambar dari berkas (BUKAN kamera) → data URL JPEG.
 * Port dari `components/shared/image-upload-field.tsx`.
 *
 * Dipakai untuk foto KTP: berbeda dari `AmbilSelfie` yang memaksa memotret saat
 * itu juga. KTP justru biasanya sudah ada sebagai berkas hasil scan/foto, jadi
 * memaksa kamera malah menyulitkan.
 *
 * Gambar dikecilkan DI BROWSER sebelum dikirim supaya payload pendaftaran tidak
 * membengkak (server tetap punya batasnya sendiri di `App\Services\FotoProfil`).
 *
 * `onFileAsli` menerima berkas MENTAH sebelum dikecilkan & di-encode ulang.
 * 🔴 Dipakai OCR, dan bedanya nyata: gambar yang sama terbaca utuh pada kualitas
 * JPEG 0,95 tapi NIK-nya salah baca dan No.KK hilang pada 0,85 — padahal 0,85
 * sudah cukup untuk dilihat mata petugas. Jadi yang DISIMPAN tetap versi kecil,
 * sedangkan yang DIBACA MESIN harus versi asli.
 */
export default function UnggahGambar({
  nilai,
  onChange,
  onFileAsli,
  nonaktif = false,
  label = 'Unggah Gambar',
  kelas = '',
}) {
  const inputRef = useRef(null);
  const [memproses, setMemproses] = useState(false);
  const [galat, setGalat] = useState(null);
  const [lihat, setLihat] = useState(false);

  const pilihBerkas = async (e) => {
    const file = e.target.files?.[0];
    // Input direset lebih dulu supaya memilih berkas yang SAMA dua kali tetap
    // memicu onChange (browser tidak menembakkan event bila nilainya sama).
    e.target.value = '';
    if (!file) return;

    setGalat(null);
    if (!TIPE_DITERIMA.includes(file.type.toLowerCase())) {
      setGalat('Format harus JPG, JPEG, atau PNG.');
      return;
    }
    if (file.size > MAKS_BYTE_MENTAH) {
      setGalat('Ukuran berkas maksimal 10 MB.');
      return;
    }

    setMemproses(true);
    try {
      onChange(await kecilkan(file));
      onFileAsli?.(file);
    } catch {
      setGalat('Gambar tidak dapat dibaca. Coba berkas lain.');
    } finally {
      setMemproses(false);
    }
  };

  return (
    <div className={`space-y-2 ${kelas}`}>
      {nilai ? (
        <div className="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
          {/* 🔴 Gambarnya harus bisa diperbesar & diputar. Foto KTP dari ponsel
              sering miring atau terbalik, dan pratinjau 256 px terlalu kecil
              untuk memastikan NIK-nya terbaca — padahal itulah satu-satunya
              kesempatan warga memeriksanya sebelum mengirim. */}
          <button type="button" onClick={() => setLihat(true)}
                  title="Klik untuk memperbesar, memutar, atau mengunduh"
                  className="block w-full cursor-zoom-in">
            <img src={nilai} alt={label} className="h-auto max-h-64 w-full object-contain" />
          </button>
          <button
            type="button"
            disabled={nonaktif}
            onClick={() => onChange('')}
            className="absolute right-2 top-2 inline-flex items-center gap-1.5 rounded-lg bg-white/90 px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow transition-colors hover:bg-white disabled:opacity-50"
          >
            <X className="h-3.5 w-3.5" />
            Hapus
          </button>
        </div>
      ) : (
        <button
          type="button"
          disabled={nonaktif || memproses}
          onClick={() => inputRef.current?.click()}
          className={`flex w-full flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center transition-colors hover:border-brand/50 hover:bg-brand/5 ${
            nonaktif || memproses ? 'cursor-not-allowed opacity-60' : ''
          }`}
        >
          {memproses ? (
            <Loader2 className="h-6 w-6 animate-spin text-brand" />
          ) : (
            <ImagePlus className="h-6 w-6 text-slate-400" />
          )}
          <span className="text-sm font-medium text-slate-700">
            {memproses ? 'Memproses gambar…' : label}
          </span>
          <span className="text-[0.7rem] text-slate-500">
            Format JPG, JPEG, atau PNG — maksimal 10 MB
          </span>
        </button>
      )}

      <input ref={inputRef} type="file" accept={ACCEPT} onChange={pilihBerkas} className="hidden" />

      {galat && <p className="text-xs text-red-600">{galat}</p>}

      {lihat && nilai && (
        <PenampilGambar daftar={[{ src: nilai, judul: label }]} onTutup={() => setLihat(false)} />
      )}
    </div>
  );
}

/** Kecilkan gambar lewat canvas lalu keluarkan sebagai data URL JPEG. */
function kecilkan(file) {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file);
    const img = new window.Image();
    img.onload = () => {
      URL.revokeObjectURL(url);
      const skala = Math.min(1, SISI_MAKS / Math.max(img.width, img.height));
      const canvas = document.createElement('canvas');
      canvas.width = Math.round(img.width * skala);
      canvas.height = Math.round(img.height * skala);
      const ctx = canvas.getContext('2d');
      if (!ctx) return reject(new Error('canvas tidak tersedia'));
      // PNG bisa transparan; diberi alas putih supaya tidak jadi hitam saat
      // dikonversi ke JPEG.
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      resolve(canvas.toDataURL('image/jpeg', 0.85));
    };
    img.onerror = () => {
      URL.revokeObjectURL(url);
      reject(new Error('gagal memuat gambar'));
    };
    img.src = url;
  });
}
