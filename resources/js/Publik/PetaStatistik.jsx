import { useEffect, useMemo, useRef, useState } from 'react';
import 'highcharts/modules/map';
import HighchartsReact from 'highcharts-react-official';
import Highcharts, { dasar } from '@/lib/grafik-dasar';
import { BATAS_TAMPILAN, GEOJSON_KECAMATAN, GEOJSON_TETANGGA, KECAMATAN_PETA, keBujurLintang } from '@/lib/peta-wilayah';
import {
  GARIS_DARAT_TETANGGA,
  LABEL_KECAMATAN,
  LABEL_TETANGGA,
  SKALA_WARNA,
  WARNA_DARAT_TETANGGA,
  WARNA_LAUT,
  WARNA_SOROT,
  WARNA_TANPA_DATA,
  fmtUkuran,
  namaCantik,
  nilaiUkuran,
  normalNama,
  warnaUntuk,
} from '@/lib/peta-label';

/**
 * Island peta choropleth kecamatan (Highcharts Maps) — halaman `/media/gis`.
 * Port dari portal Next.js saudara (components/statistik/grafik-statistik.tsx).
 *
 * 🔴 TIDAK BOLEH BOLONG. Tiga lapis selalu ada: laut (latar), daratan
 * tetangga (seri abu-abu tanpa interaksi), dan SELURUH kecamatan (`allAreas`);
 * yang tanpa data berwarna pucat "belum ada data", tidak pernah dihilangkan.
 *
 * Jebakan yang sudah ditemui di portal saudara — jangan dibuang:
 *  - `plotOptions.series.states.inactive.opacity = 1`: bawaan Highcharts
 *    meredupkan seri lain (0,2) saat satu disorot → label pudar tak terbaca.
 *  - Highcharts Maps TIDAK membaca tinggi CSS wadahnya (jatuh ke 400 px) →
 *    tinggi diukur ResizeObserver lalu diberikan eksplisit.
 *  - Sorotan kecamatan terpilih = seri `mapline` tersendiri; borderColor per
 *    titik ditimpa gaya hover.
 *  - Huruf label berukuran piksel, peta berskala → garis penunjuk dihitung
 *    dari skala fit, bukan dari satuan peta.
 */
export default function PetaStatistik({ kecamatan = [], ukuran = 'penduduk', satuan = 'jiwa', dipilih = null, hrefUntuk = {} }) {
  const wadah = useRef(null);
  const [ukuranPx, setUkuranPx] = useState({ w: 0, h: 0 });

  useEffect(() => {
    const el = wadah.current;
    if (!el) return undefined;
    const ukur = () => {
      const r = el.getBoundingClientRect();
      setUkuranPx({ w: Math.round(r.width), h: Math.round(r.height) });
    };
    ukur();
    const ro = new ResizeObserver(ukur);
    ro.observe(el);
    return () => ro.disconnect();
  }, []);

  const sempit = ukuranPx.w > 0 && ukuranPx.w < 560;

  /*
   * Sorot dari luar peta: arahkan kursor ke baris peringkat / tabel kecamatan
   * (elemen ber-atribut `data-sorot-kode`, dirender Blade) → kecamatan itu
   * menyala di peta lengkap dengan tooltip-nya. Didelegasikan di `document`
   * supaya daftar peringkat tetap HTML biasa dari server.
   */
  const petaRef = useRef(null);
  useEffect(() => {
    const kodeDari = (t) => (t instanceof Element ? t.closest('[data-sorot-kode]')?.dataset.sorotKode : undefined) ?? null;
    const titikKec = (kode) => {
      const chart = petaRef.current?.chart;
      const seri = chart?.series.find((x) => x.name === 'Kecamatan');
      return { chart, seri, titik: kode ? seri?.points.find((p) => p.kode === kode) : undefined };
    };
    const lepas = () => {
      const { chart, seri } = titikKec(null);
      seri?.points.forEach((p) => p.setState(''));
      chart?.tooltip.hide(0);
    };
    const masuk = (e) => {
      const kode = kodeDari(e.target);
      if (!kode) return;
      const { chart, titik } = titikKec(kode);
      if (!chart || !titik) return;
      lepas();
      titik.setState('hover');
      chart.tooltip.refresh(titik);
    };
    const keluar = (e) => {
      const dari = kodeDari(e.target);
      if (dari && kodeDari(e.relatedTarget) !== dari) lepas();
    };
    document.addEventListener('mouseover', masuk);
    document.addEventListener('mouseout', keluar);
    return () => {
      document.removeEventListener('mouseover', masuk);
      document.removeEventListener('mouseout', keluar);
    };
  }, []);

  const options = useMemo(() => {
    const perNama = new Map(kecamatan.map((k) => [normalNama(k.nama), k]));
    const nilai = kecamatan.map((k) => nilaiUkuran(k, ukuran)).filter((n) => n !== null && n !== undefined);
    const min = Math.min(...nilai);
    const maks = Math.max(...nilai);
    const teks = (n) => (n === null || n === undefined ? 'belum ada data' : `${fmtUkuran(n, ukuran)} ${satuan}`);
    const skala = Math.min(ukuranPx.w / 1000, ukuranPx.h / 1000) || 0.5;

    const dataKec = KECAMATAN_PETA.map((g) => {
      const k = perNama.get(normalNama(g.nama));
      const n = k ? nilaiUkuran(k, ukuran) : null;
      return {
        nama: g.nama,
        kode: k?.kode ?? null,
        label: namaCantik(g.nama),
        teks: teks(n),
        color: n === null || n === undefined ? WARNA_TANPA_DATA : warnaUntuk(n, min, maks),
        value: n,
      };
    });

    const titikLabel = KECAMATAN_PETA.map((g) => {
      const k = perNama.get(normalNama(g.nama));
      const n = k ? nilaiUkuran(k, ukuran) : null;
      const pos = LABEL_KECAMATAN[g.nama] ?? { x: g.pusat[0], y: g.pusat[1] };
      const [lon, lat] = keBujurLintang(pos.x, pos.y - 6);
      const gelap = !pos.luar && n !== null && n !== undefined && SKALA_WARNA.indexOf(warnaUntuk(n, min, maks)) >= 3;
      return {
        lon,
        lat,
        dataLabels: {
          align: pos.luar ? 'left' : 'center',
          x: 0,
          y: sempit ? 6 : pos.luar ? 12 : 10,
          format: sempit
            ? `<span style="font-weight:700;font-size:10px">${namaCantik(g.nama)}</span>`
            : `<span style="font-weight:700;font-size:12.5px">${namaCantik(g.nama)}</span><br/>` +
              `<span style="font-weight:600;font-size:11px;fill:${gelap ? '#e3eefa' : '#334155'}">${teks(n)}</span>`,
          style: {
            color: gelap ? '#ffffff' : '#0f172a',
            textOutline: gelap ? '2.5px #12324d' : '3px #ffffff',
            fontWeight: 'normal',
          },
        },
      };
    });

    const garis = KECAMATAN_PETA.filter((g) => LABEL_KECAMATAN[g.nama]?.luar).map((g) => {
      const pos = LABEL_KECAMATAN[g.nama];
      const pxNama = namaCantik(g.nama).length * (sempit ? 5.9 : 7.4);
      const pxNilai = sempit ? 0 : 13 * 6.2;
      const lebar = (Math.max(pxNama, pxNilai) + 5) / skala;
      return {
        geometry: {
          type: 'LineString',
          coordinates: [keBujurLintang(pos.x + lebar, pos.y - 6 + (sempit ? 0 : 6 / skala)), keBujurLintang(g.pusat[0], g.pusat[1])],
        },
      };
    });

    const titikPusat = KECAMATAN_PETA.filter((g) => LABEL_KECAMATAN[g.nama]?.luar).map((g) => {
      const [lon, lat] = keBujurLintang(g.pusat[0], g.pusat[1]);
      return { lon, lat };
    });

    const namaDipilih = kecamatan.find((k) => k.kode === dipilih)?.nama;
    const fiturDipilih = namaDipilih
      ? GEOJSON_KECAMATAN.features.find((f) => f.properties.nama === normalNama(namaDipilih))
      : undefined;

    const [barat, selatan, timur, utara] = BATAS_TAMPILAN;

    return {
      ...dasar,
      chart: { ...dasar.chart, backgroundColor: WARNA_LAUT, height: ukuranPx.h || undefined, spacing: [0, 0, 0, 0], animation: false },
      mapView: {
        projection: { name: 'WebMercator' },
        fitToGeometry: { type: 'MultiPoint', coordinates: [[barat, selatan], [timur, utara]] },
        padding: 0,
      },
      mapNavigation: {
        enabled: true,
        enableMouseWheelZoom: false,
        enableDoubleClickZoomTo: false,
        buttonOptions: { align: 'right', verticalAlign: 'bottom', theme: { fill: '#ffffff', stroke: '#cbd5e1', 'stroke-width': 1, r: 6 } },
      },
      tooltip: { ...dasar.tooltip, headerFormat: '', pointFormat: '<b>{point.label}</b><br/>{point.teks}' },
      plotOptions: { series: { animation: false, states: { inactive: { opacity: 1 } } } },
      series: [
        {
          type: 'map',
          name: 'Tetangga',
          mapData: GEOJSON_TETANGGA,
          data: [],
          allAreas: true,
          nullColor: WARNA_DARAT_TETANGGA,
          borderColor: GARIS_DARAT_TETANGGA,
          borderWidth: 0.8,
          enableMouseTracking: false,
        },
        {
          type: 'map',
          name: 'Kecamatan',
          mapData: GEOJSON_KECAMATAN,
          joinBy: ['nama', 'nama'],
          data: dataKec,
          allAreas: true,
          nullColor: WARNA_TANPA_DATA,
          borderColor: '#ffffff',
          borderWidth: 1.2,
          cursor: 'pointer',
          // Sorot = sedikit LEBIH GELAP + garis tepi tegas. Mencerahkan (brightness +)
          // membuat kelas warna terterang nyaris putih — terlihat seperti hilang.
          states: { hover: { brightness: -0.12, borderColor: '#1e293b', borderWidth: 2 } },
          point: {
            events: {
              click() {
                if (this.kode && hrefUntuk[this.kode]) window.location.href = hrefUntuk[this.kode];
              },
            },
          },
        },
        {
          type: 'mapline',
          name: 'Sorotan',
          data: fiturDipilih ? [{ geometry: fiturDipilih.geometry }] : [],
          color: WARNA_SOROT,
          lineWidth: 3.5,
          enableMouseTracking: false,
        },
        { type: 'mapline', name: 'Garis label', data: garis, color: '#334155', lineWidth: 1, enableMouseTracking: false },
        {
          type: 'mappoint',
          name: 'Pusat',
          data: titikPusat,
          color: '#1e293b',
          marker: { radius: 3.5, lineColor: '#ffffff', lineWidth: 1.5, symbol: 'circle' },
          dataLabels: { enabled: false },
          enableMouseTracking: false,
        },
        {
          type: 'mappoint',
          name: 'Label kecamatan',
          data: titikLabel,
          marker: { enabled: false },
          dataLabels: { enabled: true, allowOverlap: true, crop: false, overflow: 'allow' },
          enableMouseTracking: false,
        },
        {
          type: 'mappoint',
          name: 'Label tetangga',
          data: LABEL_TETANGGA.map((l) => {
            const [lon, lat] = keBujurLintang(l.x, l.y);
            return { lon, lat, name: l.teks, dataLabels: l.laut ? { style: { color: '#7fa3bf', textOutline: 'none' } } : undefined };
          }),
          marker: { enabled: false },
          dataLabels: {
            enabled: true,
            format: '{point.name}',
            allowOverlap: true,
            style: { color: '#6b7772', fontStyle: 'italic', fontWeight: '500', fontSize: '11.5px', textOutline: '2px #e2e5e3' },
          },
          enableMouseTracking: false,
        },
      ],
    };
  }, [kecamatan, ukuran, satuan, dipilih, hrefUntuk, ukuranPx, sempit]);

  return (
    <div ref={wadah} className="h-full w-full">
      {ukuranPx.h > 0 && <HighchartsReact ref={petaRef} highcharts={Highcharts} constructorType="mapChart" options={options} />}
    </div>
  );
}
