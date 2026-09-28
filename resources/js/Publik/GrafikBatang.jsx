import { useMemo } from 'react';
import HighchartsReact from 'highcharts-react-official';
import Highcharts, { BIRU, REDUP, dasar, fmt, pct } from '@/lib/grafik-dasar';

/**
 * Island batang horizontal satu kategori komposisi (agama, pendidikan, …) —
 * halaman `/media/gis`. `maxPadding` memberi ruang label "62.118 · 50,9%"
 * pada batang terpanjang; tanpa itu labelnya terpotong tepi grafik.
 */
export default function GrafikBatang({ butir = [], jumlah = 0, satuan = 'jiwa' }) {
  const options = useMemo(
    () => ({
      ...dasar,
      chart: { ...dasar.chart, type: 'bar', height: 34 * butir.length + 24, spacing: [4, 4, 4, 0] },
      xAxis: {
        categories: butir.map((b) => b.label),
        lineWidth: 0,
        tickLength: 0,
        labels: { style: { color: '#334155', fontSize: '12px' }, reserveSpace: true },
      },
      yAxis: { min: 0, maxPadding: 0.32, endOnTick: false, title: { text: undefined }, labels: { enabled: false }, gridLineWidth: 0 },
      plotOptions: {
        bar: {
          borderRadius: 4,
          borderWidth: 0,
          pointPadding: 0.12,
          groupPadding: 0.06,
          color: BIRU,
          dataLabels: {
            enabled: true,
            crop: false,
            overflow: 'allow',
            style: { fontSize: '11.5px', fontWeight: '600', color: '#0f172a', textOutline: 'none' },
            formatter() {
              return `${fmt(this.y ?? 0)} <span style="font-weight:400;fill:${REDUP}">· ${pct(this.y ?? 0, jumlah)}%</span>`;
            },
          },
        },
      },
      tooltip: {
        ...dasar.tooltip,
        formatter() {
          return `<b>${this.category ?? this.key}</b><br/>${fmt(this.y ?? 0)} ${satuan} · ${pct(this.y ?? 0, jumlah)}%`;
        },
      },
      series: [{ type: 'bar', name: 'Jumlah', data: butir.map((b) => b.nilai) }],
    }),
    [butir, jumlah, satuan],
  );

  return <HighchartsReact highcharts={Highcharts} options={options} />;
}
