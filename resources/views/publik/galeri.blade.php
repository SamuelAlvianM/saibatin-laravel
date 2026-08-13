@extends('publik.layout')

@section('judul', 'Galeri — SAIBATIN Disdukcapil Pesisir Barat')
@section('deskripsi', 'Dokumentasi kegiatan Disdukcapil Kabupaten Pesisir Barat.')

@section('konten')

{{--
  Galeri foto — port `app/galeri/page.tsx`.

  Grid & penyaringnya dirender server; yang butuh JS hanya penampil layar penuh
  di bawah — dan itu ditulis sebagai skrip kecil biasa, bukan island: ia hanya
  menempel pada gambar yang SUDAH ada di HTML, jadi memuat React untuknya cuma
  menunda hal yang sebenarnya sudah tampil.
--}}

<div class="min-h-screen bg-slate-50">
    <x-kepala-publik
        label="Media Informasi"
        judul="Galeri"
        ket="Dokumentasi kegiatan Disdukcapil Kabupaten Pesisir Barat."
        ikon="gambar" />

    <div class="container mx-auto px-4 py-12 md:px-8 lg:px-16">
        @if ($daftarKategori->isNotEmpty())
            <div class="mb-8 flex flex-wrap gap-2">
                @foreach ([['', 'Semua'], ...$daftarKategori->map(fn ($k) => [$k, $k])->all()] as [$nilai, $label])
                    @php $aktif = $kategori === $nilai; @endphp
                    <a href="{{ $nilai === '' ? route('galeri') : route('galeri', ['kategori' => $nilai]) }}"
                       @class([
                           'rounded-full px-4 py-1.5 text-sm font-medium transition-all',
                           'text-slate-900 shadow-md' => $aktif,
                           'border border-slate-200 bg-white/60 text-slate-600 hover:border-brand/40' => ! $aktif,
                       ])
                       @style(['background: linear-gradient(90deg, #ffed4a, #e77817)' => $aktif])>
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        @endif

        @if ($foto->isEmpty())
            <div class="py-24 text-center text-slate-500">
                <x-ikon nama="gambar" class="mx-auto mb-3 h-12 w-12 opacity-30" />
                <p>Belum ada foto dalam galeri.</p>
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4" id="kisi-galeri">
                @foreach ($foto as $f)
                    @php
                        // Data lama menyimpan sebagian nama berkas tanpa folder.
                        $sumber = str_starts_with($f->gambar, '/') ? $f->gambar : '/uploads/gallery/'.$f->gambar;
                    @endphp
                    <button type="button"
                            class="kartu-galeri group cursor-pointer overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-all hover:shadow-lg"
                            data-gambar="{{ $sumber }}"
                            data-judul="{{ $f->judul }}"
                            data-kategori="{{ $f->kategori }}"
                            data-tanggal="{{ $f->created_at?->translatedFormat('j F Y') }}">
                        <div class="relative aspect-[4/3] overflow-hidden bg-slate-50">
                            <img src="{{ $sumber }}" alt="{{ $f->judul }}" loading="lazy"
                                 class="h-full w-full object-contain p-1.5 transition-transform duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 transition-opacity group-hover:opacity-100"></div>
                            <div class="absolute inset-x-0 bottom-0 translate-y-full p-3 transition-transform duration-300 group-hover:translate-y-0">
                                <p class="line-clamp-2 text-left text-xs font-medium text-white">{{ $f->judul }}</p>
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>

            @if ($foto->hasPages())
                <div class="mt-12">{{ $foto->links('publik.partials.paginasi') }}</div>
            @endif
        @endif
    </div>
</div>

{{-- ── Penampil layar penuh ──────────────────────────────────────────────── --}}
<div id="penampil-galeri" hidden
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background: rgba(0,0,0,0.85); backdrop-filter: blur(8px)">
    <button type="button" id="tutup-penampil" aria-label="Tutup"
            class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white transition-colors hover:bg-white/20">
        <x-ikon nama="silang" class="h-5 w-5" />
    </button>

    <div class="w-full max-w-4xl overflow-hidden rounded-2xl bg-white/10" id="isi-penampil">
        <div class="relative aspect-video bg-black/40">
            <img id="penampil-gambar" src="" alt="" class="h-full w-full object-contain">
        </div>
        <div class="bg-white/90 p-4">
            <h3 id="penampil-judul" class="font-semibold text-slate-900"></h3>
            <span id="penampil-kategori" class="text-xs font-medium text-brand"></span>
            <p id="penampil-tanggal" class="mt-1 text-xs text-slate-400"></p>
        </div>
    </div>
</div>

<script>
    (function () {
        var penampil = document.getElementById('penampil-galeri');
        if (!penampil) return;

        var gambar = document.getElementById('penampil-gambar');
        var judul = document.getElementById('penampil-judul');
        var kategori = document.getElementById('penampil-kategori');
        var tanggal = document.getElementById('penampil-tanggal');

        function tutup() {
            penampil.hidden = true;
            document.body.style.overflow = '';
        }

        // Satu pendengar di kisi-nya, bukan 24 pendengar di tiap kartu.
        document.getElementById('kisi-galeri')?.addEventListener('click', function (e) {
            var kartu = e.target.closest('.kartu-galeri');
            if (!kartu) return;

            gambar.src = kartu.dataset.gambar;
            gambar.alt = kartu.dataset.judul;
            judul.textContent = kartu.dataset.judul;
            kategori.textContent = kartu.dataset.kategori || '';
            tanggal.textContent = kartu.dataset.tanggal || '';

            penampil.hidden = false;
            // Halaman di belakang jangan ikut tergulir saat penampil terbuka.
            document.body.style.overflow = 'hidden';
        });

        document.getElementById('tutup-penampil').addEventListener('click', tutup);
        penampil.addEventListener('click', function (e) {
            // Klik pada latarnya menutup; klik pada kartunya tidak.
            if (!e.target.closest('#isi-penampil')) tutup();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !penampil.hidden) tutup();
        });
    })();
</script>

@endsection
