import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import {
  AlertTriangle, ArrowLeft, Check, Download, FileUp, Layers, Loader2, Plus,
  Star, Trash2, X,
} from 'lucide-react';
import { Pesan, Tombol } from '@/Components/Dasbor';
import { ambilJson, kirimBerkas, kirimJson } from '@/lib/api';
import { KARTU_BAWAAN, WARNA_PRESET, labelKolom, resolveKolom, warnaPreset } from '@/lib/statistik-kartu';
import { NAMA_IKON, ikon as ikonDari } from '@/lib/ikon';
import { Input } from '@/Components/ui/input';

/**
 * 🔴 Di TINGKAT MODUL, bukan di dalam `EditorDemografi`.
 *
 * Sebelumnya komponen ini dideklarasikan di dalam komponen induknya. Tiap
 * ketikan membuat state berubah → induknya dijalankan ulang → `Tabel` jadi
 * fungsi baru yang oleh React dianggap tipe komponen BERBEDA, sehingga seluruh
 * tabel dilepas & dipasang ulang: fokus input hilang setiap satu huruf.
 * Pola yang sama pernah ada di `Pages/Auth/Register.jsx`.
 */
function TabelDemografi({
  baris, kosongTeks, kolom, jkOtomatis, children,
  sorotKolom, onSorot, kartuLain,
}) {
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
              {kolom.map((k) => {
                const disorot = sorotKolom === k;
                // Kolom yang sudah dipakai kartu LAIN di kategori ini.
                const judulLain = kartuLain?.get(k);
                return (
                  <th key={k} className={`px-3 py-2 text-right font-medium ${
                    disorot ? 'bg-amber-50' : judulLain ? 'bg-amber-50/40' : ''
                  }`}>
                    <span className="inline-flex items-center justify-end gap-1.5">
                      {/*
                        Bintang = "jadikan nilai utama". Satu kolom saja per
                        kategori — kolom inilah yang jadi kartu di beranda.

                        ⚠️ Judulnya diberi warna & latar amber saat terpilih, bukan
                        cuma ikon bintangnya. Petugas memindai baris header dari
                        kejauhan; ikon 16px sendirian terlalu mudah terlewat, dan
                        pertanyaan "yang mana yang tampil di beranda?" harus
                        terjawab dalam sekali lihat.
                      */}
                      <span className={disorot ? 'font-bold text-amber-700' : ''}>{k}</span>
                      {jkOtomatis && k === 'JML' && (
                        <span className="text-[0.6rem] font-normal text-slate-400">(otomatis)</span>
                      )}
                      {onSorot && (
                        <button
                          type="button"
                          onClick={() => onSorot(k)}
                          title={disorot
                            ? `Kolom ${k} sedang tampil sebagai kartu di beranda — klik untuk melepas`
                            : judulLain
                              ? `Kolom ${k} sudah tampil sebagai kartu “${judulLain}” — klik untuk mengedit kartu itu`
                              : `Jadikan kolom ${k} nilai utama yang tampil di beranda`}
                          className={`flex h-6 w-6 shrink-0 items-center justify-center rounded border transition-colors ${
                            disorot
                              ? 'border-amber-400 bg-amber-100 text-amber-500 hover:bg-amber-200'
                              : judulLain
                                ? 'border-amber-300 bg-white text-amber-400 hover:bg-amber-50'
                                : 'border-slate-200 bg-white text-slate-300 hover:border-amber-300 hover:text-amber-400'
                          }`}
                        >
                          <Star className={`h-3.5 w-3.5 ${disorot ? 'fill-amber-400' : ''}`} />
                        </button>
                      )}
                    </span>
                  </th>
                );
              })}
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

/**
 * Panel "Nilai Utama" — apa yang akan tampil di beranda dari kategori ini.
 *
 * 🔴 Ini bagian yang selama ini TIDAK ADA di portal Laravel. Editor bisa
 * menyunting angka, tapi tidak ada satu pun cara memilih angka mana yang jadi
 * kartu beranda — susunannya hanya bisa diubah lewat kode.
 *
 * ⚠️ Sengaja menampilkan PRATINJAU kartunya, bukan sekadar nama kolom. Petugas
 * yang menekan bintang sedang memutuskan apa yang dibaca warga di halaman
 * depan; memperlihatkan hasil akhirnya di tempat ia memilih menghilangkan
 * tebak-tebakan "yang mana tadi yang saya pilih".
 */
function PanelKartuBeranda({
  sorot, sorotAsli, kartuLain, total, judul, onJudul, ikonNama, onIkon, warna, onWarna,
}) {
  const w = warnaPreset(warna);
  const Ikon = ikonDari(ikonNama);

  if (!sorot) {
    return (
      <div className="rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 p-4">
        <p className="flex items-center gap-2 text-sm font-semibold text-slate-700">
          <Star className="h-4 w-4 text-slate-400" />
          Nilai utama belum dipilih
        </p>
        <p className="mt-1 text-xs text-slate-500">
          Tekan <Star className="inline h-3 w-3 -mt-0.5 fill-amber-300 text-amber-400" /> pada
          judul kolom di tabel untuk menjadikannya kartu di beranda. Satu kolom saja per kategori.
        </p>
      </div>
    );
  }

  return (
    <div className="rounded-2xl border border-amber-300 bg-amber-50/60 p-4">
      {/*
        🔴 Nama kartunya disebut, bukan cuma nama kolomnya. Satu kategori bisa
        memasok beberapa kartu; tanpa disebut, petugas tidak punya cara tahu
        kartu MANA yang sedang ia ubah — dan baru sadar setelah beranda berubah.
      */}
      <p className="mb-1 flex flex-wrap items-center gap-2 text-sm font-semibold text-amber-900">
        <Star className="h-4 w-4 fill-amber-400 text-amber-500" />
        Mengedit kartu “{(judul || '').trim() || labelKolom(sorot)}” — sumbernya kolom{' '}
        <span className="rounded bg-amber-200/70 px-1.5 py-0.5 font-mono">{sorot}</span>
      </p>
      {sorot !== sorotAsli && (
        <p className="mb-1 text-xs text-amber-800">
          Konfigurasi menyimpannya sebagai <span className="font-mono">{sorotAsli}</span>;
          kolom itu kini bernama <span className="font-mono">{sorot}</span> di data.
        </p>
      )}
      {kartuLain?.size > 0 && (
        <p className="mb-3 text-xs text-amber-800">
          Kategori ini juga memasok{' '}
          {[...kartuLain].map(([kol, jdl], i) => (
            <span key={kol}>
              {i > 0 && ', '}
              <b>{jdl}</b> (kolom <span className="font-mono">{kol}</span>)
            </span>
          ))}
          . Klik bintang kolomnya untuk mengedit kartu tersebut.
        </p>
      )}

      <div className="grid gap-4 lg:grid-cols-[minmax(0,15rem)_1fr]">
        {/* Pratinjau kartu — bentuknya sama dengan yang tampil di beranda. */}
        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div className={`mb-3 flex h-11 w-11 items-center justify-center rounded-2xl ${w.latar}`}>
            <Ikon className="h-5 w-5 text-white" />
          </div>
          <p className="text-[1.6rem] font-bold leading-none tracking-tight text-slate-900">
            {total === null ? '—' : angka(total)}
          </p>
          <p className="mt-1.5 text-[0.66rem] font-semibold uppercase tracking-widest text-slate-500">
            {judul || labelKolom(sorot)}
          </p>
          <p className="mt-2 text-[0.6rem] text-slate-400">Pratinjau kartu beranda</p>
        </div>

        <div className="space-y-3">
          <div>
            <label className="mb-1 block text-xs font-medium text-slate-600">Judul kartu</label>
            <Input value={judul} onChange={(e) => onJudul(e.target.value)}
                   placeholder={labelKolom(sorot)} className="h-9" />
          </div>

          <div>
            <label className="mb-1 block text-xs font-medium text-slate-600">Warna</label>
            <div className="flex flex-wrap gap-1.5">
              {Object.entries(WARNA_PRESET).map(([nama, pre]) => (
                <button key={nama} type="button" onClick={() => onWarna(nama)}
                        title={pre.label}
                        className={`h-7 w-7 rounded-lg ${pre.latar} ${
                          warna === nama ? 'ring-2 ring-slate-900 ring-offset-2' : ''
                        }`} />
              ))}
            </div>
          </div>

          <div>
            <label className="mb-1 block text-xs font-medium text-slate-600">Ikon</label>
            <div className="flex max-h-24 flex-wrap gap-1 overflow-y-auto rounded-lg border border-slate-200 bg-white p-1.5">
              {NAMA_IKON.map((nama) => {
                const I = ikonDari(nama);
                return (
                  <button key={nama} type="button" onClick={() => onIkon(nama)} title={nama}
                          className={`flex h-7 w-7 items-center justify-center rounded transition-colors ${
                            ikonNama === nama
                              ? 'bg-slate-900 text-white'
                              : 'text-slate-500 hover:bg-slate-100'
                          }`}>
                    <I className="h-4 w-4" />
                  </button>
                );
              })}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default function EditorDemografi({ kategori, label, onTutup, onTersimpan }) {
  const [memuat, setMemuat] = useState(true);
  const [menyimpan, setMenyimpan] = useState(false);
  const [mengimpor, setMengimpor] = useState(false);
  const [kolom, setKolom] = useState([]);
  const [rows, setRows] = useState([]);
  const [detail, setDetail] = useState(null); // kecamatan yang dibuka rinciannya
  const [konflik, setKonflik] = useState(null); // {conflicts, rows, kolom, ringkas, pilihan}
  const [pesan, setPesan] = useState(null);

  /*
   * Kolom yang dijadikan NILAI UTAMA — satu saja per kategori. Kolom inilah
   * yang jadi kartu "Statistik Demografi" di beranda publik.
   *
   * 🔴 Sebelum ini editor Laravel sama sekali tidak punya cara mengaturnya:
   * baris bisa disunting, tapi kartu beranda hanya bisa diubah lewat susunan
   * bawaan di kode. Petugas dinas tidak punya jalan sama sekali.
   */
  const [sorot, setSorot] = useState(null);
  const [kartuIkon, setKartuIkon] = useState('Users');
  const [kartuWarna, setKartuWarna] = useState('biru');
  const [kartuJudul, setKartuJudul] = useState('');

  /*
   * Seluruh kartu beranda apa adanya — kartu kategori LAIN harus ikut dikirim
   * saat menyimpan, kalau tidak ia terhapus.
   */
  const [kartuSemua, setKartuSemua] = useState([]);
  /** Kolom yang kartunya sedang diedit — identitas kartu di konfigurasi. */
  const [kolomTarget, setKolomTarget] = useState(null);

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

  /*
   * Muat konfigurasi kartu beranda sekali saat editor dibuka.
   *
   * ⚠️ Gagal memuat TIDAK menghentikan editor: data demografinya tetap bisa
   * disunting. Yang hilang cuma kemampuan mengubah kartu — dan itu jauh lebih
   * baik daripada editor yang menolak terbuka karena satu permintaan meleset.
   */
  useEffect(() => {
    let batal = false;
    (async () => {
      const j = await ambilJson('/api/static-content?keys=beranda.statistik');
      if (batal) return;

      const tersimpan = j.data?.items?.['beranda.statistik']?.kartu;
      const daftar = Array.isArray(tersimpan) && tersimpan.length ? tersimpan : KARTU_BAWAAN;
      setKartuSemua(daftar.map((k) => ({ ...k })));

      const milik = daftar.find((k) => k.kategori === kategori);
      setKolomTarget(milik?.kolom ?? null);
      setSorot(milik?.kolom ?? null);
      setKartuIkon(milik?.icon ?? 'Users');
      setKartuWarna(milik?.warna ?? 'biru');
      setKartuJudul(milik?.title ?? '');
    })();
    return () => { batal = true; };
  }, [kategori]);

  /*
   * Nama kolom SEBENARNYA yang dipakai kartu yang sedang diedit.
   *
   * 🔴 Yang tersimpan di konfigurasi belum tentu ada di data. Kartu "Wajib
   * KTP" menyimpan kolom `JML` sementara berkas Dukcapil terbaru menulis
   * `Total`; beranda menyetarakan keduanya lewat resolveKolom dan menampilkan
   * angkanya, tapi editor membaca `JML` mentah — pratinjaunya 0 dan bintangnya
   * tidak muncul di kolom mana pun. Dua layar, satu kartu, dua jawaban.
   */
  const sorotNyata = useMemo(
    () => (sorot ? resolveKolom(kolom, sorot) : null),
    [sorot, kolom],
  );

  /*
   * Kartu LAIN di kategori ini → { nama kolom nyata: judul kartu }.
   * Diturunkan, bukan disimpan: daftar kolom dan konfigurasi kartu datang dari
   * dua permintaan berbeda, dan mana yang tiba lebih dulu tidak dijamin.
   */
  const kartuLain = useMemo(() => {
    const peta = new Map();
    for (const c of kartuSemua) {
      if (c.kategori !== kategori || c.kolom === kolomTarget) continue;
      peta.set(resolveKolom(kolom, c.kolom) ?? c.kolom, c.title);
    }
    return peta;
  }, [kartuSemua, kategori, kolomTarget, kolom]);

  /**
   * Klik bintang. Tiga arti, bergantung keadaan kolomnya:
   *
   * 1. kolom kartu yang sedang diedit    → lepas kartunya dari beranda
   * 2. kolom yang sudah punya kartu lain → PINDAH mengedit kartu itu
   * 3. kolom bebas                       → pindahkan kartu yang diedit ke sana
   *
   * 🔴 Cabang (2) yang menentukan. Satu kategori bisa memasok beberapa kartu —
   * `jenis-kelamin` memasok tiga sekaligus. Tanpa cabang ini, membintangi
   * kolom yang sudah dipakai kartu lain akan MENIMPA kartu yang sedang diedit
   * ke sana: beranda mendapat dua kartu identik dan kehilangan satu kartu,
   * diam-diam, tanpa satu pun galat.
   */
  const alihkanSorot = (k) => {
    if (sorotNyata === k) {
      setSorot(null);
      setKartuJudul('');
      return;
    }

    const judulLain = kartuLain.get(k);
    if (judulLain !== undefined) {
      const lain = kartuSemua.find(
        (c) => c.kategori === kategori && (resolveKolom(kolom, c.kolom) ?? c.kolom) === k,
      );
      setKolomTarget(lain?.kolom ?? k);
      setSorot(k);
      setKartuIkon(lain?.icon ?? 'Users');
      setKartuWarna(lain?.warna ?? 'biru');
      setKartuJudul(lain?.title ?? judulLain);
      return;
    }

    setSorot(k);
    // Judul mengikuti kolom baru, KECUALI petugas sudah menuliskannya sendiri.
    setKartuJudul((j) => (!j || j === labelKolom(sorotNyata) ? labelKolom(k) : j));
  };

  // Kunci gulir halaman di belakang selama editor terbuka.
  useEffect(() => {
    const semula = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => { document.body.style.overflow = semula; };
  }, []);

  const kecamatan = useMemo(() => rows.filter((r) => r.level === 4), [rows]);

  /*
   * Angka pratinjau kartu — dihitung persis seperti beranda: dari baris PEKON
   * bila ada, jatuh ke kecamatan bila belum. Menjumlah keduanya sekaligus
   * membuat angkanya DUA KALI LIPAT, sebab kecamatan adalah total pekon di
   * bawahnya.
   */
  const totalSorot = useMemo(() => {
    if (!sorotNyata) return null;
    const pekon = rows.filter((r) => r.level === 5);
    const dipakai = pekon.length ? pekon : rows.filter((r) => r.level === 4);
    return dipakai.reduce((a, r) => a + (Number(r.data?.[sorotNyata]) || 0), 0);
  }, [sorotNyata, rows]);
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

    try {
      await simpanKartu();
      setPesan({ tipe: 'sukses', teks: 'Data demografi & kartu beranda disimpan' });
    } catch (e) {
      // Datanya SUDAH tersimpan — jangan berkata gagal. Yang gagal cuma kartunya.
      setPesan({ tipe: 'galat', teks: `Data tersimpan, tetapi kartu beranda gagal diperbarui: ${e.message}` });
    }

    onTersimpan?.();
    muat();
  };

  /**
   * Perbarui HANYA kartu milik kategori ini; kartu kategori lain dibiarkan utuh.
   *
   * 🔴 Kartu lain WAJIB ikut dikirim. Endpoint `static-content` MENGGANTI isi
   * kuncinya, bukan menggabungkan — mengirim satu kartu saja akan menghapus
   * lima kartu beranda lainnya.
   *
   * - ada sorot  : kartu kategori ini diganti kolom/ikon/warna/judul terbaru
   * - tanpa sorot: kartu kategori ini dihapus dari beranda
   */
  const simpanKartu = async () => {
    const sebelum = kartuSemua;
    const target = kolomTarget;
    const posisi = sebelum.findIndex((c) => c.kategori === kategori && c.kolom === target);
    const lamaKartu = posisi >= 0 ? sebelum[posisi] : undefined;

    const kartu = sebelum.filter((c) => !(c.kategori === kategori && c.kolom === target));
    // Nama kolom NYATA yang ditulis, bukan ejaan lama dari konfigurasi: sekali
    // disimpan, editor dan beranda membaca kolom yang sama persis.
    const kolomBaru = sorot ? (sorotNyata ?? sorot) : null;

    if (kolomBaru) {
      const entri = {
        ...(lamaKartu ?? {}),
        title: (kartuJudul || '').trim() || labelKolom(kolomBaru),
        icon: kartuIkon,
        kategori,
        kolom: kolomBaru,
        warna: kartuWarna,
      };

      /*
       * 🔴 Jaring pengaman terakhir: buang kartu lain yang kebetulan sudah
       * memakai kolom ini. `alihkanSorot` seharusnya sudah mencegahnya, tapi
       * konfigurasi yang tersimpan sebelum perbaikan ini bisa saja SUDAH
       * kembar — dan menyimpan ulang tidak boleh melanggengkannya.
       */
      const bentrok = kartu.findIndex(
        (c) => c.kategori === kategori && (resolveKolom(kolom, c.kolom) ?? c.kolom) === kolomBaru,
      );
      if (bentrok >= 0) kartu.splice(bentrok, 1);

      kartu.splice(posisi >= 0 ? Math.min(posisi, kartu.length) : kartu.length, 0, entri);
    }

    const j = await kirimJson('/api/admin/static-content', {
      kunci: 'beranda.statistik',
      konten: { kartu },
    }, 'PUT');

    if (j.error?.length) throw new Error(j.error[0]);

    // Kartu target kini beridentitas kolom terbaru.
    setKolomTarget(kolomBaru);
    setKartuSemua(kartu);
  };

  // Sel tabel: bentuk dasarnya dari `Components/ui/input`, di sini hanya
  // penyesuaian kepadatan supaya barisnya tetap muat di layar.
  const kelasSel = 'h-8 w-full border-slate-300 bg-white px-2 text-sm';

  const barisTabel = (r, aksiTambahan) => (
    <tr key={r.kode || `baru-${r.level}-${rows.indexOf(r)}`} className="border-b border-slate-100">
      <td className="px-3 py-1.5">
        <Input value={r.kode} inputMode="numeric" placeholder={r.level === 5 ? '10 digit' : '6 digit'}
               onChange={(e) => ubahKolomTeks(r.kode, 'kode', digit(e.target.value))}
               className={`${kelasSel} font-mono`} aria-label="Kode wilayah" />
      </td>
      <td className="px-3 py-1.5">
        <Input value={r.wilayah} onChange={(e) => ubahKolomTeks(r.kode, 'wilayah', e.target.value)}
               className={`${kelasSel} min-w-44`} aria-label="Nama wilayah" />
      </td>
      {kolom.map((k) => (
        <td key={k} className="px-3 py-1.5 text-right">
          <Input value={r.data?.[k] ?? 0} inputMode="numeric"
                 readOnly={jkOtomatis && k === 'JML'}
                 onChange={(e) => ubahSel(r.kode, k, e.target.value)}
                 className={`${kelasSel} min-w-[5.5rem] text-right tabular-nums ${jkOtomatis && k === 'JML' ? 'bg-slate-100 text-slate-400' : ''}`}
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
                            sorotKolom={sorotNyata} onSorot={alihkanSorot} kartuLain={kartuLain}
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

            <PanelKartuBeranda
              sorot={sorotNyata}
              sorotAsli={sorot}
              kartuLain={kartuLain}
              total={totalSorot}
              judul={kartuJudul}
              onJudul={setKartuJudul}
              ikonNama={kartuIkon}
              onIkon={setKartuIkon}
              warna={kartuWarna}
              onWarna={setKartuWarna}
            />

            <TabelDemografi baris={kecamatan} kolom={kolom} jkOtomatis={jkOtomatis}
                            sorotKolom={sorotNyata} onSorot={alihkanSorot} kartuLain={kartuLain}
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
                               className="mt-0.5 h-4 w-4 accent-primary" />
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
