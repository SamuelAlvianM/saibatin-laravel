import { useEffect, useRef, useState } from 'react';
import { Camera, RefreshCw, ShieldCheck, VideoOff } from 'lucide-react';

/**
 * Pengambil foto wajah/selfie.
 *
 * 🔴 SENGAJA tanpa opsi unggah berkas. Foto ini dipakai petugas untuk
 * mencocokkan pemohon dengan foto KTP-nya; kalau boleh diunggah, siapa pun bisa
 * melampirkan foto orang lain dan verifikasinya kehilangan arti. Di formulir
 * dashboard (petugas mendaftarkan warga) unggahan memang diizinkan — itu
 * komponen yang berbeda.
 *
 * Hasilnya data-URL JPEG; server yang menyimpannya ke storage di luar `public/`.
 */
export default function AmbilSelfie({ nilai, onChange }) {
  const videoRef = useRef(null);
  const streamRef = useRef(null);
  const [aktif, setAktif] = useState(false);
  const [galat, setGalat] = useState(null);

  const hentikan = () => {
    streamRef.current?.getTracks().forEach((t) => t.stop());
    streamRef.current = null;
    setAktif(false);
  };

  // Kamera wajib dimatikan saat komponen dilepas — kalau tidak, lampu kamera
  // tetap menyala setelah warga berpindah halaman.
  useEffect(() => hentikan, []);

  const nyalakan = async () => {
    setGalat(null);
    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } },
        audio: false,
      });
      streamRef.current = stream;
      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        await videoRef.current.play();
      }
      setAktif(true);
    } catch (e) {
      setGalat(
        e?.name === 'NotAllowedError'
          ? 'Izin kamera ditolak. Aktifkan izin kamera di peramban lalu coba lagi.'
          : 'Kamera tidak dapat diakses. Pastikan perangkat punya kamera dan tidak sedang dipakai aplikasi lain.'
      );
    }
  };

  const potret = () => {
    const v = videoRef.current;
    if (!v) return;

    // Dipotong jadi bujur sangkar dari bagian tengah supaya proporsinya
    // konsisten apa pun rasio kameranya.
    const sisi = Math.min(v.videoWidth, v.videoHeight);
    const c = document.createElement('canvas');
    c.width = c.height = Math.min(sisi, 720);
    const ctx = c.getContext('2d');
    ctx.drawImage(
      v,
      (v.videoWidth - sisi) / 2, (v.videoHeight - sisi) / 2, sisi, sisi,
      0, 0, c.width, c.height
    );

    onChange(c.toDataURL('image/jpeg', 0.85));
    hentikan();
  };

  return (
    <div className="space-y-2">
      <div className="relative aspect-square w-full overflow-hidden rounded-xl border border-slate-300 bg-slate-100">
        {nilai ? (
          <img src={nilai} alt="Foto wajah yang diambil" className="h-full w-full object-cover" />
        ) : (
          <>
            <video ref={videoRef} playsInline muted
                   className={`h-full w-full object-cover ${aktif ? '' : 'hidden'}`} />
            {!aktif && (
              <div className="flex h-full flex-col items-center justify-center gap-2 p-4 text-center text-slate-400">
                <VideoOff className="h-8 w-8" />
                <p className="text-xs">Kamera belum aktif</p>
              </div>
            )}
          </>
        )}
      </div>

      {galat && (
        <p className="rounded-lg border border-red-200 bg-red-50 p-2.5 text-xs text-red-700">{galat}</p>
      )}

      <div className="flex gap-2">
        {nilai ? (
          <button type="button" onClick={() => { onChange(''); nyalakan(); }}
                  className="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:border-brand hover:text-brand">
            <RefreshCw className="h-4 w-4" />Ulangi Foto
          </button>
        ) : aktif ? (
          <button type="button" onClick={potret}
                  className="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-dark">
            <Camera className="h-4 w-4" />Ambil Foto
          </button>
        ) : (
          <button type="button" onClick={nyalakan}
                  className="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-brand px-3 py-2 text-sm font-semibold text-brand transition-colors hover:bg-brand/5">
            <Camera className="h-4 w-4" />Nyalakan Kamera
          </button>
        )}
      </div>

      <p className="flex items-start gap-1.5 text-[11px] leading-relaxed text-slate-500">
        <ShieldCheck className="mt-0.5 h-3.5 w-3.5 shrink-0" />
        Foto harus diambil langsung — tidak bisa diunggah dari galeri. Petugas
        memakainya untuk mencocokkan dengan foto KTP Anda. Berkasnya disimpan
        tertutup, tidak dapat diakses publik.
      </p>
    </div>
  );
}
