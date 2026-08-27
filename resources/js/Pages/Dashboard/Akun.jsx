import { useCallback, useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import {
  AlertTriangle, Camera, CheckCircle2, ChevronRight, IdCard, KeyRound, Search,
  Trash2, UserPlus, UserRound, Users, X, XCircle, ZoomIn,
} from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import AmbilSelfie from '@/Components/AmbilSelfie';
import UnggahGambar from '@/Components/UnggahGambar';
import PenampilGambar from '@/Components/PenampilGambar';
import { SearchSelect } from '@/Components/SearchSelect';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import {
  Kartu, Kosong, Memuat, Modal, Paginasi, Pesan, Tombol, tglJam, useTunda,
} from '@/Components/Dasbor';
import { ambilJson, kirimJson } from '@/lib/api';
import { kelasSorot, useSorot } from '@/lib/sorot';
import { tanpaAwalanDataUrl } from '@/lib/gambar';

/**
 * Manajemen Akun — port `app/dashboard/AdminUsers.tsx`.
 *
 * Inilah pintu masuk warga ke portal: sebuah pendaftaran baru tidak berarti apa
 * pun sampai petugas di halaman ini mengaktifkannya. Karena itu SELURUH tindakan
 * status hanya ada di dalam panel detail — setelah selfie, KTP, dan data dirinya
 * terbaca. Tombol "Aktifkan" di baris tabel akan membuat verifikasi bisa
 * dilakukan tanpa seorang pun melihat berkasnya.
 */

const STATUS_AKUN = { MENUNGGU: 0, AKTIF: 1, DITOLAK: 2, NONAKTIF: 3 };

const INFO_STATUS = {
  0: { label: 'Menunggu', warna: 'bg-amber-50 text-amber-700 ring-amber-200' },
  1: { label: 'Aktif', warna: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
  2: { label: 'Ditolak', warna: 'bg-rose-50 text-rose-700 ring-rose-200' },
  3: { label: 'Nonaktif', warna: 'bg-slate-100 text-slate-600 ring-slate-200' },
};

const WARNA_PERMOHONAN = {
  MENUNGGU: 'bg-amber-50 text-amber-700 ring-amber-100',
  DIPROSES: 'bg-sky-50 text-sky-700 ring-sky-100',
  SELESAI: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
  DITOLAK: 'bg-rose-50 text-rose-700 ring-rose-100',
};

const GRUP = [
  { key: '3', label: 'Warga', ket: 'Masyarakat umum (NIK)' },
  { key: '4', label: 'OPD', ket: 'Operator instansi pemerintah daerah' },
  { key: 'staff', label: 'Staff', ket: 'Petugas dinas (admin & operator)' },
];

/** Bagian data yang bisa ditandai "tidak sesuai" — cermin `App\Support\AlasanTolak`. */
const KOLOM_TOLAK = [
  ['nama', 'Nama'], ['nik', 'NIK'], ['kk', 'Nomor KK'], ['hp', 'WhatsApp'],
  ['email', 'Email'], ['kecamatan', 'Kecamatan'], ['foto', 'Foto selfie'],
  ['ktp', 'Foto KTP'],
];

const FORM_KOSONG = {
  nama: '', userId: '', nik: '', kk: '', hp: '', email: '', level: 3, password: '', kecamatan: '',
};

function Baris({ label, children }) {
  return (
    <div className="grid grid-cols-[7.5rem_1fr] gap-2 py-1.5">
      <dt className="text-xs text-slate-500">{label}</dt>
      <dd className="min-w-0 break-words text-sm text-slate-800">{children}</dd>
    </div>
  );
}

function Isian({ label, ket, wajib, children }) {
  return (
    <div className="space-y-1.5">
      <label className="text-sm font-medium text-slate-700">
        {label}{wajib && <span className="text-rose-600"> *</span>}
      </label>
      {children}
      {ket && <p className="text-[0.7rem] text-slate-400">{ket}</p>}
    </div>
  );
}


// ── Panel detail ───────────────────────────────────────────────────────────

function IsiDetail({ detail, memuat, sibuk, onAktifkan, onTolak, onNonaktif }) {
  // Indeks foto identitas yang sedang dibuka di penampil layar penuh.
  const [lihatFoto, setLihatFoto] = useState(null);

  if (memuat || !detail) return <Memuat kelas="py-16" />;

  const info = INFO_STATUS[detail.status] ?? INFO_STATUS[0];
  const nama = detail.userFullname ?? detail.userId;

  // Foto identitas akun, urutan tetap: KTP dulu (yang diperiksa petugas), lalu
  // foto profil. Dipakai bersama oleh thumbnail KTP dan avatar di atas supaya
  // keduanya membuka penampil yang sama dan bisa dibolak-balik dengan ←/→.
  const fotoIdentitas = [
    ...(detail.userKtp ? [{ src: detail.userKtp, judul: `Foto KTP — ${nama}` }] : []),
    ...(detail.userFoto ? [{ src: detail.userFoto, judul: `Foto Profil — ${nama}` }] : []),
  ];
  const idxFotoProfil = detail.userKtp ? 1 : 0;

  return (
    <div className="space-y-5">
      <div className="flex items-start gap-4">
        {detail.userFoto ? (
          <button type="button" onClick={() => setLihatFoto(idxFotoProfil)} title="Klik untuk perbesar"
                  className="group relative h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-slate-200 transition-colors hover:border-brand">
            <img src={detail.userFoto} alt={`Foto ${nama}`} className="h-full w-full object-cover" />
            <span className="absolute inset-0 flex items-center justify-center bg-black/0 transition-colors group-hover:bg-black/30">
              <ZoomIn className="h-5 w-5 text-white opacity-0 transition-opacity group-hover:opacity-100" />
            </span>
          </button>
        ) : (
          <div className="flex h-20 w-20 shrink-0 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-slate-300">
            <UserRound className="h-8 w-8" />
          </div>
        )}
        <div className="min-w-0 flex-1">
          <p className="truncate font-semibold text-slate-900">{nama}</p>
          <p className="font-mono text-xs text-slate-500">{detail.userId}</p>
          <div className="mt-2 flex flex-wrap items-center gap-1.5">
            <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[0.68rem] font-medium text-slate-600">
              {detail.level?.nama ?? `Level ${detail.userlevelId}`}
            </span>
            <span className={`rounded-full px-2 py-0.5 text-[0.68rem] font-semibold ring-1 ${info.warna}`}>{info.label}</span>
          </div>
        </div>
      </div>

      {detail.status === STATUS_AKUN.DITOLAK && detail.ket && (
        <div className="rounded-xl border border-rose-200 bg-rose-50 p-3">
          <p className="text-xs font-semibold uppercase tracking-wide text-rose-500">Alasan penolakan</p>
          {detail.ketAlasan && <p className="mt-1 text-sm text-rose-800">{detail.ketAlasan}</p>}
          {detail.ketKolom?.length > 0 && (
            <div className="mt-2 flex flex-wrap gap-1">
              {detail.ketKolom.map((k) => (
                <span key={k} className="rounded-full bg-rose-100 px-2 py-0.5 text-[0.68rem] font-medium text-rose-700">
                  {KOLOM_TOLAK.find(([key]) => key === k)?.[1] ?? k}
                </span>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Foto KTP sengaja SEBELUM Data Diri: inilah yang disandingkan petugas
          dengan NIK & nama di bawahnya saat memverifikasi pendaftaran.
          Thumbnail sengaja KECIL: gambar setinggi panel mendorong Data Diri —
          yang justru harus dibandingkan dengannya — turun ke luar layar.
          Klik membuka penampil yang sama dengan berkas permohonan (zoom, putar,
          geser, unduh), bukan tab baru yang memutus alur verifikasi. */}
      {detail.userKtp && (
        <div>
          <h4 className="mb-1.5 text-xs font-semibold uppercase tracking-wide text-slate-400">Foto KTP</h4>
          <button type="button" onClick={() => setLihatFoto(0)} title="Klik untuk perbesar, zoom & putar"
                  className="group relative w-40 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 text-left transition-colors hover:border-brand">
            <img src={detail.userKtp} alt={`Foto KTP ${nama}`} loading="lazy"
                 className="h-24 w-full object-cover transition-transform group-hover:scale-105" />
            <span className="absolute inset-0 flex items-center justify-center bg-black/0 transition-colors group-hover:bg-black/30">
              <ZoomIn className="h-5 w-5 text-white opacity-0 transition-opacity group-hover:opacity-100" />
            </span>
          </button>
          <p className="mt-1 text-[0.7rem] text-slate-400">Klik untuk memperbesar, memutar, dan menggeser.</p>
        </div>
      )}

      <div>
        <h4 className="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Data Diri</h4>
        <dl className="divide-y divide-slate-100">
          <Baris label="NIK">{detail.userNik || '-'}</Baris>
          <Baris label="No. KK">{detail.userNokk || '-'}</Baris>
          <Baris label="Kecamatan">{detail.userKecamatan || '-'}</Baris>
          <Baris label="WhatsApp">{detail.userHp || '-'}</Baris>
          <Baris label="Email">{detail.userEmail || '-'}</Baris>
        </dl>
      </div>

      <div>
        <h4 className="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Riwayat Akun</h4>
        <dl className="divide-y divide-slate-100">
          <Baris label="Terdaftar">{tglJam(detail.createdAt)}</Baris>
          <Baris label="Diaktifkan">{tglJam(detail.activationTime)}</Baris>
          <Baris label="Login terakhir">{tglJam(detail.loginLast)}</Baris>
          <Baris label="IP terakhir">{detail.ipAddress || '-'}</Baris>
          {detail.ketAlasan && <Baris label="Catatan">{detail.ketAlasan}</Baris>}
        </dl>
      </div>

      <div>
        <h4 className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
          Permohonan ({detail.jumlahPermohonan})
        </h4>
        {detail.permohonanTerakhir.length === 0 ? (
          <p className="text-xs text-slate-400">Belum ada permohonan.</p>
        ) : (
          <ul className="space-y-1.5">
            {detail.permohonanTerakhir.map((p) => (
              <li key={p.id} className="flex items-center gap-2 rounded-lg border border-slate-100 px-2.5 py-2">
                <div className="min-w-0 flex-1">
                  <p className="truncate text-xs font-medium text-slate-700">{p.jenisNama}</p>
                  <p className="truncate font-mono text-[0.65rem] text-slate-400">{p.noregister}</p>
                </div>
                <span className={`shrink-0 rounded-full px-2 py-0.5 text-[0.62rem] font-bold uppercase ring-1 ${
                  WARNA_PERMOHONAN[p.status] ?? 'bg-slate-100 text-slate-600 ring-slate-200'
                }`}>{p.status}</span>
              </li>
            ))}
          </ul>
        )}
        {detail.jumlahPermohonan > 0 && (
          <a href={`/dashboard/permohonan?q=${encodeURIComponent(detail.userId)}`}
             className="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand hover:underline">
            Lihat semua permohonan <ChevronRight className="h-3 w-3" />
          </a>
        )}
      </div>

      <div className="border-t border-slate-100 pt-4">
        {detail.status === STATUS_AKUN.AKTIF ? (
          <Tombol varian="garis" kelas="w-full border-amber-300 text-amber-700" disabled={sibuk} onClick={onNonaktif}>
            <XCircle className="h-4 w-4" />Nonaktifkan Akun
          </Tombol>
        ) : (
          <div className="grid grid-cols-2 gap-2">
            <Tombol varian="sukses" disabled={sibuk} onClick={onAktifkan}>
              <CheckCircle2 className="h-4 w-4" />Aktifkan
            </Tombol>
            <Tombol varian="garis" kelas="border-rose-300 text-rose-600" disabled={sibuk} onClick={onTolak}>
              <XCircle className="h-4 w-4" />Tolak
            </Tombol>
          </div>
        )}
      </div>

      {/* Penampil layar penuh — komponen yang sama dengan berkas permohonan. */}
      {lihatFoto !== null && fotoIdentitas.length > 0 && (
        <PenampilGambar
          daftar={fotoIdentitas}
          indeksAwal={Math.min(lihatFoto, fotoIdentitas.length - 1)}
          onTutup={() => setLihatFoto(null)}
        />
      )}
    </div>
  );
}

// ── Halaman ────────────────────────────────────────────────────────────────

/** Sama dengan Permohonan & Log — satu tinggi tabel untuk seluruh dashboard. */
const PER_HALAMAN = 20;

export default function Akun({ kecamatan, sorot }) {
  const level = usePage().props.auth.user?.level ?? 2;

  const [items, setItems] = useState([]);
  const [grup, setGrup] = useState('3');
  const [status, setStatus] = useState('');
  const [cari, setCari] = useState('');
  const cariTertunda = useTunda(cari);
  const [memuat, setMemuat] = useState(true);

  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [totalHalaman, setTotalHalaman] = useState(1);
  const [sibukId, setSibukId] = useState(null);
  const [pesan, setPesan] = useState(null);

  const [detailId, setDetailId] = useState(null);
  const [detail, setDetail] = useState(null);
  const [memuatDetail, setMemuatDetail] = useState(false);
  const panelDetail = useRef(null);

  const [konfirmasi, setKonfirmasi] = useState(null);   // {tipe, user}
  const [alasan, setAlasan] = useState('');
  const [kolomTolak, setKolomTolak] = useState([]);
  const [jeda, setJeda] = useState(0);

  const [formTerbuka, setFormTerbuka] = useState(false);
  const [form, setForm] = useState({ ...FORM_KOSONG });
  const [foto, setFoto] = useState('');
  const [ktp, setKtp] = useState('');
  const [membuat, setMembuat] = useState(false);

  // Datang dari notifikasi akun baru (`?sorot=<id>`). Dipakai SEKALI pada
  // pemuatan pertama: server yang menghitung akun itu ada di halaman berapa,
  // lalu `page` disetel dari jawabannya dan permintaan berikutnya kembali biasa.
  //
  // ⚠️ Tab grup tidak ikut disetel. Bawaannya "Warga" (level 3), yang benar
  // untuk hampir semua pendaftaran; pendaftar OPD (level 4) tetap perlu
  // dipindah tabnya sendiri.
  const sorotAwal = useRef(sorot ? Number(sorot) : null);
  const sorotId = useSorot(sorot, 'akun', !memuat, items);

  // Tombol hapus baru bisa ditekan setelah beberapa detik — jeda singkat ini
  // menahan penghapusan permanen karena klik refleks atau klik ganda.
  useEffect(() => {
    if (konfirmasi?.tipe !== 'hapus') return undefined;
    setJeda(3);
    const t = setInterval(() => setJeda((d) => (d <= 1 ? 0 : d - 1)), 1000);
    return () => clearInterval(t);
  }, [konfirmasi]);

  const muat = useCallback(async () => {
    setMemuat(true);
    const q = new URLSearchParams({
      level: grup, page: String(page), limit: String(PER_HALAMAN),
    });
    if (status) q.set('status', status);
    if (cariTertunda.trim()) q.set('q', cariTertunda.trim());
    if (sorotAwal.current) {
      q.set('sorot', String(sorotAwal.current));
      q.delete('page');
    }

    const j = await ambilJson(`/api/admin/users?${q}`);
    setMemuat(false);
    sorotAwal.current = null;

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setItems(j.data.items ?? []);
    setTotal(j.data.total ?? 0);
    setTotalHalaman(j.data.totalHalaman ?? 1);
    // Server menjepit halaman yang di luar jangkauan; ikuti angkanya supaya
    // tombol yang tersorot sama dengan isi tabel yang benar-benar tampil.
    if (j.data.page && j.data.page !== page) setPage(j.data.page);
  }, [grup, status, cariTertunda, page]);

  useEffect(() => { muat(); }, [muat]);

  // Ganti tab/filter/pencarian → kembali ke halaman 1. Tanpa ini petugas yang
  // sedang di halaman 12 lalu menyaring "Menunggu" mendarat di halaman kosong.
  useEffect(() => { setPage(1); }, [grup, status, cariTertunda]);

  // Pindah tab/filter → detail lama tidak lagi relevan.
  useEffect(() => { setDetailId(null); }, [grup, status]);

  // Di bawah `lg` panel detail turun ke BAWAH tabel yang panjangnya ratusan
  // baris, jadi klik "Detail" terlihat seperti tidak melakukan apa pun.
  // Di atas `lg` panelnya memang sudah terlihat di sisi kanan — jangan digeser.
  useEffect(() => {
    if (detailId == null || window.innerWidth >= 1024) return;
    panelDetail.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }, [detailId]);

  useEffect(() => {
    if (detailId == null) {
      setDetail(null);
      return undefined;
    }

    let batal = false;
    setMemuatDetail(true);

    ambilJson(`/api/admin/users/${detailId}`).then((j) => {
      if (batal) return;
      setMemuatDetail(false);
      if (j.error?.length) {
        setPesan({ tipe: 'galat', teks: j.error[0] });
        setDetailId(null);
        return;
      }
      setDetail(j.data ?? null);
    });

    return () => { batal = true; };
  }, [detailId]);

  const ubahStatus = async (id, statusBaru, alasanTeks, kolom) => {
    setSibukId(id);
    const j = await kirimJson('/api/admin/users', { id, status: statusBaru, alasan: alasanTeks, kolom }, 'PATCH');
    setSibukId(null);

    if (j.error?.length) {
      setPesan({ tipe: 'galat', teks: j.error[0] });
      return;
    }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Status akun diperbarui' });
    setItems((p) => p.map((u) => (u.id === id ? { ...u, status: statusBaru } : u)));
    // Panel detail ikut menyesuaikan agar tidak menampilkan status basi.
    setDetail((d) => (d && d.id === id ? { ...d, status: statusBaru } : d));
  };

  const hapusAkun = async (id) => {
    setSibukId(id);
    const j = await kirimJson(`/api/admin/users/${id}`, undefined, 'DELETE');
    setSibukId(null);

    if (j.error?.length) {
      setPesan({ tipe: 'galat', teks: j.error[0] });
      return;
    }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Akun dihapus' });
    setItems((p) => p.filter((u) => u.id !== id));
    if (detailId === id) setDetailId(null);
    setKonfirmasi(null);
  };

  const buatAkun = async () => {
    setMembuat(true);
    // 🔴 Awalan data URI dibuang: mod_security cPanel memblokir badan
    // permintaan yang memuatnya (lihat lib/gambar.js).
    const j = await kirimJson('/api/admin/users', {
      ...form,
      foto: foto ? tanpaAwalanDataUrl(foto) : undefined,
      ktp: ktp ? tanpaAwalanDataUrl(ktp) : undefined,
    });
    setMembuat(false);

    if (j.error?.length) {
      setPesan({ tipe: 'galat', teks: j.error[0] });
      return;
    }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Akun berhasil dibuat' });
    setFormTerbuka(false);
    muat();
  };

  const warga = form.level === 3;
  const grupAktif = GRUP.find((g) => g.key === grup);

  return (
    <LayoutDashboard judul="Manajemen Akun">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <div className="mb-4">
        <h1 className="text-2xl font-semibold text-slate-900">Manajemen Akun</h1>
        <p className="text-sm text-slate-500">
          Kelola akun <b>Warga</b>, <b>Operator OPD</b>, dan <b>Staff</b> dinas — aktivasi,
          penonaktifan, dan pembuatan akun baru.
        </p>
      </div>

      <div className="grid gap-4 lg:grid-cols-4">
        <div className={detailId !== null ? 'lg:col-span-2' : 'lg:col-span-4'}>
          <Kartu>
            <div className="mb-4 flex items-center justify-between gap-2">
              <div className="flex items-center gap-2">
                <Users className="h-5 w-5 text-slate-700" />
                <h2 className="font-semibold text-slate-900">Daftar Akun</h2>
              </div>
              <Tombol onClick={() => { setForm({ ...FORM_KOSONG }); setFoto(''); setKtp(''); setFormTerbuka(true); }}>
                <UserPlus className="h-4 w-4" />Tambah Akun
              </Tombol>
            </div>

            <div className="mb-4 flex flex-wrap items-center gap-2 border-b border-slate-100 pb-4">
              {GRUP.map((g) => (
                <button key={g.key} onClick={() => setGrup(g.key)} title={g.ket}
                        className={`rounded-full px-4 py-1.5 text-sm font-semibold transition-colors ${
                          grup === g.key ? 'bg-brand text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        }`}>
                  {g.label}
                </button>
              ))}
              {detailId === null && <span className="ml-1 text-xs text-slate-400">{grupAktif?.ket}</span>}
            </div>

            <div className="mb-4 flex flex-col gap-3 sm:flex-row">
              <div className="flex flex-wrap gap-1">
                {[['', 'Semua'], ['0', 'Menunggu'], ['1', 'Aktif'], ['2', 'Ditolak'], ['3', 'Nonaktif']].map(([nilai, label]) => (
                  <button key={nilai} onClick={() => setStatus(nilai)}
                          className={`rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors ${
                            status === nilai ? 'border-transparent bg-brand text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-brand'
                          }`}>
                    {label}
                  </button>
                ))}
              </div>
              <div className="relative flex-1">
                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <Input value={cari} onChange={(e) => setCari(e.target.value)}
                       placeholder="Cari NIK / nama / email…" className="pl-9" />
              </div>
            </div>

            {memuat ? <Memuat /> : items.length === 0 ? <Kosong>Tidak ada akun yang cocok.</Kosong> : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-slate-200 text-left text-slate-500">
                      <th className="py-2 pr-4 font-medium">User ID / NIK</th>
                      <th className="py-2 pr-4 font-medium">Nama</th>
                      {detailId === null && <th className="py-2 pr-4 font-medium">Kontak</th>}
                      {detailId === null && <th className="py-2 pr-4 font-medium">Level</th>}
                      <th className="py-2 pr-4 font-medium">Status</th>
                      <th className="py-2 pr-4 font-medium">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    {items.map((u) => (
                      <tr key={u.id} id={`akun-${u.id}`}
                          onClick={() => setDetailId(u.id)} title="Klik untuk melihat detail akun"
                          className={`cursor-pointer border-b border-slate-100 transition-colors ${
                            detailId === u.id ? 'bg-brand/5' : 'hover:bg-slate-50'
                          } ${kelasSorot(sorotId === u.id)}`}>
                        <td className="py-2.5 pr-4 font-mono text-xs">{u.userId}</td>
                        <td className="py-2.5 pr-4">
                          <span className="flex items-center gap-2">
                            {u.userFoto && <img src={u.userFoto} alt="" className="h-7 w-7 shrink-0 rounded-full object-cover" />}
                            <span className="min-w-0 truncate">{u.userFullname ?? '-'}</span>
                          </span>
                        </td>
                        {detailId === null && (
                          <td className="py-2.5 pr-4 text-xs text-slate-500">
                            {u.userEmail || '-'}{u.userHp && <><br />{u.userHp}</>}
                          </td>
                        )}
                        {detailId === null && <td className="py-2.5 pr-4">{u.level?.nama ?? u.userlevelId}</td>}
                        <td className="py-2.5 pr-4">
                          <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ring-1 ${(INFO_STATUS[u.status] ?? INFO_STATUS[0]).warna}`}>
                            {(INFO_STATUS[u.status] ?? INFO_STATUS[0]).label}
                          </span>
                        </td>
                        {/* Tombol aksi tidak boleh ikut membuka panel detail. */}
                        <td className="py-2.5 pr-4" onClick={(e) => e.stopPropagation()}>
                          <div className="flex items-center gap-1.5">
                            <Tombol varian="garis" onClick={() => setDetailId(u.id)}>
                              <ChevronRight className="h-3.5 w-3.5" />Detail
                            </Tombol>
                            {level === 1 && (
                              <button title="Hapus akun permanen" aria-label={`Hapus akun ${u.userFullname ?? u.userId}`}
                                      disabled={sibukId === u.id} onClick={() => setKonfirmasi({ tipe: 'hapus', user: u })}
                                      className="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600">
                                <Trash2 className="h-4 w-4" />
                              </button>
                            )}
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}

            {!memuat && (
              <Paginasi page={page} totalHalaman={totalHalaman} total={total} limit={PER_HALAMAN}
                        nonaktif={memuat} onGanti={setPage} />
            )}
          </Kartu>
        </div>

        {detailId !== null && (
          <aside ref={panelDetail} className="scroll-mt-4 lg:col-span-2">
            <div className="sticky top-4 max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div className="mb-4 flex items-center justify-between gap-2">
                <h2 className="font-semibold text-slate-900">Detail Akun</h2>
                <button onClick={() => setDetailId(null)} aria-label="Tutup detail" className="text-slate-400 hover:text-slate-600">
                  <X className="h-4 w-4" />
                </button>
              </div>
              <IsiDetail
                detail={detail}
                memuat={memuatDetail}
                sibuk={sibukId === detail?.id}
                onAktifkan={() => detail && ubahStatus(detail.id, STATUS_AKUN.AKTIF)}
                onTolak={() => { setAlasan(''); setKolomTolak([]); setKonfirmasi({ tipe: 'tolak', user: detail }); }}
                onNonaktif={() => { setAlasan(''); setKonfirmasi({ tipe: 'nonaktif', user: detail }); }}
              />
            </div>
          </aside>
        )}
      </div>

      {/* ── Konfirmasi tolak / nonaktifkan ───────────────────────────────── */}
      {(konfirmasi?.tipe === 'tolak' || konfirmasi?.tipe === 'nonaktif') && (() => {
        const tolak = konfirmasi.tipe === 'tolak';
        const nama = konfirmasi.user.userFullname ?? konfirmasi.user.userId;

        return (
          <Modal judul={tolak ? 'Tolak Pendaftaran' : 'Nonaktifkan Akun'} onTutup={() => setKonfirmasi(null)}>
            <div className="space-y-4">
              <p className="text-sm leading-relaxed text-slate-600">
                {tolak ? (
                  <>Pendaftaran <b>{nama}</b> akan ditolak. Warga melihat alasannya di <b>Cek Status
                    Pendaftaran</b> dan dapat mengajukan ulang.</>
                ) : (
                  <><b>{nama}</b> tidak akan bisa masuk lagi sampai diaktifkan kembali oleh staff.
                    Riwayat permohonannya tetap tersimpan.</>
                )}
              </p>

              {tolak && (
                <Isian label="Bagian data yang tidak sesuai"
                       ket="Opsional. Warga melihat daftar ini dan diminta memperbaikinya saat mendaftar ulang.">
                  <div className="flex flex-wrap gap-1.5">
                    {KOLOM_TOLAK.map(([key, label]) => {
                      const pilih = kolomTolak.includes(key);
                      return (
                        <button key={key} type="button"
                                onClick={() => setKolomTolak((p) => (pilih ? p.filter((k) => k !== key) : [...p, key]))}
                                className={`rounded-full border px-3 py-1 text-xs font-medium transition-colors ${
                                  pilih ? 'border-rose-300 bg-rose-50 text-rose-700' : 'border-slate-200 text-slate-600 hover:border-rose-300'
                                }`}>
                          {label}
                        </button>
                      );
                    })}
                  </div>
                </Isian>
              )}

              <Isian label={`Alasan ${tolak ? 'penolakan' : 'penonaktifan'}`} wajib={tolak}
                     ket={tolak
                       ? 'Wajib diisi. Ditampilkan ke pemohon & dikirim ke emailnya.'
                       : 'Opsional. Akun dinonaktifkan sementara karena alasan keamanan.'}>
                <Textarea rows={3} value={alasan} onChange={(e) => setAlasan(e.target.value)}
                          placeholder="mis. Foto selfie buram, nama tidak sesuai Kartu Keluarga" />
              </Isian>

              <div className="flex justify-end gap-2">
                <Tombol varian="garis" onClick={() => setKonfirmasi(null)}>Batal</Tombol>
                <Tombol varian={tolak ? 'bahaya' : 'utama'}
                        disabled={sibukId === konfirmasi.user.id || (tolak && !alasan.trim())}
                        onClick={() => {
                          const u = konfirmasi.user;
                          setKonfirmasi(null);
                          ubahStatus(u.id, tolak ? STATUS_AKUN.DITOLAK : STATUS_AKUN.NONAKTIF,
                            alasan.trim() || undefined, tolak ? kolomTolak : undefined);
                        }}>
                  {tolak ? 'Tolak Pendaftaran' : 'Nonaktifkan'}
                </Tombol>
              </div>
            </div>
          </Modal>
        );
      })()}

      {/* ── Konfirmasi hapus permanen ────────────────────────────────────── */}
      {konfirmasi?.tipe === 'hapus' && (
        <Modal judul="Hapus Akun Permanen" onTutup={() => setKonfirmasi(null)}>
          <div className="space-y-4">
            <p className="flex items-start gap-2 text-sm leading-relaxed text-slate-600">
              <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
              <span>
                Akun <b>{konfirmasi.user.userFullname ?? konfirmasi.user.userId}</b>{' '}
                <span className="font-mono text-xs">({konfirmasi.user.userId})</span> akan dihapus
                beserta foto dan notifikasinya.
              </span>
            </p>

            <div className="rounded-xl border border-rose-200 bg-rose-50 p-3.5">
              <p className="text-xs leading-relaxed text-slate-700">
                <b>Tindakan ini tidak dapat dibatalkan.</b> Akun yang sudah pernah mengajukan
                permohonan akan ditolak oleh sistem — untuk kasus itu gunakan <b>Nonaktifkan</b>
                {' '}agar arsip pelayanan tetap utuh.
              </p>
            </div>

            <div className="flex justify-end gap-2">
              <Tombol varian="garis" onClick={() => setKonfirmasi(null)}>Batal</Tombol>
              <Tombol varian="bahaya" disabled={jeda > 0 || sibukId === konfirmasi.user.id}
                      onClick={() => hapusAkun(konfirmasi.user.id)}>
                <Trash2 className="h-4 w-4" />{jeda > 0 ? `Tunggu ${jeda} detik…` : 'Ya, Hapus Permanen'}
              </Tombol>
            </div>
          </div>
        </Modal>
      )}

      {/* ── Formulir akun baru ───────────────────────────────────────────── */}
      {formTerbuka && (
        <Modal judul="Tambah Akun Baru" lebar="max-w-xl" onTutup={() => setFormTerbuka(false)}>
          <p className="mb-4 text-sm text-slate-500">
            Akun yang dibuat petugas langsung aktif. Pemberitahuan dikirim ke email jika diisi.
          </p>

          <div className="space-y-4">
            <Isian label="Jenis Akun">
              <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                {[
                  [3, 'Warga', 'Masyarakat umum'],
                  [4, 'OPD', 'Instansi pemerintah daerah'],
                  // Akun Staff hanya boleh dibuat Super Admin — servernya pun menolak.
                  ...(level === 1 ? [[2, 'Staff', 'Petugas dinas']] : []),
                ].map(([nilai, label, ket]) => (
                  <button key={nilai} type="button" onClick={() => setForm((f) => ({ ...f, level: nilai }))}
                          className={`rounded-xl border p-3 text-left transition-all ${
                            form.level === nilai ? 'border-brand bg-brand/5 ring-2 ring-brand/30' : 'border-slate-200 hover:border-brand/40'
                          }`}>
                    <p className="text-sm font-semibold text-slate-800">{label}</p>
                    <p className="mt-0.5 text-[11px] text-slate-400">{ket}</p>
                  </button>
                ))}
              </div>
            </Isian>

            {warga ? (
              <>
                <section className="space-y-4 rounded-xl border border-slate-200 p-4">
                  <h4 className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <UserRound className="h-4 w-4 text-brand" /> Informasi Personal
                  </h4>

                  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Isian label="NIK" wajib ket="16 digit angka sesuai KTP — dipakai sebagai identitas login.">
                      <Input value={form.userId} maxLength={16} inputMode="numeric" placeholder="Nomor Induk Kependudukan"
                             onChange={(e) => setForm((f) => ({ ...f, userId: e.target.value.replace(/\D/g, '') }))}
                             />
                    </Isian>
                    <Isian label="Nomor Kartu Keluarga" ket="16 digit angka sesuai Kartu Keluarga.">
                      <Input value={form.kk} maxLength={16} inputMode="numeric" placeholder="Nomor Kartu Keluarga"
                             onChange={(e) => setForm((f) => ({ ...f, kk: e.target.value.replace(/\D/g, '') }))}
                             />
                    </Isian>
                  </div>

                  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Isian label="Nama Lengkap" wajib ket="Sesuai KTP, tanpa gelar, tulis dengan huruf kapital.">
                      <Input value={form.nama} placeholder="NAMA LENGKAP"
                             onChange={(e) => setForm((f) => ({ ...f, nama: e.target.value }))} />
                    </Isian>
                    <Isian label="Kecamatan" wajib ket="Sesuai domisili dan alamat pada Kartu Keluarga.">
                      <SearchSelect
                        value={form.kecamatan}
                        onValueChange={(v) => setForm((f) => ({ ...f, kecamatan: v }))}
                        options={kecamatan.map((k) => ({ value: k, label: k }))}
                        placeholder="Pilih Kecamatan"
                        searchPlaceholder="Cari kecamatan…"
                      />
                    </Isian>
                  </div>
                </section>

                <section className="space-y-4 rounded-xl border border-slate-200 p-4">
                  <h4 className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <KeyRound className="h-4 w-4 text-brand" /> Informasi Akun
                  </h4>

                  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Isian label="Nomor WhatsApp" ket="Dimulai angka 0, pastikan nomor aktif.">
                      <Input value={form.hp} placeholder="08xxxxxxxxxx"
                             onChange={(e) => setForm((f) => ({ ...f, hp: e.target.value }))} />
                    </Isian>
                    <Isian label="Alamat Email" ket="Dipakai untuk notifikasi dan pengiriman dokumen jadi.">
                      <Input type="email" value={form.email} placeholder="nama@email.com"
                             onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} />
                    </Isian>
                  </div>

                  <Isian label="Kata Sandi Awal" wajib
                         ket="Sampaikan kata sandi ini ke pemilik akun; sarankan segera diganti lewat pengaturan akun.">
                    <Input value={form.password} placeholder="Minimal 6 karakter, bukan angka semua"
                           onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))} />
                  </Isian>
                </section>

                <section className="space-y-3 rounded-xl border border-slate-200 p-4">
                  <h4 className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <Camera className="h-4 w-4 text-brand" /> Foto Wajah / Selfie
                    <span className="font-normal text-slate-400">(opsional)</span>
                  </h4>
                  <AmbilSelfie nilai={foto} onChange={setFoto} />
                  <p className="text-[0.7rem] text-slate-400">
                    Ambil bila warga sedang berada di loket. Foto dipakai petugas untuk mencocokkan
                    dengan KTP sekaligus menjadi foto profil akun.
                  </p>
                </section>

                {/* Foto KTP diunggah dari berkas, bukan dipotret: di loket petugas
                    biasanya sudah memegang hasil scan/fotokopinya. */}
                <section className="space-y-3 rounded-xl border border-slate-200 p-4">
                  <h4 className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <IdCard className="h-4 w-4 text-brand" /> Foto KTP
                    <span className="font-normal text-slate-400">(opsional)</span>
                  </h4>
                  <UnggahGambar nilai={ktp} onChange={setKtp} label="Pilih Foto KTP" />
                  <p className="text-[0.7rem] text-slate-400">
                    Format JPG, JPEG, atau PNG. Disimpan pada penyimpanan tertutup — hanya petugas
                    yang dapat membukanya.
                  </p>
                </section>
              </>
            ) : (
              <>
                <Isian label={`Nama Lengkap${form.level === 4 ? ' / Nama Instansi' : ''}`} wajib>
                  <Input value={form.nama} onChange={(e) => setForm((f) => ({ ...f, nama: e.target.value }))}
                         placeholder={form.level === 4 ? 'mis. Dinas Kesehatan Pesisir Barat' : 'Nama sesuai KTP'}
                         />
                </Isian>

                <Isian label={form.level === 4 ? 'Username Instansi' : 'Username'} wajib
                       ket={form.level === 4
                         ? 'Username ini yang dipakai instansi untuk login (4-30 karakter, huruf/angka/titik/underscore/strip).'
                         : undefined}>
                  <Input value={form.userId} maxLength={30}
                         onChange={(e) => setForm((f) => ({ ...f, userId: e.target.value }))}
                         placeholder={form.level === 4 ? 'mis. rs.saibatin' : 'mis. staff_dinas'} />
                </Isian>

                {form.level === 4 && (
                  <Isian label="NIK Perwakilan (16 digit)" wajib ket="Dipakai untuk fitur lupa password akun instansi.">
                    <Input value={form.nik} maxLength={16}
                           onChange={(e) => setForm((f) => ({ ...f, nik: e.target.value.replace(/\D/g, '') }))}
                           placeholder="16 digit NIK perwakilan/penanggung jawab instansi" />
                  </Isian>
                )}

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <Isian label="No. HP">
                    <Input value={form.hp} placeholder="08xxxxxxxxxx"
                           onChange={(e) => setForm((f) => ({ ...f, hp: e.target.value }))} />
                  </Isian>
                  <Isian label="Email">
                    <Input type="email" value={form.email} placeholder="nama@email.com"
                           onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} />
                  </Isian>
                </div>

                <Isian label="Password Awal" wajib ket="Sampaikan password ini ke pemilik akun; sarankan segera diganti.">
                  <Input value={form.password} placeholder="Minimal 6 karakter, bukan angka semua"
                         onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))} />
                </Isian>
              </>
            )}
          </div>

          <div className="mt-5 flex justify-end gap-2">
            <Tombol varian="garis" onClick={() => setFormTerbuka(false)} disabled={membuat}>Batal</Tombol>
            <Tombol onClick={buatAkun} disabled={membuat}>Buat Akun</Tombol>
          </div>
        </Modal>
      )}
    </LayoutDashboard>
  );
}
