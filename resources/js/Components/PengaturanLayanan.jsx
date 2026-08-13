import { useEffect, useMemo, useRef, useState } from 'react';
import {
  CalendarX2, Check, Clock, Copy, Eye, EyeOff, ListChecks, Loader2,
  SlidersHorizontal, X,
} from 'lucide-react';
import { ambilJson, kirimJson } from '@/lib/api';
import { Tombol } from '@/Components/Dasbor';
import { Checkbox } from '@/Components/ui/checkbox';
import { DatePicker } from '@/Components/ui/date-picker';
import { TimePicker } from '@/Components/ui/time-picker';

/**
 * Drawer "Kelola Layanan" — port `components/dashboard/jam-layanan-editor.tsx`
 * + `pengaturan-pelayanan.tsx`, yang di portal Next.js dibuka dari tombol
 * Pengaturan di halaman Pengajuan Baru (bukan halaman tersendiri).
 *
 * Keduanya **menyimpan otomatis**: tidak ada tombol Simpan, perubahan dikirim
 * setelah jeda diam. Itu bukan pilihan gaya — dua editor ini dipakai sambil
 * melayani warga di loket, dan tombol Simpan yang terlewat berarti jam layanan
 * berbeda antara yang terlihat petugas dan yang sebenarnya berlaku.
 *
 * Keduanya khusus Super Admin (level 1); server memeriksanya lagi.
 */

const URUTAN_HARI = [1, 2, 3, 4, 5, 6, 0];
const HARI_LABEL = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

/** Saklar kecil bergaya iOS. */
function Saklar({ nyala, onUbah, label }) {
  return (
    <button type="button" role="switch" aria-checked={nyala} aria-label={label}
            onClick={() => onUbah(!nyala)}
            className={`relative h-6 w-11 shrink-0 rounded-full transition-colors ${nyala ? 'bg-emerald-500' : 'bg-slate-300'}`}>
      <span className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all ${nyala ? 'left-[22px]' : 'left-0.5'}`} />
    </button>
  );
}

function StatusSimpan({ status }) {
  return (
    <div className="flex items-center justify-end gap-1.5 text-xs text-slate-400">
      {status === 'menyimpan' ? (
        <><Loader2 className="h-3.5 w-3.5 animate-spin" /> Menyimpan…</>
      ) : status === 'tersimpan' ? (
        <><Check className="h-3.5 w-3.5 text-emerald-500" /> Perubahan tersimpan otomatis</>
      ) : (
        <>Perubahan tersimpan otomatis</>
      )}
    </div>
  );
}

const kelasJam = 'w-[116px]';

// ── Tab 1: jam kerja ────────────────────────────────────────────────────────

function EditorJamLayanan({ onGalat }) {
  const [cfg, setCfg] = useState(null);
  const [memuat, setMemuat] = useState(true);
  const [status, setStatus] = useState('idle');
  const terakhir = useRef('');

  const [hariMassal, setHariMassal] = useState(() => new Set());
  const [massalMulai, setMassalMulai] = useState('08:00');
  const [massalSelesai, setMassalSelesai] = useState('16:00');
  const [liburBaru, setLiburBaru] = useState('');

  useEffect(() => {
    ambilJson('/api/admin/jam-layanan').then((j) => {
      setMemuat(false);
      if (j.error?.length) { onGalat(j.error[0]); return; }
      setCfg(j.data);
      terakhir.current = JSON.stringify(j.data);
    });
  }, [onGalat]);

  // Autosave 800 ms setelah perubahan berhenti. Jam tak masuk akal
  // (mulai ≥ selesai) ditahan — jangan menyimpan konfigurasi yang menutup
  // layanan seharian gara-gara satu ketikan setengah jadi.
  useEffect(() => {
    if (memuat || !cfg) return undefined;
    const kini = JSON.stringify(cfg);
    if (kini === terakhir.current) return undefined;
    if (cfg.enabled && cfg.days.some((d) => d.buka && d.mulai >= d.selesai)) return undefined;

    setStatus('menyimpan');
    const t = setTimeout(async () => {
      const j = await kirimJson('/api/admin/jam-layanan', cfg, 'PUT');
      if (j.error?.length) { onGalat(j.error[0]); setStatus('idle'); return; }
      terakhir.current = kini;
      setStatus('tersimpan');
    }, 800);

    return () => clearTimeout(t);
  }, [cfg, memuat, onGalat]);

  if (memuat || !cfg) {
    return <div className="flex justify-center py-10"><Loader2 className="h-6 w-6 animate-spin text-brand" /></div>;
  }

  const setHari = (i, tambal) =>
    setCfg((c) => ({ ...c, days: c.days.map((d, k) => (k === i ? { ...d, ...tambal } : d)) }));

  const terapkanMassal = () => {
    if (hariMassal.size === 0) { onGalat('Pilih dulu hari yang mau diterapkan'); return; }
    setCfg((c) => ({
      ...c,
      days: c.days.map((d, i) =>
        hariMassal.has(i) ? { buka: true, mulai: massalMulai, selesai: massalSelesai } : d),
    }));
  };

  const tambahLibur = () => {
    if (!liburBaru) return;
    setCfg((c) => ({
      ...c,
      holidays: c.holidays.includes(liburBaru) ? c.holidays : [...c.holidays, liburBaru].sort(),
    }));
    setLiburBaru('');
  };

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
        <div>
          <p className="flex items-center gap-2 text-sm font-semibold text-slate-800">
            <Clock className="h-4 w-4 text-brand" /> Batasi jam pembuatan permohonan
          </p>
          <p className="mt-0.5 text-xs text-slate-500">
            Saat aktif, permohonan baru (warga &amp; staff) hanya bisa dibuat pada jam di
            bawah. Permohonan yang sudah ada tetap bisa diproses kapan pun.
          </p>
        </div>
        <Saklar nyala={cfg.enabled} onUbah={(v) => setCfg((c) => ({ ...c, enabled: v }))}
                label="Batasi jam pembuatan permohonan" />
      </div>

      <div className={`space-y-5 ${cfg.enabled ? '' : 'pointer-events-none opacity-50'}`}>
        <div className="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
          {URUTAN_HARI.map((i) => {
            const d = cfg.days[i];
            return (
              <div key={i} className="flex flex-wrap items-center gap-3 px-4 py-2.5">
                <Saklar nyala={d.buka} onUbah={(v) => setHari(i, { buka: v })}
                        label={`Buka hari ${HARI_LABEL[i]}`} />
                <span className="w-16 text-sm font-medium text-slate-800">{HARI_LABEL[i]}</span>
                {d.buka ? (
                  <div className="flex items-center gap-2">
                    <TimePicker value={d.mulai} onChange={(v) => setHari(i, { mulai: v })} className={kelasJam} />
                    <span className="text-xs text-slate-400">s/d</span>
                    <TimePicker value={d.selesai} onChange={(v) => setHari(i, { selesai: v })} className={kelasJam} />
                  </div>
                ) : (
                  <span className="text-sm text-slate-400">Tutup</span>
                )}
              </div>
            );
          })}
        </div>

        <div className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
          <p className="mb-2.5 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
            <Copy className="h-3.5 w-3.5" /> Terapkan jam sekaligus
          </p>
          <div className="mb-3 flex flex-wrap gap-1.5">
            {URUTAN_HARI.map((i) => (
              <button key={i} type="button"
                      onClick={() => setHariMassal((p) => {
                        const n = new Set(p);
                        if (n.has(i)) n.delete(i); else n.add(i);
                        return n;
                      })}
                      className={`rounded-full border px-3 py-1 text-xs font-medium transition-colors ${
                        hariMassal.has(i)
                          ? 'border-brand bg-brand text-white'
                          : 'border-slate-200 bg-white text-slate-600 hover:border-brand/40'
                      }`}>
                {HARI_LABEL[i].slice(0, 3)}
              </button>
            ))}
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <TimePicker value={massalMulai} onChange={setMassalMulai} className={kelasJam} />
            <span className="text-xs text-slate-400">s/d</span>
            <TimePicker value={massalSelesai} onChange={setMassalSelesai} className={kelasJam} />
            <Tombol varian="garis" onClick={terapkanMassal}>Terapkan</Tombol>
          </div>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-4">
          <p className="mb-2.5 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
            <CalendarX2 className="h-3.5 w-3.5" /> Tanggal libur khusus
          </p>
          <div className="mb-3 flex items-center gap-2">
            <DatePicker value={liburBaru} onChange={setLiburBaru} placeholder="Pilih tanggal libur"
                        className="w-48" />
            <Tombol varian="garis" onClick={tambahLibur} disabled={!liburBaru}>Tambah</Tombol>
          </div>
          {cfg.holidays.length === 0 ? (
            <p className="text-xs text-slate-400">Belum ada tanggal libur khusus.</p>
          ) : (
            <div className="flex flex-wrap gap-1.5">
              {cfg.holidays.map((h) => (
                <span key={h} className="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700">
                  {h}
                  <button type="button" aria-label={`Hapus libur ${h}`}
                          onClick={() => setCfg((c) => ({ ...c, holidays: c.holidays.filter((x) => x !== h) }))}
                          className="text-slate-400 hover:text-rose-600">
                    <X className="h-3 w-3" />
                  </button>
                </span>
              ))}
            </div>
          )}
        </div>
      </div>

      <StatusSimpan status={status} />
    </div>
  );
}

// ── Tab 2: ketersediaan layanan ─────────────────────────────────────────────

function EditorVisibilitas({ onGalat }) {
  const [hidden, setHidden] = useState(() => new Set());
  const [daftar, setDaftar] = useState([]);
  const [kategori, setKategori] = useState({});
  const [memuat, setMemuat] = useState(true);
  const [status, setStatus] = useState('idle');
  const terakhir = useRef('');

  useEffect(() => {
    ambilJson('/api/admin/pelayanan-visibilitas').then((j) => {
      setMemuat(false);
      if (j.error?.length) { onGalat(j.error[0]); return; }
      const h = Array.isArray(j.data?.hidden) ? j.data.hidden : [];
      setHidden(new Set(h));
      setDaftar(j.data?.layanan ?? []);
      setKategori(Object.fromEntries((j.data?.kategori ?? []).map((k) => [k.id, k.name])));
      terakhir.current = JSON.stringify([...h].sort());
    });
  }, [onGalat]);

  useEffect(() => {
    if (memuat) return undefined;
    const kini = JSON.stringify([...hidden].sort());
    if (kini === terakhir.current) return undefined;

    setStatus('menyimpan');
    const t = setTimeout(async () => {
      const j = await kirimJson('/api/admin/pelayanan-visibilitas', { hidden: [...hidden] }, 'PUT');
      if (j.error?.length) { onGalat(j.error[0]); setStatus('idle'); return; }
      terakhir.current = kini;
      setStatus('tersimpan');
    }, 700);

    return () => clearTimeout(t);
  }, [hidden, memuat, onGalat]);

  const grup = useMemo(() => {
    const g = {};
    for (const l of daftar) (g[l.category] ??= []).push(l);
    return g;
  }, [daftar]);

  if (memuat) {
    return <div className="flex justify-center py-16"><Loader2 className="h-6 w-6 animate-spin text-brand" /></div>;
  }

  const tampil = daftar.length - hidden.size;

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-brand/20 bg-brand/5 px-4 py-3">
        <p className="text-sm text-slate-700">
          <b>{tampil}</b> dari {daftar.length} layanan tampil di <b>Permohonan Online</b>.
          Hilangkan centang untuk menyembunyikan.
        </p>
        <div className="flex gap-2">
          <Tombol varian="garis" onClick={() => setHidden(new Set())}>
            <Eye className="mr-1.5 h-4 w-4" /> Tampilkan semua
          </Tombol>
          <Tombol varian="garis" onClick={() => setHidden(new Set(daftar.map((l) => l.kunci)))}>
            <EyeOff className="mr-1.5 h-4 w-4" /> Sembunyikan semua
          </Tombol>
        </div>
      </div>

      <div className="space-y-5">
        {Object.entries(grup).map(([kat, isi]) => (
          <div key={kat} className="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
              {kategori[kat] ?? kat}
            </h3>
            <div className="grid gap-2 sm:grid-cols-2">
              {isi.map((l) => {
                const terlihat = !hidden.has(l.kunci);
                return (
                  <label key={l.kunci}
                         className={`flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition-colors ${
                           terlihat ? 'border-slate-200 hover:border-brand/40'
                                    : 'border-dashed border-slate-200 bg-slate-50 opacity-70'
                         }`}>
                    <Checkbox checked={terlihat}
                              onCheckedChange={() => setHidden((p) => {
                                const n = new Set(p);
                                if (n.has(l.kunci)) n.delete(l.kunci); else n.add(l.kunci);
                                return n;
                              })} />
                    <span className={`text-sm font-medium ${terlihat ? 'text-slate-800' : 'text-slate-400 line-through'}`}>
                      {l.title}
                    </span>
                  </label>
                );
              })}
            </div>
          </div>
        ))}
      </div>

      <StatusSimpan status={status} />
    </div>
  );
}

// ── Drawer ──────────────────────────────────────────────────────────────────

export default function PengaturanLayanan({ onTutup, onGalat }) {
  const [tab, setTab] = useState('jam');

  useEffect(() => {
    const esc = (e) => { if (e.key === 'Escape') onTutup(); };
    window.addEventListener('keydown', esc);
    return () => window.removeEventListener('keydown', esc);
  }, [onTutup]);

  const kelasTab = (nilai) =>
    `flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg border py-2 text-sm font-medium transition-colors ${
      tab === nilai ? 'border-brand bg-brand text-white shadow-sm' : 'border-slate-200 text-slate-500 hover:text-brand'
    }`;

  return (
    <div className="fixed inset-0 z-50 flex justify-end">
      <div className="absolute inset-0 bg-slate-900/50" onClick={onTutup} />
      <div role="dialog" aria-modal="true" aria-label="Kelola Layanan"
           className="relative flex h-full w-full max-w-xl flex-col overflow-y-auto bg-slate-50 shadow-2xl">
        <div className="sticky top-0 z-10 border-b border-slate-200 bg-white px-5 py-4">
          <div className="flex items-start justify-between gap-3">
            <div>
              <h2 className="flex items-center gap-2 font-semibold text-slate-900">
                <SlidersHorizontal className="h-5 w-5 text-brand" /> Kelola Layanan
              </h2>
              <p className="mt-0.5 text-sm text-slate-500">
                Atur ketersediaan jenis layanan &amp; jam kerja permohonan.
              </p>
            </div>
            <button onClick={onTutup} aria-label="Tutup pengaturan" className="text-slate-400 hover:text-slate-600">
              <X className="h-5 w-5" />
            </button>
          </div>
        </div>

        <div className="px-5 py-4">
          <div className="flex w-full flex-row gap-1 rounded-md border border-slate-200 bg-white p-1">
            <button onClick={() => setTab('jam')} className={kelasTab('jam')}>
              <Clock className="h-4 w-4" /> Jam Kerja
            </button>
            <button onClick={() => setTab('layanan')} className={kelasTab('layanan')}>
              <ListChecks className="h-4 w-4" /> Ketersediaan Layanan
            </button>
          </div>

          <div className="pt-4">
            {tab === 'jam' ? <EditorJamLayanan onGalat={onGalat} /> : <EditorVisibilitas onGalat={onGalat} />}
          </div>
        </div>
      </div>
    </div>
  );
}
