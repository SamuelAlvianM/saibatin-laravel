@extends('publik.layout')

@section('judul', 'Berita & Informasi — SAIBATIN Disdukcapil Pesisir Barat')
@section('deskripsi', 'Kabar dan informasi terkini seputar layanan Disdukcapil Kabupaten Pesisir Barat.')

@section('konten')

{{--
  Daftar berita — port `app/media/berita/page.tsx`.

  Seluruhnya dirender server. Di portal Next.js halaman ini mengambil datanya
  lewat `fetch` sesudah tampil, sehingga judul & tautan artikelnya tidak pernah
  ikut ke HTML yang dibaca mesin pencari — padahal justru berita yang paling
  layak diindeks dari situs ini.
--}}

<div class="min-h-screen bg-slate-50">
    <x-kepala-publik
        label="Media Informasi"
        judul="Berita &amp; Informasi"
        ket="Kabar dan informasi terkini seputar layanan Disdukcapil Kabupaten Pesisir Barat."
        ikon="koran" />

    <div class="container mx-auto -mt-2 px-4 pb-16 md:px-8 lg:px-16">
        @if ($berita->isEmpty())
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100">
                    <x-ikon nama="koran" class="h-8 w-8 text-slate-300" />
                </div>
                <p class="font-medium text-slate-600">Belum ada berita</p>
                <p class="mt-1 text-sm text-slate-400">Berita yang dipublikasikan akan tampil di sini.</p>
            </div>
        @else
            @php
                // Artikel unggulan hanya di halaman pertama — di halaman
                // berikutnya tidak ada "terbaru" yang pantas ditonjolkan.
                $unggulan = $berita->currentPage() === 1 ? $berita->first() : null;
                $sisa = $unggulan ? $berita->slice(1) : $berita;
            @endphp

            @if ($unggulan)
                <a href="{{ route('berita.detail', $unggulan->slug) }}"
                   class="group mb-8 grid grid-cols-1 overflow-hidden rounded-3xl border border-slate-200/70 bg-white shadow-sm transition-all duration-300 hover:border-brand/30 hover:shadow-xl lg:grid-cols-2">
                    <div class="relative aspect-video overflow-hidden bg-slate-100 lg:aspect-auto lg:min-h-[320px]">
                        @if ($unggulan->gambar)
                            <img src="{{ $unggulan->gambar }}" alt="{{ $unggulan->judul }}"
                                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-slate-300">
                                <x-ikon nama="koran" class="h-12 w-12" />
                            </div>
                        @endif
                        <span class="absolute left-4 top-4 rounded-full bg-brand px-3 py-1.5 text-xs font-bold text-white shadow-lg">Terbaru</span>
                    </div>

                    <div class="flex flex-col justify-center p-6 md:p-9">
                        @if ($unggulan->kategori)
                            <span class="mb-2 text-xs font-bold uppercase tracking-widest text-brand">{{ $unggulan->kategori }}</span>
                        @endif
                        <h2 class="line-clamp-3 text-2xl font-bold leading-tight tracking-tight text-slate-900 transition-colors group-hover:text-brand md:text-[1.75rem]">
                            {{ $unggulan->judul }}
                        </h2>
                        @if ($unggulan->ringkasan)
                            <p class="mt-3 line-clamp-3 leading-relaxed text-slate-500">{{ $unggulan->ringkasan }}</p>
                        @endif
                        <div class="mt-5 flex items-center gap-2 text-xs text-slate-400">
                            {{ $unggulan->created_at?->translatedFormat('j F Y') }}
                        </div>
                        <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand transition-all group-hover:gap-2.5">
                            Baca Selengkapnya <x-ikon nama="panah-kanan" class="h-4 w-4" />
                        </span>
                    </div>
                </a>
            @endif

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($sisa as $b)
                    <a href="{{ route('berita.detail', $b->slug) }}"
                       class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-brand/30 hover:shadow-lg">
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
                            <h2 class="line-clamp-2 font-bold leading-snug text-slate-900 transition-colors group-hover:text-brand">{{ $b->judul }}</h2>
                            @if ($b->ringkasan)
                                <p class="mt-2 line-clamp-2 flex-1 text-sm leading-relaxed text-slate-500">{{ $b->ringkasan }}</p>
                            @endif
                            <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                                <span class="text-xs text-slate-400">{{ $b->created_at?->translatedFormat('j F Y') }}</span>
                                <x-ikon nama="panah-kanan" class="h-4 w-4 text-slate-300 transition-all group-hover:translate-x-0.5 group-hover:text-brand" />
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            @if ($berita->hasPages())
                <div class="mt-12">{{ $berita->links('publik.partials.paginasi') }}</div>
            @endif
        @endif
    </div>
</div>

@endsection
