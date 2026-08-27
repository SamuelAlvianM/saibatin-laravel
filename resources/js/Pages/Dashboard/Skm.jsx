import { useCallback, useEffect, useMemo, useState } from 'react';
import { Accessibility, ClipboardCheck, Gauge, Star, Users } from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import GrafikTampak from '@/Components/GrafikTampak';
import { Kartu, Kosong, Memuat, tglSingkat } from '@/Components/Dasbor';
import { ambilJson } from '@/lib/api';
import { cn } from '@/lib/utils';

/**
 * Rekap Survei Kepuasan Masyarakat — port `app/dashboard/skm/SkmDashboard.tsx`.
 *
 * Ambang mutu mengikuti **Permenpan RB 14/2017**; angkanya bukan pilihan desain
 * dan tidak boleh dibulatkan sendiri — nilai inilah yang dilaporkan dinas.
 *
 * 🔴 DUA GENERASI KUESIONER ditampilkan terpisah: 16 pertanyaan kuesioner dinas
 * 2026 dan 9 unsur warisan (204 responden). Menggabungkan rata-ratanya dalam
 * satu daftar akan menyandingkan pertanyaan yang isinya berbeda. Nilai IKM
 * sendiri dihitung dari rata-rata TIAP RESPONDEN, jadi keduanya tetap ikut.
 *
 * 🔴 SEMUA DAFTAR PANJANG DIGANTI GRAFIK + TAB. Versi sebelumnya menumpuk
 * 16 baris bar-tangan, lalu 9 baris lagi, lalu tiga daftar sebaran, lalu tabel
 * responden — satu halaman yang harus digulir belasan layar, dan tidak ada satu
 * pun angka yang bisa dibandingkan tanpa menggulir bolak-balik. Sekarang tiap
 * kelompok jadi SATU grafik Highcharts setinggi tetap, dan yang isinya sejenis
 * (dua generasi kuesioner; tiga sebaran responden) ditumpuk di balik tab —
 * hanya satu yang memakan ruang pada satu waktu.
 */

function mutu(ikm) {
  if (ikm >= 88.31) return { label: 'A — Sangat Baik', warna: 'text-emerald-600' };
  if (ikm >= 76.61) return { label: 'B — Baik', warna: 'text-brand' };
  if (ikm >= 65.0) return { label: 'C — Kurang Baik', warna: 'text-amber-600' };
  return { label: 'D — Tidak Baik', warna: 'text-rose-600' };
}

/** Pemilih tab kecil — dipakai dua kali, jadi ditulis sekali. */
function Tab({ pilihan, nilai, onGanti }) {
  return (
    <div className="flex flex-wrap gap-1 rounded-lg bg-slate-100 p-0.5">
      {pilihan.map((p) => (
        <button key={p.kunci} type="button" onClick={() => onGanti(p.kunci)}
                aria-pressed={nilai === p.kunci}
                className={cn(
                  'rounded-md px-2.5 py-1 text-[0.7rem] font-medium transition-colors',
                  nilai === p.kunci
                    ? 'bg-white text-brand shadow-sm'
                    : 'text-slate-500 hover:text-slate-700',
                )}>
          {p.label}
        </button>
      ))}
    </div>
  );
}

/**
 * Chip nomor pertanyaan — pengganti label sumbu yang dulu saling menimpa.
 *
 * Warnanya mengikuti nilai, jadi pertanyaan yang melorot bisa ditemukan tanpa
 * membaca satu pun angka. Ambangnya sama dengan mutu IKM (Permenpan 14/2017)
 * setelah nilai 1–4 diubah ke skala 100.
 */
function warnaNilai(nilai, skalaMax) {
  const persen = (nilai / skalaMax) * 100;
  if (persen >= 88.31) return 'border-emerald-200 bg-emerald-50 text-emerald-700';
  if (persen >= 76.61) return 'border-brand/25 bg-brand/5 text-brand';
  if (persen >= 65) return 'border-amber-200 bg-amber-50 text-amber-700';
  return 'border-rose-200 bg-rose-50 text-rose-700';
}

/** Kartu angka ringkas di baris atas. */
function KartuAngka({ label, nilai, ikon: Ikon, warna, kecil }) {
  return (
    <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3.5 py-3 shadow-sm">
      <span className={cn('flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-slate-50', warna)}>
        <Ikon className="h-[1.125rem] w-[1.125rem]" />
      </span>
      <div className="min-w-0">
        <p className={cn('truncate font-semibold text-slate-900', kecil ? 'text-sm' : 'text-xl leading-tight')}>
          {nilai}
        </p>
        <p className="truncate text-[0.68rem] text-slate-500">{label}</p>
      </div>
    </div>
  );
}

export default function Skm() {
  const [data, setData] = useState(null);
  const [memuat, setMemuat] = useState(true);
  const [generasi, setGenerasi] = useState('baru');
  const [sebaran, setSebaran] = useState('pendidikan');
  const [pilih, setPilih] = useState(null);

  // Diteruskan ke Highcharts sebagai bagian dari `options` — harus stabil,
  // kalau tidak grafiknya digambar ulang tiap render.
  const gantiPilih = useCallback((i) => setPilih((p) => (p === i ? null : i)), []);

  useEffect(() => {
    ambilJson('/api/admin/skm').then((j) => {
      setData(j.data);
      setMemuat(false);
    });
  }, []);

  // Bentuk data grafik dihitung sekali per perubahan — Highcharts menggambar
  // ulang seluruh grafik setiap `options` berganti identitas.
  const nilaiBaru = useMemo(
    () => (data?.rataPerAspek ?? []).map((a) => ({
      nama: a.nomor ? `${a.nomor}. ${a.aspek}` : a.aspek,
      nilai: a.rata,
      responden: a.responden,
    })),
    [data],
  );

  const nilaiWarisan = useMemo(
    () => (data?.rataWarisan ?? []).map((a, i) => ({
      nama: `${i + 1}. ${a.aspek}`,
      nilai: a.rata,
      responden: a.responden,
    })),
    [data],
  );

  const sebaranData = useMemo(() => {
    const d = data?.demografi;
    if (!d) return [];
    const sumber = { pendidikan: d.pendidikan, pekerjaan: d.pekerjaan, produk: d.produkLayanan }[sebaran] ?? [];
    // Delapan teratas: sisanya berekor panjang dan hanya menambah tinggi.
    return sumber.slice(0, 8).map((i) => ({ nama: i.label, count: i.jumlah }));
  }, [data, sebaran]);

  if (memuat) {
    return <LayoutDashboard judul="SKM & IKM"><Memuat kelas="py-20" /></LayoutDashboard>;
  }
  if (!data) {
    return <LayoutDashboard judul="SKM & IKM"><Kosong>Gagal memuat data survei.</Kosong></LayoutDashboard>;
  }

  const m = mutu(data.nilaiIKM);
  const dis = data.demografi?.disabilitas;
  const adaWarisan = (data.generasi?.warisan ?? 0) > 0;
  const nilaiTampil = generasi === 'warisan' ? nilaiWarisan : nilaiBaru;

  const kartu = [
    { label: 'Total Responden', nilai: data.totalResponden, ikon: Users, warna: 'text-brand' },
    { label: `Rata-rata Skor (dari ${data.skalaMax})`, nilai: data.rataKeseluruhan, ikon: Star, warna: 'text-amber-500' },
    { label: 'Nilai IKM', nilai: data.nilaiIKM, ikon: Gauge, warna: 'text-emerald-600' },
    { label: 'Mutu Pelayanan', nilai: m.label, ikon: ClipboardCheck, warna: m.warna, kecil: true },
  ];

  return (
    <LayoutDashboard judul="SKM & IKM">
      <div className="mb-4 flex flex-wrap items-end justify-between gap-2">
        <div>
          <h1 className="text-xl font-semibold text-slate-900">Survei Kepuasan Masyarakat</h1>
          <p className="text-xs text-slate-500">
            Rekap penilaian warga — kuesioner 16 pertanyaan (Permenpan RB 14/2017), skala 1–{data.skalaMax}.
          </p>
        </div>
        {adaWarisan && (
          // Dua generasi kuesioner — dinyatakan terang-terangan supaya angka
          // "responden" per pertanyaan tidak terbaca sebagai data yang hilang.
          <p className="text-[0.68rem] leading-relaxed text-slate-500">
            <b>{data.generasi.baru}</b> responden kuesioner 2026 · <b>{data.generasi.warisan}</b> responden
            kuesioner 9 unsur. IKM dihitung dari rata-rata <b>tiap responden</b>, jadi keduanya ikut.
          </p>
        )}
      </div>

      <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        {kartu.map((c) => <KartuAngka key={c.label} {...c} />)}
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <Kartu
            judul="Rata-rata Nilai per Pertanyaan"
            aksi={adaWarisan && (
              <Tab
                nilai={generasi}
                // Pilihan chip dibuang saat generasi berganti: indeks 12 pada
                // kuesioner 16 pertanyaan bukan pertanyaan yang sama dengan
                // indeks 12 pada kuesioner 9 unsur — dan seringnya tidak ada.
                onGanti={(g) => { setGenerasi(g); setPilih(null); }}
                pilihan={[
                  { kunci: 'baru', label: '16 pertanyaan (2026)' },
                  { kunci: 'warisan', label: '9 unsur (lama)' },
                ]}
              />
            )}
          >
            {/* Keterangan grafik. Tanpa ini pembaca harus menebak sumbu tegaknya
                mengukur apa — dan angka 1–4 bisa saja disangka jumlah. */}
            <p className="-mt-2 mb-3 text-[0.72rem] leading-relaxed text-slate-500">
              Setiap titik adalah <b>satu pertanyaan</b> kuesioner, berurutan sesuai nomornya.
              Sumbu tegak menunjukkan <b>rata-rata nilai yang diberikan warga</b> untuk pertanyaan
              itu, pada skala 1 ({(data.skalaLabel?.[0] ?? 'tidak baik').toLowerCase()}) sampai{' '}
              {data.skalaMax} ({(data.skalaLabel?.[data.skalaMax - 1] ?? 'sangat baik').toLowerCase()}).
              Semakin dekat ke {data.skalaMax}, semakin baik penilaiannya — <b>lembah pada garis
              menandai unsur pelayanan yang paling perlu dibenahi</b>.
            </p>

            {nilaiTampil.length === 0 ? (
              <Kosong>Belum ada responden pada kuesioner ini.</Kosong>
            ) : (
              <>
                <GrafikTampak
                  jenis="garis"
                  /* `key` memaksa grafik dibangun ulang saat tab berganti —
                     jumlah kategorinya berbeda (16 vs 9), dan Highcharts yang
                     memperbarui di tempat menyisakan sumbu lama. */
                  key={generasi}
                  data={nilaiTampil}
                  skalaMax={data.skalaMax}
                  terpilih={pilih}
                  onPilih={gantiPilih}
                  tinggi={250}
                />

                {/* Chip = daftar isi grafik. Nomornya cocok dengan sumbu-X,
                    jadi teks pertanyaan tidak perlu masuk ke dalam grafik. */}
                <div className="mt-3 flex flex-wrap gap-1.5">
                  {nilaiTampil.map((x, i) => (
                    <button key={x.nama} type="button" onClick={() => gantiPilih(i)}
                            title={x.nama}
                            aria-pressed={pilih === i}
                            className={cn(
                              'rounded-full border px-2 py-0.5 text-[0.68rem] font-semibold transition-all',
                              warnaNilai(x.nilai, data.skalaMax),
                              pilih === i && 'ring-2 ring-brand ring-offset-1',
                            )}>
                      {i + 1}
                    </button>
                  ))}
                </div>

                {/* Rincian pertanyaan terpilih. Ruangnya dipesan sejak awal
                    (`min-h`) supaya kartu tidak melompat saat chip diklik. */}
                <div className="mt-3 min-h-[3.25rem] rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2.5">
                  {pilih == null || !nilaiTampil[pilih] ? (
                    <p className="text-[0.72rem] leading-relaxed text-slate-400">
                      Nomor chip di atas sama dengan nomor pada sumbu mendatar. Klik salah satunya
                      — atau titik pada grafik — untuk membaca bunyi lengkap pertanyaannya.
                      Warnanya mengikuti mutu nilai: hijau sangat baik, biru baik, kuning kurang.
                    </p>
                  ) : (
                    <>
                      <p className="text-[0.78rem] font-semibold leading-snug text-slate-800">
                        {nilaiTampil[pilih].nama}
                      </p>
                      <p className="mt-0.5 text-[0.7rem] text-slate-500">
                        Rata-rata <b className="text-slate-700">
                          {nilaiTampil[pilih].nilai.toLocaleString('id-ID', {
                            minimumFractionDigits: 2, maximumFractionDigits: 2,
                          })}
                        </b> dari {data.skalaMax}
                        {nilaiTampil[pilih].responden !== undefined
                          && ` · dinilai ${nilaiTampil[pilih].responden} responden`}
                      </p>
                    </>
                  )}
                </div>
              </>
            )}
          </Kartu>
        </div>

        <div className="flex flex-col gap-4">
          {dis && (dis.ya > 0 || dis.tidak > 0) && (
            <Kartu judul="Responden Penyandang Disabilitas">
              <div className="flex items-center gap-3">
                <Accessibility className="h-7 w-7 flex-shrink-0 text-brand" />
                <div className="min-w-0">
                  <p className="text-xl font-semibold leading-tight text-slate-900">{dis.ya}</p>
                  <p className="text-[0.68rem] leading-snug text-slate-500">
                    dari {dis.ya + dis.tidak} yang menjawab
                    {dis.tidakDitanya > 0 && ` · ${dis.tidakDitanya} kuesioner lama tidak ditanya`}
                  </p>
                </div>
              </div>
              {dis.jenis.length > 0 && (
                <>
                  <p className="mt-3 text-[0.7rem] leading-relaxed text-slate-500">
                    Jenis disabilitas yang disebutkan responden, diurutkan dari yang terbanyak.
                    Panjang batang = jumlah responden.
                  </p>
                  <GrafikTampak
                    jenis="peringkat"
                    data={dis.jenis.slice(0, 5).map((i) => ({ nama: i.label, count: i.jumlah }))}
                    tinggi={Math.max(90, Math.min(5, dis.jenis.length) * 26)}
                    satuan="responden"
                    judul="Jenis disabilitas responden"
                    kecil
                  />
                </>
              )}
            </Kartu>
          )}

          <Kartu
            judul="Sebaran Responden"
            aksi={(
              <Tab
                nilai={sebaran}
                onGanti={setSebaran}
                pilihan={[
                  { kunci: 'pendidikan', label: 'Pendidikan' },
                  { kunci: 'pekerjaan', label: 'Pekerjaan' },
                  { kunci: 'produk', label: 'Layanan' },
                ]}
              />
            )}
          >
            <p className="-mt-2 mb-2 text-[0.7rem] leading-relaxed text-slate-500">
              Siapa saja yang mengisi survei ini. Panjang batang = <b>jumlah responden</b>,
              delapan terbanyak. Dipakai untuk menilai apakah respondennya sudah mewakili
              warga yang benar-benar dilayani.
            </p>

            {sebaranData.length === 0 ? (
              <Kosong>Belum ada data.</Kosong>
            ) : (
              <GrafikTampak
                jenis="peringkat"
                key={sebaran}
                data={sebaranData}
                tinggi={Math.max(110, sebaranData.length * 26)}
                satuan="responden"
                judul={`Sebaran responden menurut ${sebaran}`}
                kecil
              />
            )}
          </Kartu>
        </div>
      </div>

      <div className="mt-4">
        <Kartu judul="Responden Terbaru">
          {data.respondenTerbaru.length === 0 ? <Kosong>Belum ada responden.</Kosong> : (
            // Tinggi dibatasi + kepala tabel melekat: daftar ini bisa panjang,
            // dan sebelumnya ia sendirian menambah beberapa layar gulir.
            <div className="max-h-80 overflow-auto">
              <table className="w-full text-sm">
                <thead className="sticky top-0 z-10 bg-white">
                  <tr className="border-b border-slate-200 text-left text-slate-500">
                    {['Nama', 'Tanggal', 'Produk Layanan', 'Kuesioner', 'Rata Skor', 'Saran'].map((h) => (
                      <th key={h} className="whitespace-nowrap py-2 pr-4 text-xs font-medium">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {data.respondenTerbaru.map((r) => (
                    <tr key={r.id} className="border-b border-slate-100">
                      <td className="py-2 pr-4 font-medium text-slate-800">
                        {r.nama}
                        {r.disabilitas && (
                          <Accessibility className="ml-1.5 inline h-3.5 w-3.5 text-brand"
                                         aria-label="Penyandang disabilitas / pendamping" />
                        )}
                      </td>
                      <td className="whitespace-nowrap py-2 pr-4 text-xs text-slate-500">{tglSingkat(r.createdAt)}</td>
                      <td className="py-2 pr-4 text-slate-600">{r.produkLayanan ?? '-'}</td>
                      <td className="py-2 pr-4">
                        <span className={`whitespace-nowrap rounded-full px-2 py-0.5 text-[0.68rem] font-medium ${
                          r.kuesioner === 'baru' ? 'bg-brand/10 text-brand' : 'bg-slate-100 text-slate-500'
                        }`}>
                          {r.kuesioner === 'baru' ? '16 pertanyaan' : '9 unsur'}
                        </span>
                      </td>
                      <td className="py-2 pr-4">{r.rataSkor.toFixed(2)}</td>
                      <td className="max-w-xs truncate py-2 pr-4 text-slate-500">{r.saran ?? '-'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </Kartu>
      </div>
    </LayoutDashboard>
  );
}
