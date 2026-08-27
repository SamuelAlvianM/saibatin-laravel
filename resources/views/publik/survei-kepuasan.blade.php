@extends('publik.layout')

@section('judul', 'Survei Kepuasan Masyarakat — Disdukcapil Pesisir Barat')
@section('deskripsi', 'Isi Survei Kepuasan Masyarakat (SKM) Disdukcapil Kabupaten Pesisir Barat — 16 pertanyaan pelayanan sesuai Permenpan RB No. 14 Tahun 2017.')

@section('konten')

{{--
  Survei Kepuasan Masyarakat — menu mengikuti SIDAKO, ISI kuesionernya milik
  portal ini sendiri.

  🔴 SIDAKO menyematkan skm.go.id lewat iframe; di sini TIDAK. Alamat iframe itu
  menunjuk instansi Tana Tidung, jadi menyalinnya berarti jawaban warga Pesisir
  Barat masuk ke rekap dinas lain. Kuesioner beserta rekap IKM-nya milik portal
  ini sendiri sejak Fase 3.

  🔴 Isinya kini KUESIONER RESMI DINAS (berkas Word 17 Agu 2026): 16 pertanyaan
  + identitas responden (termasuk pertanyaan disabilitas) + kolom keluhan.
  Kuesioner 9 unsur sebelumnya tidak dihapus dari sistem — 204 responden lama
  memakainya dan rekap dashboard tetap menghitung jawaban mereka
  (`config/skm.php` → `warisan`).
--}}

<div class="min-h-screen bg-slate-50">
    <x-kepala-publik label="Layanan Publik · Disdukcapil"
                     judul="Survei Kepuasan Masyarakat"
                     ket="Penilaian Anda membantu kami meningkatkan mutu pelayanan administrasi kependudukan. Pengisian hanya butuh beberapa menit."
                     ikon="bintang" />

    @php $tampilFormulir = $terbuka || $pratinjauPetugas; @endphp

    {{--
      Keterangan dipindah ke KOLOM SAMPING, tidak lagi menumpuk di atas formulir.
      Dua kotak berturut-turut (info + pratinjau petugas) mendorong pertanyaan
      pertama sampai ke luar layar, sehingga halaman ini terbaca seperti halaman
      pengumuman alih-alih kuesioner. Di layar sempit keduanya tetap di atas —
      di sana kolom samping justru mempersempit formulir.
    --}}
    <div class="container mx-auto max-w-6xl px-4 py-12 md:px-8">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-start">

            {{-- `lg:top-24` menghindari navbar publik yang `sticky top-0`. --}}
            <aside class="flex flex-col gap-4 lg:order-2 lg:sticky lg:top-24">
                <div class="flex gap-2.5 rounded-xl border border-brand/25 bg-brand/5 p-4">
                    <x-ikon nama="info" class="mt-0.5 h-4 w-4 flex-shrink-0 text-brand" />
                    <p class="text-xs leading-relaxed text-slate-600">
                        Kuesioner ini memuat <b>16 pertanyaan pelayanan</b> sesuai Peraturan Menteri
                        PANRB No. 14 Tahun 2017, dinilai pada skala 1–4. Hasilnya dipakai menghitung
                        <b>Indeks Kepuasan Masyarakat (IKM)</b> Disdukcapil Kabupaten Pesisir Barat.
                        <b>Identitas responden boleh dikosongkan</b> (nama cukup inisial) dan tidak
                        dipublikasikan.
                    </p>
                </div>

                @if ($tampilFormulir && ! $terbuka)
                    {{-- Petugas melihat formulir penuh walau survei belum dibuka —
                         supaya dinas memeriksanya langsung di portal, bukan lewat
                         tangkapan layar. Warga tidak pernah sampai ke sini. --}}
                    <div class="flex gap-2.5 rounded-xl border border-amber-300 bg-amber-50 p-4">
                        <x-ikon nama="info" class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-600" />
                        <p class="text-xs leading-relaxed text-amber-900">
                            <b>Pratinjau petugas.</b> Survei ini <b>belum dibuka untuk warga</b> —
                            halaman ini hanya tampil bagi petugas yang masuk. Jawaban yang Anda kirim
                            tetap tersimpan dan ikut terhitung di rekap, jadi hapus dari
                            Dashboard bila hanya mencoba. Membukanya untuk warga: setel
                            <code>SKM_TERBUKA=true</code> pada <code>.env</code> server.
                        </p>
                    </div>
                @endif
            </aside>

            <div class="min-w-0 lg:order-1">
                @if ($tampilFormulir)
                    {{-- 🔴 Props sudah jadi dari controller. Merakit lariknya di sini
                         (`@json([...])` bertingkat) membuat Blade gagal compile dengan
                         "Unclosed '['" — jebakan HANDOFF §5 no. 21. --}}
                    <div data-island="FormSkm" data-props='@json($propsSkm)'></div>
                @else
                    <div class="rounded-2xl border border-slate-200/60 bg-white p-10 text-center shadow-sm">
                        <x-ikon nama="bintang" class="mx-auto h-9 w-9 text-slate-300" />
                        <h2 class="mt-3 text-lg font-semibold text-slate-900">Survei sedang disiapkan</h2>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-500">
                            Kuesioner Survei Kepuasan Masyarakat sedang dalam penyesuaian bersama Dinas
                            Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Barat, dan akan dibuka
                            kembali dalam waktu dekat.
                        </p>
                        <p class="mt-4 text-sm text-slate-500">
                            Ada keluhan atau masukan yang mendesak?
                            <a href="/pusat-bantuan/pengaduan-konsultasi" class="font-semibold text-brand hover:underline">
                                Sampaikan lewat Pengaduan &amp; Konsultasi
                            </a>.
                        </p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

@endsection
