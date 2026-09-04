import { useCallback, useEffect, useRef, useState } from 'react';
import {
  AlertTriangle, CheckCircle2, Download, FileSpreadsheet, Loader2, Pencil,
  RotateCcw, Trash2, Upload,
} from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import EditorDemografi from '@/Components/EditorDemografi';
import PemilihPeriode from '@/Components/PemilihPeriode';
import { Modal, Pesan, Tombol } from '@/Components/Dasbor';
import { ambilJson, kirimBerkas, kirimJson } from '@/lib/api';
import { gabungPeriode, kueriPeriode, labelPeriode, periodeDugaan } from '@/lib/periode';

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
  /*
   * Periode yang sedang dikelola. Seluruh halaman ini — hitungan tersimpan,
   * unggah, unduh, hapus, dan editor — bekerja PADA periode ini saja.
   *
   * 🔴 Sebelum ada periode, mengunggah DKB semester baru menghapus semester
   * sebelumnya: tabelnya berkunci `(kategori, kode)`, satu baris per wilayah,
   * dan impor mengganti total. Dinas kehilangan datanya tanpa peringatan.
   */
  const [periode, setPeriode] = useState(null);
  const [periodeTersedia, setPeriodeTersedia] = useState([]);
  const [mengunggah, setMengunggah] = useState(null);
  const [sunting, setSunting] = useState(null);
  const [konfirmHapus, setKonfirmHapus] = useState(false);
  const [menghapus, setMenghapus] = useState(false);
  const [konfirmReset, setKonfirmReset] = useState(false);
  const [mereset, setMereset] = useState(false);
  const [pesan, setPesan] = useState(null);
  const inputs = useRef({});

  const segarkan = useCallback(async (slug, pakai = periode) => {
    const q = kueriPeriode(pakai);
    const j = await ambilJson(
      `/api/demografi?kategori=${encodeURIComponent(slug)}${q ? `&${q}` : ''}`,
    );
    setJumlah((c) => ({ ...c, [slug]: j.data?.items?.length ?? 0 }));
  }, [periode]);

  /*
   * Daftar periode diambil dari endpoint ADMIN, bukan publik.
   *
   * 🔴 `/api/demografi` menghitung periode untuk SATU kategori saja — angkanya
   * akan terbaca sebagai "129 baris" padahal seluruh kategori berjumlah 1.032.
   * Pemilih di halaman ini mengatur SEMUA kategori sekaligus, jadi hitungannya
   * harus lintas kategori pula.
   */
  const segarkanPeriode = useCallback(async () => {
    const j = await ambilJson(
      `/api/admin/demografi?kategori=${encodeURIComponent(kategori[0]?.slug ?? '')}`,
    );
    const daftar = Array.isArray(j.data?.periodeTersedia) ? j.data.periodeTersedia : [];
    setPeriodeTersedia((lama) => gabungPeriode(lama, daftar));

    return { daftar, periode: j.data?.periode ?? null };
  }, [kategori]);

  const segarkanSemua = useCallback((pakai = periode) => {
    kategori.forEach((k) => segarkan(k.slug, pakai));
  }, [kategori, segarkan, periode]);

  /*
   * Periode awal ditentukan SEKALI, dari data yang benar-benar ada — bukan
   * ditebak dari kalender. Kalau tabelnya masih kosong sama sekali, barulah
   * dugaan dari tanggal hari ini dipakai sebagai isian awal.
   */
  useEffect(() => {
    let batal = false;

    (async () => {
      const { daftar, periode: dariServer } = await segarkanPeriode();
      if (batal) return;

      setPeriode(dariServer ?? (daftar[0]
        ? { tahun: daftar[0].tahun, semester: daftar[0].semester }
        : periodeDugaan()));
    })();

    return () => { batal = true; };
  }, [segarkanPeriode]);

  useEffect(() => { if (periode) segarkanSemua(periode); }, [periode]); // eslint-disable-line react-hooks/exhaustive-deps

  const totalTersimpan = kategori.reduce((a, k) => a + (jumlah[k.slug] ?? 0), 0);

  const unggah = async (slug, berkas) => {
    if (!berkas) return;

    setMengunggah(slug);
    const fd = new FormData();
    fd.append('file', berkas);
    fd.append('kategori', slug);
    // Tanpa ini server memakai periode terbaru — dan berkas semester I bisa
    // mendarat menimpa semester II hanya karena periodenya tidak disebut.
    fd.append('tahun', periode.tahun);
    fd.append('semester', periode.semester);

    const j = await kirimBerkas('/api/admin/demografi/import', fd);
    setMengunggah(null);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Import berhasil' });
    segarkan(slug);
    // Impor ke periode yang belum pernah ada menambah satu entri di pemilih.
    segarkanPeriode();
  };

  /*
   * Menghapus HANYA periode yang sedang dipilih.
   *
   * 🔴 Dulu tombol ini menyapu seluruh tabel. Sejak beberapa periode bisa
   * berdampingan, menyapu semuanya berarti satu klik menghapus data
   * bertahun-tahun — termasuk semester yang tidak sedang dilihat petugas.
   */
  const hapusSemua = async () => {
    setMenghapus(true);
    const j = await kirimJson(
      `/api/admin/demografi?${kueriPeriode(periode)}`, {}, 'DELETE',
    );
    setMenghapus(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Data periode ini dihapus' });
    setKonfirmHapus(false);
    setPeriodeTersedia((d) => d.filter(
      (x) => !(x.tahun === periode.tahun && x.semester === periode.semester),
    ));
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
            per kategori. Setiap unggahan <b>mengganti</b> data kategori tersebut{' '}
            <b>pada periode yang sedang dipilih saja</b> — periode lain tidak tersentuh. Klik{' '}
            <b>Edit / Import</b> untuk mengelola data kecamatan &amp; <b>detail desa</b>-nya.
          </div>
          <div className="flex flex-shrink-0 flex-wrap items-center gap-2">
            {/*
              Pemilih periode berdiri PALING KIRI di antara tombol-tombol ini,
              sebelum Export/Reset/Hapus — ketiganya bekerja pada periode yang
              dipilih di sini, dan urutan bacanya harus mencerminkan itu.
            */}
            <PemilihPeriode
              nilai={periode}
              tersedia={periodeTersedia}
              onPilih={setPeriode}
              bolehBaru
            />
            <Tombol varian="garis" disabled={totalTersimpan === 0}
                    title="Unduh semua kategori dalam satu file Excel"
                    onClick={() => unduh(`/api/admin/demografi/export?${kueriPeriode(periode)}`)}>
              <Download className="h-4 w-4" />Export Semua
            </Tombol>
            <Tombol varian="garis" onClick={() => setKonfirmReset(true)}
                    title="Kembalikan kartu statistik beranda ke 6 kartu bawaan">
              <RotateCcw className="h-4 w-4" />Reset Kartu Beranda
            </Tombol>
            <Tombol varian="garis" kelas="border-rose-300 text-rose-600 hover:bg-rose-50"
                    disabled={totalTersimpan === 0} onClick={() => setKonfirmHapus(true)}
                    title="Hapus data demografi semua kategori PADA PERIODE INI">
              <Trash2 className="h-4 w-4" />Hapus Periode Ini
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
                        onClick={() => unduh(
                          `/api/admin/demografi/export?kategori=${encodeURIComponent(k.slug)}&${kueriPeriode(periode)}`,
                        )}
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
          periode={periode}
          periodeTersedia={periodeTersedia}
          onPeriode={setPeriode}
          onTutup={() => setSunting(null)}
          onTersimpan={() => { segarkan(sunting.slug); segarkanPeriode(); }}
        />
      )}

      {konfirmHapus && (
        <Modal
          judul={`Hapus data ${periode ? labelPeriode(periode.tahun, periode.semester) : 'periode ini'}?`}
          onTutup={() => !menghapus && setKonfirmHapus(false)}
        >
          <p className="flex items-start gap-2 text-sm text-slate-600">
            <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
            <span>
              Data <b>semua kategori</b> (kecamatan &amp; desa) pada{' '}
              <b>{periode ? labelPeriode(periode.tahun, periode.semester) : 'periode ini'}</b>{' '}
              akan dihapus permanen — total <b>{totalTersimpan} kecamatan</b> tersimpan.
              Periode lain tidak tersentuh. Sebaiknya <b>Export Semua</b> dulu sebagai
              cadangan. Tindakan ini tidak dapat dibatalkan.
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
