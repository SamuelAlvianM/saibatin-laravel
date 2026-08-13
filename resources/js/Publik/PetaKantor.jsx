import { useEffect } from 'react';
import { MapContainer, Marker, TileLayer, useMap } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

/**
 * Peta lokasi kantor Disdukcapil — port `components/landingpage/office-map.tsx`.
 *
 * 🔴 Dimuat MALAS oleh `Statistik.jsx`. Leaflet menyentuh `window` saat modulnya
 * dievaluasi dan ukurannya ± 150 KB; menariknya ke bundel inti berarti setiap
 * halaman publik ikut membayarnya demi satu kartu di beranda.
 */

// Kompleks Perkantoran Pemda, Way Redak, Krui.
const LAT = -5.19361;
const LNG = 103.9425;

/** Penanda berdenyut dari divIcon + CSS (`.office-marker` di app.css). */
const penanda = L.divIcon({
  className: '',
  html: `
    <div class="office-marker">
      <span class="office-marker__pulse"></span>
      <span class="office-marker__pin">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="white"
             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
          <circle cx="12" cy="10" r="3"/>
        </svg>
      </span>
    </div>`,
  iconSize: [44, 44],
  iconAnchor: [22, 40],
});

/** Terbang halus ke kantor saat peta pertama tampil. */
function TerbangMasuk() {
  const peta = useMap();

  useEffect(() => {
    peta.setView([LAT, LNG], 11, { animate: false });
    const t = setTimeout(() => peta.flyTo([LAT, LNG], 15, { duration: 2.2, easeLinearity: 0.18 }), 250);
    return () => clearTimeout(t);
  }, [peta]);

  return null;
}

export default function PetaKantor() {
  return (
    <MapContainer center={[LAT, LNG]} zoom={11} scrollWheelZoom={false}
                  zoomControl={false} attributionControl={false}
                  style={{ height: '100%', width: '100%' }}>
      {/* CARTO Voyager: gaya bersih, gratis untuk pemakaian wajar. */}
      <TileLayer url="https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png" />
      <Marker position={[LAT, LNG]} icon={penanda} />
      <TerbangMasuk />
    </MapContainer>
  );
}
