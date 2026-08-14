@extends('publik.layout')

@section('judul', 'Peta Situs — SAIBATIN Disdukcapil Pesisir Barat')
@section('deskripsi', 'Daftar lengkap seluruh halaman portal SAIBATIN Disdukcapil Kabupaten Pesisir Barat dalam satu halaman.')

@section('konten')

{{--
  Peta situs untuk MANUSIA — port `app/sitemap/page.tsx`.
  (Berbeda dari `sitemap.xml` yang dibaca mesin.)

  Daftarnya dirakit `PublikController::petaSitus()` dari sumber yang sama dengan
  navbar & halaman informasi, jadi halaman baru otomatis ikut. Versi
  tulis-tangan pasti basi: halaman ditambah di config, daftarnya menyusul
  belakangan atau tidak sama sekali.
--}}

<div class="min-h-screen bg-slate-50">
    <x-kepala-publik label="Navigasi"
                     judul="Peta Situs"
                     ket="Seluruh halaman portal dalam satu daftar."
                     ikon="peta" />

    <div class="container mx-auto px-4 py-12 md:px-8 lg:px-16">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($bagian as $i => $b)
                <section class="masuk-naik rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm"
                         style="animation-delay: {{ $i * 50 }}ms">
                    <h2 class="mb-3 border-b border-slate-100 pb-2.5 text-sm font-bold uppercase tracking-wide text-slate-400">
                        {{ $b['judul'] }}
                    </h2>
                    <ul class="space-y-1.5">
                        @foreach ($b['items'] as $item)
                            <li>
                                <a href="{{ $item['href'] }}"
                                   class="group flex items-start gap-1.5 text-sm leading-snug text-slate-600 transition-colors hover:text-brand">
                                    <x-ikon nama="chevron-kanan" class="mt-0.5 h-3.5 w-3.5 flex-none text-slate-300 transition-colors group-hover:text-brand" />
                                    {{ $item['judul'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    </div>
</div>

@endsection
