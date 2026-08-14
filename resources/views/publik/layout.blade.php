{{--
  Kerangka SELURUH halaman publik.

  🔴 Sengaja Blade, bukan Inertia. Port ini tanpa Inertia SSR (butuh daemon Node
  yang tidak ada di cPanel), jadi halaman Inertia sampai ke mesin pencari sebagai
  <div id="app"> kosong. Halaman publik justru yang paling butuh terbaca Google —
  karena itu isinya dirender di server di sini, dan hanya bagian yang benar-benar
  interaktif dipasang sebagai React island (lihat `resources/js/publik.jsx`).
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('judul', 'SAIBATIN - Disdukcapil Pesisir Barat')</title>
    <meta name="description" content="@yield('deskripsi', 'Portal layanan administrasi kependudukan & pencatatan sipil Kabupaten Pesisir Barat (SAIBATIN).')">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="@yield('og_tipe', 'website')">
    <meta property="og:site_name" content="Portal SAIBATIN">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="@yield('judul', 'SAIBATIN - Disdukcapil Pesisir Barat')">
    <meta property="og:description" content="@yield('deskripsi', 'Portal layanan administrasi kependudukan & pencatatan sipil Kabupaten Pesisir Barat (SAIBATIN).')">
    <meta property="og:url" content="{{ url()->current() }}">
    {{-- Bisa ditimpa per halaman (mis. tiap artikel memakai gambarnya sendiri).
         Ditulis sebagai @yield, BUKAN tag kedua di @section('kepala'): pengurai
         kartu berbagi mengambil og:image yang PERTAMA, jadi tag tambahan di
         bawah tidak akan pernah terpakai. --}}
    <meta property="og:image" content="@yield('og_gambar', url('/og-saibatin.png'))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Ikon tab peramban. `favicon.ico` bawaan Laravel sengaja DIBUANG: berkas
         itu 0 byte, dan peramban yang menemukannya berhenti mencari ikon lain
         sehingga tab tampil kosong walau icon.png sudah ada. --}}
    <link rel="icon" type="image/png" href="/icon.png">
    <link rel="apple-touch-icon" href="/icon.png">
    <meta name="theme-color" content="#1b4b72">

    {{-- Figtree = font antarmuka. Cormorant Garamond & Montserrat HANYA dipakai
         judul/subjudul carousel hero (`.carousel-font-*`) — begitulah portal
         Next.js, dan tanpa keduanya judul carousel jatuh ke serif bawaan
         peramban yang tampilannya jelas beda. --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:300,400|figtree:400,500,600,700|montserrat:300,400,500&display=swap" rel="stylesheet">

    @yield('kepala')

    {{-- 🔴 Preferensi aksesibilitas diterapkan SEBELUM body dirender.
         Kalau menunggu widget React-nya dimuat, halaman tampil sekejap dengan
         gaya normal lalu berubah — tepat pada pengguna yang paling terganggu
         oleh perubahan mendadak, dan pada perangkat lambat kedipannya lama.
         Logikanya kembar dengan `terapkanPrefs()` di `lib/a11y.js`; kalau yang
         satu diubah, ubah juga yang lain. --}}
    <script>
        (function () {
            try {
                var r = document.documentElement;
                var mentah = localStorage.getItem('saibatin-a11y');
                if (!mentah) return;

                var s = JSON.parse(mentah);
                if (!s || typeof s !== 'object') return;

                var F = [90, 100, 110, 125, 150, 175, 200];
                var i = Math.min(Math.max(s.fontIdx || 0, 0), F.length - 1);
                if (i !== 1) r.style.fontSize = F[i] + '%';

                var f = [];
                if (s.contrast) f.push('contrast(1.2)');
                if (s.grayscale) f.push('grayscale(1)');
                if (s.invert) f.push('invert(1) hue-rotate(180deg)');
                if (f.length) r.style.setProperty('--a11y-filter', f.join(' '));

                var c = {
                    'a11y-contrast': s.contrast, 'a11y-invert': s.invert,
                    'a11y-underline': s.highlightLinks, 'a11y-dyslexia': s.dyslexia,
                    'a11y-lightbg': s.lightBg, 'a11y-cursor': s.bigCursor,
                    'a11y-no-motion': s.noMotion,
                };
                for (var k in c) if (c[k]) r.classList.add(k);

                if (s.spacing > 0) r.classList.add('a11y-spacing-' + Math.min(s.spacing, 2));
            } catch (e) { /* preferensi rusak — pakai tampilan bawaan */ }
        })();
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/publik.jsx'])
</head>
<body class="antialiased">
    <a href="#konten-utama" class="skip-to-content">Lompat ke konten utama</a>

    <div data-island="Navbar" data-props='@json($navbar ?? [])'></div>

    <main id="konten-utama">
        @yield('konten')
    </main>

    @include('publik.partials.footer')

    {{-- Widget aksesibilitas (14 kontrol). Island terakhir supaya tombolnya
         tidak pernah menutupi konten saat halaman masih dimuat. Sengaja hanya
         di situs publik — dashboard petugas punya UI padat yang akan tertimpa
         tombol melayangnya. --}}
    <div data-island="WidgetAksesibilitas"></div>

    {{-- Pencatat kunjungan. Sengaja skrip kecil, bukan island: tidak punya
         tampilan sama sekali, jadi memuat React untuknya cuma pemborosan.
         Satu ping saat halaman dibuka (menambah hitungan tampilan) + tiap 2
         menit selama tab terbuka (hanya menandai "online"). --}}
    <script>
        (function () {
            // 🔴 Token CSRF WAJIB ikut. `/api/*` portal ini dimuat di dalam grup
            // middleware `web` (autentikasinya berbasis sesi cookie), jadi POST
            // tanpa token dijawab 419 dan hitungan kunjungan tidak pernah
            // bertambah — diam-diam, karena galatnya cuma muncul di konsol.
            var token = document.querySelector('meta[name="csrf-token"]');
            var kirim = function (pv) {
                fetch('/api/kunjungan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token ? token.content : '',
                    },
                    body: JSON.stringify({ pv: pv }),
                    keepalive: pv,
                }).catch(function () {});
            };
            kirim(true);
            setInterval(function () { kirim(false); }, 120000);
        })();
    </script>
</body>
</html>
