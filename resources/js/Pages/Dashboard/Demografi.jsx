import { useCallback, useEffect, useRef, useState } from 'react';
import {
  AlertTriangle, CheckCircle2, Download, FileSpreadsheet, Loader2, Pencil,
  RotateCcw, Trash2, Upload,
} from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import EditorDemografi from '@/Components/EditorDemografi';
import { Modal, Pesan, Tombol } from '@/Components/Dasbor';
import { ambilJson, kirimBerkas, kirimJson } from '@/lib/api';

/**
 * Data Demografi — port `app/dashboard/demografi/AdminDemografi.tsx`.
 *
 * Sumber datanya berkas Excel agregat Dukcapil (SIAK). Satu kartu per kategori:
 * unggah cepat (mengganti seluruh kategori), unduh, atau buka editor untuk
 * mengurus kecamatan **dan rincian desanya**.
 */

/** Unduh lewat <a download> — memicu dialog simpan peramban. */
function unduh(url) {
  const a = document.createElement('a');
  a.href = url;
  a.rel = 'noopener';
  document.body.appendChild(a);
  a.click();
  a.remove();
}

export default function Demografi({ kategori, kartuBawaan }) {
  const [jumlah, setJumlah] = useState({});
  const [mengunggah, setMengunggah] = useState(null);
  const [sunting, setSunting] = useState(null);
  const [konfirmHapus, setKonfirmHapus] = useState(false);
  const [menghapus, setMenghapus] = useState(false);
  const [konfirmReset, setKonfirmReset] = useState(false);
  const [mereset, setMereset] = useState(false);
  const [pesan, setPesan] = useState(null);
  const inputs = useRef({});

  const segarkan = useCallback(async (slug) => {
    const j = await ambilJson(`/api/demografi?kategori=${encodeURIComponent(slug)}`);
    setJumlah((c) => ({ ...c, [slug]: j.data?.items?.length ?? 0 }));
  }, []);

  const segarkanSemua = useCallback(() => {
    kategori.forEach((k) => segarkan(k.slug));
  }, [kategori, segarkan]);

  useEffect(() => { segarkanSemua(); }, [segarkanSemua]);

  const totalTersimpan = kategori.reduce((a, k) => a + (jumlah[k.slug] ?? 0), 0);

  const unggah = async (slug, berkas) => {
    if (!berkas) return;

    setMengunggah(slug);
    const fd = new FormData();
    fd.append('file', berkas);
    fd.append('kategori', slug);

    const j = await kirimBerkas('/api/admin/demografi/import', fd);
    setMengunggah(null);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Import berhasil' });
    segarkan(slug);
  };

  const hapusSemua = async () => {
    setMenghapus(true);
    const j = await kirimJson('/api/admin/demografi', {}, 'DELETE');
    setMenghapus(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Semua data demografi dihapus' });
    setKonfirmHapus(false);
    segarkanSemua();
  };

  const resetKartu = async () => {
    setMereset(true);
    // Susunan bawaannya datang dari server (`config/konten.php`) supaya hanya
    // ada SATU definisi "6 kartu bawaan" — halaman beranda nanti membaca
    // definisi yang sama.
    const j = await kirimJson('/api/admin/static-content', {
      kunci: 'beranda.statistik',
      konten: { kartu: kartuBawaan },
    }, 'PUT');
    setMereset(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: 'Kartu beranda dikembalikan ke 6 kartu bawaan' });
    setKonfirmReset(false);
  };

  return (
    <LayoutDashboard judul="Data Demografi">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <div className="space-y-4">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div className="flex-1 rounded-xl border border-brand/20 bg-brand/5 px-4 py-3 text-sm text-slate-700">
            Unggah file Excel agregat Dukcapil (format SIAK: kolom <b>IDEM, KODE, WILAYAH, …</b>)
            per kategori. Setiap unggahan <b>mengganti</b> data lama kategori tersebut. Klik{' '}
            <b>Edit / Import</b> untuk mengelola data kecamatan &amp; <b>detail desa</b>-nya.
          </div>
          <div className="flex flex-shrink-0 flex-wrap gap-2">
            <Tombol varian="garis" disabled={totalTersimpan === 0}
                    title="Unduh semua kategori dalam satu file Excel"
                    onClick={() => unduh('/api/admin/demografi/export')}>
              <Download className="h-4 w-4" />Export Semua
            </Tombol>
            <Tombol varian="garis" onClick={() => setKonfirmReset(true)}
                    title="Kembalikan kartu statistik beranda ke 6 kartu bawaan">
              <RotateCcw className="h-4 w-4" />Reset Kartu Beranda
            </Tombol>
            <Tombol varian="garis" kelas="border-rose-300 text-rose-600 hover:bg-rose-50"
                    disabled={totalTersimpan === 0} onClick={() => setKonfirmHapus(true)}
                    title="Hapus seluruh data demografi (semua kategori)">
              <Trash2 className="h-4 w-4" />Hapus Semua
            </Tombol>
          </div>
        </div>

        <div className="grid gap-3 sm:grid-cols-2">
          {kategori.map((k) => {
            const n = jumlah[k.slug];
            const sibuk = mengunggah === k.slug;

            return (
              <div key={k.slug} className="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4">
                <div className="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand">
                  <FileSpreadsheet className="h-5 w-5" />
                </div>

                <div className="min-w-0 flex-1">
                  <p className="text-sm font-semibold text-slate-900">{k.label}</p>
                  <p className="truncate text-xs text-slate-400">File: {k.fileHint}</p>
                  <p className="mt-0.5 text-xs">
                    {n == null ? <span className="text-slate-400">memeriksa…</span>
                      : n > 0 ? (
                        <span className="inline-flex items-center gap-1 text-emerald-600">
                          <CheckCircle2 className="h-3.5 w-3.5" /> {n} kecamatan tersimpan
                        </span>
                      ) : <span className="text-slate-400">Belum ada data</span>}
                  </p>
                </div>

                <button onClick={() => setSunting(k)} disabled={sibuk}
                        title="Edit / import (bisa banyak file) dengan pemeriksaan data berbeda"
                        className="flex flex-shrink-0 items-center gap-1.5 rounded-lg px-2.5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 disabled:opacity-50">
                  <Pencil className="h-4 w-4" /><span className="hidden sm:inline">Edit / Import</span>
                </button>

                <button disabled={sibuk || !n} aria-label={`Unduh data ${k.label}`}
                        title="Unduh data kategori ini ke Excel"
                        onClick={() => unduh(`/api/admin/demografi/export?kategori=${encodeURIComponent(k.slug)}`)}
                        className="flex-shrink-0 rounded-lg p-2 text-slate-600 hover:bg-slate-100 disabled:opacity-40">
                  <Download className="h-4 w-4" />
                </button>

                <Tombol varian="garis" disabled={sibuk} kelas="flex-shrink-0"
                        onClick={() => inputs.current[k.slug]?.click()}>
                  {sibuk ? <Loader2 className="h-4 w-4 animate-spin" /> : <Upload className="h-4 w-4" />}
                  {n ? 'Ganti' : 'Unggah'}
                </Tombol>

                <input ref={(el) => { inputs.current[k.slug] = el; }} type="file" accept=".xlsx"
                       className="hidden" disabled={sibuk}
                       onChange={(e) => { unggah(k.slug, e.target.files?.[0]); e.target.value = ''; }} />
              </div>
            );
          })}
        </div>
      </div>

      {sunting && (
        <EditorDemografi
          kategori={sunting.slug}
          label={sunting.label}
          onTutup={() => setSunting(null)}
          onTersimpan={() => segarkan(sunting.slug)}
        />
      )}

      {konfirmHapus && (
        <Modal judul="Hapus semua data demografi?" onTutup={() => !menghapus && setKonfirmHapus(false)}>
          <p className="flex items-start gap-2 text-sm text-slate-600">
            <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
            <span>
              Seluruh data <b>semua kategori</b> (kecamatan &amp; desa) akan dihapus permanen —
              total <b>{totalTersimpan} kecamatan</b> tersimpan. Sebaiknya <b>Export Semua</b> dulu
              sebagai cadangan. Tindakan ini tidak dapat dibatalkan.
            </span>
          </p>
          <div className="mt-5 flex justify-end gap-2">
            <Tombol varian="garis" onClick={() => setKonfirmHapus(false)} disabled={menghapus}>Batal</Tombol>
            <Tombol varian="bahaya" onClick={hapusSemua} disabled={menghapus}>
              {menghapus ? <Loader2 className="h-4 w-4 animate-spin" /> : <Trash2 className="h-4 w-4" />}
              Ya, hapus semua
            </Tombol>
          </div>
        </Modal>
      )}

      {konfirmReset && (
        <Modal judul="Reset kartu beranda?" onTutup={() => !mereset && setKonfirmReset(false)}>
          <p className="text-sm text-slate-600">
            Susunan kartu <b>Statistik Demografi</b> di beranda dikembalikan ke <b>6 kartu bawaan</b>
            {' '}(Jumlah Penduduk, Kepala Keluarga, Laki-laki, Perempuan, Wajib KTP, Sudah Rekam
            KTP-el). Data demografi tidak terpengaruh — hanya tampilan kartunya. Penyesuaian
            ikon/kolom yang sudah Anda buat akan tergantikan.
          </p>
          <div className="mt-5 flex justify-end gap-2">
            <Tombol varian="garis" onClick={() => setKonfirmReset(false)} disabled={mereset}>Batal</Tombol>
            <Tombol onClick={resetKartu} disabled={mereset}>
              {mereset ? <Loader2 className="h-4 w-4 animate-spin" /> : <RotateCcw className="h-4 w-4" />}
              Ya, kembalikan 6 kartu
            </Tombol>
          </div>
        </Modal>
      )}
    </LayoutDashboard>
  );
}
