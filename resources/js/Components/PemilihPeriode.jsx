import { Calendar, Check, ChevronDown, Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { labelPeriode, periodeSama, tahunPilihan } from '@/lib/periode';

/**
 * Pemilih periode DKB — tahun + semester.
 *
 * 🔴 Dipakai di TIGA tempat dengan aturan yang sama: dashboard demografi,
 * editor, dan badge beranda. Satu komponen, supaya "Semester II 2024" di
 * ketiganya tidak pernah dieja berbeda.
 *
 * `bolehBaru` membedakan dua peran yang tampak mirip:
 *  - petugas (true)  : boleh MEMBUAT periode yang belum ada — itulah cara
 *                      data semester baru masuk untuk pertama kalinya
 *  - warga   (false) : hanya boleh berpindah ke periode yang ADA datanya;
 *                      memilih periode kosong hanya menghasilkan halaman
 *                      kosong yang terlihat seperti kerusakan
 */
export default function PemilihPeriode({
  nilai,
  tersedia = [],
  onPilih,
  bolehBaru = false,
  kelas = '',
  ukuran = 'normal',
}) {
  const [buka, setBuka] = useState(false);
  const [buatBaru, setBuatBaru] = useState(false);
  const [tahunBaru, setTahunBaru] = useState(nilai?.tahun ?? new Date().getFullYear());
  const [semesterBaru, setSemesterBaru] = useState(nilai?.semester ?? 1);
  const bungkus = useRef(null);

  // Tutup saat klik di luar / tekan Esc — panel ini menutupi isi di bawahnya.
  useEffect(() => {
    if (!buka) return undefined;

    const klikLuar = (e) => {
      if (bungkus.current && !bungkus.current.contains(e.target)) {
        setBuka(false);
        setBuatBaru(false);
      }
    };
    const tekan = (e) => {
      if (e.key === 'Escape') { setBuka(false); setBuatBaru(false); }
    };

    document.addEventListener('mousedown', klikLuar);
    document.addEventListener('keydown', tekan);

    return () => {
      document.removeEventListener('mousedown', klikLuar);
      document.removeEventListener('keydown', tekan);
    };
  }, [buka]);

  const pilih = (p) => {
    setBuka(false);
    setBuatBaru(false);
    onPilih?.(p);
  };

  const kecil = ukuran === 'kecil';

  return (
    <div ref={bungkus} className={`relative ${kelas}`}>
      <button
        type="button"
        onClick={() => setBuka((b) => !b)}
        aria-haspopup="listbox"
        aria-expanded={buka}
        title="Pilih tahun & semester data kependudukan"
        className={`inline-flex items-center gap-2 rounded-full border border-brand/30 bg-white font-semibold text-brand shadow-sm transition-colors hover:border-brand hover:bg-brand/5 ${
          kecil ? 'px-3 py-1.5 text-xs' : 'px-4 py-2 text-sm'
        }`}
      >
        <Calendar className={kecil ? 'h-3.5 w-3.5' : 'h-4 w-4'} />
        {nilai ? labelPeriode(nilai.tahun, nilai.semester) : 'Belum ada data'}
        <ChevronDown className={`${kecil ? 'h-3.5 w-3.5' : 'h-4 w-4'} transition-transform ${buka ? 'rotate-180' : ''}`} />
      </button>

      {buka && (
        <div
          role="listbox"
          className="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
        >
          <p className="border-b border-slate-100 bg-slate-50 px-3 py-2 text-[0.65rem] font-bold uppercase tracking-widest text-slate-500">
            Periode data
          </p>

          <div className="max-h-64 overflow-y-auto">
            {tersedia.length === 0 && (
              <p className="px-3 py-3 text-xs text-slate-400">Belum ada data periode mana pun.</p>
            )}

            {tersedia.map((p) => {
              const aktif = periodeSama(p, nilai);

              return (
                <button
                  key={`${p.tahun}-${p.semester}`}
                  type="button"
                  role="option"
                  aria-selected={aktif}
                  onClick={() => pilih({ tahun: p.tahun, semester: p.semester })}
                  className={`flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm transition-colors ${
                    aktif ? 'bg-brand/10 font-semibold text-brand' : 'text-slate-700 hover:bg-slate-50'
                  }`}
                >
                  <span>{labelPeriode(p.tahun, p.semester)}</span>
                  <span className="flex items-center gap-1.5">
                    {/* Jumlah barisnya ikut ditampilkan: periode yang cuma
                        berisi satu kategori tidak boleh terlihat selengkap
                        periode yang berisi delapan. */}
                    {p.baris != null && (
                      <span className="text-[0.65rem] font-normal text-slate-400">{p.baris} baris</span>
                    )}
                    {aktif && <Check className="h-3.5 w-3.5" />}
                  </span>
                </button>
              );
            })}
          </div>

          {bolehBaru && (
            <div className="border-t border-slate-100">
              {!buatBaru ? (
                <button
                  type="button"
                  onClick={() => setBuatBaru(true)}
                  className="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm font-medium text-slate-600 hover:bg-slate-50"
                >
                  <Plus className="h-4 w-4" /> Periode baru…
                </button>
              ) : (
                <div className="space-y-2 p-3">
                  <div className="flex gap-2">
                    <label className="flex-1">
                      <span className="mb-1 block text-[0.65rem] font-medium text-slate-500">Tahun</span>
                      <select
                        value={tahunBaru}
                        onChange={(e) => setTahunBaru(Number(e.target.value))}
                        className="h-8 w-full rounded border border-slate-300 bg-white px-2 text-sm"
                      >
                        {tahunPilihan(tersedia).map((t) => <option key={t} value={t}>{t}</option>)}
                      </select>
                    </label>
                    <label className="flex-1">
                      <span className="mb-1 block text-[0.65rem] font-medium text-slate-500">Semester</span>
                      <select
                        value={semesterBaru}
                        onChange={(e) => setSemesterBaru(Number(e.target.value))}
                        className="h-8 w-full rounded border border-slate-300 bg-white px-2 text-sm"
                      >
                        <option value={1}>I</option>
                        <option value={2}>II</option>
                      </select>
                    </label>
                  </div>
                  <button
                    type="button"
                    onClick={() => pilih({ tahun: tahunBaru, semester: semesterBaru })}
                    className="w-full rounded bg-brand px-3 py-1.5 text-sm font-semibold text-white hover:opacity-90"
                  >
                    Pakai {labelPeriode(tahunBaru, semesterBaru)}
                  </button>
                </div>
              )}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
