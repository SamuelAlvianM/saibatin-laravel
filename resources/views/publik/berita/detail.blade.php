@extends('publik.layout')

@section('judul', $berita->judul.' — SAIBATIN')
@section('deskripsi', \Illuminate\Support\Str::limit(strip_tags($berita->ringkasan ?: $berita->konten), 155))
@section('og_tipe', 'article')

{{-- Gambar artikel jadi kartu berbagi, menggantikan OG bawaan situs. Tanpa ini
     setiap artikel yang dibagikan ke WhatsApp/Facebook tampil dengan gambar
     yang sama persis. --}}
@if ($berita->gambar)
    @section('og_gambar', url($berita->gambar))
@endif

@section('kepala')
    <meta property="article:published_time" content="{{ $berita->created_at?->toIso8601String() }}">

    {{-- Data terstruktur: judul, tanggal, dan penulis yang dibaca mesin pencari
         apa adanya, tanpa menebak dari markup.

         🔴 JSON-nya dirakit di CONTROLLER, bukan di sini. Dua hal yang sudah
         menggagalkannya di view: `@json([...])` bertingkat membuat Blade
         memotong argumen pada `)` pertama ("Unclosed '['"), dan blok
         `@php … @endphp` di dalam `@section` ini membuat sisa seksinya hilang
         diam-diam dari HTML — halaman tetap tampil, hanya tag ini yang lenyap
         tanpa satu pun galat. --}}
    <script type="application/ld+json">{!! $ldJson !!}</script>
@endsection

@section('konten')

{{-- Satu artikel — port `app/media/berita/[slug]/page.tsx`. --}}

<div class="min-h-screen bg-white">
    <article class="container mx-auto max-w-4xl px-4 pb-14 pt-8 md:px-8 lg:px-16">
        <a href="{{ route('berita.indeks') }}"
           class="mb-8 inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition-colors hover:text-brand">
            <x-ikon nama="panah-kanan" class="h-4 w-4 rotate-180" />
            Kembali ke Berita
        </a>

        <header class="mx-auto max-w-3xl text-center">
            <h1 class="text-3xl font-bold leading-[1.15] tracking-tight text-slate-900 sm:text-[2.6rem]">
                {{ $berita->judul }}
            </h1>
            <div class="mt-5 flex flex-wrap items-center justify-center gap-3 text-sm">
                <span class="text-slate-500">{{ $berita->created_at?->translatedFormat('j F Y') }}</span>
                @if ($berita->penulis)
                    <span class="text-slate-400">· {{ $berita->penulis }}</span>
                @endif
                @if ($berita->kategori)
                    <span class="rounded-full bg-brand/10 px-3 py-1 text-xs font-bold uppercase tracking-widest text-brand">
                        {{ $berita->kategori }}
                    </span>
                @endif
            </div>
        </header>

        <div class="relative mt-9 aspect-[16/9] w-full overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-200/70">
            @if ($berita->gambar)
                <img src="{{ $berita->gambar }}" alt="{{ $berita->judul }}" class="h-full w-full object-cover">
            @else
                <div class="flex h-full w-full items-center justify-center text-slate-300">
                    <x-ikon nama="koran" class="h-12 w-12" />
                </div>
            @endif
        </div>

        {{-- Isi artikel HTML dari penyunting kaya (tiptap) di dashboard.
             Ditulis petugas, bukan pengunjung — sama seperti portal Next.js
             yang memakai `dangerouslySetInnerHTML` di titik yang sama. --}}
        <div class="prose prose-slate prose-lg mx-auto mt-10 max-w-3xl
                    prose-headings:font-bold prose-headings:tracking-tight prose-headings:text-slate-900
                    prose-a:text-brand prose-a:no-underline hover:prose-a:underline
                    prose-img:rounded-xl prose-strong:text-slate-900">
            {!! $berita->konten !!}
        </div>

        <div class="mx-auto mt-12 max-w-3xl border-t border-slate-100 pt-6">
            <a href="{{ route('berita.indeks') }}"
               class="inline-flex items-center gap-2 text-sm font-semibold text-brand transition-all hover:gap-3">
                <x-ikon nama="panah-kanan" class="h-4 w-4 rotate-180" />
                Lihat berita lainnya
            </a>
        </div>
    </article>
</div>

@endsection
