@extends('publik.layout')

@section('judul', 'Laporan Data Demografi — Disdukcapil Pesisir Barat')
@section('deskripsi', 'Data kependudukan agregat Kabupaten Pesisir Barat per kecamatan dan desa/kelurahan: jenis kelamin, agama, pekerjaan, pendidikan, dan lainnya.')

@section('konten')

{{--
  Laporan Data Demografi — port `app/media/demografi/page.tsx` +
  `components/landingpage/demografi-view.tsx`.

  Tabelnya island karena penelusurannya dua tingkat dan setiap tingkat memuat
  datanya sendiri; merendernya di server berarti mengirim seluruh desa dari
  seluruh kategori sekaligus.
--}}

<div class="min-h-screen bg-slate-50">
    <x-kepala-publik label="Media Informasi"
                     judul="Laporan Data Demografi"
                     ket="Data kependudukan agregat per kecamatan — klik nama kecamatan untuk melihat rincian tiap desa/kelurahan."
                     ikon="Users" />

    <div class="container mx-auto px-4 py-12 md:px-8 lg:px-16">
        <div data-island="TabelDemografi" data-props='@json(["kategori" => $kategori])'></div>

        <div class="mt-6 rounded-2xl border border-slate-200/70 bg-white p-5">
            <p class="text-sm text-slate-500">
                Ingin melihat sebarannya di peta?
                <a href="/media/gis" class="font-semibold text-brand hover:underline">Buka GIS Dukcapil</a>.
            </p>
        </div>
    </div>
</div>

@endsection
