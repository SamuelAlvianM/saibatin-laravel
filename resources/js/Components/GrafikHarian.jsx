import { useMemo } from 'react';
import Highcharts from 'highcharts';
// Modul aksesibilitas: grafik aslinya punya role="img" + aria-label, jadi
// jangan turun kualitas di sini. Ikut chunk yang sama, bukan bundel utama.
import 'highcharts/modules/accessibility';
import HighchartsReact from 'highcharts-react-official';

/**
 * Grafik "Permohonan per Tanggal · 30 Hari Terakhir".
 *
 * Padanan `components/dashboard/chart-harian.tsx` di portal Next.js — di sana
 * seluruh geometri SVG (kurva Catmull-Rom, crosshair, chip nilai) ditulis
 * tangan; di sini Highcharts yang mengerjakannya, atas permintaan user.
 * Bentuk akhirnya sengaja dibuat sama: kurva mulus biru tua di atas area
 * gradien, garis rata-rata putus-putus berlabel, penanda di titik hari ini,
 * dan label tanggal tiap 5 hari.
 *
 * 🔴 Highcharts hanya berjalan di BROWSER — tidak menambah kebutuhan Node di
 * server. Bundelnya ikut `npm run build` seperti komponen lain, jadi keputusan
 * "tanpa Inertia SSR / tanpa daemon Node di cPanel" tetap utuh.
 */

const GARIS = '#1b4b72';
const AREA = '#2176bd';

export default function GrafikHarian({ harian }) {
  const opsi = useMemo(() => {
    const nilai = harian.map((h) => h.count);
    const rata = nilai.reduce((a, b) => a + b, 0) / Math.max(1, nilai.length);

    return {
      chart: {
        type: 'areaspline',
        height: 176,
        backgroundColor: 'transparent',
        spacing: [8, 4, 0, 0],
        style: { fontFamily: 'inherit' },
      },
      title: { text: null },
      credits: { enabled: false },
      legend: { enabled: false },
      accessibility: {
        description: 'Grafik jumlah permohonan per tanggal, 30 hari terakhir',
      },
      xAxis: {
        categories: harian.map((h) => h.label),
        // Label tiap 5 hari — sama seperti aslinya, supaya 30 tanggal tidak
        // saling menindih di layar sempit.
        tickInterval: 5,
        tickLength: 0,
        lineColor: '#e2e8f0',
        crosshair: { width: 1, color: 'rgba(33,118,189,0.3)' },
        labels: { style: { color: '#94a3b8', fontSize: '10px', fontWeight: '500' } },
      },
      yAxis: {
        title: { text: null },
        gridLineColor: '#eef2f6',
        tickAmount: 3,
        labels: { enabled: false },
        plotLines: [{
          value: rata,
          color: AREA,
          width: 1,
          dashStyle: 'Dash',
          zIndex: 3,
          label: {
            text: `rata² ${rata.toFixed(1)}`,
            align: 'right',
            x: -2,
            y: -4,
            style: { color: '#1d4ed8', fontSize: '9.6px', fontWeight: '600' },
          },
        }],
      },
      tooltip: {
        backgroundColor: '#0f172a',
        borderWidth: 0,
        borderRadius: 8,
        shadow: false,
        style: { color: '#ffffff', fontSize: '10.9px', fontWeight: '600' },
        formatter() {
          return `${this.key} · ${this.y} permohonan`;
        },
      },
      plotOptions: {
        areaspline: {
          lineWidth: 2.25,
          color: GARIS,
          fillColor: {
            linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
            stops: [[0, 'rgba(33,118,189,0.28)'], [1, 'rgba(33,118,189,0)']],
          },
          marker: {
            enabled: false,
            fillColor: AREA,
            lineColor: '#ffffff',
            lineWidth: 2,
            radius: 4,
            states: { hover: { enabled: true, radius: 5 } },
          },
          states: { hover: { lineWidth: 2.25, halo: { size: 0 } } },
        },
      },
      series: [{
        name: 'Permohonan',
        data: harian.map((h, i) => ({
          y: h.count,
          // Titik terakhir = hari ini, ditandai permanen seperti di aslinya.
          marker: i === harian.length - 1 ? { enabled: true } : undefined,
        })),
      }],
    };
  }, [harian]);

  return <HighchartsReact highcharts={Highcharts} options={opsi} />;
}
