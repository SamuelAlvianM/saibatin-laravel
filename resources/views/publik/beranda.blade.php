@extends('publik.layout')

@section('judul', 'SAIBATIN — Portal Layanan Kependudukan Kabupaten Pesisir Barat')
@section('deskripsi', $hero['subheading'])

@section('konten')

{{--
  Beranda — port `app/page.tsx` portal Next.js.

  Susunan seksinya sama persis: Hero → Statistik → Alur Layanan → Berita
  Terbaru → Profil Instansi → Relasi Terkait. "Menu Layanan Populer" TIDAK
  ikut: di portal Next.js seksi itu sudah dikomentari, jadi menghidupkannya di
  sini justru membuat kedua situs berbeda.

  Yang jadi island hanya tiga: Carousel, Statistik, dan tab Profil. Sisanya
  HTML dari server — teks beranda termasuk yang paling perlu terbaca Google,
  dan tidak ada alasan mengirim React untuk menampilkan tiga kartu statis.
--}}

{{-- ── Hero ──────────────────────────────────────────────────────────────── --}}
<section class="relative overflow-hidden">
    <div class="absolute inset-0" style="background: linear-gradient(135deg, #143a5c 0%, #1b4b72 45%, #2176bd 100%)"></div>
    {{-- Pola titik halus + dua glow aksen. --}}
    <div class="absolute inset-0 opacity-[0.07]"
         style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 28px 28px"></div>
    <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-sky-400/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-40 -left-20 h-96 w-96 rounded-full bg-blue-300/10 blur-3xl"></div>

    <div class="container relative mx-auto px-4 py-12 md:px-8 md:py-16 lg:px-16">
        <div class="flex flex-col items-center gap-10 lg:flex-row">
            <div class="w-full text-white lg:flex-1">
                <h1 class="masuk-naik mt-5 text-3xl font-bold leading-tight tracking-tight md:text-4xl lg:text-[2.75rem]"
                    style="animation-delay: 80ms">
                    {{ $hero['heading'] }}
                </h1>

                <p class="masuk-naik mt-4 max-w-xl text-base leading-relaxed text-white/80 md:text-lg"
                   style="animation-delay: 160ms">
                    {{ $hero['subheading'] }}
                </p>

                {{-- Pencarian layanan. Sengaja <form method="get"> biasa, bukan
                     island: perilakunya identik dengan aslinya (menuju pemilih
                     layanan membawa kata kunci) tanpa satu baris JS pun, jadi
                     tetap jalan sebelum bundel dimuat. --}}
                <form action="/user/pengajuan/baru" method="get"
                      class="masuk-naik mt-7 flex max-w-xl items-center gap-2 rounded-2xl bg-white p-2 shadow-2xl shadow-blue-950/30"
                      style="animation-delay: 240ms">
                    <x-ikon nama="cari" class="ml-3 h-5 w-5 shrink-0 text-slate-400" />
                    <input type="search" name="q" autocomplete="off"
                           placeholder="{{ $hero['searchPlaceholder'] }}"
                           aria-label="Cari layanan"
                           class="min-w-0 flex-1 bg-transparent text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none md:text-base">
                    <button type="submit"
                            class="flex shrink-0 items-center gap-1.5 rounded-xl bg-gradient-to-r from-[#1b4b72] to-[#2176bd] px-5 py-3 text-sm font-semibold text-white transition-opacity hover:opacity-90">
                        Cari
                        <x-ikon nama="panah-kanan" class="h-4 w-4" />
                    </button>
                </form>

                {{-- Pintasan layanan tersering. --}}
                <div class="masuk-naik mt-5 flex flex-wrap gap-2" style="animation-delay: 320ms">
                    @foreach ([
                        'Akta Kelahiran', 'KTP Elektronik', 'Kartu Keluarga',
                        'KIA', 'Pindah Datang', 'Semua Layanan',
                    ] as $pintasan)
                        <a href="{{ $pintasan === 'Semua Layanan' ? '/user/pengajuan/baru' : '/user/pengajuan/baru?q='.urlencode($pintasan) }}"
                           class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-3.5 py-1.5 text-xs font-medium text-white/90 backdrop-blur-sm transition-all hover:bg-white hover:text-[#1b4b72]">
                            {{ $pintasan }}
                        </a>
                    @endforeach
                </div>

                <div class="masuk-naik mt-8 flex flex-wrap items-center gap-5 text-xs text-white/70" style="animation-delay: 450ms">
                    @foreach ([
                        ['Resmi & Aman', 'perisai-centang'],
                        ['Proses Cepat', 'jam'],
                        ['Gratis', 'lencana-centang'],
                    ] as [$janji, $ikonJanji])
                        <span class="inline-flex items-center gap-1.5">
                            <x-ikon :nama="$ikonJanji" class="h-4 w-4 text-emerald-300" />
                            {{ $janji }}
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- Carousel: satu-satunya bagian hero yang benar-benar butuh JS. --}}
            <div class="w-full shrink-0 lg:w-[52%]">
                <div class="h-[280px] overflow-hidden rounded-3xl shadow-2xl shadow-blue-950/40 ring-1 ring-white/20 sm:h-[380px] md:h-[460px] lg:h-[520px]">
                    <div data-island="Carousel" data-props='@json(['slides' => $slides])' class="h-full"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Lengkung pemisah ke seksi berikutnya. --}}
    <svg class="relative block w-full text-white" viewBox="0 0 1440 48" fill="currentColor"
         preserveAspectRatio="none" aria-hidden="true">
        <path d="M0,48 C360,0 1080,0 1440,48 L1440,48 L0,48 Z" />
    </svg>
</section>

{{-- ── Statistik layanan & demografi ─────────────────────────────────────
     Island penuh: angkanya dari `/api/stats`, dan isinya memang tidak berguna
     bagi mesin pencari (angka tanpa konteks). Peta Leaflet & tabel rincian
     dimuat malas di dalamnya. --}}
<section class="relative bg-white pb-10 pt-2">
    <div class="container mx-auto px-4 md:px-8 lg:px-16">
        <div data-island="Statistik"></div>
    </div>
</section>

{{-- ── Alur layanan 3 langkah ────────────────────────────────────────────── --}}
<section class="relative bg-slate-50 py-14">
    <div class="container mx-auto px-4 md:px-8 lg:px-16">
        <div class="masuk-naik mx-auto mb-10 max-w-2xl text-center">
            <span class="text-[0.7rem] font-bold uppercase tracking-widest text-brand">Cara Kerja</span>
            <h2 class="mt-2 text-2xl font-bold text-slate-900 md:text-3xl">Urus Dokumen dalam 3 Langkah</h2>
            <p class="mt-3 text-sm text-slate-500 md:text-base">
                Tanpa antre di kantor — semua proses permohonan dilakukan online dan dapat dipantau kapan saja.
            </p>
        </div>

        <div class="relative grid grid-cols-1 gap-5 md:grid-cols-3">
            {{-- Garis penghubung antar langkah (desktop). --}}
            <div class="absolute left-[16%] right-[16%] top-[52px] hidden h-px bg-gradient-to-r from-transparent via-slate-300 to-transparent md:block"></div>

            @foreach ([
                ['Daftar / Masuk', 'Buat akun dengan NIK & nomor KK Anda, lalu tunggu verifikasi petugas.', 'tambah-pengguna'],
                ['Ajukan Permohonan', 'Pilih layanan, isi formulir online, dan unggah berkas persyaratan.', 'berkas-tulis'],
                ['Pantau & Terima Hasil', 'Cek status di halaman riwayat — pemberitahuan dikirim ke email Anda.', 'paket-centang'],
            ] as $i => [$judulLangkah, $ketLangkah, $ikonLangkah])
                <div class="masuk-naik relative rounded-2xl border border-slate-200/70 bg-white p-6 text-center shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                     style="animation-delay: {{ $i * 120 }}ms">
                    <div class="relative inline-flex">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-[#1b4b72] to-[#2176bd] text-white shadow-lg shadow-blue-900/20">
                            <x-ikon :nama="$ikonLangkah" class="h-7 w-7" />
                        </div>
                        <span class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-amber-400 text-xs font-bold text-[#1b4b72] shadow">
                            {{ $i + 1 }}
                        </span>
                    </div>
                    <h3 class="mt-4 font-semibold text-slate-900">{{ $judulLangkah }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $ketLangkah }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-8 text-center">
            <a href="/user/pengajuan/baru"
               class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#1b4b72] to-[#2176bd] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-900/20 transition-all hover:-translate-y-0.5 hover:opacity-90">
                Mulai Ajukan Permohonan
                <x-ikon nama="panah-kanan" class="h-4 w-4" />
            </a>
        </div>
    </div>
</section>

{{-- ── Berita terbaru ────────────────────────────────────────────────────── --}}
@if ($berita->isNotEmpty())
    <section class="py-6">
        <div class="container mx-auto px-4 md:px-8 lg:px-16">
            <div class="mb-6 flex items-end justify-between gap-4">
                <div>
                    <p class="mb-1 text-[0.66rem] font-bold uppercase tracking-widest text-brand">Media Informasi</p>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Berita Terbaru</h2>
                </div>
                <a href="/media/berita" class="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-brand transition-all hover:gap-2.5">
                    Lihat Semua <x-ikon nama="panah-kanan" class="h-4 w-4" />
                </a>
            </div>

            {{-- Dirender server, bukan diambil klien seperti di portal Next.js:
                 tautan berita termasuk yang paling berguna diindeks, dan di sana
                 ia tidak pernah ikut ke HTML. --}}
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($berita as $i => $b)
                    <a href="/media/berita/{{ $b->slug }}"
                       class="masuk-naik group flex flex-col overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-brand/30 hover:shadow-lg"
                       style="animation-delay: {{ $i * 90 }}ms">
                        <div class="relative aspect-[16/10] overflow-hidden bg-slate-100">
                            @if ($b->gambar)
                                <img src="{{ $b->gambar }}" alt="{{ $b->judul }}" loading="lazy"
                                     class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-slate-300">
                                    <x-ikon nama="koran" class="h-9 w-9" />
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                            @if ($b->kategori)
                                <span class="mb-1.5 text-[0.66rem] font-bold uppercase tracking-widest text-brand">{{ $b->kategori }}</span>
                            @endif
                            <h3 class="line-clamp-2 font-bold leading-snug text-slate-900 transition-colors group-hover:text-brand">
                                {{ $b->judul }}
                            </h3>
                            @if ($b->ringkasan)
                                <p class="mt-2 line-clamp-2 flex-1 text-sm leading-relaxed text-slate-500">{{ $b->ringkasan }}</p>
                            @endif
                            <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                                <span class="text-xs text-slate-400">
                                    {{ $b->created_at?->translatedFormat('j F Y') }}
                                </span>
                                <x-ikon nama="panah-kanan" class="h-4 w-4 text-slate-300 transition-all group-hover:translate-x-0.5 group-hover:text-brand" />
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ── Profil instansi (tab) ─────────────────────────────────────────────── --}}
<section class="relative overflow-hidden border-t border-slate-100 bg-white py-14">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute left-0 top-0 h-80 w-full bg-gradient-to-b from-slate-50/80 to-transparent"></div>
        <div class="absolute bottom-0 right-0 h-96 w-96 rounded-full bg-brand/5 blur-3xl"></div>
    </div>

    <div class="container relative z-10 mx-auto max-w-5xl px-4 md:px-8 lg:px-16">
        <div class="mb-4 flex flex-col items-center text-center">
            <p class="mb-2 text-[0.65rem] font-bold uppercase tracking-widest text-slate-400">Profil Instansi</p>
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">
                Dinas Kependudukan &amp; Pencatatan Sipil
            </h2>
            <div class="mt-4 h-0.5 w-16 rounded-full bg-gradient-to-r from-brand to-brand/60"></div>
        </div>

        {{-- Isi tiap tab dikirim sebagai props (bukan diambil ulang klien),
             sehingga tab pertama langsung terisi begitu island hidup. --}}
        <div data-island="ProfilTabs" data-props='@json(['isi' => $profil])'></div>
    </div>
</section>

{{-- ── Relasi terkait ────────────────────────────────────────────────────── --}}
<section class="py-12 md:py-16">
    <div class="container mx-auto px-4 md:px-8 lg:px-16">
        <div class="mb-8 flex items-center justify-center gap-3">
            <span class="h-[3px] w-8 rounded-full" style="background: #ffed4a"></span>
            <h2 class="text-center text-lg font-bold uppercase tracking-wider text-slate-800 md:text-xl">Relasi Terkait</h2>
            <span class="h-[3px] w-8 rounded-full" style="background: #ffed4a"></span>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-8">
            @foreach ([
                ['Dinas Kependudukan dan Pencatatan Sipil', '/relasi/dinas.png', 'https://pesisirbaratkab.go.id'],
                ['Kementerian Dalam Negeri', '/relasi/kemendagri.png', 'https://kemendagri.go.id'],
                ['LPSE Pesisir Barat', '/relasi/lpse.png', 'https://lpse.pesisirbaratkab.go.id'],
                ['LAPOR!', '/relasi/lapor.png', 'https://www.lapor.go.id'],
                ['Ombudsman RI', '/relasi/ombudsman.jpg', 'https://ombudsman.go.id'],
                ['KemenPAN-RB', '/relasi/panrb.png', 'https://menpan.go.id'],
                ['Kota Tanpa Kumuh', '/relasi/kotaku.png', 'https://kotaku.pu.go.id'],
                ['SIAPP', '/relasi/siapp.svg', '#'],
            ] as [$namaRelasi, $logo, $tautan])
                <a href="{{ $tautan }}" target="_blank" rel="noopener noreferrer" title="{{ $namaRelasi }}"
                   class="group flex flex-col items-center gap-2">
                    <div class="relative h-14 w-24 opacity-70 transition-all duration-300 group-hover:scale-110 group-hover:opacity-100">
                        <img src="{{ $logo }}" alt="{{ $namaRelasi }}" loading="lazy" class="h-full w-full object-contain">
                    </div>
                    <span class="max-w-[8rem] text-center text-xs text-slate-500 group-hover:text-slate-700">{{ $namaRelasi }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

@endsection
