import { useMemo, useState } from 'react';
import { ChevronRight, ExternalLink, Home, RefreshCw, ShieldCheck } from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import { menuNavigasi } from '@/lib/navigasi';

/**
 * Konten Halaman — port `app/dashboard/konten/AdminKonten.tsx`.
 *
 * 🔴 BUKAN halaman formulir. Ia "site editor": kolom kiri seluruh menu navbar
 * publik, deret sub-menu di atas, dan bagian tengahnya me-render halaman publik
 * ITU SENDIRI di dalam iframe dengan `?editmode=1`. Penyuntingannya terjadi di
 * halaman publik lewat pensil yang muncul saat mode edit menyala — lihat island
 * `Publik/ModeEdit.jsx`.
 *
 * Karena itu halaman ini baru bisa dibuat setelah situs publiknya ada:
 * membangunnya lebih dulu hanya menghasilkan iframe kosong.
 *
 * Daftar menunya dibaca dari `lib/navigasi.js` yang sama dengan navbar publik,
 * jadi halaman yang ditambahkan di sana langsung muncul di sini — daftar
 * terpisah pasti tertinggal.
 */

/** Ratakan satu menu navbar (item + subItem) jadi daftar tautan. */
function ratakan(menu) {
  if (!menu.items?.length) {
    return menu.href && menu.href !== '#' ? [{ judul: menu.title, href: menu.href }] : [];
  }

  return menu.items.flatMap((item) => (
    item.subItems?.length
      ? item.subItems
          .filter((s) => s.href && s.href !== '#')
          .map((s) => ({ judul: s.title, href: s.href, grup: item.title }))
      : (item.href && item.href !== '#' ? [{ judul: item.title, href: item.href }] : [])
  ));
}

export default function Konten() {
  const menu = useMemo(() => [
    { judul: 'Beranda', ikon: Home, daun: [{ judul: 'Beranda', href: '/' }] },
    ...menuNavigasi.map((m) => ({ judul: m.title, daun: ratakan(m) })),
    {
      // Tiga halaman di luar navbar — ditautkan dari footer, tapi isinya sama
      // saja bisa disunting. Tanpa entri ini petugas tidak punya jalan masuk
      // ke mode editnya sama sekali.
      judul: 'Halaman Footer',
      ikon: ShieldCheck,
      daun: [
        { judul: 'Hubungi Kami', href: '/hubungi-kami' },
        { judul: 'Kebijakan & Privasi', href: '/kebijakan-privasi' },
        { judul: 'Syarat & Ketentuan', href: '/syarat' },
      ],
    },
  ].filter((m) => m.daun.length > 0), []);

  // SATU sumber kebenaran: halaman yang sedang dibuka. Menu kiri yang tersorot
  // DITURUNKAN darinya, bukan disimpan sebagai state kedua — dua state yang
  // saling menyalin hanya menunggu waktu untuk menyimpang (mis. saat peramban
  // memulihkan state dari riwayat).
  const [href, setHref] = useState(menu[0].daun[0].href);
  // Mengubah `key` iframe memaksanya dipasang ulang — memuat ulang isi iframe
  // dari luar tidak bisa dilakukan langsung karena isinya beda dokumen.
  const [muatUlang, setMuatUlang] = useState(0);

  const indeks = Math.max(0, menu.findIndex((m) => m.daun.some((d) => d.href === href)));
  const aktif = menu[indeks];
  const alamatPratinjau = `${href}${href.includes('?') ? '&' : '?'}editmode=1`;

  const pilihMenu = (i) => setHref(menu[i].daun[0].href);

  return (
    <LayoutDashboard judul="Konten Halaman" lebar="max-w-full">
      <div className="mb-4">
        <h1 className="text-xl font-bold text-slate-900">Konten Halaman</h1>
        <p className="mt-0.5 text-sm text-slate-500">
          Pilih halaman di kiri, lalu sunting langsung di pratinjau — tekan pensil pada
          bagian yang ingin diubah.
        </p>
      </div>

      <div className="grid min-h-[560px] gap-3 lg:h-[calc(100vh-190px)] lg:grid-cols-[230px_1fr]">
        {/* ── Kiri: seluruh isi navbar ─────────────────────────────────── */}
        <aside className="overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2">
          <p className="px-3 pb-1.5 pt-2 text-[0.65rem] font-bold uppercase tracking-widest text-slate-400">
            Menu Situs
          </p>

          {menu.map((m, i) => {
            const ini = i === indeks;
            const Ikon = m.ikon;

            return (
              <button key={m.judul} type="button" onClick={() => pilihMenu(i)}
                      className={`flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition-colors ${
                        ini ? 'bg-brand text-white' : 'text-slate-700 hover:bg-brand/5 hover:text-brand'
                      }`}>
                <span className="flex min-w-0 items-center gap-2">
                  {Ikon && <Ikon className="h-4 w-4 shrink-0" aria-hidden />}
                  <span className="truncate">{m.judul}</span>
                </span>
                <span className={`shrink-0 rounded-full px-1.5 text-[0.65rem] font-semibold ${
                  ini ? 'bg-white/20' : 'bg-slate-100 text-slate-500'
                }`}>
                  {m.daun.length}
                </span>
              </button>
            );
          })}
        </aside>

        {/* ── Kanan: sub-menu + pratinjau yang bisa disunting ───────────── */}
        <div className="flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white">
          <div className="flex items-center gap-2 border-b border-slate-100 p-2">
            <div className="flex min-w-0 flex-1 items-center gap-1.5 overflow-x-auto pb-1 pt-0.5">
              {aktif.daun.map((d) => {
                const ini = d.href === href;

                return (
                  <button key={d.href} type="button" onClick={() => setHref(d.href)}
                          title={d.grup ? `${d.grup} → ${d.judul}` : d.judul}
                          className={`flex shrink-0 items-center gap-1 rounded-full px-3 py-1.5 text-xs font-medium transition-colors ${
                            ini ? 'bg-brand text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                          }`}>
                    {d.grup && (
                      <>
                        <span className={ini ? 'text-white/70' : 'text-slate-400'}>{d.grup}</span>
                        <ChevronRight className="h-3 w-3 opacity-60" aria-hidden />
                      </>
                    )}
                    {d.judul}
                  </button>
                );
              })}
            </div>

            <div className="flex shrink-0 items-center gap-1">
              <button type="button" onClick={() => setMuatUlang((n) => n + 1)}
                      title="Muat ulang pratinjau" aria-label="Muat ulang pratinjau"
                      className="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                <RefreshCw className="h-4 w-4" />
              </button>
              <a href={href} target="_blank" rel="noopener noreferrer"
                 title="Buka halaman di tab baru" aria-label="Buka halaman di tab baru"
                 className="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                <ExternalLink className="h-4 w-4" />
              </a>
            </div>
          </div>

          <iframe key={`${href}-${muatUlang}`} src={alamatPratinjau} title={`Pratinjau ${href}`}
                  className="h-full min-h-[480px] w-full flex-1 bg-slate-50" />
        </div>
      </div>
    </LayoutDashboard>
  );
}
