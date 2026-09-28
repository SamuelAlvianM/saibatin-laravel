import { useMemo } from 'react';
import HighchartsReact from 'highcharts-react-official';
import Highcharts, { BIRU, KUNING, REDUP, dasar, fmt, pct } from '@/lib/grafik-dasar';

/** Island kolom jumlah penduduk per periode data; periode yang sedang dilihat kuning. */
export default function GrafikTren({ tren = [], kini = '' }) {
  const options = useMemo(
    () => ({
      ...dasar,
      chart: { ...dasar.chart, type: 'column', height: 240 },
      xAxis: {
        categories: tren.map((t) => t.label),
        tickLength: 0,
        lineColor: '#e2e8f0',
        labels: { style: { color: REDUP, fontSize: '11px', fontWeight: '600' } },
      },
      yAxis: { min: 0, title: { text: undefined }, gridLineColor: '#eef1f3', gridLineDashStyle: 'Dash', labels: { style: { color: REDUP } } },
      plotOptions: {
        column: {
          borderRadius: 5,
          borderWidth: 0,
          maxPointWidth: 72,
          dataLabels: {
            enabled: true,
            style: { fontSize: '11.5px', fontWeight: '600', color: '#0f172a', textOutline: 'none' },
            formatter() {
              return fmt(this.y ?? 0);
            },
          },
        },
      },
      tooltip: {
        ...dasar.tooltip,
        formatter() {
          const i = this.index ?? this.point?.index ?? 0;
          const lalu = i > 0 ? tren[i - 1] : null;
          const d = lalu ? (this.y ?? 0) - lalu.penduduk : null;
          const ubah = d === null ? 'Periode awal' : `${d >= 0 ? '+' : ''}${fmt(d)} (${d >= 0 ? '+' : ''}${pct(d, lalu.penduduk)}%)`;
          return `<b>${this.category ?? this.key}</b><br/>${fmt(this.y ?? 0)} jiwa<br/><span style="opacity:.8">${ubah}</span>`;
        },
      },
      series: [{ type: 'column', name: 'Penduduk', data: tren.map((t) => ({ y: t.penduduk, color: t.label === kini ? KUNING : BIRU })) }],
    }),
    [tren, kini],
  );

  return <HighchartsReact highcharts={Highcharts} options={options} />;
}
