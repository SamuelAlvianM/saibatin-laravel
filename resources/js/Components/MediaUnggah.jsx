import { Suspense, lazy, useCallback, useState } from 'react';
import { useDropzone } from 'react-dropzone';
import { CloudUpload, Loader2 } from 'lucide-react';
import { kirimBerkas } from '@/lib/api';

// Pemotong gambar ± 150 KB dan hanya dipakai setelah seseorang benar-benar
// menjatuhkan berkas — jangan bebankan ke setiap halaman yang memuat pemilih
// media. Sama alasannya dengan Highcharts di beranda dashboard.
const PotongGambar = lazy(() => import('@/Components/PotongGambar'));

/**
 * Zona tarik-lepas unggah media → potong (opsional) → simpan ke pustaka.
 * Port `components/media/media-upload.tsx`.
 */
export default function MediaUnggah({
  onTerunggah, onGalat, hanyaGambar = true, denganPotong = true, rasio, kelas = '',
}) {
  const [mengunggah, setMengunggah] = useState(false);
  const [sumberPotong, setSumberPotong] = useState(null);
  const [namaTertunda, setNamaTertunda] = useState('gambar.webp');

  const unggah = useCallback(async (blob, namaBerkas) => {
    setMengunggah(true);

    const fd = new FormData();
    fd.append('file', new File([blob], namaBerkas, { type: blob.type }));

    const j = await kirimBerkas('/api/media/upload', fd);
    setMengunggah(false);

    if (j.error?.length) {
      onGalat?.(j.error[0]);
      return;
    }
    if (!j.data?.media) {
      onGalat?.('Respons server tidak dikenali');
      return;
    }

    onTerunggah(j.data.media);
  }, [onTerunggah, onGalat]);

  const onDrop = useCallback((diterima) => {
    const berkas = diterima[0];
    if (!berkas) return;

    const gambar = berkas.type.startsWith('image/');
    if (denganPotong && gambar && berkas.type !== 'image/gif') {
      setNamaTertunda(`${berkas.name.replace(/\.[^.]+$/, '')}.webp`);
      setSumberPotong(URL.createObjectURL(berkas));
      return;
    }
    unggah(berkas, berkas.name);
  }, [unggah, denganPotong]);

  const { getRootProps, getInputProps, isDragActive } = useDropzone({
    onDrop,
    multiple: false,
    disabled: mengunggah,
    accept: hanyaGambar
      ? { 'image/*': ['.jpg', '.jpeg', '.png', '.webp', '.gif'] }
      : {
          'image/*': ['.jpg', '.jpeg', '.png', '.webp', '.gif'],
          'application/pdf': ['.pdf'],
        },
  });

  const tutupPotong = () => {
    if (sumberPotong) URL.revokeObjectURL(sumberPotong);
    setSumberPotong(null);
  };

  return (
    <>
      <div {...getRootProps()}
           className={`flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed p-8 text-center transition-colors ${
             isDragActive ? 'border-brand bg-brand/5' : 'border-slate-300 hover:border-brand/60 hover:bg-slate-50'
           } ${mengunggah ? 'pointer-events-none opacity-70' : ''} ${kelas}`}>
        <input {...getInputProps()} />
        <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand/10">
          {mengunggah
            ? <Loader2 className="h-7 w-7 animate-spin text-brand" />
            : <CloudUpload className="h-7 w-7 text-brand" />}
        </div>
        <div>
          <p className="font-medium text-slate-800">
            {mengunggah ? 'Mengunggah...' : isDragActive ? 'Lepaskan file di sini' : 'Tarik & letakkan gambar di sini'}
          </p>
          <p className="mt-0.5 text-sm text-slate-500">
            atau klik untuk memilih file{hanyaGambar ? ' (JPG, PNG, WebP, GIF' : ' (gambar/PDF'}, maks. 10 MB)
          </p>
        </div>
      </div>

      {sumberPotong && (
        <Suspense fallback={null}>
          <PotongGambar
            sumber={sumberPotong}
            rasio={rasio}
            onTutup={tutupPotong}
            onPotong={async (blob) => { await unggah(blob, namaTertunda); tutupPotong(); }}
          />
        </Suspense>
      )}
    </>
  );
}
