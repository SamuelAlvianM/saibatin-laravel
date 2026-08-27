@extends('publik.layout')

@section('judul', $halaman['title'].' — SAIBATIN Disdukcapil Pesisir Barat')
@section('deskripsi', $halaman['description'])

@section('konten')

{{--
  Kebijakan & Privasi / Syarat & Ketentuan — port
  `components/shared/kebijakan-privasi-view.tsx` & `syarat-ketentuan-view.tsx`.

  Satu view untuk keduanya: bentuknya sama persis (pengantar + beberapa bagian
  bernomor), yang berbeda hanya daftar bagiannya — dan itu datang dari
  `config/ketentuan.php`.

  Poinnya dinomori `<ol>`, bukan bullet: ketentuan hukum dirujuk per nomor
  ("sesuai butir 4"), dan nomor itu harus ada di halamannya.
--}}

<div class="min-h-screen bg-slate-50">
    <x-kepala-publik label="Ketentuan"
                     :judul="$halaman['title']"
                     :ket="$halaman['description']"
                     ikon="berkas" />

    {{-- Seluruh isi halaman = SATU blok CMS, jadi penanda MODE EDIT-nya di
         pembungkus ini. `$kunci` di bawah adalah nama bagian dalam perulangan,
         bukan kunci blok — karena itu kunci bloknya bernama `$kunciBlok`. --}}
    <div class="container mx-auto max-w-4xl px-4 py-12 md:px-8"
         @isset($kunciBlok) data-blok="{{ $kunciBlok }}" data-blok-label="Isi Halaman" @endisset>
        @if (! empty($isi['intro']))
            <p class="masuk-naik mb-6 rounded-2xl border border-brand/20 bg-brand/5 p-5 text-sm leading-relaxed text-slate-700">
                {{ $isi['intro'] }}
            </p>
        @endif

        @if (! empty($isi['pembaruan']))
            <p class="mb-6 text-xs text-slate-400">{{ $isi['pembaruan'] }}</p>
        @endif

        <div class="space-y-6">
            @foreach ($halaman['urutan'] as $kunci => $judulBagian)
                @php($poin = $isi[$kunci] ?? [])
                @continue(empty($poin))

                <section class="masuk-naik rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm md:p-8"
                         style="animation-delay: {{ $loop->index * 50 }}ms">
                    <h2 class="mb-4 border-b border-slate-100 pb-3 font-semibold text-slate-900">
                        {{ $loop->iteration }}. {{ $judulBagian }}
                    </h2>
                    <ol class="space-y-3">
                        @foreach ($poin as $butir)
                            <li class="flex gap-3 text-sm leading-relaxed text-slate-700">
                                <span class="flex h-6 w-6 flex-none items-center justify-center rounded-lg bg-slate-100 text-[0.7rem] font-bold text-slate-500">
                                    {{ $loop->iteration }}
                                </span>
                                <span class="min-w-0 flex-1">{{ $butir }}</span>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endforeach
        </div>

        <p class="mt-8 text-center text-sm text-slate-500">
            Ada yang ingin ditanyakan?
            <a href="/hubungi-kami" class="font-semibold text-brand hover:underline">Hubungi Disdukcapil Pesisir Barat</a>.
        </p>
    </div>
</div>

@endsection
