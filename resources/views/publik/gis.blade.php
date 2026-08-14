@extends('publik.layout')

@section('judul', 'GIS Dukcapil — Peta Sebaran Penduduk · Disdukcapil Pesisir Barat')
@section('deskripsi', 'Peta sebaran jumlah penduduk per kecamatan di Kabupaten Pesisir Barat, dari rekap Data Kependudukan Bersih Disdukcapil.')

@section('konten')

{{--
  GIS Dukcapil — port `app/media/gis/page.tsx`.

  Isinya satu island: peta memang tidak bisa dirender server, dan angkanya pun
  baru berarti bersama petanya. Judul & keterangannya tetap HTML biasa supaya
  halaman ini punya isi terbaca meski JS-nya gagal dimuat.
--}}

<div class="min-h-screen bg-slate-50">
    <x-kepala-publik label="Media Informasi"
                     judul="GIS Dukcapil — Peta Sebaran Penduduk"
                     ket="Persebaran jumlah penduduk per kecamatan di Kabupaten Pesisir Barat. Arahkan kursor ke lingkaran untuk melihat rinciannya."
                     ikon="peta" />

    <div class="container mx-auto px-4 py-12 md:px-8 lg:px-16">
        <div data-island="PetaSebaran" data-props='@json([])'></div>

        <div class="mt-6 rounded-2xl border border-slate-200/70 bg-white p-5">
            <p class="text-sm text-slate-500">
                Butuh angkanya dalam bentuk tabel per desa/kelurahan?
                <a href="/media/demografi" class="font-semibold text-brand hover:underline">Buka Laporan Data Demografi</a>.
            </p>
        </div>
    </div>
</div>

@endsection
