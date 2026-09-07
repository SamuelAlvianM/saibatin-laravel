import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  AlertTriangle, CalendarDays, CheckCircle2, ChevronRight, Download, Eye, EyeOff,
  FileSpreadsheet, Loader2, Pencil, Plus, RotateCcw, Tag, Trash2, Upload,
} from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import EditorDemografi from '@/Components/EditorDemografi';
import { Modal, Pesan, Tombol } from '@/Components/Dasbor';
import { ambilJson, kirimBerkas, kirimJson } from '@/lib/api';
import { deteksiKategori } from '@/lib/deteksi-kategori';
import {
  gabungPeriode, kueriPeriode, kunciPeriode, labelPeriode, periodeDugaan, tahunPilihan,
} from '@/lib/periode';

/**
 * Data Demografi — port `app/dashboard/demografi/AdminDemografi.tsx`.
 *
 * Sumber datanya berkas Excel agregat Dukcapil (SIAK). Tiap TAHUN adalah satu
 * baris berisi dua wadah — Semester I dan Semester II — dan isi wadah yang
 * dibuka terbentang penuh di bawahnya: satu tombol impor untuk seluruh periode,
 * lalu daftar kategori yang masing-masing bisa disunting sendiri.
 */

/**
 * Judul kategori yang DIUSULKAN dari nama berkas.
 *
 * 🔴 Ini yang dimaksud "sistem membaca judulnya sendiri". Berkas agregat
 * buatan dinas tidak punya pola SIAK yang bisa dikenali, dan namanya adalah
 * satu-satunya keterangan yang ikut bersama berkasnya. Mengusulkan judul dari
 * situ menghemat pengetikan — tapi usulan tetap DAPAT DISUNTING sebelum
 * disimpan, karena nama berkas sering mengandung sisa penamaan internal yang
 * tidak layak jadi judul publik.
 *
 * Yang dibuang hanya derau yang pasti: penanda AGR/DUSUN dari SIAK, tahun, dan
 * penanda semester. Kata seperti "Data" DIPERTAHANKAN — "Data Kemiskinan"
 * adalah judul yang sah, dan memangkasnya berarti menebak maksud dinas.
 */
function usulJudul(namaBerkas) {
  const bersih = namaBerkas
    .replace(/\.xlsx$/i, '')
    .replace(/[_\-.]+/g, ' ')
    .replace(/\b(agr|dusun|kec|kel)\b/gi, ' ')
    .replace(/\bsem(ester)?\s*(i{1,3}|[12])\b/gi, ' ')
    .replace(/\b(19|20)\d{2}\b/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

  const judul = (bersih.length >= 3 ? bersih : namaBerkas.replace(/\.xlsx$/i, ''))
    .toLowerCase()
    .replace(/(^|\s)\S/g, (c) => c.toUpperCase());

  return judul.slice(0, 60);
}

/** Penanda tujuan "buat kategori baru" pada daftar konfirmasi impor. */
const TUJUAN_BARU = '__baru__';

/** Unduh lewat <a download> — memicu dialog simpan peramban. */
function unduh(url) {
  const a = document.createElement('a');
  a.href = url;
  a.rel = 'noopener';
  document.body.appendChild(a);
  a.click();
  a.remove();
}

/*
 * Gerak buka-tutup wadah periode dan panel kategori.
 *
 * 🔴 DURASI BUKA DAN TUTUP SENGAJA SAMA. Membuka satu semester menutup
 * pasangannya, jadi dua panel selalu bergerak berlawanan pada saat yang sama.
 * Kalau yang menutup lebih cepat, tinggi total baris itu menciut dulu lalu
 * memuai lagi — dan seluruh isi halaman di bawahnya tersentak turun lalu naik.
 * Dengan durasi yang sama, tinggi totalnya nyaris tetap dan pergantian semester
 * terbaca sebagai satu panel yang bergeser, bukan dua yang berebut tempat.
 *
 * Kurvanya melambat panjang di ujung, tidak seperti `ease-out` bawaan yang
 * berhenti mendadak persis saat panel mencapai tinggi penuh.
 *
 * Isinya ikut memudar dan bergeser sedikit: panel yang tingginya menciut
 * sementara tulisannya tetap pekat terbaca seperti TERPENGGAL, bukan tertutup.
 * Saat membuka, pudarnya diberi jeda singkat supaya kotaknya sempat terbuka
 * lebih dulu — tulisan yang muncul sebelum ada ruangnya terlihat berdesakan.
 *
 * `motion-reduce` dihormati: pengguna yang mematikan animasi di sistemnya
 * langsung melihat keadaan akhirnya, tanpa gerak sama sekali.
 */
const GERAK_WADAH = 'grid transition-[grid-template-rows] duration-[340ms] '
  + 'ease-[cubic-bezier(0.32,0.72,0,1)] motion-reduce:transition-none';

const GERAK_ISI = 'transition-[opacity,transform] ease-out motion-reduce:transition-none';

const GERAK_ISI_BUKA = 'opacity-100 translate-y-0 duration-[260ms] delay-[90ms]';

const GERAK_ISI_TUTUP = 'opacity-0 -translate-y-1.5 duration-[160ms]';

/** Anak panah kepala wadah — ikut kurva yang sama supaya terasa satu gerakan. */
const GERAK_PANAH = 'transition-transform duration-[340ms] '
  + 'ease-[cubic-bezier(0.32,0.72,0,1)] motion-reduce:transition-none';

export default function Demografi({ kategori: kategoriAwal, kartuBawaan }) {
  /*
   * 🔴 DAFTAR KATEGORI JADI STATE, bukan prop yang beku.
   *
   * Propnya benar saat halaman dirender, tapi kategori bisa ditambah dan
   * dihapus tanpa memuat ulang halaman — dan penanda "tampil di halaman utama"
   * hanya datang dari endpoint kategori. Prop dipakai sebagai nilai awal supaya
   * daftarnya tidak berkedip kosong, lalu jawaban peladen yang berlaku.
   */
  const [kategori, setKategori] = useState(
    () => kategoriAwal.map((k) => ({ ...k, bawaan: true, beranda: true })),
  );
  /*
   * 🔴 HALAMAN INI TIDAK PUNYA "PERIODE TERPILIH" LAGI.
   *
   * Dulu seluruh halaman bekerja pada satu periode aktif yang dipilih lewat
   * badge di pojok. Akibatnya periode adalah MODE, bukan benda: petugas yang
   * baru mengunggah DKB Semester I tidak punya cara melihat bahwa Semester II
   * ada dan masih kosong — ia harus membuka pemilih dan menebak. Yang lebih
   * berbahaya, tombol Hapus dan Unggah bekerja pada periode aktif tanpa
   * menyebutkan periode itu di dekat tombolnya sendiri.
   */
  const [hitungan, setHitungan] = useState({});
  const [periodeTersedia, setPeriodeTersedia] = useState([]);
  /*
   * Tahun yang dibuat petugas tapi belum berisi apa pun — peladen tidak tahu
   * tentangnya, karena peladen hanya mendaftar periode yang PUNYA baris.
   *
   * ⚠️ Disemai dengan tahun berjalan, BUKAN dibiarkan kosong lalu ditambal
   * belakangan saat daftarnya nihil. Tambalan semacam itu menguap begitu tahun
   * pertama ditambahkan: wadah yang sedang ditatap petugas hilang dari layar
   * hanya karena ia membuat wadah lain.
   */
  const [tahunTambahan, setTahunTambahan] = useState(() => [periodeDugaan().tahun]);
  const [terbuka, setTerbuka] = useState(() => new Set());
  const [memuatDaftar, setMemuatDaftar] = useState(true);

  /** Periode yang sedang mengimpor, beserta kemajuannya. */
  const [impor, setImpor] = useState(null);
  const [sunting, setSunting] = useState(null);
  const [konfirmHapus, setKonfirmHapus] = useState(null);
  const [menghapus, setMenghapus] = useState(false);
  const [konfirmReset, setKonfirmReset] = useState(false);
  const [mereset, setMereset] = useState(false);
  const [pesan, setPesan] = useState(null);

  const [tambahBuka, setTambahBuka] = useState(false);
  const [tahunBaru, setTahunBaru] = useState(new Date().getFullYear());
  const kotakTambah = useRef(null);

  const [panelKategori, setPanelKategori] = useState(false);
  const [judulKategoriBaru, setJudulKategoriBaru] = useState('');
  const [sibukKategori, setSibukKategori] = useState(false);
  const [konfirmHapusKategori, setKonfirmHapusKategori] = useState(null);
  /** Berkas yang MENUNGGU DIKONFIRMASI tujuannya — belum ditulis ke mana pun. */
  const [antre, setAntre] = useState(null);

  const berkas = useRef({});
  const sudahBukaAwal = useRef(false);

  /*
   * Daftar periode diambil dari endpoint ADMIN, bukan publik.
   *
   * 🔴 `/api/demografi` menghitung periode untuk SATU kategori saja — angkanya
   * akan terbaca sebagai "129 baris" padahal seluruh kategori berjumlah 1.032.
   * Halaman ini mengurus SEMUA kategori sekaligus, jadi hitungannya harus
   * lintas kategori pula.
   */
  const segarkanPeriode = useCallback(async () => {
    const j = await ambilJson(
      `/api/admin/demografi?kategori=${encodeURIComponent(kategori[0]?.slug ?? '')}`,
    );
    const daftar = Array.isArray(j.data?.periodeTersedia) ? j.data.periodeTersedia : [];
    setPeriodeTersedia((lama) => gabungPeriode(lama, daftar));

    return daftar;
  }, [kategori]);

  const segarkanKategori = useCallback(async () => {
    const j = await ambilJson('/api/admin/demografi/kategori');
    if (Array.isArray(j.data?.kategori)) setKategori(j.data.kategori);
  }, []);

  useEffect(() => {
    Promise.all([segarkanPeriode(), segarkanKategori()])
      .finally(() => setMemuatDaftar(false));
  }, [segarkanPeriode, segarkanKategori]);

  /** Tambah kategori buatan dinas. `judul` bebas; slug-nya disusun peladen. */
  const tambahKategori = async (judul) => {
    setSibukKategori(true);
    const j = await kirimJson('/api/admin/demografi/kategori', { judul }, 'POST');
    setSibukKategori(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return null; }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Kategori dibuat' });
    await segarkanKategori();

    return j.data?.kategori ?? null;
  };

  /*
   * Tampil di halaman utama.
   *
   * ⚠️ Dikirim sebagai DAFTAR LENGKAP, bukan satu slug yang di-toggle. Dua
   * petugas yang menyalakan kategori berbeda pada saat yang hampir sama akan
   * saling menimpa kalau tiap permintaan hanya membawa perubahannya sendiri —
   * dan yang kalah tidak pernah tahu pilihannya hilang. Daftar penuh membuat
   * keadaan akhir persis seperti yang terlihat di layar pengirimnya.
   */
  const ubahBeranda = async (slug, tampil) => {
    const sebelum = kategori;
    const sesudah = kategori.map((k) => (k.slug === slug ? { ...k, beranda: tampil } : k));
    setKategori(sesudah); // optimistis — jawabannya harus terasa seketika

    const j = await kirimJson(
      '/api/admin/demografi/kategori',
      { beranda: sesudah.filter((k) => k.beranda).map((k) => k.slug) },
      'PUT',
    );

    if (j.error?.length) {
      setPesan({ tipe: 'galat', teks: j.error[0] });
      setKategori(sebelum); // layar kembali jujur
    }
  };

  const hapusKategori = async (slug, label) => {
    setSibukKategori(true);
    const j = await kirimJson(
      `/api/admin/demografi/kategori?slug=${encodeURIComponent(slug)}`, {}, 'DELETE',
    );
    setSibukKategori(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? `Kategori "${label}" dihapus` });
    await segarkanKategori();
  };

  /*
   * Hitungan kategori dimuat SAAT WADAHNYA DIBUKA, bukan di awal.
   *
   * ⚠️ Delapan kategori dikali sekian periode berarti puluhan permintaan
   * sekaligus pada tiap kunjungan — untuk angka yang sebagian besarnya tidak
   * sedang dilihat siapa pun. Ringkasan pada wadah yang tertutup sudah cukup
   * dijawab oleh jumlah baris yang ikut datang bersama daftar periode.
   */
  const muatHitungan = useCallback(async (p) => {
    const k = kunciPeriode(p);

    /*
     * 🔴 ENDPOINT ADMIN, bukan `/api/demografi` publik.
     *
     * Yang publik sengaja JATUH KE PERIODE TERBARU bila periode yang diminta
     * kosong — benar untuk warga yang membuka tautan lama, bencana di sini.
     * Dasbor bertanya "berapa isi Semester I 2026?", dijawab isi Semester II
     * 2024, lalu memasang "8 dari 8 kategori terisi" pada wadah yang
     * sebenarnya kosong. Tombol Export dan Hapus-nya ikut menyala untuk
     * periode yang tidak pernah ada isinya.
     *
     * Endpoint ini menjawab PERSIS periode yang diminta, dan sekaligus
     * menggantikan delapan permintaan dengan satu.
     */
    const j = await ambilJson(`/api/admin/demografi/hitungan?${kueriPeriode(p)}`);
    const dari = j.data?.hitungan ?? {};
    const hasil = {};
    for (const kat of kategori) hasil[kat.slug] = dari[kat.slug] ?? 0;

    setHitungan((h) => ({ ...h, [k]: hasil }));
  }, [kategori]);

  /** Tahun yang punya wadah, terbaru di atas. Tiap tahun selalu dua semester. */
  const tahunUrut = useMemo(() => {
    const tahun = new Set([
      ...periodeTersedia.map((p) => p.tahun),
      ...tahunTambahan,
    ]);

    return [...tahun].sort((a, b) => b - a);
  }, [periodeTersedia, tahunTambahan]);

  const barisPeriode = useCallback(
    (tahun, semester) => periodeTersedia.find(
      (p) => p.tahun === tahun && p.semester === semester,
    )?.baris ?? 0,
    [periodeTersedia],
  );

  /* Periode terbaru yang BERISI dibuka sendiri sekali di awal — halaman yang
     seluruhnya terlipat tidak memberi tahu apa pun tentang isinya. */
  useEffect(() => {
    if (sudahBukaAwal.current || periodeTersedia.length === 0) return;
    sudahBukaAwal.current = true;

    const p = periodeTersedia[0];
    setTerbuka(new Set([kunciPeriode(p)]));
    muatHitungan(p);
  }, [periodeTersedia, muatHitungan]);

  // Tutup panel "Tambah Tahun" saat klik di luar / Esc.
  useEffect(() => {
    if (!tambahBuka) return undefined;

    const klikLuar = (e) => {
      if (kotakTambah.current && !kotakTambah.current.contains(e.target)) setTambahBuka(false);
    };
    const tekan = (e) => { if (e.key === 'Escape') setTambahBuka(false); };

    document.addEventListener('mousedown', klikLuar);
    document.addEventListener('keydown', tekan);

    return () => {
      document.removeEventListener('mousedown', klikLuar);
      document.removeEventListener('keydown', tekan);
    };
  }, [tambahBuka]);

  const bukaTutup = (p) => {
    const k = kunciPeriode(p);
    const akanBuka = !terbuka.has(k);

    setTerbuka((prev) => {
      const n = new Set(prev);
      if (akanBuka) {
        /*
         * Membuka satu semester MENUTUP pasangannya.
         *
         * 🔴 Isi wadah terbentang penuh di bawah pasangan kepalanya. Kalau
         * keduanya boleh terbuka bersamaan, dua panel setinggi delapan baris
         * bertumpuk di bawah satu baris kepala — dan tidak ada lagi petunjuk
         * visual panel mana milik semester mana. Satu terbuka pada satu waktu
         * menjaga jawaban "isi ini milik siapa" tetap satu.
         */
        n.delete(`${p.tahun}-${p.semester === 1 ? 2 : 1}`);
        n.add(k);
      } else {
        n.delete(k);
      }
      return n;
    });

    if (akanBuka && !hitungan[k]) muatHitungan(p);
  };

  const tahunBisaDitambah = useMemo(
    () => tahunPilihan(periodeTersedia).filter((t) => !tahunUrut.includes(t)),
    [periodeTersedia, tahunUrut],
  );

  /*
   * ⚠️ Isian tahun harus selalu menunjuk tahun yang MEMANG bisa ditambah.
   *
   * Nilai awalnya tahun berjalan — dan justru tahun itulah yang paling mungkin
   * sudah punya wadah, sehingga tersaring keluar dari daftar. Akibatnya kotak
   * pilihan memperlihatkan pilihan pertamanya (mis. 2027) sementara state masih
   * 2026: tombolnya bertuliskan "Buat wadah 2026" dan menekannya tidak
   * mengubah apa pun, karena 2026 sudah ada.
   */
  useEffect(() => {
    if (tahunBisaDitambah.length > 0 && !tahunBisaDitambah.includes(tahunBaru)) {
      setTahunBaru(tahunBisaDitambah[0]);
    }
  }, [tahunBisaDitambah, tahunBaru]);

  const tambahTahun = () => {
    setTahunTambahan((d) => (d.includes(tahunBaru) ? d : [...d, tahunBaru]));
    setTambahBuka(false);
    /* Wadah barunya langsung dibuka: yang dicari petugas sesudah membuatnya
       adalah tombol impor di dalamnya, bukan barisnya yang masih terlipat. */
    setTerbuka((prev) => new Set([...prev, `${tahunBaru}-1`]));

    /*
     * Wadah baru disemai NOL, bukan objek kosong.
     *
     * ⚠️ Objek kosong membuat tiap kategori terbaca `null` — dan `null` di sini
     * berarti "sedang diperiksa", sehingga barisnya tertahan di "memeriksa…"
     * selamanya karena tidak ada permintaan yang akan menjawabnya. Tahun ini
     * baru saja disaring keluar dari daftar periode yang punya baris, jadi
     * kosongnya bukan dugaan: sudah pasti.
     */
    const kosong = Object.fromEntries(kategori.map((kat) => [kat.slug, 0]));
    setHitungan((h) => ({ ...h, [`${tahunBaru}-1`]: kosong, [`${tahunBaru}-2`]: kosong }));

    setPesan({
      tipe: 'sukses',
      teks: `Wadah tahun ${tahunBaru} dibuat — Semester I & II siap diisi`,
    });
  };

  /**
   * Impor satu berkas ke satu kategori. Mengembalikan pesan galat, atau null
   * bila berhasil — pemanggilnya mengimpor berkas berurutan dan perlu tahu.
   */
  const imporSatu = async (slug, file, p) => {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('kategori', slug);
    // Tanpa ini peladen memakai periode terbaru — dan berkas Semester I bisa
    // mendarat menimpa Semester II hanya karena periodenya tidak disebut.
    fd.append('tahun', p.tahun);
    fd.append('semester', p.semester);

    const j = await kirimBerkas('/api/admin/demografi/import', fd);

    return j.error?.length ? String(j.error[0]) : null;
  };

  /*
   * 🔴 BERKAS TIDAK LANGSUNG DITULIS — ia masuk daftar konfirmasi dulu.
   *
   * Dinas menerima paket DKB sebagai sekumpulan berkas sekaligus dan tidak
   * menghafal berkas mana milik kategori mana. Deteksi nama berkas menolong,
   * tapi ia menebak — dan tebakan yang langsung dijalankan adalah tebakan yang
   * tidak bisa dibatalkan: impor MENGGANTI seluruh isi satu kategori pada satu
   * periode, jadi satu nama yang keliru menghapus data kategori lain tanpa
   * satu pun tanda di layar.
   *
   * Karena itu deteksi turun pangkat dari keputusan jadi USULAN. Petugas
   * melihat "berkas ini → kategori itu" untuk setiap berkas, bisa
   * mengoreksinya, dan tidak ada apa pun yang ditulis sampai ia menekan Impor.
   */
  const siapkanImpor = (daftar, p) => {
    if (!daftar || daftar.length === 0) return;

    setAntre({
      kunci: kunciPeriode(p),
      periode: p,
      item: [...daftar].map((file) => {
        const tebakan = deteksiKategori(file.name, kategori);

        return {
          file,
          tujuan: tebakan?.slug ?? '',
          judulBaru: usulJudul(file.name),
          terdeteksi: !!tebakan,
        };
      }),
    });
  };

  /** Jalankan antrean yang sudah dikonfirmasi petugas. */
  const jalankanImpor = async () => {
    if (!antre) return;

    const { kunci: k, periode: p, item } = antre;
    const gagal = [];
    let berhasil = 0;

    for (let i = 0; i < item.length; i += 1) {
      const it = item[i];
      let slug = it.tujuan;
      let label = kategori.find((x) => x.slug === slug)?.label ?? slug;

      // Tujuan "kategori baru": dibuat lebih dulu, lalu berkasnya masuk ke situ.
      if (slug === TUJUAN_BARU) {
        setImpor({ kunci: k, ke: i + 1, dari: item.length, nama: it.judulBaru });
        // eslint-disable-next-line no-await-in-loop
        const dibuat = await tambahKategori(it.judulBaru.trim());
        if (!dibuat) {
          gagal.push(`${it.file.name}: kategori gagal dibuat`);
          continue;
        }
        slug = dibuat.slug;
        label = dibuat.label;
      }

      setImpor({ kunci: k, ke: i + 1, dari: item.length, nama: label });
      // eslint-disable-next-line no-await-in-loop
      const galat = await imporSatu(slug, it.file, p);
      if (galat) gagal.push(`${label}: ${galat}`);
      else berhasil += 1;
    }

    setImpor(null);
    setAntre(null);

    if (gagal.length > 0) {
      setPesan({ tipe: 'galat', teks: gagal.join(' · ') });
    } else if (berhasil > 0) {
      setPesan({
        tipe: 'sukses',
        teks: `${berhasil} kategori terimpor ke ${labelPeriode(p.tahun, p.semester)}`,
      });
    }

    muatHitungan(p);
    // Impor ke periode yang belum pernah ada menambah satu entri di daftar.
    segarkanPeriode();
  };

  /*
   * Menghapus HANYA periode wadah ini.
   *
   * 🔴 Dulu tombol ini menyapu seluruh tabel. Sejak beberapa periode bisa
   * berdampingan, menyapu semuanya berarti satu klik menghapus data
   * bertahun-tahun — termasuk semester yang tidak sedang dilihat petugas.
   */
  const hapusPeriode = async () => {
    const p = konfirmHapus;
    if (!p) return;

    setMenghapus(true);
    const j = await kirimJson(`/api/admin/demografi?${kueriPeriode(p)}`, {}, 'DELETE');
    setMenghapus(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Data periode ini dihapus' });
    setKonfirmHapus(null);
    setPeriodeTersedia((d) => d.filter(
      (x) => !(x.tahun === p.tahun && x.semester === p.semester),
    ));
    /* Wadahnya TETAP ada, hanya isinya yang hilang — tahunnya dipertahankan
       supaya petugas bisa langsung mengimpor ulang di tempat yang sama. */
    setTahunTambahan((d) => (d.includes(p.tahun) ? d : [...d, p.tahun]));
    muatHitungan(p);
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

  /** Kepala wadah satu semester — separuh baris, bisa diklik untuk membuka. */
  const kepalaPeriode = (p) => {
    const k = kunciPeriode(p);
    const buka = terbuka.has(k);
    const isi = hitungan[k];
    const terisi = isi ? kategori.filter((kat) => (isi[kat.slug] ?? 0) > 0).length : 0;
    const baris = barisPeriode(p.tahun, p.semester);
    const adaData = isi ? terisi > 0 : baris > 0;

    let ringkas = 'Belum ada data';
    if (isi && terisi > 0) ringkas = `${terisi} dari ${kategori.length} kategori terisi`;
    else if (!isi && baris > 0) ringkas = `${baris.toLocaleString('id-ID')} baris tersimpan`;

    return (
      <button key={k} type="button" onClick={() => bukaTutup(p)} aria-expanded={buka}
              title={`Buka isi ${labelPeriode(p.tahun, p.semester)}`}
              className={`flex min-w-0 items-center gap-3 rounded-2xl border bg-white px-4 py-3 text-left transition-colors ${
                buka ? 'border-brand/40 ring-1 ring-brand/20'
                     : adaData ? 'border-slate-200 hover:border-brand/40'
                               : 'border-dashed border-slate-300 hover:border-brand/40'
              }`}>
        <ChevronRight className={`h-4 w-4 flex-shrink-0 text-slate-400 transition-transform ${
          buka ? 'rotate-90' : ''
        }`} />
        <span className={`flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl ${
          adaData ? 'bg-brand/10 text-brand' : 'bg-slate-100 text-slate-400'
        }`}>
          <CalendarDays className="h-4 w-4" />
        </span>
        <span className="min-w-0">
          <span className="block text-sm font-semibold text-slate-900">
            {labelPeriode(p.tahun, p.semester)}
          </span>
          <span className="block truncate text-xs text-slate-400">{ringkas}</span>
        </span>
      </button>
    );
  };

  /** Isi wadah yang terbuka — membentang penuh di bawah pasangan semesternya. */
  const isiPeriode = (p) => {
    const k = kunciPeriode(p);
    const isi = hitungan[k];
    const sedangImpor = impor?.kunci === k;
    const adaData = isi
      ? kategori.some((kat) => (isi[kat.slug] ?? 0) > 0)
      : barisPeriode(p.tahun, p.semester) > 0;

    return (
      <div key={`isi-${k}`} className="rounded-2xl border border-slate-200 bg-white">
        {/*
          Impor, Export, dan Hapus DUDUK DI ATAS isi wadahnya.

          🔴 Ketiganya bekerja pada SELURUH periode ini. Ketika tombol unggah
          masih menempel pada tiap kategori, tidak ada satu pun tempat di layar
          yang berkata "ini yang berlaku untuk seluruh Semester I 2027" — dan
          tombol Hapus melayang di kepala halaman, jauh dari data yang
          dihapusnya.
        */}
        <div className="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-3">
          <p className="mr-auto text-xs font-semibold uppercase tracking-wide text-slate-400">
            Isi {labelPeriode(p.tahun, p.semester)}
          </p>

          <Tombol disabled={!!impor} onClick={() => berkas.current[k]?.click()}>
            {sedangImpor ? <Loader2 className="h-4 w-4 animate-spin" /> : <Upload className="h-4 w-4" />}
            {sedangImpor ? `${impor.nama} (${impor.ke}/${impor.dari})` : 'Import Excel'}
          </Tombol>
          <Tombol varian="garis" disabled={!adaData}
                  title={`Unduh semua kategori ${labelPeriode(p.tahun, p.semester)} dalam satu file Excel`}
                  onClick={() => unduh(`/api/admin/demografi/export?${kueriPeriode(p)}`)}>
            <Download className="h-4 w-4" />Export
          </Tombol>
          <Tombol varian="garis" kelas="border-rose-300 text-rose-600 hover:bg-rose-50"
                  disabled={!adaData} onClick={() => setKonfirmHapus(p)}
                  title={`Hapus seluruh data ${labelPeriode(p.tahun, p.semester)}`}>
            <Trash2 className="h-4 w-4" />Hapus
          </Tombol>

          <input ref={(el) => { berkas.current[k] = el; }} type="file" accept=".xlsx" multiple
                 className="hidden" disabled={!!impor}
                 onChange={(e) => { siapkanImpor(e.target.files, p); e.target.value = ''; }} />
        </div>

        <p className="border-b border-slate-100 bg-slate-50/60 px-4 py-2 text-xs text-slate-500">
          Beberapa berkas sekaligus boleh dipilih — kategorinya dikenali dari nama
          berkas (mis. <b>AGR_JK_DUSUN</b> → Jenis Kelamin).
        </p>

        {/*
          DAFTAR KONFIRMASI TUJUAN — tampil sebelum apa pun ditulis.

          🔴 Impor MENGGANTI seluruh isi satu kategori pada satu periode.
          Menjalankan tebakan nama berkas secara langsung berarti satu nama
          yang keliru menghapus data kategori lain, diam-diam, tanpa jalan
          kembali. Di sini tebakannya ditampilkan sebagai usulan yang bisa
          dikoreksi, dan Impor baru bisa ditekan setelah setiap berkas punya
          tujuan yang jelas.
        */}
        {antre?.kunci === k && antre.item.length > 0 && (
          <div className="border-b border-brand/20 bg-brand/5 px-4 py-3">
            <p className="mb-2 text-xs font-semibold text-slate-700">
              {antre.item.length} berkas siap diimpor ke{' '}
              {labelPeriode(p.tahun, p.semester)} — periksa tujuannya dulu.
            </p>

            <div className="space-y-2">
              {antre.item.map((it, i) => {
                const ubah = (ganti) => setAntre((b) => (b
                  ? { ...b, item: b.item.map((x, j) => (j === i ? { ...x, ...ganti } : x)) }
                  : b));

                return (
                  <div key={it.file.name} className="flex flex-wrap items-center gap-2">
                    <span className="min-w-0 flex-1 truncate text-xs text-slate-600" title={it.file.name}>
                      {it.file.name}
                    </span>
                    <span className="text-xs text-slate-400">&rarr;</span>

                    <select
                      value={it.tujuan}
                      onChange={(e) => ubah({ tujuan: e.target.value, terdeteksi: false })}
                      aria-label={`Kategori tujuan untuk ${it.file.name}`}
                      className={`h-8 w-56 rounded-lg border bg-white px-2 text-sm ${
                        it.tujuan ? 'border-slate-300' : 'border-amber-400 bg-amber-50'
                      }`}
                    >
                      <option value="">&mdash; pilih kategori &mdash;</option>
                      {kategori.map((kat) => (
                        <option key={kat.slug} value={kat.slug}>{kat.label}</option>
                      ))}
                      <option value={TUJUAN_BARU}>+ Kategori baru&hellip;</option>
                    </select>

                    {it.tujuan === TUJUAN_BARU && (
                      <input
                        value={it.judulBaru}
                        onChange={(e) => ubah({ judulBaru: e.target.value })}
                        maxLength={60}
                        placeholder="Nama kategori baru"
                        aria-label={`Nama kategori baru untuk ${it.file.name}`}
                        className="h-8 w-52 rounded-lg border border-slate-300 bg-white px-2 text-sm"
                      />
                    )}

                    {/* Menyebut asal usulan: petugas berhak tahu mana yang
                        ditebak mesin dan mana yang ia pilih sendiri. */}
                    {it.terdeteksi && (
                      <span className="text-[0.68rem] text-slate-400">dari nama berkas</span>
                    )}
                  </div>
                );
              })}
            </div>

            <div className="mt-3 flex flex-wrap items-center gap-2">
              <Tombol
                disabled={!!impor || antre.item.some(
                  (it) => !it.tujuan
                    || (it.tujuan === TUJUAN_BARU && it.judulBaru.trim().length < 3),
                )}
                onClick={jalankanImpor}
              >
                {impor ? <Loader2 className="h-4 w-4 animate-spin" /> : <Upload className="h-4 w-4" />}
                Impor {antre.item.length} berkas
              </Tombol>
              <Tombol varian="garis" disabled={!!impor} onClick={() => setAntre(null)}>
                Batal
              </Tombol>
              {antre.item.some((it) => !it.tujuan) && (
                <span className="inline-flex items-center gap-1 text-[0.7rem] font-medium text-amber-700">
                  <AlertTriangle className="h-3.5 w-3.5 shrink-0" />
                  Masih ada berkas tanpa tujuan
                </span>
              )}
            </div>
          </div>
        )}

        {!isi ? (
          <div className="flex justify-center py-10">
            <Loader2 className="h-5 w-5 animate-spin text-brand" />
          </div>
        ) : (
          <div className="divide-y divide-slate-100">
            {kategori.map((kat) => {
              const n = isi[kat.slug];

              return (
                <div key={kat.slug} className="flex items-center gap-3 px-4 py-3">
                  <div className={`flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl ${
                    n ? 'bg-brand/10 text-brand' : 'bg-slate-100 text-slate-400'
                  }`}>
                    <FileSpreadsheet className="h-4 w-4" />
                  </div>

                  <div className="min-w-0 flex-1">
                    <p className="text-sm font-semibold text-slate-900">{kat.label}</p>
                    <p className="truncate text-xs text-slate-400">File: {kat.fileHint}</p>
                  </div>

                  <p className="hidden flex-shrink-0 text-xs sm:block">
                    {n == null ? <span className="text-slate-400">memeriksa…</span>
                      : n > 0 ? (
                        <span className="inline-flex items-center gap-1 text-emerald-600">
                          <CheckCircle2 className="h-3.5 w-3.5" /> {n} kecamatan
                        </span>
                      ) : <span className="text-slate-400">Belum ada data</span>}
                  </p>

                  {/*
                    Dua tindakan per baris: unduh kategori ini saja, dan Edit.

                    🔴 Tombol Export di kepala wadah mengunduh SELURUH kategori
                    sekaligus. Yang dibawa ke rapat biasanya satu tabel saja —
                    tanpa tombol ini petugas harus mengunduh berkas berisi
                    belasan sheet lalu membuang sendiri yang tidak dipakai, dan
                    berkas sebesar itu tak jarang dikirim apa adanya. Keduanya
                    tetap ada: satu berkas untuk arsip, satu tabel untuk dipakai.

                    Nonaktif bila kategori ini kosong pada periode tersebut —
                    mengunduh berkas nol baris hanya membuat orang mengira
                    datanya hilang.
                  */}
                  <Tombol varian="polos" disabled={!n} kelas="flex-shrink-0 text-slate-500 hover:text-slate-900"
                          onClick={() => unduh(
                            `/api/admin/demografi/export?${kueriPeriode(p)}&kategori=${encodeURIComponent(kat.slug)}`,
                          )}
                          title={n
                            ? `Unduh ${kat.label} ${labelPeriode(p.tahun, p.semester)} saja`
                            : `${kat.label} belum berisi data pada ${labelPeriode(p.tahun, p.semester)}`}>
                    <Download className="h-4 w-4" />
                    <span className="sr-only">Unduh {kat.label}</span>
                  </Tombol>

                  <Tombol varian="garis" disabled={!!impor} kelas="flex-shrink-0"
                          onClick={() => setSunting({ ...kat, periode: p })}
                          title={`Edit / import ${kat.label} pada ${labelPeriode(p.tahun, p.semester)}`}>
                    <Pencil className="h-4 w-4" />Edit
                  </Tombol>
                </div>
              );
            })}
          </div>
        )}
      </div>
    );
  };

  return (
    <LayoutDashboard judul="Data Demografi">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <div className="space-y-4">
        {/*
          Petunjuk membentang SATU BARIS PENUH di paling atas.

          🔴 Sebelumnya ia berbagi baris dengan deretan tombol aksi. Di layar
          selebar apa pun tombol-tombol itu mengambil haknya lebih dulu, dan
          petunjuknya terjepit jadi kolom sempit setinggi dua belas baris —
          masih terbaca, tapi terlihat seperti kerusakan tata letak. Petunjuk
          dibaca sekali lalu diabaikan; ia tidak perlu bersaing dengan apa pun.
        */}
        <p className="rounded-xl border border-brand/20 bg-brand/5 px-4 py-2.5 text-sm text-slate-700">
          Unggah Excel agregat Dukcapil (format SIAK: kolom <b>IDEM, KODE, WILAYAH, …</b>)
          pada wadah periodenya. Setiap unggahan <b>mengganti</b> kategori itu{' '}
          <b>di periode tersebut saja</b> — periode lain tidak tersentuh.
        </p>

        <div className="flex flex-wrap items-center justify-between gap-2">
          <div ref={kotakTambah} className="relative">
            <Tombol varian="garis" onClick={() => setTambahBuka((b) => !b)}>
              <Plus className="h-4 w-4" />Tambah Tahun
            </Tombol>

            {tambahBuka && (
              <div className="absolute left-0 z-40 mt-2 w-60 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                {tahunBisaDitambah.length === 0 ? (
                  <p className="text-xs text-slate-500">
                    Semua tahun yang mungkin sudah punya wadah.
                  </p>
                ) : (
                  <>
                    <label className="block">
                      <span className="mb-1 block text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">
                        Tahun
                      </span>
                      <select
                        value={tahunBaru}
                        onChange={(e) => setTahunBaru(Number(e.target.value))}
                        className="h-9 w-full rounded-lg border border-slate-300 bg-white px-2 text-sm"
                      >
                        {tahunBisaDitambah.map((t) => <option key={t} value={t}>{t}</option>)}
                      </select>
                    </label>
                    <p className="mt-2 text-[0.7rem] leading-relaxed text-slate-500">
                      Membuat dua wadah kosong — <b>Semester I</b> dan <b>Semester II</b>.
                      Berkasnya diimpor ke dalamnya setelah itu.
                    </p>
                    <Tombol kelas="mt-2 w-full" onClick={tambahTahun}>
                      Buat wadah {tahunBaru}
                    </Tombol>
                  </>
                )}
              </div>
            )}
          </div>

          <Tombol varian="garis" onClick={() => setKonfirmReset(true)}
                  title="Kembalikan kartu statistik beranda ke 6 kartu bawaan">
            <RotateCcw className="h-4 w-4" />Reset Kartu Beranda
          </Tombol>
        </div>

        {/*
          KATEGORI berdiri SENDIRI, di luar wadah periode.

          🔴 Menambah kategori dan menampilkannya di halaman utama berlaku
          untuk SELURUH portal, bukan untuk satu semester. Menaruh sakelarnya
          di dalam wadah Semester I 2027 akan mengajarkan hal yang salah —
          seolah sebuah kategori bisa tampil di beranda untuk satu semester dan
          tidak untuk semester lain. Yang berbeda per periode hanyalah DATANYA.
        */}
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
          <button type="button" onClick={() => setPanelKategori((b) => !b)}
                  aria-expanded={panelKategori}
                  className="flex w-full items-center gap-3 px-4 py-3 text-left">
            <ChevronRight className={`h-4 w-4 flex-shrink-0 text-slate-400 transition-transform ${
              panelKategori ? 'rotate-90' : ''
            }`} />
            <span className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand">
              <Tag className="h-4 w-4" />
            </span>
            <span className="min-w-0 flex-1">
              <span className="block text-sm font-semibold text-slate-900">Kategori Data</span>
              <span className="block truncate text-xs text-slate-400">
                {kategori.length} kategori · {kategori.filter((k) => k.beranda).length} tampil
                di halaman utama
              </span>
            </span>
          </button>

          <div inert={!panelKategori}
               className={`${GERAK_WADAH} ${
                 panelKategori ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'
               }`}>
            <div className="overflow-hidden">
              <div className={`divide-y divide-slate-100 border-t border-slate-100 ${GERAK_ISI} ${
                panelKategori ? GERAK_ISI_BUKA : GERAK_ISI_TUTUP
              }`}>
                {kategori.map((kat) => (
                  <div key={kat.slug} className="flex flex-wrap items-center gap-2 px-4 py-2.5">
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-medium text-slate-800">
                        {kat.label}
                        {!kat.bawaan && (
                          <span className="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-[0.65rem] font-semibold text-slate-500">
                            dibuat dinas
                          </span>
                        )}
                      </p>
                      <p className="truncate text-xs text-slate-400">{kat.slug}</p>
                    </div>

                    {/*
                      Sakelar tampil-di-beranda ditulis sebagai KALIMAT KEADAAN,
                      bukan ikon mata sendirian. Ikon mata punya dua bacaan yang
                      berlawanan — "sedang terlihat" dan "klik untuk melihat" —
                      dan petugas tidak boleh menebak yang mana pada pengaturan
                      yang mengubah apa yang dilihat warga.
                    */}
                    <button type="button"
                            onClick={() => ubahBeranda(kat.slug, !kat.beranda)}
                            title={kat.beranda
                              ? 'Sedang tampil di halaman utama — klik untuk menyembunyikan'
                              : 'Tersembunyi dari halaman utama — klik untuk menampilkan'}
                            className={`inline-flex flex-shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 transition-colors ${
                              kat.beranda
                                ? 'bg-brand/10 text-brand ring-brand/20 hover:bg-brand/15'
                                : 'bg-slate-100 text-slate-500 ring-slate-200 hover:bg-slate-200'
                            }`}>
                      {kat.beranda ? <Eye className="h-3.5 w-3.5" /> : <EyeOff className="h-3.5 w-3.5" />}
                      {kat.beranda ? 'Tampil di Halaman utama' : 'Tidak tampil'}
                    </button>

                    {!kat.bawaan && (
                      <button type="button" disabled={sibukKategori}
                              onClick={() => setKonfirmHapusKategori(kat)}
                              title={`Hapus kategori ${kat.label}`}
                              className="flex-shrink-0 rounded-lg p-2 text-rose-600 hover:bg-rose-50 disabled:opacity-40">
                        <Trash2 className="h-4 w-4" />
                      </button>
                    )}
                  </div>
                ))}
              </div>

              <div className="flex flex-wrap items-center gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-3">
                <input
                  value={judulKategoriBaru}
                  onChange={(e) => setJudulKategoriBaru(e.target.value)}
                  placeholder="Nama kategori baru, mis. Penyandang Disabilitas"
                  maxLength={60}
                  className="h-9 min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 text-sm"
                />
                <Tombol
                  disabled={sibukKategori || judulKategoriBaru.trim().length < 3}
                  onClick={async () => {
                    const dibuat = await tambahKategori(judulKategoriBaru.trim());
                    if (dibuat) setJudulKategoriBaru('');
                  }}
                >
                  {sibukKategori ? <Loader2 className="h-4 w-4 animate-spin" /> : <Plus className="h-4 w-4" />}
                  Tambah Kategori
                </Tombol>
                <p className="w-full text-[0.7rem] leading-relaxed text-slate-500">
                  Kategori baru langsung bisa diisi berkas di tiap periode. Berkas
                  dikenali bila namanya memuat nama kategori ini.
                </p>
              </div>
            </div>
          </div>
        </div>

        {memuatDaftar ? (
          <div className="flex justify-center py-16">
            <Loader2 className="h-6 w-6 animate-spin text-brand" />
          </div>
        ) : (
          <div className="space-y-4">
            {tahunUrut.map((tahun) => (
                <div key={tahun}>
                  {/*
                    Satu tahun = SATU BARIS berisi dua wadah bersebelahan.

                    Semester I dan II adalah pasangan; menumpuknya sebagai dua
                    baris terpisah membuat tahun yang sama terbaca seperti dua
                    hal yang tak berhubungan, dan daftar empat tahun langsung
                    menjadi delapan baris yang harus digulir.
                  */}
                  <div className="grid gap-3 sm:grid-cols-2">
                    {kepalaPeriode({ tahun, semester: 1 })}
                    {kepalaPeriode({ tahun, semester: 2 })}
                  </div>

                  {/*
                    Isinya terbentang PENUH di bawah pasangannya, bukan
                    terjepit di kolom separuh layar tempat kepalanya berada.

                    ⚠️ Panelnya SELALU ada di DOM, tingginya yang dianimasikan
                    lewat `grid-rows-[0fr] → [1fr]`. Melepas dan memasang ulang
                    elemennya membuat pergantian semester berkedip: yang lama
                    lenyap seketika, yang baru muncul seketika, dan mata
                    kehilangan jejak apa yang barusan terjadi. Trik grid ini
                    menganimasikan tinggi tanpa perlu mengetahui tinggi isinya
                    lebih dulu.
                  */}
                  {[1, 2].map((s) => {
                    const buka = terbuka.has(`${tahun}-${s}`);

                    return (
                      <div key={`panel-${tahun}-${s}`}
                           /* `inert` mencabut panel tertutup dari urutan Tab dan
                              dari pohon aksesibilitas. Tanpa itu tombol Import /
                              Hapus milik semester yang TIDAK terlihat tetap bisa
                              dijangkau keyboard — dan ditekan tanpa pernah
                              tampak di layar. */
                           inert={!buka}
                           className={`${GERAK_WADAH} ${
                             buka ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'
                           }`}>
                        {/* Padding atasnya ikut di DALAM area yang menciut,
                            supaya panel tertutup benar-benar setinggi nol —
                            bukan menyisakan celah kosong di bawah tiap tahun. */}
                        <div className="overflow-hidden">
                          <div className={`pt-3 ${GERAK_ISI} ${
                            buka ? GERAK_ISI_BUKA : GERAK_ISI_TUTUP
                          }`}>
                            {isiPeriode({ tahun, semester: s })}
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>
            ))}
          </div>
        )}
      </div>

      {sunting && (
        <EditorDemografi
          kategori={sunting.slug}
          label={sunting.label}
          /* Editor mewarisi periode WADAHNYA dan tidak boleh berpindah sendiri:
             tanpa `onPeriode` pemilih periode di dalamnya tidak dirender, jadi
             tidak ada jalan bagi berkas untuk mendarat di semester yang salah. */
          periode={sunting.periode}
          onTutup={() => setSunting(null)}
          onTersimpan={() => { muatHitungan(sunting.periode); segarkanPeriode(); }}
        />
      )}

      {konfirmHapus && (
        <Modal
          judul={`Hapus data ${labelPeriode(konfirmHapus.tahun, konfirmHapus.semester)}?`}
          onTutup={() => !menghapus && setKonfirmHapus(null)}
        >
          <p className="flex items-start gap-2 text-sm text-slate-600">
            <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
            <span>
              Data <b>semua kategori</b> (kecamatan &amp; desa) pada{' '}
              <b>{labelPeriode(konfirmHapus.tahun, konfirmHapus.semester)}</b>{' '}
              akan dihapus permanen. <b>Periode lain tidak tersentuh.</b> Sebaiknya{' '}
              <b>Export</b> dulu sebagai cadangan. Tindakan ini tidak dapat dibatalkan.
            </span>
          </p>
          <div className="mt-5 flex justify-end gap-2">
            <Tombol varian="garis" onClick={() => setKonfirmHapus(null)} disabled={menghapus}>Batal</Tombol>
            <Tombol varian="bahaya" onClick={hapusPeriode} disabled={menghapus}>
              {menghapus ? <Loader2 className="h-4 w-4 animate-spin" /> : <Trash2 className="h-4 w-4" />}
              Ya, hapus periode ini
            </Tombol>
          </div>
        </Modal>
      )}

      {konfirmHapusKategori && (
        <Modal
          judul={`Hapus kategori ${konfirmHapusKategori.label}?`}
          onTutup={() => !sibukKategori && setKonfirmHapusKategori(null)}
        >
          <p className="flex items-start gap-2 text-sm text-slate-600">
            <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
            <span>
              Kategori ini hilang dari dasbor dan dari halaman utama, di{' '}
              <b>semua periode</b>. Penghapusan <b>ditolak</b> selama masih ada
              datanya — hapus dulu isinya lewat tombol <b>Hapus</b> pada periode
              yang bersangkutan, supaya tidak ada baris yang tertinggal tanpa
              pemilik.
            </span>
          </p>
          <div className="mt-5 flex justify-end gap-2">
            <Tombol varian="garis" onClick={() => setKonfirmHapusKategori(null)}
                    disabled={sibukKategori}>
              Batal
            </Tombol>
            <Tombol varian="bahaya" disabled={sibukKategori}
                    onClick={async () => {
                      await hapusKategori(konfirmHapusKategori.slug, konfirmHapusKategori.label);
                      setKonfirmHapusKategori(null);
                    }}>
              {sibukKategori ? <Loader2 className="h-4 w-4 animate-spin" /> : <Trash2 className="h-4 w-4" />}
              Ya, hapus kategori
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
