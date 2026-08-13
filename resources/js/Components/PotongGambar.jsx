import { useRef, useState } from 'react';
import { Cropper } from 'react-advanced-cropper';
import 'react-advanced-cropper/dist/style.css';
import { Crop, Loader2, RotateCcw, RotateCw } from 'lucide-react';
import { Modal, Tombol } from '@/Components/Dasbor';

/**
 * Dialog potong gambar — port `components/media/image-cropper-dialog.tsx`.
 *
 * Hasilnya WebP kualitas 0,92 dan sisi maksimum 2560 px, sama seperti aslinya;
 * server tetap memeriksa ulang ukuran & formatnya (`PustakaMedia`).
 */
export default function PotongGambar({ sumber, rasio, onPotong, onTutup }) {
  const cropper = useRef(null);
  const [sibuk, setSibuk] = useState(false);

  const putar = (derajat) => cropper.current?.rotateImage(derajat);

  const terapkan = () => {
    const kanvas = cropper.current?.getCanvas({ maxWidth: 2560, maxHeight: 2560 });
    if (!kanvas) return;

    setSibuk(true);
    kanvas.toBlob(async (blob) => {
      if (blob) await onPotong(blob);
      setSibuk(false);
    }, 'image/webp', 0.92);
  };

  return (
    <Modal judul="Potong Gambar" lebar="max-w-2xl" onTutup={onTutup}>
      <p className="-mt-2 mb-3 text-sm text-slate-500">
        Geser dan ubah ukuran area untuk memotong gambar sesuai kebutuhan.
      </p>
      <div className="max-h-[60vh] overflow-hidden rounded-xl bg-slate-950">
        <Cropper ref={cropper} src={sumber}
                 stencilProps={rasio ? { aspectRatio: rasio } : undefined}
                 className="h-[50vh]" />
      </div>

      <div className="mt-4 flex flex-row justify-between gap-2">
        <div className="flex gap-1">
          <Tombol varian="garis" onClick={() => putar(-90)} title="Putar kiri"><RotateCcw className="h-4 w-4" /></Tombol>
          <Tombol varian="garis" onClick={() => putar(90)} title="Putar kanan"><RotateCw className="h-4 w-4" /></Tombol>
        </div>
        <div className="flex gap-2">
          <Tombol varian="garis" onClick={onTutup} disabled={sibuk}>Batal</Tombol>
          <Tombol onClick={terapkan} disabled={sibuk}>
            {sibuk ? <Loader2 className="h-4 w-4 animate-spin" /> : <Crop className="h-4 w-4" />}Terapkan
          </Tombol>
        </div>
      </div>
    </Modal>
  );
}
