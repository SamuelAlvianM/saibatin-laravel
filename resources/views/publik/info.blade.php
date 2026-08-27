@extends('publik.layout')

@section('judul', $isi['title'].' — SAIBATIN Disdukcapil Pesisir Barat')
@section('deskripsi', $isi['description'])

@section('konten')

{{--
  Halaman informasi statis — port `components/shared/info-page.tsx`.

  SATU view untuk SELURUH alamat informasi di empat grup (produk, ppid, wbs,
  pusat-bantuan, hubungi-kami): isinya dari `config/info-halaman.php` (ditimpa
  blok CMS per-kunci), berkasnya dari Dokumen Publikasi berdasarkan kategori
  yang dipetakan ke alamat halaman ini. Menambah halaman informasi baru =
  menambah satu entri config, tanpa menyentuh view maupun rute.

  Tiga hal opsional yang menempel bila entri config-nya memintanya:
    • `subnav`   → bar sub-tab PPID di atas judul
    • `faq`      → daftar tanya-jawab dari blok CMS `pusat-bantuan.faq`
    • `formulir` → formulir publik (pengaduan / kritik-saran) di bawah isinya

  MODE EDIT: kartu isinya ditandai `data-blok` dengan kunci `info.<grup>.<slug>`
  (dikirim controller sebagai `$kunci`), jadi Super Admin menyuntingnya langsung
  dari halaman ini lewat pensil. Daftar FAQ punya blok TERSENDIRI — pensilnya
  ada di partial-nya sendiri.
--}}

<div class="relative flex min-h-screen flex-col bg-slate-50/30">
    <div class="container mx-auto flex-1 px-4 py-12 md:px-8 lg:px-16 lg:py-16">

        @isset($subnav)
            @include('publik.partials.subnav', ['subnav' => $subnav])
        @endisset

        <div class="masuk-naik mb-8 flex items-start gap-4">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-brand text-white shadow-lg shadow-brand/20">
                <x-ikon nama="{{ $ikon ?? 'berkas' }}" class="h-6 w-6" />
            </div>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">{{ $isi['title'] }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">{{ $isi['description'] }}</p>
            </div>
        </div>

        <div class="masuk-naik space-y-4 rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm md:p-8"
             style="animation-delay: 100ms"
             @isset($kunciBlok) data-blok="{{ $kunciBlok }}" data-blok-label="Isi Halaman" @endisset>
            @include('publik.partials.blok-isi', ['isi' => $isi])

            @if (! empty($isi['faq']))
                @include('publik.partials.faq', ['faq' => $faq ?? []])
            @endif

            @include('publik.partials.berkas', ['berkas' => $berkas, 'judul' => 'Berkas'])

            @include('publik.partials.dokumen-edit', ['jenis' => $jenisDokumen ?? []])
        </div>

        @if (! empty($isi['formulir']))
            {{-- Formulir dipasang sebagai island terpisah DI LUAR kartu isi:
                 ia satu-satunya bagian interaktif halaman ini, jadi sisanya
                 tetap HTML yang berguna walau JS-nya gagal dimuat. --}}
            <div class="masuk-naik mt-6" style="animation-delay: 150ms"
                 data-island="FormAspirasi" data-props='@json($propsFormulir)'></div>
        @endif
    </div>
</div>

@endsection
