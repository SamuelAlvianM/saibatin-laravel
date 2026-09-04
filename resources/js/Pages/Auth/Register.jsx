import { Link, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import {
  ArrowLeft, BadgeCheck, Loader2, MailCheck, MapPin, ScanLine, UserPlus,
} from 'lucide-react';
import KartuAuth, { KotakGalat } from '@/Components/KartuAuth';
import AmbilSelfie from '@/Components/AmbilSelfie';
import UnggahGambar from '@/Components/UnggahGambar';
import { SearchSelect } from '@/Components/SearchSelect';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { PasswordInput } from '@/Components/ui/password-input';
import { kirimJson } from '@/lib/api';
import { tanpaAwalanDataUrl } from '@/lib/gambar';
import { bacaKtp } from '@/lib/ocr-ktp';

/**
 * 🔴 SAKELAR MATI OTP — kembaran `Otp::AKTIF` di sisi server (ubah keduanya
 * bersamaan). Selama `false` blok verifikasi tidak dirender dan `kirimOtp()`
 * langsung keluar; mesin OTP-nya sendiri tetap utuh.
 */
const OTP_AKTIF = false;

/**
 * 🔴 `Bagian` dan `TandaOcr` WAJIB berada di luar komponen halaman.
 *
 * Sebelumnya keduanya dideklarasikan di dalam `Register()`. Setiap ketikan
 * membuat state berubah → `Register()` dijalankan ulang → `Bagian` menjadi
 * **fungsi baru**, yang oleh React dianggap **tipe komponen berbeda**. Akibatnya
 * seluruh isi fieldset dilepas lalu dipasang ulang pada tiap huruf: DOM input
 * yang lama dibuang, fokusnya hilang, dan warga harus mengklik ulang kolomnya
 * setiap satu karakter. Dilaporkan user 13 Agu 2026.
 *
 * Pola yang sama pernah ada di `Components/EditorDemografi.jsx`.
 */

/** `sorot` untuk bagian yang isinya bukan <input> (mis. kamera selfie), yang
 *  tidak bisa ditandai lewat kelas field seperti isian biasa. */
function Bagian({ no, judul, anak, sorot }) {
  return (
    <fieldset className={`space-y-3 rounded-xl border p-4 ${
      sorot ? 'border-rose-400 bg-rose-50/40' : 'border-slate-200 bg-white/60'
    }`}>
      <legend className="flex items-center gap-2 px-1 text-xs font-semibold uppercase tracking-wide text-brand">
        <span className="flex h-5 w-5 items-center justify-center rounded-full bg-brand text-[11px] text-white">{no}</span>
        {judul}
      </legend>
      {anak}
    </fieldset>
  );
}

/** Badge "dari scan KTP" di samping label kolom hasil OCR. */
function TandaOcr({ aktif }) {
  if (!aktif) return null;

  return (
    <span className="ml-1.5 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[0.62rem] font-semibold uppercase tracking-wide text-amber-800 ring-1 ring-amber-300">
      <ScanLine className="h-3 w-3" /> Dari scan — periksa
    </span>
  );
}

/**
 * Pendaftaran akun warga — port dari `app/register/RegisterContent.tsx`.
 *
 * Mengikuti template Dukcapil Bantul: SATU formulir bersegmen, bukan wizard
 * berlangkah (langkah "Cek NIK & KK" terpisah sudah dihapus di portal lama).
 *   1. Informasi Personal — NIK, No. KK, Nama, Kecamatan
 *   2. Informasi Akun     — WhatsApp, Email, Sandi ×2, verifikasi OTP
 *   3. Foto Wajah         — wajib dipotret saat itu juga
 *   4. Foto KTP           — wajib, diunggah dari berkas + dibaca OCR
 */
export default function Register({ kecamatan, otpWajib, otpKanal, prefill, perbaiki }) {
  const [otp, setOtp] = useState({
    dikirim: false, kode: '', challenge: '', memuat: false,
    pesan: null, galat: null, devKode: null,
  });
  // Hasil pembacaan OCR atas foto KTP (lihat `bacaKtpOtomatis`).
  const [ocrJalan, setOcrJalan] = useState(false);
  const [ocrPesan, setOcrPesan] = useState(null);
  /**
   * Kolom yang nilainya datang dari OCR dan BELUM disentuh warga.
   *
   * 🔴 Alasannya bukan kosmetik. OCR bisa salah baca digit — pada pengujian,
   * NIK 1801234503980001 pernah terbaca 1601254508950001, dan salah baca
   * seperti itu tetap lolos pemeriksaan struktur NIK (tanggal & bulannya masih
   * masuk akal). NIK salah yang terisi diam-diam lebih berbahaya daripada kolom
   * kosong, jadi kolomnya ditandai supaya warga benar-benar melihatnya. Tanda
   * hilang begitu kolomnya disunting.
   */
  const [ocrTerisi, setOcrTerisi] = useState([]);

  const { data, setData, post, transform, processing, errors } = useForm({
    nama: prefill?.nama ?? '',
    nik: prefill?.nik ?? '',
    kk: prefill?.kk ?? '',
    hp: prefill?.hp ?? '',
    email: prefill?.email ?? '',
    kecamatan: prefill?.kecamatan ?? '',
    pass: '',
    pass2: '',
    foto: '',
    ktp: '',
    otpBukti: '',
  });

  // Cermin `data` yang selalu mutakhir, dipakai `bacaKtpOtomatis` untuk tahu
  // kolom mana yang masih kosong tanpa bergantung pada closure lama.
  const dataRef = useRef(data);
  dataRef.current = data;

  // Bagian yang ditandai petugas saat menolak — disorot supaya warga tahu
  // persis apa yang harus diubah, bukan mengetik ulang semuanya.
  const ditandai = (k) => (perbaiki ?? []).includes(k);
  /** Cincin amber pada kolom yang baru diisi OCR dan belum diperiksa warga. */
  const cincinOcr = (k) => (ocrTerisi.includes(k) ? 'ring-2 ring-amber-400 border-amber-400' : '');
  /**
   * Hanya PENANDA keadaan — bentuk dasar field (tinggi, border, cincin fokus)
   * datang dari `Components/ui/input`, jadi tidak ada lagi gaya yang ditulis
   * ulang per halaman dan pelan-pelan menyimpang.
   */
  const kelasField = (k) =>
    [ditandai(k) ? 'border-rose-400 bg-rose-50/50' : '', cincinOcr(k)].filter(Boolean).join(' ');

  /** Setter yang sekaligus melepas tanda OCR: kolom yang disunting warga bukan
   *  lagi "hasil pemindaian yang belum diperiksa". */
  const isi = (kunci, nilai) => {
    setData(kunci, nilai);
    setOcrTerisi((k) => (k.includes(kunci) ? k.filter((x) => x !== kunci) : k));
  };

  /**
   * Baca foto KTP yang baru diunggah lalu isikan NIK / No. KK / Nama.
   *
   * Sifatnya MEMBANTU, bukan menghakimi: kegagalan OCR tidak pernah menghalangi
   * pendaftaran — warga tinggal mengetik sendiri. Karena itu galatnya
   * ditampilkan sebagai keterangan kecil, bukan kotak merah.
   *
   * 🔴 Hanya mengisi kolom yang MASIH KOSONG. OCR bisa salah baca (angka 0/O,
   * 1/I), jadi menimpa yang sudah diketik warga justru merusak. Hasilnya tetap
   * wajib diperiksa warga sebelum kirim — ditegaskan lewat pesan di bawah.
   */
  const bacaKtpOtomatis = async (sumber) => {
    setOcrPesan(null);
    setOcrJalan(true);
    try {
      const hasil = await bacaKtp(sumber);

      // Keadaan terkini dibaca dari ref, BUKAN dari updater setData: updater
      // baru dijalankan React saat render berikutnya, sehingga apa pun yang
      // dikumpulkan di dalamnya belum tersedia di baris setelah ini.
      const kini = dataRef.current;
      const tambalan = {};
      const terisi = [];
      const dilewati = [];

      const coba = (kunci, label, nilai) => {
        if (!nilai) return;
        if (String(kini[kunci] ?? '').trim()) {
          dilewati.push(label);
          return;
        }
        tambalan[kunci] = kunci === 'nama' ? nilai.toUpperCase() : nilai;
        terisi.push({ kunci, label });
      };
      coba('nik', 'NIK', hasil.nik);
      coba('kk', 'No. KK', hasil.nokk);
      coba('nama', 'Nama', hasil.nama);

      if (terisi.length > 0) {
        setData((f) => ({ ...f, ...tambalan }));
        setOcrTerisi(terisi.map((t) => t.kunci));
        setOcrPesan(
          `Terisi otomatis dari KTP: ${terisi.map((t) => t.label).join(', ')}. `
          + '🔴 Hasil pemindaian bisa salah baca — cocokkan dengan KTP Anda sebelum mengirim.',
        );
      } else if (dilewati.length > 0) {
        // Pesan dibedakan supaya jujur: bukan "tidak terbaca", melainkan
        // kolomnya memang sudah diisi warga dan sengaja tidak ditimpa.
        setOcrPesan(`KTP terbaca (${dilewati.join(', ')}), tapi kolomnya sudah Anda isi — tidak ada yang ditimpa.`);
      } else {
        setOcrPesan('Data pada KTP tidak terbaca. Silakan isi NIK, No. KK, dan Nama secara manual.');
      }
    } catch (e) {
      setOcrPesan(e?.message || 'Pemindaian gagal. Silakan isi data secara manual.');
    } finally {
      setOcrJalan(false);
    }
  };

  const kirimOtp = async () => {
    if (!OTP_AKTIF) return; // sakelar mati — tombolnya pun sudah tak dirender
    setOtp((s) => ({ ...s, memuat: true, galat: null, pesan: null }));
    try {
      const j = await kirimJson('/otp/send', { email: data.email, hp: data.hp });

      if (j.error?.length) {
        setOtp((s) => ({ ...s, memuat: false, galat: j.error[0] }));
        return;
      }
      if (j.data?.dinonaktifkan) {
        setOtp((s) => ({ ...s, memuat: false, pesan: 'Verifikasi OTP sedang tidak diaktifkan.' }));
        return;
      }

      setOtp((s) => ({
        ...s,
        memuat: false,
        dikirim: true,
        challenge: j.data.challenge,
        devKode: j.data.devKode ?? null,
        pesan: j.data.devKode
          ? null
          : `Kode dikirim lewat ${j.data.kanal === 'wa' ? 'WhatsApp' : 'email'}. Berlaku 5 menit.`,
      }));
    } catch {
      setOtp((s) => ({ ...s, memuat: false, galat: 'Gagal menghubungi server.' }));
    }
  };

  const verifikasiOtp = async () => {
    setOtp((s) => ({ ...s, memuat: true, galat: null }));
    try {
      const j = await kirimJson('/otp/verify', {
        email: data.email, hp: data.hp, kode: otp.kode, challenge: otp.challenge,
      });

      if (j.error?.length) {
        setOtp((s) => ({ ...s, memuat: false, galat: j.error[0] }));
        return;
      }
      setData('otpBukti', j.data.bukti);
      setOtp((s) => ({ ...s, memuat: false, pesan: 'Terverifikasi.', galat: null }));
    } catch {
      setOtp((s) => ({ ...s, memuat: false, galat: 'Gagal menghubungi server.' }));
    }
  };

  const sudahOtp = Boolean(data.otpBukti);
  const targetOtp = otpKanal === 'wa' ? data.hp : data.email;

  return (
    <KartuAuth
      judul="Pendaftaran Akun"
      subjudul="Portal SAIBATIN — Disdukcapil Kab. Pesisir Barat"
      ikon={<UserPlus className="h-7 w-7" />}
      lebar="max-w-[560px]"
    >
      <form
        onSubmit={(e) => {
          e.preventDefault();
          // 🔴 Awalan data URI dibuang sebelum dikirim — mod_security cPanel
          // memblokir badan permintaan yang memuatnya (lihat lib/gambar.js).
          transform((d) => ({
            ...d,
            foto: tanpaAwalanDataUrl(d.foto),
            ktp: tanpaAwalanDataUrl(d.ktp),
          }));
          post('/register');
        }}
        className="space-y-4 px-8 pb-8"
      >
        <KotakGalat errors={errors} />

        {prefill && (
          <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            Pendaftaran sebelumnya ditolak. Data lama sudah diisikan — perbaiki
            bagian yang <span className="font-semibold text-rose-700">ditandai merah</span>, lalu kirim ulang.
          </div>
        )}

        <Bagian no={1} judul="Informasi Personal" anak={
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="nik" className="text-slate-700">
                NIK<TandaOcr aktif={ocrTerisi.includes('nik')} />
              </Label>
              <Input id="nik" value={data.nik} inputMode="numeric" placeholder="16 digit"
                     onChange={(e) => isi('nik', e.target.value.replace(/\D/g, '').slice(0, 16))}
                     className={`${kelasField('nik')} font-mono`} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="kk" className="text-slate-700">
                Nomor KK<TandaOcr aktif={ocrTerisi.includes('kk')} />
              </Label>
              <Input id="kk" value={data.kk} inputMode="numeric" placeholder="16 digit"
                     onChange={(e) => isi('kk', e.target.value.replace(/\D/g, '').slice(0, 16))}
                     className={`${kelasField('kk')} font-mono`} />
            </div>
            <div className="space-y-1.5 sm:col-span-2">
              <Label htmlFor="nama" className="text-slate-700">
                Nama Lengkap<TandaOcr aktif={ocrTerisi.includes('nama')} />
              </Label>
              <Input id="nama" value={data.nama} onChange={(e) => isi('nama', e.target.value)}
                     placeholder="Sesuai KTP" className={kelasField('nama')} />
            </div>
            <div className="space-y-1.5 sm:col-span-2">
              <Label htmlFor="kecamatan" className="text-slate-700">Kecamatan Domisili</Label>
              {/* Daftar kecamatan panjang → dropdown yang bisa DICARI, sama
                  seperti portal. Menggulir 11 pilihan buta itu yang bikin warga
                  salah pilih. */}
              <SearchSelect
                id="kecamatan"
                value={data.kecamatan}
                onValueChange={(v) => setData('kecamatan', v)}
                options={(kecamatan ?? []).map((k) => ({ value: k, label: k }))}
                placeholder="— pilih kecamatan —"
                searchPlaceholder="Cari kecamatan…"
                icon={<MapPin className="h-4 w-4 shrink-0 text-muted-foreground" />}
                className={kelasField('kecamatan')}
              />
            </div>
          </div>
        } />

        <Bagian no={2} judul="Informasi Akun" anak={
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="hp" className="text-slate-700">Nomor WhatsApp</Label>
              <Input id="hp" value={data.hp} inputMode="tel" placeholder="08xx…"
                     onChange={(e) => setData('hp', e.target.value)} className={kelasField('hp')} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="email" className="text-slate-700">Email</Label>
              <Input id="email" value={data.email} type="email" placeholder="nama@contoh.com"
                     onChange={(e) => setData('email', e.target.value)} className={kelasField('email')} />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="pass" className="text-slate-700">Password</Label>
              <PasswordInput id="pass" value={data.pass} autoComplete="new-password"
                             onChange={(e) => setData('pass', e.target.value)}
                             className={kelasField('pass')} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="pass2" className="text-slate-700">Ulangi Password</Label>
              <PasswordInput id="pass2" value={data.pass2} autoComplete="new-password"
                             onChange={(e) => setData('pass2', e.target.value)}
                             className={kelasField('pass2')} />
            </div>

            <p className="text-xs text-slate-500 sm:col-span-2">
              Minimal 6 karakter dan tidak boleh angka semua.
            </p>

            {/* 🔴 Tidak dirender selama OTP_AKTIF === false. */}
            {OTP_AKTIF && otpWajib && (
              <div className="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3 sm:col-span-2">
                <div className="flex items-center justify-between gap-2">
                  <p className="text-sm font-medium text-slate-700">
                    Verifikasi {otpKanal === 'wa' ? 'WhatsApp' : 'Email'}
                  </p>
                  {sudahOtp && (
                    <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                      <BadgeCheck className="h-3.5 w-3.5" />Terverifikasi
                    </span>
                  )}
                </div>

                {!sudahOtp && (
                  <>
                    <div className="flex gap-2">
                      <Button type="button" variant="outline" onClick={kirimOtp}
                              disabled={otp.memuat || !targetOtp}
                              className="border-primary font-semibold text-primary hover:bg-primary/5">
                        {otp.memuat ? <Loader2 className="h-4 w-4 animate-spin" /> : <MailCheck className="h-4 w-4" />}
                        {otp.dikirim ? 'Kirim Ulang' : 'Kirim Kode'}
                      </Button>
                      {otp.dikirim && (
                        <>
                          <Input value={otp.kode} inputMode="numeric" placeholder="6 digit"
                                 onChange={(e) => setOtp((s) => ({ ...s, kode: e.target.value.replace(/\D/g, '').slice(0, 6) }))}
                                 className="w-28 text-center font-mono" />
                          <Button type="button" onClick={verifikasiOtp}
                                  disabled={otp.memuat || otp.kode.length < 6}
                                  className="font-semibold">
                            Verifikasi
                          </Button>
                        </>
                      )}
                    </div>

                    {!targetOtp && (
                      <p className="text-xs text-slate-500">
                        Isi {otpKanal === 'wa' ? 'nomor WhatsApp' : 'alamat email'} dulu.
                      </p>
                    )}
                    {otp.devKode && (
                      <p className="rounded border border-amber-200 bg-amber-50 p-2 text-xs text-amber-800">
                        Mode pengembangan — layanan pengirim belum dikonfigurasi.
                        Kode: <strong className="font-mono text-sm">{otp.devKode}</strong>
                      </p>
                    )}
                    {otp.pesan && <p className="text-xs text-slate-600">{otp.pesan}</p>}
                    {otp.galat && <p className="text-xs text-red-600">{otp.galat}</p>}
                  </>
                )}
              </div>
            )}
          </div>
        } />

        <Bagian no={3} judul="Foto Wajah / Selfie" sorot={ditandai('foto')} anak={
          <div className="mx-auto max-w-[260px] space-y-2">
            {ditandai('foto') && (
              <p className="rounded-lg border border-rose-200 bg-white p-2.5 text-xs text-rose-700">
                Petugas menandai foto Anda perlu diperbaiki — mohon potret ulang.
              </p>
            )}
            <AmbilSelfie nilai={data.foto} onChange={(v) => setData('foto', v)} />
          </div>
        } />

        {/* ── Foto KTP ──
            Sengaja UNGGAH BERKAS, bukan kamera: KTP umumnya sudah ada sebagai
            hasil scan/foto, jadi memaksa memotret saat itu juga malah
            menyulitkan. Selfie tetap dipotret langsung karena di sana justru
            itulah tujuannya. */}
        <Bagian no={4} judul="Foto KTP" sorot={ditandai('ktp')} anak={
          <div className="space-y-2">
            {ditandai('ktp') && (
              <p className="rounded-lg border border-rose-200 bg-white p-2.5 text-xs text-rose-700">
                Petugas menandai foto KTP Anda perlu diperbaiki — mohon unggah ulang.
              </p>
            )}

            <UnggahGambar
              nilai={data.ktp}
              onChange={(v) => {
                setData('ktp', v);
                // Saat foto dihapus, keterangan lama ikut dibersihkan supaya
                // tidak menggantung tanpa gambarnya. Pembacaan dipicu onFileAsli.
                if (!v) setOcrPesan(null);
              }}
              onFileAsli={bacaKtpOtomatis}
              nonaktif={processing}
              label="Pilih Foto KTP"
            />

            {/* Status pembacaan otomatis. Sengaja bukan kotak merah: OCR itu
                bantuan, gagalnya tidak menghalangi warga. */}
            {(ocrJalan || ocrPesan) && (
              <p className="flex items-start gap-1.5 text-[0.72rem] leading-relaxed text-slate-600" aria-live="polite">
                {ocrJalan ? (
                  <>
                    <Loader2 className="mt-0.5 h-3.5 w-3.5 shrink-0 animate-spin text-brand" />
                    Membaca data pada foto KTP…
                  </>
                ) : (
                  <>
                    <ScanLine className="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand" />
                    {ocrPesan}
                  </>
                )}
              </p>
            )}

            <ul className="list-disc space-y-1 pl-4 text-[0.72rem] leading-relaxed text-slate-500">
              <li>Boleh hasil scan maupun foto dari ponsel — pastikan NIK, nama, dan alamat terbaca jelas</li>
              <li>NIK, No. KK, dan Nama dicoba diisi otomatis dari foto ini — periksa kembali hasilnya</li>
              <li>Fotokan seluruh bagian KTP, jangan ada sudut yang terpotong; hindari pantulan blitz</li>
              <li>KTP harus milik pendaftar sendiri, sesuai NIK yang diisi di atas</li>
              <li>Foto KTP disandingkan dengan foto selfie oleh petugas saat verifikasi</li>
              <li>Berkas disimpan pada penyimpanan tertutup dan hanya dapat dilihat petugas — tidak dapat diakses publik</li>
            </ul>
          </div>
        } />

        <Button
          type="submit"
          size="lg"
          disabled={processing}
          className="w-full font-semibold text-white shadow-lg transition-all hover:shadow-xl active:scale-[0.98] disabled:opacity-50"
          style={{ background: 'linear-gradient(90deg,#2e6da4,#1b4b72)' }}
        >
          {processing ? <><Loader2 className="mr-2 h-4 w-4 animate-spin" />Mengirim...</> : 'Daftar'}
        </Button>

        <div className="flex items-center justify-center gap-4 text-sm">
          <Link href="/login" className="inline-flex items-center gap-1.5 font-medium text-slate-600 hover:text-primary">
            <ArrowLeft className="h-4 w-4" />Sudah punya akun? Login
          </Link>
        </div>
      </form>
    </KartuAuth>
  );
}
