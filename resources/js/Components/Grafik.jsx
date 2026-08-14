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

/** Dasar yang sama untuk semua grafik supaya tidak ada gaya yang menyimpang. */
function dasar(tinggi) {
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
      backgroundColor: 'rgba(255,255,255,0.97)',
      borderColor: 'rgba(33,118,189,0.2)',
      borderRadius: 10,
      shadow: false,
      style: { fontSize: '12px' },
      // Pemisah ribuan Indonesia — bawaan Highcharts memakai spasi.
      formatter() {
        return `<b>${this.key}</b><br>${angkaId(this.y)} permohonan`;
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
export function GrafikPeringkat({ data, tinggi, kecil = false }) {
  const opsi = useMemo(() => {
    const t = tinggi ?? Math.max(120, data.length * (kecil ? 30 : 38));
    const d = dasar(t);

    return {
      ...d,
      chart: { ...d.chart, type: 'bar' },
      accessibility: { description: 'Grafik jenis layanan yang paling banyak diajukan' },
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
  }, [data, tinggi, kecil]);

  return <HighchartsReact highcharts={Highcharts} options={opsi} />;
}
