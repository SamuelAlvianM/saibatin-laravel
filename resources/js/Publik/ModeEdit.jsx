import { useCallback, useEffect, useMemo, useState } from 'react';
import { createPortal } from 'react-dom';
import { Check, Loader2, Pencil, PencilRuler, X } from 'lucide-react';
import { Modal, Pesan, Tombol } from '@/Components/Dasbor';
import EditorMedan from '@/Components/EditorMedan';
import { ambilJson, kirimJson } from '@/lib/api';
import { bukaEditor, setModeEdit } from '@/lib/mode-edit';

/**
 * MODE EDIT halaman publik — port `components/konten/inline-edit.tsx`.
 *
 * Petugas (Super Admin) menyunting isi situs DI HALAMANNYA SENDIRI: tombol
 * melayang menyalakan mode edit, tiap bagian yang bisa disunting diberi garis
 * putus-putus + tombol pensil, dan pensilnya membuka dialog berisi medan blok
 * itu. Inilah yang dipratinjau halaman dashboard **Konten Halaman** di dalam
 * iframe `?editmode=1`.
 *
 * 🔴 Bedanya dengan portal Next.js: di sana halaman publik komponen React, jadi
 * tiap blok dibungkus `<EditableBlock kunci=…>`. Halaman publik port ini Blade
 * yang dirender server, jadi penandanya atribut biasa:
 *
 *     <section data-blok="beranda.hero" data-blok-label="Teks Hero"> … </section>
 *
 * Island ini yang menyusul memasang garis & pensilnya. Konsekuensinya elemen
 * yang muncul BELAKANGAN (island lain yang baru hidup) juga harus tertangkap —
 * karena itu ada MutationObserver, bukan sekali query saat dipasang.
 *
 * Island ini hanya dirender untuk Super Admin (lihat `publik/layout.blade.php`),
 * jadi tidak ada beban apa pun bagi warga.
 */

/** Mode edit bertahan melewati muat ulang setelah menyimpan. */
const KUNCI_SESI = 'saibatin-mode-edit';

/** Tombol pensil yang ditempelkan di pojok kanan atas sebuah blok. */
function TombolPensil({ kunci, label }) {
  return (
    <button
      type="button"
      onClick={(e) => {
        // Blok bisa berada di dalam tautan atau <summary>; tanpa ini menekan
        // pensil ikut memicu navigasi/akordeon di baliknya.
        e.preventDefault();
        e.stopPropagation();
        bukaEditor(kunci);
      }}
      title={`Sunting ${label ?? kunci}`}
      className="absolute right-2 top-2 z-40 inline-flex items-center gap-1.5 rounded-lg bg-brand px-2.5 py-1.5 text-xs font-semibold text-white shadow-lg transition-colors hover:bg-brand-dark"
    >
      <Pencil className="h-3.5 w-3.5" />
      Edit{label ? ` ${label}` : ''}
    </button>
  );
}

/** Dialog satu blok: medannya dari server, isinya sudah digabung bawaan. */
function DialogBlok({ kunci, onTutup, onSelesai, onGalat }) {
  const [blok, setBlok] = useState(null);
  const [draf, setDraf] = useState({});
  const [memuat, setMemuat] = useState(true);
  const [menyimpan, setMenyimpan] = useState(false);

  useEffect(() => {
    let hidup = true;
    setMemuat(true);

    ambilJson(`/api/admin/static-content?kunci=${encodeURIComponent(kunci)}`).then((j) => {
      if (!hidup) return;
      setMemuat(false);

      if (j.error?.length) { onGalat(j.error[0]); onTutup(); return; }

      setBlok(j.data.blok);
      // Salinan dalam: menyunting baris `items` tidak boleh mengubah objek yang
      // dipakai halaman di belakang dialog sebelum tombol Simpan ditekan.
      setDraf(structuredClone(j.data.konten ?? {}));
    });

    return () => { hidup = false; };
  }, [kunci, onGalat, onTutup]);

  const simpan = async () => {
    setMenyimpan(true);
    const j = await kirimJson('/api/admin/static-content', { kunci, konten: draf }, 'PUT');
    setMenyimpan(false);

    if (j.error?.length) { onGalat(j.error[0]); return; }
    onSelesai();
  };

  return (
    <Modal judul={blok?.judul ?? 'Sunting Konten'} sub={kunci} lebar="max-w-2xl" onTutup={onTutup}>
      {blok?.deskripsi && <p className="-mt-2 text-sm text-slate-500">{blok.deskripsi}</p>}

      {memuat ? (
        <div className="flex justify-center py-10">
          <Loader2 className="h-6 w-6 animate-spin text-brand" />
        </div>
      ) : (
        <div className="mt-4 space-y-5">
          {(blok?.medan ?? []).map((m) => (
            <EditorMedan
              key={m.nama}
              medan={m}
              nilai={draf[m.nama]}
              onGalat={onGalat}
              onUbah={(v) => setDraf((d) => ({ ...d, [m.nama]: v }))}
            />
          ))}
        </div>
      )}

      <div className="mt-6 flex justify-end gap-2">
        <Tombol varian="garis" onClick={onTutup} disabled={menyimpan}>
          <X className="h-4 w-4" /> Batal
        </Tombol>
        <Tombol onClick={simpan} disabled={menyimpan || memuat}>
          {menyimpan ? <Loader2 className="h-4 w-4 animate-spin" /> : <Check className="h-4 w-4" />}
          Simpan
        </Tombol>
      </div>
    </Modal>
  );
}

export default function ModeEdit({ auto = false }) {
  const [aktif, setAktif] = useState(() => {
    try {
      return auto || sessionStorage.getItem(KUNCI_SESI) === '1';
    } catch {
      return auto;
    }
  });
  const [blokDom, setBlokDom] = useState([]);
  const [kunci, setKunci] = useState(null);
  const [pesan, setPesan] = useState(null);

  const galat = useCallback((teks) => setPesan({ tipe: 'galat', teks }), []);
  const tutup = useCallback(() => setKunci(null), []);

  // Beri tahu island lain (ProfilTabs memasang pensilnya sendiri).
  useEffect(() => {
    setModeEdit(aktif);
    try {
      sessionStorage.setItem(KUNCI_SESI, aktif ? '1' : '0');
    } catch { /* penyimpanan sesi diblokir — mode edit tetap jalan, cuma tidak bertahan */ }
  }, [aktif]);

  // Pensil dibuka dari mana pun lewat `bukaEditor()`.
  useEffect(() => {
    const dengar = (e) => setKunci(e.detail);
    window.addEventListener('konten:edit', dengar);

    return () => window.removeEventListener('konten:edit', dengar);
  }, []);

  // Kumpulkan blok yang ada di halaman. Diamati terus karena island lain hidup
  // belakangan — blok di dalamnya tidak akan ada saat efek ini pertama jalan.
  useEffect(() => {
    if (!aktif) { setBlokDom([]); return undefined; }

    const kumpulkan = () => setBlokDom((lama) => {
      const baru = Array.from(document.querySelectorAll('[data-blok]'));

      return baru.length === lama.length && baru.every((el, i) => el === lama[i]) ? lama : baru;
    });

    kumpulkan();

    const pengamat = new MutationObserver(kumpulkan);
    pengamat.observe(document.body, { childList: true, subtree: true });

    return () => pengamat.disconnect();
  }, [aktif]);

  // Garis putus-putus + jangkar untuk pensilnya. Gaya dipasang langsung ke
  // elemen (bukan kelas) supaya tidak perlu menambah CSS yang ikut terkirim ke
  // seluruh pengunjung; nilai lamanya disimpan dan dikembalikan saat mode edit
  // dimatikan, jadi tata letak halaman tidak berubah sedikit pun sesudahnya.
  useEffect(() => {
    if (!aktif) return undefined;

    const semula = new Map();

    blokDom.forEach((el) => {
      semula.set(el, { outline: el.style.outline, offset: el.style.outlineOffset, posisi: el.style.position });
      el.style.outline = '2px dashed rgba(33,118,189,0.55)';
      el.style.outlineOffset = '2px';
      if (getComputedStyle(el).position === 'static') el.style.position = 'relative';
    });

    return () => {
      semula.forEach((v, el) => {
        el.style.outline = v.outline;
        el.style.outlineOffset = v.offset;
        el.style.position = v.posisi;
      });
    };
  }, [aktif, blokDom]);

  const pensil = useMemo(
    // Kuncinya memakai indeks juga: satu halaman boleh memuat dua elemen dengan
    // kunci blok yang sama (mis. blok yang tampil dua kali), dan kunci portal
    // yang kembar membuat React membuang salah satu pensilnya diam-diam.
    () => blokDom.map((el, i) => createPortal(
      <TombolPensil kunci={el.dataset.blok} label={el.dataset.blokLabel} />,
      el,
      `${el.dataset.blok}-${i}`,
    )),
    [blokDom],
  );

  return (
    <>
      {aktif && pensil}

      <button
        type="button"
        onClick={() => setAktif((v) => !v)}
        title={aktif ? 'Selesai menyunting halaman' : 'Aktifkan mode edit halaman'}
        className={`fixed bottom-6 right-6 z-[60] inline-flex items-center gap-2 rounded-full px-4 py-3 text-sm font-semibold text-white shadow-xl transition-transform hover:scale-105 ${
          aktif ? 'bg-emerald-600' : 'bg-brand'
        }`}
      >
        {aktif ? <><Check className="h-4 w-4" /> Selesai Edit</> : <><PencilRuler className="h-4 w-4" /> Mode Edit</>}
      </button>

      {kunci && (
        <DialogBlok
          kunci={kunci}
          onTutup={tutup}
          onGalat={galat}
          // Halaman publik dirender server, jadi isinya tidak bisa disegarkan
          // dari klien — muat ulang adalah satu-satunya cara melihat hasilnya,
          // dan mode edit sengaja bertahan (sessionStorage) supaya penyuntingan
          // berikutnya tidak perlu dinyalakan lagi.
          onSelesai={() => window.location.reload()}
        />
      )}

      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />
    </>
  );
}
