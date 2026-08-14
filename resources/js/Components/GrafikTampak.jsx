import { Suspense, lazy, useEffect, useRef, useState } from 'react';

/**
 * Gerbang tampilan untuk grafik Highcharts.
 *
 * 🔴 Highcharts ± 300 KB. Beranda publik memuat grafiknya JAUH di bawah layar,
 * jadi menariknya ke pemuatan awal berarti setiap pengunjung — termasuk yang
 * cuma membuka satu berita — membayar 300 KB untuk sesuatu yang mungkin tidak
 * pernah dilihatnya. Di jaringan desa itu bukan angka kecil.
 *
 * Karena itu berkasnya baru di-`import()` ketika wadahnya benar-benar mendekati
 * layar. Sebelum itu yang tampil kerangka abu setinggi grafiknya — tingginya
 * sengaja sama supaya tata letak tidak melompat saat grafiknya masuk.
 *
 * ⚠️ Jangan menggantinya dengan `lazy()` polos tanpa gerbang: `lazy()` memecah
 * chunk, TAPI React tetap memuatnya begitu komponennya dirender. Memecah chunk
 * bukan berarti menunda pengunduhan (jebakan lama keluarga Next.js, journal
 * induk §5).
 */

const Grafik = {
  tren: lazy(() => import('@/Components/Grafik').then((m) => ({ default: m.GrafikTren }))),
  peringkat: lazy(() => import('@/Components/Grafik').then((m) => ({ default: m.GrafikPeringkat }))),
};

function Kerangka({ tinggi }) {
  return (
    <div className="animate-pulse rounded-xl bg-slate-100/70" style={{ height: tinggi }} aria-hidden="true" />
  );
}

export default function GrafikTampak({ jenis, tinggi = 144, ...sisa }) {
  const ref = useRef(null);
  const [terlihat, setTerlihat] = useState(false);

  useEffect(() => {
    const el = ref.current;
    if (!el) return undefined;

    // Tab tersembunyi tidak pernah memicu IntersectionObserver dengan andal,
    // dan pengguna yang mematikan animasi tidak perlu menunggu gerbang —
    // dua-duanya langsung muat saja.
    if (document.hidden) {
      setTerlihat(true);
      return undefined;
    }

    const pengamat = new IntersectionObserver(([masuk]) => {
      if (!masuk.isIntersecting) return;
      pengamat.disconnect();
      setTerlihat(true);
      // `rootMargin` positif: mulai mengunduh SEBELUM grafiknya terlihat,
      // supaya saat pengguna sampai ke sana ia sudah tergambar.
    }, { rootMargin: '320px' });

    pengamat.observe(el);
    return () => pengamat.disconnect();
  }, []);

  const Komponen = Grafik[jenis];

  return (
    <div ref={ref}>
      {terlihat && Komponen ? (
        <Suspense fallback={<Kerangka tinggi={tinggi} />}>
          <Komponen tinggi={tinggi} {...sisa} />
        </Suspense>
      ) : (
        <Kerangka tinggi={tinggi} />
      )}
    </div>
  );
}
