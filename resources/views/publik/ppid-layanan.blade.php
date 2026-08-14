@extends('publik.layout')

@section('judul', $halaman['judul'].' — SAIBATIN Disdukcapil Pesisir Barat')
@section('deskripsi', $halaman['deskripsi'])

@section('konten')

{{--
  Halaman layanan PPID dua-seksi — port `components/ppid/ppid-layanan-halaman.tsx`.

  Melayani `/ppid/formulir-ppid` & `/ppid/register-ppid`. Bedanya dari halaman
  informasi biasa: satu alamat memuat DUA seksi yang masing-masing punya isi,
  gambar, dan tabel berkasnya sendiri (formulir permohonan vs keberatan,
  register permintaan vs keberatan). Memecahnya jadi dua halaman terpisah akan
  memisahkan sepasang berkas yang di dinas memang selalu dibaca bersama.

  `duaKolom` dipakai halaman Register: isinya pendek dan tanpa infografis, jadi
  dua kartu berdampingan lebih terbaca daripada dua kartu bertumpuk yang
  memaksa gulir. Halaman Formulir tetap satu kolom karena infografis alurnya
  butuh lebar penuh agar tulisannya terbaca.
--}}

<div class="relative flex min-h-screen flex-col bg-slate-50/30">
    <div class="container mx-auto flex-1 px-4 py-12 md:px-8 lg:px-16 lg:py-16">

        @include('publik.partials.subnav', ['subnav' => $subnav])

        <div class="masuk-naik mb-8 flex items-start gap-4">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-brand text-white shadow-lg shadow-brand/20">
                <x-ikon nama="berkas" class="h-6 w-6" />
            </div>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">{{ $halaman['judul'] }}</h1>
                <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ $halaman['deskripsi'] }}</p>
            </div>
        </div>

        <div class="grid gap-6 {{ ($halaman['duaKolom'] ?? false) ? 'lg:grid-cols-2' : '' }}">
            @foreach ($seksi as $i => $s)
                <section class="masuk-naik space-y-4 rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm md:p-8"
                         style="animation-delay: {{ 100 + $i * 80 }}ms">
                    <div>
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900">{{ $s['isi']['title'] }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $s['isi']['description'] }}</p>
                    </div>

                    @include('publik.partials.blok-isi', ['isi' => $s['isi']])

                    @include('publik.partials.berkas', ['berkas' => $s['berkas'], 'judul' => 'Berkas'])
                </section>
            @endforeach
        </div>
    </div>
</div>

@endsection
