@extends('publik.layout')

@section('judul', 'Hubungi Kami — Disdukcapil Pesisir Barat')
@section('deskripsi', 'Alamat, kontak, dan jam layanan Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Barat.')

@section('konten')

{{--
  Hubungi Kami — port `app/hubungi-kami/page.tsx`.

  Tidak lagi di navbar (mengikuti SIDAKO): kontaknya sudah permanen di footer,
  jadi menu tersendiri hanya mengulang isi yang sama. Halamannya tetap ada dan
  ditautkan dari footer, halaman informasi, serta peta situs.

  ⚠️ Zona waktunya **WIB** — Pesisir Barat ada di Lampung. Halaman Pengaduan
  portal Next.js sempat menulis WITA karena disalin mentah dari SIDAKO.
--}}

<div class="min-h-screen bg-slate-50">
    <x-kepala-publik label="Kontak"
                     judul="Hubungi Kami"
                     ket="Datang langsung ke kantor, kirim surel, atau sampaikan lewat kanal pengaduan — kami siap membantu."
                     ikon="telepon" />

    <div class="container mx-auto px-4 py-12 md:px-8 lg:px-16">
        <div class="grid gap-6 lg:grid-cols-2">

            <section class="masuk-naik space-y-4 rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm md:p-8">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-brand/10 text-brand">
                        <x-ikon nama="peta-pin" class="h-5 w-5" />
                    </div>
                    <h2 class="font-semibold text-slate-900">{{ $alamat['title'] }}</h2>
                </div>
                @foreach ($alamat['body'] ?? [] as $baris)
                    <p class="text-sm leading-relaxed text-slate-700">{{ $baris }}</p>
                @endforeach
            </section>

            <section class="masuk-naik space-y-4 rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm md:p-8"
                     style="animation-delay: 80ms">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-brand/10 text-brand">
                        <x-ikon nama="surel" class="h-5 w-5" />
                    </div>
                    <h2 class="font-semibold text-slate-900">{{ $kontak['title'] }}</h2>
                </div>
                <ul class="space-y-2">
                    @foreach ($kontak['list'] ?? [] as $butir)
                        <li class="flex items-start gap-2 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <span class="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-brand"></span>
                            {{ $butir }}
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        {{-- Tiga kanal aspirasi. Ditaruh di sini karena "hubungi kami" hampir
             selalu berarti salah satu dari ketiganya — dan tanpa tautan ini
             warga harus menebak menu mana yang tepat. --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['href' => '/pusat-bantuan/pengaduan-konsultasi', 'ikon' => 'pelampung', 'judul' => 'Pengaduan & Konsultasi', 'ket' => 'Keluhan atau pertanyaan seputar layanan.'],
                ['href' => '/hubungi-kami/kritik-saran', 'ikon' => 'obrolan', 'judul' => 'Kritik & Saran', 'ket' => 'Masukan untuk peningkatan mutu layanan.'],
                ['href' => '/wbs/tentang-wbs', 'ikon' => 'perisai-seru', 'judul' => 'WBS', 'ket' => 'Laporan dugaan pelanggaran — identitas dirahasiakan.'],
            ] as $i => $kanal)
                <a href="{{ $kanal['href'] }}"
                   class="masuk-naik group flex flex-col rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-brand/30 hover:shadow-lg"
                   style="animation-delay: {{ 160 + $i * 60 }}ms">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-brand/10 text-brand transition-colors group-hover:bg-brand group-hover:text-white">
                        <x-ikon :nama="$kanal['ikon']" class="h-5 w-5" />
                    </div>
                    <h3 class="font-semibold text-slate-900 transition-colors group-hover:text-brand">{{ $kanal['judul'] }}</h3>
                    <p class="mt-1 flex-1 text-sm leading-relaxed text-slate-500">{{ $kanal['ket'] }}</p>
                </a>
            @endforeach
        </div>
    </div>
</div>

@endsection
