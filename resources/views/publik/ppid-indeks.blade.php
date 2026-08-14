@extends('publik.layout')

@section('judul', $grup['judul'].' — PPID Disdukcapil Pesisir Barat')
@section('deskripsi', $grup['deskripsi'])

@section('konten')

{{--
  Halaman indeks klasifikasi Informasi Publik PPID — port
  `components/ppid/informasi-index.tsx`.

  Kartunya dari `config/ppid.php`; tiap kartu menunjuk ke halaman
  `/ppid/{slug}` yang isinya diatur `config/info-halaman.php`. Jumlah dokumen
  dihitung server dari Dokumen Publikasi — angka itu yang membedakan kategori
  yang sudah terisi dari yang masih kosong, tanpa warga harus membuka satu per
  satu.
--}}

<div class="min-h-screen bg-slate-50">
    <x-kepala-publik label="PPID" :judul="$grup['judul']" :ket="$grup['deskripsi']" ikon="berkas" />

    <div class="container mx-auto px-4 py-12 md:px-8 lg:px-16">
        @isset($subnav)
            @include('publik.partials.subnav', ['subnav' => $subnav])
        @endisset

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as $i => $item)
                <a href="{{ $item['href'] }}"
                   class="masuk-naik group flex flex-col rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-brand/30 hover:shadow-lg"
                   style="animation-delay: {{ $i * 60 }}ms">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $item['gradasi'] }} text-white shadow-sm transition-transform duration-300 group-hover:scale-105">
                            <x-ikon :nama="$item['icon']" class="h-6 w-6" />
                        </div>

                        @if ($item['dokumen'] > 0)
                            <span class="rounded-full bg-brand/10 px-2.5 py-1 text-[0.68rem] font-bold text-brand">
                                {{ $item['dokumen'] }} dokumen
                            </span>
                        @else
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[0.68rem] font-medium text-slate-400">
                                belum ada
                            </span>
                        @endif
                    </div>

                    <h2 class="mt-4 font-semibold leading-snug text-slate-900 transition-colors group-hover:text-brand">
                        {{ $item['title'] }}
                    </h2>
                    <p class="mt-1.5 flex-1 text-sm leading-relaxed text-slate-500">{{ $item['description'] }}</p>

                    <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand transition-all group-hover:gap-2.5">
                        Lihat dokumen <x-ikon nama="panah-kanan" class="h-4 w-4" />
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Tautan silang ke klasifikasi satunya — keduanya bagian dari
             kewajiban yang sama, dan warga yang mencari satu dokumen sering
             tidak tahu masuk klasifikasi mana. --}}
        @php
            $lain = collect(config('ppid'))->except($slug)->first();
            $slugLain = collect(config('ppid'))->keys()->first(fn ($k) => $k !== $slug);
        @endphp
        @if ($lain)
            <div class="mt-10 rounded-2xl border border-slate-200/70 bg-white p-5 text-center">
                <p class="text-sm text-slate-500">
                    Mencari dokumen lain? Lihat juga
                    <a href="{{ route('ppid', $slugLain) }}" class="font-semibold text-brand hover:underline">
                        {{ $lain['judul'] }}</a>.
                </p>
            </div>
        @endif
    </div>
</div>

@endsection
