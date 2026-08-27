import { lazy, Suspense, useEffect, useState } from 'react';
import { CheckSquare, ChevronRight, FileText, Network, Pencil, Quote, Target, Users } from 'lucide-react';
import { ikon } from '@/lib/ikon';
import { bukaEditor, modeEditAktif, pantauModeEdit } from '@/lib/mode-edit';

/**
 * Profil instansi — port `components/landingpage/profile-tabs.tsx`.
 *
 * Isi tiap tab datang dari props (dirender server dari `t_static_contents`),
 * BUKAN diambil sendiri lewat `fetch` seperti aslinya. Dua alasannya: tab
 * pertama langsung terisi tanpa kedipan, dan teks visi-misi ikut terkirim
 * sebagai HTML pada halaman yang memuatnya.
 *
 * Bagan struktur dimuat MALAS — pustaka bagannya besar dan hanya dibutuhkan
 * kalau pengunjung benar-benar membuka tab itu.
 */

const StrukturBagan = lazy(() => import('./StrukturBagan'));

const TAB = [
  { id: 'visi-misi', label: 'Visi & Misi', pendek: 'Visi', ikon: Target, ket: 'Arah dan komitmen organisasi' },
  { id: 'motto', label: 'Motto & Tujuan', pendek: 'Motto', ikon: FileText, ket: 'Nilai dan sasaran strategis' },
  { id: 'maklumat', label: 'Maklumat', pendek: 'Maklumat', ikon: Users, ket: 'Janji pelayanan publik' },
  { id: 'tugas', label: 'Tugas & Fungsi', pendek: 'Tugas', ikon: CheckSquare, ket: 'Wewenang dan tanggung jawab' },
  { id: 'struktur', label: 'Struktur', pendek: 'Struktur', ikon: Network, ket: 'Susunan organisasi' },
];

function Label({ children }) {
  return (
    <span className="mb-3 block text-[0.65rem] font-bold uppercase tracking-widest text-slate-400">{children}</span>
  );
}

function Pemisah({ label }) {
  return (
    <div className="my-2 flex items-center gap-3">
      <div className="h-px flex-1 bg-slate-100" />
      {label && <Label>{label}</Label>}
      <div className="h-px flex-1 bg-slate-100" />
    </div>
  );
}

function Bernomor({ nomor, children }) {
  return (
    <div className="group flex items-start gap-4 rounded-xl border border-transparent p-4 transition-all duration-300 hover:border-slate-100 hover:bg-slate-50">
      <span className="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500 transition-all duration-300 group-hover:bg-brand group-hover:text-white">
        {nomor}
      </span>
      <p className="pt-0.5 text-sm leading-relaxed text-slate-600">{children}</p>
    </div>
  );
}

function Butir({ children }) {
  return (
    <div className="group flex items-start gap-3 text-sm leading-relaxed text-slate-600">
      <ChevronRight className="mt-0.5 h-4 w-4 flex-shrink-0 text-slate-300 transition-colors group-hover:text-brand" />
      {children}
    </div>
  );
}

function PanelVisiMisi({ data }) {
  return (
    <div className="space-y-10">
      <div className="relative border-l-2 border-brand/30 pl-6">
        <Label>Visi</Label>
        <p className="text-xl font-light leading-relaxed text-slate-800 md:text-2xl">{data.visi}</p>
      </div>

      <div>
        <Pemisah label="Misi" />
        <div className="mt-4 space-y-2">
          {(data.misi ?? []).map((m, i) => <Bernomor key={i} nomor={i + 1}>{m}</Bernomor>)}
        </div>
      </div>
    </div>
  );
}

function PanelMotto({ data }) {
  // Motto ditulis "Profesional, Integritas, Prima" lalu dipecah jadi kata-kata
  // bergaris miring — bentuk itu bagian dari identitasnya, bukan sekadar gaya.
  const kata = String(data.motto ?? '').split(', ');

  return (
    <div className="space-y-10">
      <div className="py-6 text-center">
        <Label>Motto</Label>
        <h4 className="text-3xl font-light tracking-wide text-slate-900 md:text-4xl">
          {kata.map((k, i) => (
            <span key={i}>
              <span className="bg-gradient-to-r from-brand to-brand/70 bg-clip-text font-semibold text-transparent">{k}</span>
              {i < kata.length - 1 && <span className="mx-3 text-slate-300">/</span>}
            </span>
          ))}
        </h4>
      </div>

      <div className="grid gap-8 md:grid-cols-2">
        <div>
          <Label>Tujuan</Label>
          <div className="space-y-3">
            {(data.tujuan ?? []).map((t, i) => <Butir key={i}>{t}</Butir>)}
          </div>
        </div>
        <div>
          <Label>Sasaran</Label>
          <div className="space-y-3">
            {(data.sasaran ?? []).map((s, i) => <Butir key={i}>{s}</Butir>)}
          </div>
        </div>
      </div>
    </div>
  );
}

function PanelMaklumat({ data }) {
  return (
    <div className="space-y-8">
      <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
        {(data.janji ?? []).map((j, i) => {
          const Ikon = ikon(j.icon);

          return (
            <div key={i}
                 className="group flex flex-col items-center rounded-2xl border border-brand/15 bg-gradient-to-br from-brand/[0.09] to-brand/[0.03] p-5 text-center shadow-[0_4px_20px_rgba(33,118,189,0.06)] transition-all duration-300 hover:-translate-y-1 hover:from-brand/[0.13] hover:shadow-lg">
              <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-2xl border border-brand/10 bg-white text-brand shadow-sm transition-all duration-300 group-hover:scale-110">
                <Ikon className="h-5 w-5" />
              </div>
              <p className="text-sm font-semibold text-slate-800">{j.title}</p>
              <p className="mt-0.5 text-xs text-slate-500">{j.desc}</p>
            </div>
          );
        })}
      </div>

      <div className="relative overflow-hidden rounded-2xl p-7 text-white"
           style={{ background: 'linear-gradient(135deg, #2176bd 0%, #1b4b72 100%)' }}>
        <div className="pointer-events-none absolute right-0 top-0 h-48 w-48 rounded-full bg-white/10 blur-3xl" />
        <Quote className="mb-3 h-7 w-7 text-white/40" />
        <p className="relative z-10 text-base font-light leading-relaxed text-white/90">{data.standar}</p>
      </div>
    </div>
  );
}

function PanelTugas({ data }) {
  return (
    <div className="space-y-8">
      <div className="rounded-2xl border border-brand/10 bg-brand/5 p-5">
        <Label>Tugas Pokok</Label>
        <p className="text-base font-light leading-relaxed text-slate-800">{data.utama}</p>
      </div>

      <div>
        <Label>Fungsi</Label>
        <div className="space-y-1">
          {(data.fungsi ?? []).map((f, i) => (
            <div key={i} className="group flex items-center gap-4 rounded-xl border border-transparent p-3.5 transition-all duration-300 hover:border-slate-100 hover:bg-slate-50">
              <div className="h-1.5 w-1.5 flex-shrink-0 rounded-full bg-slate-300 transition-colors group-hover:bg-brand" />
              <span className="text-sm text-slate-700">{f}</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

export default function ProfilTabs({ isi = {} }) {
  const [aktif, setAktif] = useState('visi-misi');

  // MODE EDIT: island ini memasang pensilnya SENDIRI, tidak lewat `data-blok`.
  // Alasannya bukan gaya — lima tab berbagi satu kotak, jadi pensil yang
  // melayang di atas kotaknya akan menyunting blok yang salah begitu tab
  // berpindah. Kuncinya harus ikut tab yang sedang tampil.
  const [modeEdit, setModeEditLokal] = useState(modeEditAktif);
  useEffect(() => pantauModeEdit(setModeEditLokal), []);

  const konfigurasi = TAB.find((t) => t.id === aktif);
  const data = isi[aktif] ?? {};
  const IkonTab = konfigurasi.ikon;

  return (
    <>
      <div className="mx-auto mb-6 flex max-w-fit flex-wrap justify-center gap-1.5 rounded-2xl border border-slate-200/60 bg-slate-100/70 p-1.5 backdrop-blur-md">
        {TAB.map((t) => {
          const ini = aktif === t.id;
          const Ikon = t.ikon;

          return (
            <button key={t.id} onClick={() => setAktif(t.id)}
                    className={`relative flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors duration-200 ${
                      ini ? 'border border-slate-200/80 bg-white text-slate-900 shadow-sm' : 'border border-transparent text-slate-500 hover:text-slate-700'
                    }`}>
              <Ikon className={`h-4 w-4 transition-colors duration-200 ${ini ? 'text-brand' : 'text-slate-400'}`} />
              <span className="hidden sm:inline">{t.label}</span>
              <span className="sm:hidden">{t.pendek}</span>
            </button>
          );
        })}
      </div>

      <div className="overflow-hidden rounded-3xl border border-brand/15 bg-gradient-to-br from-brand/[0.06] to-brand/[0.02] shadow-[0_8px_40px_rgba(33,118,189,0.08)]">
        <div className="h-0.5 bg-gradient-to-r from-brand via-brand/70 to-brand/40" />

        <div className="p-7 md:p-10">
          <div className="mb-8 flex items-center gap-4 border-b border-slate-100 pb-6">
            <div className="flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-100 bg-slate-50 text-slate-600">
              <IkonTab className="h-4 w-4" />
            </div>
            <div>
              <h3 className="text-lg font-semibold text-slate-900">{konfigurasi.label}</h3>
              <p className="mt-0.5 text-xs text-slate-500">{konfigurasi.ket}</p>
            </div>

            {/* Tombolnya duduk DI DALAM kartu, sebaris dengan judul panel —
                bukan melayang di pojok halaman, jauh dari kotak yang sedang
                disunting. Ini perbaikan yang sama dengan `profile-tabs.tsx`
                portal Next.js, dan menutup satu-satunya sisa sinkronisasi
                13 Agu yang selama ini menunggu mode edit. */}
            {modeEdit && (
              <button type="button" onClick={() => bukaEditor(`profil.${aktif}`)}
                      className="ml-auto inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-xs font-semibold text-white shadow-lg transition-colors hover:bg-brand-dark">
                <Pencil className="h-3.5 w-3.5" />
                Edit {konfigurasi.label}
              </button>
            )}
          </div>

          {/* `key` memaksa panel dirender ulang saat tab berganti, jadi animasi
              masuknya jalan lagi alih-alih hanya isinya yang bertukar diam. */}
          <div key={aktif} className="masuk-naik min-h-[260px]">
            {aktif === 'visi-misi' && <PanelVisiMisi data={data} />}
            {aktif === 'motto' && <PanelMotto data={data} />}
            {aktif === 'maklumat' && <PanelMaklumat data={data} />}
            {aktif === 'tugas' && <PanelTugas data={data} />}
            {aktif === 'struktur' && (
              <Suspense fallback={<p className="py-10 text-center text-sm text-slate-400">Memuat bagan struktur…</p>}>
                <StrukturBagan data={data} />
              </Suspense>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
