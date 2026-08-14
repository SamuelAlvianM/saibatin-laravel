import { usePage } from '@inertiajs/react';
import { Clock, Mail, MapPin } from 'lucide-react';

/**
 * Footer untuk halaman **Inertia** (area warga) — kembaran React dari
 * `publik/partials/footer.blade.php`.
 *
 * 🔴 Markupnya memang ada dua: Blade tidak bisa mengimpor modul JS, dan
 * halaman Inertia tidak melewati Blade. Yang TIDAK boleh ada dua adalah
 * isinya — tautan, alamat, surel, dan jam layanan datang dari
 * `config/footer.php` lewat prop bersama Inertia. Kalau dinas mengganti
 * alamat, satu berkas config saja yang disunting.
 *
 * Bedanya dari versi Blade: **tanpa penghitung pengunjung**. Pencacah itu
 * mengukur kunjungan halaman PUBLIK; menghitung halaman dashboard warga
 * yang sedang login akan menggelembungkan angkanya dengan orang yang sama
 * berkali-kali.
 */

const IKON = { 'peta-pin': MapPin, surel: Mail, jam: Clock };

export default function FooterPublik() {
  const { footer } = usePage().props;

  if (!footer) return null;

  return (
    <footer className="bg-gradient-to-b from-slate-900 to-[#0d1b2a] text-slate-400">
      <div className="container mx-auto px-4 py-14 md:px-8 lg:px-16 lg:py-16">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-8">
          <div className="space-y-5 lg:col-span-5">
            <div className="flex items-center gap-3">
              <img src="/logo-saibatin.png" alt="Logo SAIBATIN"
                   className="h-11 w-11 flex-shrink-0 object-contain" />
              <div>
                <span className="text-lg font-bold tracking-wide text-white">SAIBATIN</span>
                <p className="text-xs text-slate-400">Disdukcapil Kabupaten Pesisir Barat</p>
              </div>
            </div>
            <p className="max-w-md text-sm leading-relaxed">
              Portal layanan administrasi kependudukan dan pencatatan sipil
              Kabupaten Pesisir Barat. Melayani masyarakat secara profesional,
              akuntabel, dan prima.
            </p>
          </div>

          <div className="grid grid-cols-2 gap-8 lg:col-span-3">
            {footer.tautan.map((grup) => (
              <div key={grup.judul}>
                <h4 className="mb-4 text-sm font-semibold uppercase tracking-wider text-white">{grup.judul}</h4>
                <ul className="space-y-2.5">
                  {grup.items.map((item) => (
                    <li key={item.href}>
                      {/* <a> biasa, bukan <Link>: tujuannya halaman publik yang
                          memang dirender Blade, di luar aplikasi Inertia. */}
                      <a href={item.href} className="text-sm transition-colors hover:text-white">{item.label}</a>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>

          <div className="lg:col-span-4">
            <div className="rounded-2xl border border-white/10 bg-white/5 p-5">
              <h4 className="mb-4 text-sm font-semibold uppercase tracking-wider text-white">Kantor Kami</h4>
              <ul className="space-y-3 text-sm">
                {footer.kantor.map((baris, i) => {
                  const Ikon = IKON[baris.ikon] ?? MapPin;
                  return (
                    <li key={i} className="flex items-start gap-3">
                      <Ikon className="mt-0.5 h-4 w-4 flex-shrink-0 text-yellow-400" />
                      <span className="whitespace-pre-line">{baris.teks}</span>
                    </li>
                  );
                })}
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div className="border-t border-white/10">
        <div className="container mx-auto px-4 py-5 md:px-8 lg:px-16">
          <div className="flex flex-col items-center justify-between gap-3 text-xs text-slate-500 md:flex-row">
            <p>
              &copy; {new Date().getFullYear()}{' '}
              <span className="font-medium text-slate-300">SAIBATIN</span> —
              Disdukcapil Kabupaten Pesisir Barat
            </p>
            <div className="flex items-center gap-4">
              {footer.legal.map((item, i) => (
                <span key={item.href} className="flex items-center gap-4">
                  {i > 0 && <span className="text-slate-700">|</span>}
                  <a href={item.href} className="transition-colors hover:text-white">{item.label}</a>
                </span>
              ))}
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
