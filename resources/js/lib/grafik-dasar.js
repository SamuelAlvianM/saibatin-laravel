/**
 * Setelan bersama grafik Highcharts halaman GIS (island Peta/Grafik*).
 * Satu tempat supaya ejaan angka (titik ribuan, koma desimal, "rb"/"jt") dan
 * gaya tooltip tidak berbeda antar grafik.
 */
import Highcharts from 'highcharts';

Highcharts.setOptions({
  lang: { thousandsSep: '.', decimalPoint: ',', numericSymbols: [' rb', ' jt', ' M', ' T'] },
});

export const FONT = 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
export const BIRU = '#1b4b72';
export const BIRU_MUDA = '#2176bd';
export const KUNING = '#f5b301';
export const REDUP = '#64748b';

export const dasar = {
  credits: { enabled: false },
  title: { text: undefined },
  accessibility: { enabled: false },
  legend: { enabled: false },
  chart: { backgroundColor: 'transparent', style: { fontFamily: FONT } },
  tooltip: {
    backgroundColor: '#1f2937',
    borderWidth: 0,
    borderRadius: 8,
    shadow: false,
    style: { color: '#ffffff', fontSize: '12px' },
    useHTML: true,
  },
};

export const fmt = (n) => Highcharts.numberFormat(n, 0);
export const pct = (n, dari) => (dari ? Highcharts.numberFormat((n / dari) * 100, 1) : '0');

export default Highcharts;
