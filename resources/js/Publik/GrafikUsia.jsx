import { useMemo } from 'react';
import HighchartsReact from 'highcharts-react-official';
import Highcharts, { BIRU, KUNING, REDUP, dasar, fmt, pct } from '@/lib/grafik-dasar';

/** Island kolom kelompok umur lima tahunan — anak kuning, produktif biru, lansia biru tua. */
export default function GrafikUsia({ butir = [], jumlah = 0 }) {
  const options = useMemo(
    () => ({
      ...dasar,
      chart: { ...dasar.chart, type: 'column', height: 280 },
      xAxis: {
        categories: butir.map((b) => b.label),
        title: { text: 'Umur (tahun)', style: { color: REDUP } },
        tickLength: 0,
        lineColor: '#e2e8f0',
        labels: { style: { color: REDUP, fontSize: '11px' } },
      },
      yAxis: { min: 0, title: { text: undefined }, gridLineColor: '#eef1f3', gridLineDashStyle: 'Dash', labels: { style: { color: REDUP } } },
      plotOptions: { column: { borderRadius: 4, borderWidth: 0, pointPadding: 0.06, groupPadding: 0.08 } },
      tooltip: {
        ...dasar.tooltip,
        formatter() {
          return `<b>${this.category ?? this.key} tahun</b><br/>${fmt(this.y ?? 0)} jiwa · ${pct(this.y ?? 0, jumlah)}%`;
        },
      },
      series: [{ type: 'column', name: 'Jiwa', data: butir.map((b, i) => ({ y: b.nilai, color: i < 3 ? KUNING : i < 13 ? BIRU : '#12324d' })) }],
    }),
    [butir, jumlah],
  );

  return <HighchartsReact highcharts={Highcharts} options={options} />;
}
