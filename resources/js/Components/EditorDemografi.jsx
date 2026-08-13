import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import {
  AlertTriangle, ArrowLeft, Check, Download, FileUp, Layers, Loader2, Plus,
  Trash2, X,
} from 'lucide-react';
import { Pesan, Tombol } from '@/Components/Dasbor';
import { ambilJson, kirimBerkas, kirimJson } from '@/lib/api';

/**
 * 🔴 Di TINGKAT MODUL, bukan di dalam `EditorDemografi`.
 *
 * Sebelumnya komponen ini dideklarasikan di dalam komponen induknya. Tiap
 * ketikan membuat state berubah → induknya dijalankan ulang → `Tabel` jadi
 * fungsi baru yang oleh React dianggap tipe komponen BERBEDA, sehingga seluruh
 * tabel dilepas & dipasang ulang: fokus input hilang setiap satu huruf.
 * Pola yang sama pernah ada di `Pages/Auth/Register.jsx`.
 */
function TabelDemografi({ baris, kosongTeks, kolom, jkOtomatis, children }) {
  return (
    <div className="min-h-0 flex-1 overflow-auto rounded-2xl border border-slate-200 bg-white">
      {baris.length === 0 ? (
        <p className="py-16 text-center text-sm text-slate-500">{kosongTeks}</p>
      ) : (
        <table className="w-full text-sm">
          <thead className="sticky top-0 z-10 bg-slate-50">
            <tr className="border-b border-slate-200 text-left text-slate-500">
              <th className="px-3 py-2 font-medium">Kode</th>
              <th className="px-3 py-2 font-medium">Wilayah</th>
              {kolom.map((k) => (
                <th key={k} className="px-3 py-2 text-right font-medium">
                  {k}{jkOtomatis && k === 'JML' && <span className="ml-1 text-[0.6rem] font-normal text-slate-400">(otomatis)</span>}
                </th>
              ))}
              <th className="px-3 py-2" />
            </tr>
          </thead>
          <tbody>{children}</tbody>
        </table>
      )}
    </div>
  );
}


/**
 * Editor data demografi — port `components/dashboard/demografi-editor.tsx`.
 *
 * Dirender sebagai **halaman penuh lewat portal** (bukan modal): tabelnya lebar
 * dan sering diedit puluhan baris sekaligus; di dalam modal areanya terlalu
 * sempit. Rincian desa juga jadi halaman tersendiri, bukan modal bertumpuk.
 *
 * Dua jalan masuk data:
 *  1. **Impor Excel** (boleh beberapa berkas sekaligus) — dibaca server tanpa
 *     disimpan; kalau dua berkas memberi angka berbeda untuk wilayah yang sama,
 *     petugas yang memilih (dialog konflik), bukan berkas terakhir yang menang.
 *  2. **Isi manual** langsung di tabel.
 *
 * Apa pun jalannya, **tidak ada yang tersimpan sampai tombol Simpan ditekan.**
 */

const angka = (n) => Number(n ?? 0).toLocaleString('id-ID');
const digit = (s) => String(s ?? '').replace(/\D/g, '');

export default function EditorDemografi({ kategori, label, onTutup, onTersimpan }) {
  const [memuat, setMemuat] = useState(true);
  const [menyimpan, setMenyimpan] = useState(false);
  const [mengimpor, setMengimpor] = useState(false);
  const [kolom, setKolom] = useState([]);
  const [rows, setRows] = useState([]);
  const [detail, setDetail] = useState(null); // kecamatan yang dibuka rinciannya
  const [konflik, setKonflik] = useState(null); // {conflicts, rows, kolom, ringkas, pilihan}
  const [pesan, setPesan] = useState(null);

  const berkasUtama = useRef(null);
  const berkasDetail = useRef(null);

  // Kolom "JML" pada jenis kelamin dihitung otomatis dari L + P — di berkas
  // Dukcapil pun begitu, dan mengetiknya manual hampir pasti meleset.
  const jkOtomatis = kategori === 'jenis-kelamin';

  const muat = useCallback(async () => {
    setMemuat(true);
    const j = await ambilJson(`/api/admin/demografi?kategori=${encodeURIComponent(kategori)}`);
    setMemuat(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }
    setKolom(j.data?.kolom ?? []);
    setRows(j.data?.rows ?? []);
  }, [kategori]);

  useEffect(() => { muat(); }, [muat]);

  // Kunci gulir halaman di belakang selama editor terbuka.
  useEffect(() => {
    const semula = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => { document.body.style.overflow = semula; };
  }, []);

  const kecamatan = useMemo(() => rows.filter((r) => r.level === 4), [rows]);
  const pekonDetail = useMemo(
    () => (detail ? rows.filter((r) => r.level === 5 && r.parentKode === detail.kode) : []),
    [rows, detail],
  );

  const ubahSel = (kode, namaKolom, nilai) => {
    setRows((p) => p.map((r) => {
      if (r.kode !== kode) return r;
      const data = { ...r.data, [namaKolom]: Number(digit(nilai) || 0) };
      if (jkOtomatis && (namaKolom === 'L' || namaKolom === 'P') && 'JML' in data) {
        data.JML = (Number(data.L) || 0) + (Number(data.P) || 0);
      }
      return { ...r, data };
    }));
  };

  const ubahKolomTeks = (kode, medan, nilai) =>
    setRows((p) => p.map((r) => (r.kode === kode ? { ...r, [medan]: nilai } : r)));

  const hapusBaris = (kode) => setRows((p) => p.filter((r) => r.kode !== kode));

  const tambahKecamatan = () => {
    const kosong = Object.fromEntries(kolom.map((k) => [k, 0]));
    setRows((p) => [...p, { kode: '', wilayah: '', level: 4, parentKode: null, data: kosong }]);
  };

  const tambahPekon = () => {
    const kosong = Object.fromEntries(kolom.map((k) => [k, 0]));
    setRows((p) => [...p, {
      kode: '', wilayah: '', level: 5, parentKode: detail.kode, data: kosong,
    }]);
  };

  // ── Impor (banyak berkas, tanpa menyimpan) ────────────────────────────────

  const impor = async (daftarBerkas) => {
    if (!daftarBerkas?.length) return;

    setMengimpor(true);
    const fd = new FormData();
    fd.append('kategori', kategori);
    for (const f of daftarBerkas) fd.append('files[]', f);

    const j = await kirimBerkas('/api/admin/demografi/parse', fd);
    setMengimpor(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    const { conflicts = [], rows: baris = [], kolom: kol = [], ringkas } = j.data ?? {};

    if (conflicts.length > 0) {
      // Pilihan awal = opsi pertama tiap konflik; petugas boleh menggantinya.
      setKonflik({
        conflicts, rows: baris, kolom: kol, ringkas,
        pilihan: Object.fromEntries(conflicts.map((c) => [c.kode, 0])),
      });
      return;
    }

    terapkan(baris, kol, ringkas);
  };

  /** Gabungkan hasil impor ke tabel di layar (belum tersimpan). */
  const terapkan = (baris, kol, ringkas) => {
    if (kol.length) setKolom((p) => (p.length ? p : kol));

    setRows((p) => {
      const peta = new Map(p.map((r) => [r.kode, r]));
      for (const r of baris) peta.set(r.kode, r);
      return [...peta.values()].sort((a, b) => String(a.kode).localeCompare(String(b.kode)));
    });

    const kec = baris.filter((r) => r.level === 4).length;
    const pek = baris.filter((r) => r.level === 5).length;
    setPesan({
      tipe: 'sukses',
      teks: `${kec} kecamatan & ${pek} desa dimuat${ringkas?.berubah ? ` (${ringkas.berubah} berubah)` : ''} — periksa lalu Simpan`,
    });
  };

  const selesaikanKonflik = () => {
    const dipilih = konflik.conflicts.map((c) => ({
      kode: c.kode,
      wilayah: c.wilayah,
      level: c.level,
      parentKode: c.parentKode,
      data: c.options[konflik.pilihan[c.kode] ?? 0].data,
    }));

    terapkan([...konflik.rows, ...dipilih], konflik.kolom, konflik.ringkas);
    setKonflik(null);
  };

  // ── Simpan ────────────────────────────────────────────────────────────────

  const simpan = async () => {
    setMenyimpan(true);
    const j = await kirimJson('/api/admin/demografi', { kategori, rows }, 'PUT');
    setMenyimpan(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Data demografi disimpan' });
    onTersimpan?.();
    muat();
  };

  const kelasSel = 'w-full rounded border border-transparent bg-transparent px-1.5 py-1 text-sm outline-none hover:border-slate-200 focus:border-brand focus:bg-white';

  const barisTabel = (r, aksiTambahan) => (
    <tr key={r.kode || `baru-${r.level}-${rows.indexOf(r)}`} className="border-b border-slate-100">
      <td className="px-3 py-1.5">
        <input value={r.kode} inputMode="numeric" placeholder={r.level === 5 ? '10 digit' : '6 digit'}
               onChange={(e) => ubahKolomTeks(r.kode, 'kode', digit(e.target.value))}
               className={`${kelasSel} font-mono`} aria-label="Kode wilayah" />
      </td>
      <td className="px-3 py-1.5">
        <input value={r.wilayah} onChange={(e) => ubahKolomTeks(r.kode, 'wilayah', e.target.value)}
               className={kelasSel} aria-label="Nama wilayah" />
      </td>
      {kolom.map((k) => (
        <td key={k} className="px-3 py-1.5 text-right">
          <input value={r.data?.[k] ?? 0} inputMode="numeric"
                 readOnly={jkOtomatis && k === 'JML'}
                 onChange={(e) => ubahSel(r.kode, k, e.target.value)}
                 className={`${kelasSel} text-right tabular-nums ${jkOtomatis && k === 'JML' ? 'text-slate-400' : ''}`}
                 aria-label={`${k} ${r.wilayah}`} />
        </td>
      ))}
      <td className="px-3 py-1.5">
        <div className="flex items-center justify-end gap-1">
          {aksiTambahan?.(r)}
          <button onClick={() => hapusBaris(r.kode)} aria-label={`Hapus ${r.wilayah || 'baris'}`}
                  className="rounded p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600">
            <Trash2 className="h-3.5 w-3.5" />
          </button>
        </div>
      </td>
    </tr>
  );

  return createPortal(
    <div className="fixed inset-0 z-[120] flex flex-col bg-slate-50">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <header className="border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
        <div className="mx-auto flex w-full flex-wrap items-center justify-between gap-3 lg:max-w-[95vw]">
          <div className="min-w-0">
            {detail ? (
              <>
                <button onClick={() => setDetail(null)}
                        className="inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-brand hover:text-brand-dark">
                  <ArrowLeft className="h-4 w-4" /> Kembali ke daftar kecamatan
                </button>
                <h1 className="flex items-center gap-2 text-lg font-bold text-slate-900">
                  <Layers className="h-5 w-5 text-brand" />
                  Detail Desa — {detail.wilayah}
                </h1>
                <p className="text-xs text-slate-500">
                  Import Excel <b>detail distrik</b> (berisi desa kecamatan ini) atau isi manual.
                  Kode desa = 10 digit (diawali {detail.kode}).
                </p>
              </>
            ) : (
              <>
                <h1 className="text-lg font-bold text-slate-900">Edit Data — {label}</h1>
                <p className="text-xs text-slate-500">
                  <b>Import Excel</b> agregat (kecamatan). Klik <b>Detail</b> di kanan tiap kecamatan
                  untuk mengelola / import data desanya di halaman tersendiri.{' '}
                  {jkOtomatis && 'Kolom Jumlah dihitung otomatis. '}
                  Kode 6 digit = kecamatan, 10 digit = desa.
                </p>
              </>
            )}
          </div>

          <div className="flex shrink-0 items-center gap-2">
            <a href={`/api/admin/demografi/export?kategori=${encodeURIComponent(kategori)}`} download
               title={`Unduh data ${label} tersimpan sebagai Excel`}
               className="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:border-brand hover:text-brand">
              <Download className="h-4 w-4" /> Export Excel
            </a>
            {detail ? (
              <Tombol varian="garis" onClick={() => setDetail(null)} disabled={menyimpan}>
                <ArrowLeft className="h-4 w-4" />Kembali
              </Tombol>
            ) : (
              <Tombol varian="garis" onClick={onTutup} disabled={menyimpan}>
                <X className="h-4 w-4" />Batal
              </Tombol>
            )}
            <Tombol onClick={simpan} disabled={menyimpan || memuat}>
              {menyimpan ? <Loader2 className="h-4 w-4 animate-spin" /> : <Check className="h-4 w-4" />}Simpan
            </Tombol>
          </div>
        </div>
      </header>

      <div className="mx-auto flex min-h-0 w-full flex-1 flex-col gap-3 px-4 py-4 sm:px-6 lg:max-w-[95vw]">
        {memuat ? (
          <div className="flex justify-center py-24"><Loader2 className="h-6 w-6 animate-spin text-brand" /></div>
        ) : detail ? (
          <>
            <div className="flex flex-wrap items-center justify-between gap-2">
              <div className="flex items-center gap-2">
                <Tombol varian="garis" onClick={() => berkasDetail.current?.click()} disabled={mengimpor}>
                  {mengimpor ? <Loader2 className="h-4 w-4 animate-spin" /> : <FileUp className="h-4 w-4" />}
                  Import Excel Detail
                </Tombol>
                <Tombol varian="garis" onClick={tambahPekon}><Plus className="h-4 w-4" />Tambah Desa</Tombol>
                <input ref={berkasDetail} type="file" accept=".xlsx" multiple className="hidden"
                       onChange={(e) => { impor([...e.target.files]); e.target.value = ''; }} />
              </div>
              <p className="text-xs text-slate-500">{pekonDetail.length} desa</p>
            </div>

            <TabelDemografi baris={pekonDetail} kolom={kolom} jkOtomatis={jkOtomatis}
                            kosongTeks="Belum ada desa. Import Excel detail atau klik “Tambah Desa”.">
              {pekonDetail.map((r) => barisTabel(r))}
            </TabelDemografi>
          </>
        ) : (
          <>
            <div className="flex flex-wrap items-center justify-between gap-2">
              <div className="flex items-center gap-2">
                <Tombol varian="garis" onClick={() => berkasUtama.current?.click()} disabled={mengimpor}>
                  {mengimpor ? <Loader2 className="h-4 w-4 animate-spin" /> : <FileUp className="h-4 w-4" />}
                  Import Excel
                </Tombol>
                <Tombol varian="garis" onClick={tambahKecamatan}><Plus className="h-4 w-4" />Tambah Kecamatan</Tombol>
                <input ref={berkasUtama} type="file" accept=".xlsx" multiple className="hidden"
                       onChange={(e) => { impor([...e.target.files]); e.target.value = ''; }} />
              </div>
              <p className="text-xs text-slate-500">
                {kecamatan.length} kecamatan · {rows.filter((r) => r.level === 5).length} desa
              </p>
            </div>

            <TabelDemografi baris={kecamatan} kolom={kolom} jkOtomatis={jkOtomatis}
                            kosongTeks="Belum ada kecamatan. Import Excel atau klik “Tambah Kecamatan”.">
              {kecamatan.map((r) => barisTabel(r, (baris) => (
                <button onClick={() => setDetail(baris)}
                        className="rounded px-2 py-1 text-xs font-medium text-brand hover:bg-brand/5">
                  Detail
                </button>
              )))}
            </TabelDemografi>
          </>
        )}
      </div>

      {/* ── Dialog konflik antar berkas ── */}
      {konflik && (
        <div className="fixed inset-0 z-[130] flex items-center justify-center bg-slate-900/50 p-4">
          <div className="flex max-h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <div className="border-b border-slate-200 p-5">
              <h2 className="flex items-center gap-2 font-semibold text-slate-900">
                <AlertTriangle className="h-5 w-5 text-amber-500" />
                {konflik.conflicts.length} wilayah punya angka berbeda antar berkas
              </h2>
              <p className="mt-1 text-sm text-slate-500">
                {konflik.ringkas?.file} berkas dibaca · {konflik.ringkas?.totalWilayah} wilayah ·{' '}
                {konflik.ringkas?.berubah} berubah · {konflik.ringkas?.tetap} tetap. Pilih angka yang
                dipakai untuk tiap wilayah di bawah — belum ada yang tersimpan.
              </p>
            </div>

            <div className="flex-1 space-y-4 overflow-y-auto p-5">
              {konflik.conflicts.map((c) => (
                <div key={c.kode} className="rounded-xl border border-slate-200 p-4">
                  <p className="mb-2 text-sm font-semibold text-slate-800">
                    {c.wilayah} <span className="font-mono text-xs font-normal text-slate-400">{c.kode}</span>
                  </p>
                  <div className="space-y-2">
                    {c.options.map((o, i) => (
                      <label key={i} className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm ${
                        (konflik.pilihan[c.kode] ?? 0) === i ? 'border-brand bg-brand/5' : 'border-slate-200'
                      }`}>
                        <input type="radio" name={`konflik-${c.kode}`} checked={(konflik.pilihan[c.kode] ?? 0) === i}
                               onChange={() => setKonflik((p) => ({ ...p, pilihan: { ...p.pilihan, [c.kode]: i } }))}
                               className="mt-0.5 h-4 w-4 text-brand focus:ring-brand/40" />
                        <span className="min-w-0">
                          <span className="block font-medium text-slate-700">{o.label}</span>
                          <span className="mt-0.5 block text-xs text-slate-500">
                            {Object.entries(o.data).map(([k, v]) => `${k}: ${angka(v)}`).join(' · ')}
                          </span>
                        </span>
                      </label>
                    ))}
                  </div>
                </div>
              ))}
            </div>

            <div className="flex justify-end gap-2 border-t border-slate-200 p-4">
              <Tombol varian="garis" onClick={() => setKonflik(null)}>Batal</Tombol>
              <Tombol onClick={selesaikanKonflik}>Pakai pilihan ini</Tombol>
            </div>
          </div>
        </div>
      )}
    </div>,
    document.body,
  );
}
