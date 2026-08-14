@extends('publik.layout')

@section('judul', 'Produk Disdukcapil — SAIBATIN Disdukcapil Pesisir Barat')
@section('deskripsi', 'Produk layanan Pencatatan Sipil (Capil) dan Pendaftaran Penduduk (Dafduk) Disdukcapil Kabupaten Pesisir Barat.')

@section('konten')

{{--
  Produk Disdukcapil — port `components/produk/produk-disdukcapil-view.tsx`.

  Satu-satunya halaman `/produk/*` yang TIDAK memakai view informasi generik:
  isinya akordeon bergambar, bukan paragraf + daftar butir. Karena itu isinya
  pun di blok CMS tersendiri (`produk.disdukcapil`), bukan
  `config/info-halaman.php`.

  🔴 Akordeonnya `<details>` bawaan, bukan island React — alasannya sama dengan
  FAQ: nama dan penjelasan produk justru isi yang paling dicari lewat mesin
  pencari, dan `<details>` menaruh keduanya di HTML sejak awal. Versi React
  hanya mengirim daftar kosong ke crawler. Buka-tutupnya pun jalan tanpa JS.

  Rutenya didaftarkan SEBELUM `/produk/{slug}` supaya tidak ditelan catch-all.
--}}

<div class="relative flex min-h-screen flex-col bg-slate-50/30">
    <div class="container mx-auto flex-1 px-4 py-12 md:px-8 lg:px-16 lg:py-16">
        <div class="masuk-naik mb-8 flex items-start gap-4">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-brand text-white shadow-lg shadow-brand/20">
                <x-ikon nama="paket-centang" class="h-6 w-6" />
            </div>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">Produk Disdukcapil</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">
                    Produk layanan Pencatatan Sipil (Capil) dan Pendaftaran Penduduk (Dafduk)
                    Disdukcapil Kabupaten Pesisir Barat.
                </p>
            </div>
        </div>

        <div class="masuk-naik space-y-6 rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm md:p-8"
             style="animation-delay: 100ms">
            @if (! empty($isi['intro']))
                <p class="text-sm leading-relaxed text-slate-700">{{ $isi['intro'] }}</p>
            @endif

            <div class="space-y-2">
                @forelse ($isi['produk'] ?? [] as $i => $p)
                    <details class="group overflow-hidden rounded-xl border border-slate-200 transition-colors open:border-brand/40 open:bg-brand/[0.03]">
                        <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-3 text-left [&::-webkit-details-marker]:hidden">
                            <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-brand/10 text-xs font-bold text-brand group-open:bg-brand group-open:text-white">
                                {{ $i + 1 }}
                            </span>
                            <span class="flex-1 text-sm font-semibold text-slate-800">{{ $p['nama'] ?? '—' }}</span>
                            <span class="flex-shrink-0 text-slate-400 transition-transform duration-300 group-open:rotate-90 group-open:text-brand">
                                <x-ikon nama="chevron-kanan" class="h-4 w-4" />
                            </span>
                        </summary>

                        <div class="flex flex-col items-start gap-4 px-4 pb-5 pt-1 sm:flex-row">
                            @if (! empty($p['image']))
                                <img src="{{ $p['image'] }}" alt="{{ $p['nama'] ?? 'Produk layanan' }}"
                                     loading="lazy" class="mx-auto h-36 w-48 flex-shrink-0 object-contain sm:mx-0">
                            @endif

                            {{--
                              🔴 `desc` DIRENDER SEBAGAI HTML. Di produksi isinya
                              memang HTML — daftar persyaratan bernomor lengkap
                              dengan tautan ke halaman Formulir & Persyaratan.
                              Kalau di-escape, warga membaca `<ol><li>` mentah
                              alih-alih daftarnya. Sumbernya blok CMS yang hanya
                              bisa disunting petugas, sama seperti isi berita
                              yang juga dirender begini.
                            --}}
                            <div class="prose prose-sm prose-slate max-w-none text-slate-600
                                        prose-a:text-brand prose-a:no-underline hover:prose-a:underline
                                        prose-strong:text-slate-900 prose-li:my-0.5">
                                {!! $p['desc'] ?? '' !!}
                            </div>
                        </div>
                    </details>
                @empty
                    <p class="py-8 text-center text-sm text-slate-400">Belum ada produk yang ditampilkan.</p>
                @endforelse
            </div>
        </div>

        @include('publik.partials.berkas', ['berkas' => $berkas, 'judul' => 'Berkas'])
    </div>
</div>

@endsection
