@props(['nama'])

{{--
  Ikon untuk halaman publik yang dirender Blade.

  🔴 Isinya dari `config/ikon.php`, yang DIBANGKITKAN dari paket `lucide-react`
  terpasang — bentuknya identik dengan ikon React di dashboard, tanpa memuat
  React di halaman publik. Ikon baru: tambahkan namanya di skrip pembangkit,
  jangan menempel path SVG manual ke config.

  Nama tak dikenal digambar sebagai lingkaran samar, bukan SVG kosong: kotak
  yang hilang diam-diam jauh lebih sulit disadari daripada penanda yang jelas
  "ikon ini belum ada".
--}}

@php
    $isi = config('ikon.'.$nama)
        ?? '<circle cx="12" cy="12" r="9" stroke-dasharray="3 3"/>';
@endphp

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
     {{ $attributes }}>{!! $isi !!}</svg>
