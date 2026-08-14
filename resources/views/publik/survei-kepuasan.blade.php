@extends('publik.layout')

@section('judul', 'Survei Kepuasan Masyarakat — Disdukcapil Pesisir Barat')
@section('deskripsi', 'Isi Survei Kepuasan Masyarakat (SKM) Disdukcapil Kabupaten Pesisir Barat — 9 unsur pelayanan sesuai Permenpan RB No. 14 Tahun 2017.')

@section('konten')

{{--
  Survei Kepuasan Masyarakat — menu mengikuti SIDAKO, ISI kuesionernya milik
  portal ini sendiri.

  🔴 SIDAKO menyematkan skm.go.id lewat iframe; di sini TIDAK. Alamat iframe itu
  menunjuk instansi Tana Tidung, jadi menyalinnya berarti jawaban warga Pesisir
  Barat masuk ke rekap dinas lain. Kuesioner 9 unsur beserta rekap IKM-nya sudah
  ada di port ini sejak Fase 3, jadi fiturnya setara.
--}}

<div class="min-h-screen bg-slate-50">
    <x-kepala-publik label="Layanan Publik · Disdukcapil"
                     judul="Survei Kepuasan Masyarakat"
                     ket="Penilaian Anda membantu kami meningkatkan mutu pelayanan administrasi kependudukan. Pengisian hanya butuh beberapa menit."
                     ikon="bintang" />

    <div class="container mx-auto max-w-3xl px-4 py-12 md:px-8">
        <div class="mb-6 flex gap-2.5 rounded-xl border border-brand/25 bg-brand/5 p-4">
            <x-ikon nama="info" class="mt-0.5 h-4 w-4 flex-shrink-0 text-brand" />
            <p class="text-xs leading-relaxed text-slate-600">
                Kuesioner ini memuat <b>9 unsur pelayanan</b> sesuai Peraturan Menteri PANRB
                No. 14 Tahun 2017, dinilai pada skala 1–4. Hasilnya dipakai menghitung
                <b>Indeks Kepuasan Masyarakat (IKM)</b> Disdukcapil Kabupaten Pesisir Barat.
                Identitas Anda tidak dipublikasikan.
            </p>
        </div>

        <div data-island="FormSkm" data-props='@json(["aspek" => $aspek, "skalaLabel" => $skalaLabel])'></div>
    </div>
</div>

@endsection
