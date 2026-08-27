import { useMemo } from 'react';
import Highcharts from 'highcharts';
// Modul aksesibilitas: grafik ini menggantikan angka yang tadinya bisa dibaca
// pembaca layar sebagai teks biasa, jadi jangan turun kualitas.
import 'highcharts/modules/accessibility';
import HighchartsReact from 'highcharts-react-official';

/**
 * Dua bentuk grafik yang dipakai berulang di dashboard DAN beranda publik:
 * batang tegak (tren per bulan) dan batang datar berperingkat (layanan
 * terpopuler / sebaran status).
 *
 * 🔴 Sebelumnya keduanya digambar tangan dengan `<div>` + `style={{height}}`.
 * Selain menyalahi keputusan user ("grafik memakai Highcharts"), versi tangan
 * itu **rusak di dashboard**: batangnya bersarang di dalam flex `h-full` yang
 * tingginya ikut runtuh ke 0, jadi kartu "Tren Permohonan" tampil sebagai
 * deretan angka melayang di atas ruang kosong. Highcharts mengukur wadahnya
 * sendiri, jadi kelas tinggi yang runtuh tidak lagi menghapus grafiknya.
 *
 * 🔴 Highcharts hanya berjalan di BROWSER — tidak menambah kebutuhan Node di
 * server, jadi keputusan "tanpa Inertia SSR / tanpa daemon Node di cPanel"
 * tetap utuh. Berkasnya besar (± 300 KB), karena itu SELALU dimuat lewat
 * `lazy()` + gerbang tampilan; lihat `GrafikTampak`.
 */

const BRAND = '#1b4b72';
const BRAND_TERANG = '#2176bd';
const MUDA = '#7db8e8';

const angkaId = (n) => Number(n ?? 0).toLocaleString('id-ID');

/**
 * Kartu tooltip HTML.
 *
 * 🔴 Kotak bawaan Highcharts SENGAJA dilumpuhkan (`backgroundColor` transparan,
 * tanpa border/padding) dan kartunya digambar sendiri di sini. Dengan
 * `useHTML: true`, Highcharts mengukur kotaknya dari teks polos — bukan dari
 * HTML yang sebenarnya dirender — sehingga judul pertanyaan yang membungkus
 * jadi meluber keluar kotak dan baris terakhirnya terpotong. `outside: true`
 * melengkapinya: tanpa itu kartu masih terpotong oleh tepi wadah grafik.
 *
 * 🔴 `width`, BUKAN `max-width`. Dengan `max-width` kartunya menciut mengikuti
 * wadah yang sudah salah diukur Highcharts — terukur 80 px lebar dan 203 px
 * tinggi, yaitu satu-dua kata per baris. Lebar tetap memaksa ukuran yang benar
 * (262 × 71 px) sebelum Highcharts sempat menebak.
 */
function kartuTooltip(isi) {
  return '<div style="width:14rem;white-space:normal;background:#fff;'
    + 'border:1px solid rgba(33,118,189,0.22);border-radius:10px;'
    + 'box-shadow:0 6px 20px rgba(15,23,42,0.12);padding:8px 10px;'
    + 'font-size:12px;line-height:1.4;color:#334155">' + isi + '</div>';
}

/** Dasar yang sama untuk semua grafik supaya tidak ada gaya yang menyimpang. */
function dasar(tinggi, satuan = 'permohonan') {
  return {
    credits: { enabled: false },
    legend: { enabled: false },
    title: { text: null },
    chart: {
      height: tinggi,
      backgroundColor: 'transparent',
      spacing: [6, 2, 2, 2],
      style: { fontFamily: 'inherit' },
    },
    tooltip: {
      useHTML: true,
      outside: true,
      backgroundColor: 'transparent',
      borderWidth: 0,
      shadow: false,
      padding: 0,
      style: { fontSize: '12px' },
      // Pemisah ribuan Indonesia — bawaan Highcharts memakai spasi.
      formatter() {
        return kartuTooltip(
          `<div style="font-weight:700;color:#0f172a">${this.key}</div>`
          + `<div style="margin-top:2px">${angkaId(this.y)} ${satuan}</div>`,
        );
      },
    },
    plotOptions: {
      series: {
        animation: { duration: 700 },
        borderWidth: 0,
        states: { hover: { brightness: 0.06 } },
      },
    },
  };
}

/**
 * Nilai rata-rata tiap pertanyaan SKM — GARIS, bukan batang.
 *
 * 🔴 VERSI BATANGNYA DIBUANG, dan alasannya bukan selera. Pertanyaan kuesioner
 * Permenpan berbunyi panjang ("Tidak ada percaloan/perantara dalam pelayanan"),
 * dan pada batang datar tiap nama harus muat di baris setinggi ± 26 px. Enam
 * belas nama sepanjang itu saling menimpa sampai tak satu pun terbaca — grafik
 * yang isinya justru menyembunyikan datanya.
 *
 * Garis memindahkan seluruh teks KELUAR dari grafik: sumbu-X cukup bernomor
 * 1…16, dan nama lengkapnya dibaca lewat chip di bawahnya. Sebagai bonus,
 * bentuk garis memperlihatkan pertanyaan mana yang melorot — hal yang justru
 * tidak terlihat pada 16 batang yang panjangnya nyaris sama.
 *
 * 🔴 Sumbu-Y dikunci 1…skalaMax (skala kuesioner), BUKAN diskalakan ke nilai
 * terbesar. Yang dilaporkan dinas adalah jarak ke skor maksimum; sumbu yang
 * menyesuaikan diri akan membuat selisih 3,71 vs 3,68 tampak seperti jurang.
 *
 * `data`: `[{ nama, nilai, responden }]`.
 * `terpilih`: indeks titik yang sedang disorot chip (boleh null).
 * `onPilih`: dipanggil dengan indeks saat titik diklik — WAJIB `useCallback`,
 * kalau tidak `options` beridentitas baru tiap render dan Highcharts
 * menggambar ulang seluruh grafik pada setiap gerakan.
 */
export function GrafikGaris({
  data, skalaMax = 4, terpilih = null, onPilih, tinggi = 268,
  judulX = 'Nomor pertanyaan', judulY = 'Rata-rata nilai',
}) {
  const opsi = useMemo(() => {
    const d = dasar(tinggi);
    const angka2 = (n) => Number(n ?? 0).toLocaleString('id-ID', {
      minimumFractionDigits: 2, maximumFractionDigits: 2,
    });
    const gayaJudulSumbu = {
      color: '#64748b', fontSize: '10px', fontWeight: '600',
      textTransform: 'uppercase', letterSpacing: '0.06em',
    };

    return {
      ...d,
      chart: { ...d.chart, type: 'line', spacing: [10, 8, 4, 4] },
      accessibility: { description: `Rata-rata nilai tiap pertanyaan pada skala 1 sampai ${skalaMax}` },
      tooltip: {
        ...d.tooltip,
        // Bunyi lengkap pertanyaan muncul DI SINI — itulah yang memungkinkan
        // sumbu-X cukup bernomor.
        formatter() {
          const p = this.point;
          return kartuTooltip(
            `<div style="font-weight:700;color:#0f172a">${p.judul}</div>`
            + `<div style="margin-top:3px">Rata-rata <b>${angka2(this.y)}</b> dari ${skalaMax}</div>`
            + `<div style="color:#64748b;font-size:11px">dinilai ${angkaId(p.responden)} responden</div>`,
          );
        },
      },
      xAxis: {
        categories: data.map((_, i) => String(i + 1)),
        title: { text: judulX, style: gayaJudulSumbu, margin: 8 },
        lineColor: '#e2e8f0',
        tickLength: 0,
        crosshair: { color: 'rgba(33,118,189,0.10)', width: 24 },
        labels: { style: { color: '#94a3b8', fontSize: '10px' } },
      },
      yAxis: {
        title: { text: `${judulY} (1–${skalaMax})`, style: gayaJudulSumbu, margin: 10 },
        min: 1,
        max: skalaMax,
        tickInterval: 0.5,
        gridLineColor: '#eef2f6',
        labels: {
          style: { color: '#94a3b8', fontSize: '10px' },
          formatter() { return Number(this.value).toLocaleString('id-ID'); },
        },
      },
      plotOptions: {
        ...d.plotOptions,
        series: {
          ...d.plotOptions.series,
          cursor: 'pointer',
          point: { events: { click() { onPilih?.(this.index); } } },
        },
      },
      series: [{
        name: 'Rata-rata',
        type: 'areaspline',
        data: data.map((x, i) => ({
          y: Number(x.nilai ?? 0),
          judul: x.nama,
          responden: x.responden,
          marker: i === terpilih
            ? { radius: 6, fillColor: BRAND, lineWidth: 2, lineColor: '#ffffff', enabled: true }
            : { radius: 3.5 },
        })),
        color: BRAND_TERANG,
        lineWidth: 2.5,
        fillColor: {
          linearGradient: { x1: 0, x2: 0, y1: 0, y2: 1 },
          stops: [[0, 'rgba(33,118,189,0.18)'], [1, 'rgba(33,118,189,0)']],
        },
        marker: { symbol: 'circle', lineWidth: 2, lineColor: '#ffffff', fillColor: BRAND_TERANG },
        states: { hover: { lineWidth: 2.5 } },
      }],
    };
  }, [data, skalaMax, terpilih, onPilih, tinggi, judulX, judulY]);

  return <HighchartsReact highcharts={Highcharts} options={opsi} />;
}

/**
 * Tren per bulan — batang tegak.
 *
 * Bulan BERJALAN diberi warna lebih pekat: angkanya belum utuh, dan tanpa
 * pembeda batang pendek di ujung kanan terbaca sebagai penurunan tajam
 * padahal bulannya memang baru mulai.
 */
export function GrafikTren({ data, tinggi = 144, kecil = false }) {
  const opsi = useMemo(() => {
    const akhir = data.length - 1;

    return {
      ...dasar(tinggi),
      chart: { ...dasar(tinggi).chart, type: 'column' },
      accessibility: {
        description: `Grafik jumlah permohonan per bulan selama ${data.length} bulan terakhir`,
      },
      xAxis: {
        categories: data.map((d) => d.label),
        lineColor: '#e2e8f0',
        tickLength: 0,
        labels: { style: { color: '#94a3b8', fontSize: kecil ? '10px' : '11px', fontWeight: '500' } },
      },
      yAxis: {
        title: { text: null },
        gridLineColor: '#eef2f6',
        tickAmount: 3,
        labels: { enabled: !kecil, style: { color: '#cbd5e1', fontSize: '10px' } },
      },
      series: [{
        name: 'Permohonan',
        data: data.map((d, i) => ({
          y: d.count,
          color: i === akhir ? BRAND : BRAND_TERANG,
        })),
        borderRadius: 5,
        // Batang tanpa nilai tetap perlu terbaca sebagai "nol", bukan hilang.
        pointPadding: 0.12,
        groupPadding: 0.08,
        dataLabels: {
          enabled: !kecil,
          style: { fontSize: '10px', fontWeight: '700', textOutline: 'none', color: '#475569' },
          formatter() { return angkaId(this.y); },
        },
      }],
    };
  }, [data, tinggi, kecil]);

  return <HighchartsReact highcharts={Highcharts} options={opsi} />;
}

/**
 * Peringkat — batang datar. Dipakai "Layanan Terpopuler".
 *
 * Datar, bukan tegak: nama layanan panjang ("KK - Perubahan Biodata") dan pada
 * batang tegak ia harus dimiringkan atau dipotong.
 */
export function GrafikPeringkat({ data, tinggi, kecil = false, satuan = 'permohonan', judul }) {
  const opsi = useMemo(() => {
    const t = tinggi ?? Math.max(120, data.length * (kecil ? 30 : 38));
    // `satuan` wajib bisa diganti: grafik yang sama dipakai untuk sebaran
    // RESPONDEN di rekap SKM, dan tooltip yang tetap berbunyi "204 permohonan"
    // di sana bukan sekadar janggal — ia menyebut data sebagai sesuatu yang
    // bukan dirinya.
    const d = dasar(t, satuan);

    return {
      ...d,
      chart: { ...d.chart, type: 'bar' },
      accessibility: { description: judul ?? 'Grafik jenis layanan yang paling banyak diajukan' },
      xAxis: {
        categories: data.map((x) => x.nama),
        lineWidth: 0,
        tickLength: 0,
        labels: {
          style: {
            color: '#64748b',
            fontSize: kecil ? '10px' : '11px',
            // Nama panjang dipotong dengan elipsis, bukan dibungkus — kalau
            // dibungkus, tinggi tiap baris berbeda dan batangnya tidak lagi
            // sejajar dengan namanya.
            textOverflow: 'ellipsis',
            width: kecil ? 130 : 190,
          },
        },
      },
      yAxis: {
        title: { text: null },
        gridLineColor: '#eef2f6',
        labels: { enabled: false },
        // Ruang untuk label angka di ujung batang terpanjang — tanpa ini
        // angkanya menempel ke tepi kartu, atau terpotong.
        maxPadding: 0.14,
      },
      series: [{
        name: 'Permohonan',
        data: data.map((x) => x.count),
        color: {
          linearGradient: { x1: 0, x2: 1, y1: 0, y2: 0 },
          stops: [[0, BRAND], [1, MUDA]],
        },
        borderRadius: 4,
        pointPadding: 0.14,
        groupPadding: 0.06,
        dataLabels: {
          enabled: true,
          align: 'right',
          inside: false,
          crop: false,
          overflow: 'allow',
          style: { fontSize: kecil ? '10px' : '11px', fontWeight: '700', textOutline: 'none', color: '#0f172a' },
          formatter() { return angkaId(this.y); },
        },
      }],
    };
  }, [data, tinggi, kecil, satuan, judul]);

  return <HighchartsReact highcharts={Highcharts} options={opsi} />;
}
