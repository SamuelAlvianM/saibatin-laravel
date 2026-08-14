{{--
  Bar sub-tab — port `components/ppid/ppid-subnav.tsx`.

  Dipakai tiga grup PPID (Tentang / Informasi Publik / Layanan & Formulir).
  Tiap tab benar-benar BERPINDAH HALAMAN (<a>), bukan menukar isi seperti
  komponen Tabs — rute aslinya tetap satu-satu, bar ini hanya menaut mereka
  secara visual sehingga terlihat sebagai satu halaman bertab.

  Sengaja TANPA island: tab aktif ditentukan dari path saat ini, yang sudah
  diketahui server. Memuat React demi menyorot satu tautan berarti tab-nya baru
  tampil aktif setelah JS jalan — dan berkedip pindah di depan mata pembaca.

  Yang hilang dibanding aslinya: animasi geser latar (framer-motion). Itu
  bergantung pada dua halaman hidup bersamaan di satu DOM, dan di sini tiap tab
  memang pemuatan halaman baru — jadi tidak ada yang bisa dianimasikan.

  @param array $subnav ['judul' => …, 'items' => [['href','label','pendek'], …]]
--}}
@php($sekarang = request()->path() === '/' ? '/' : '/'.request()->path())

<nav aria-label="Sub-navigasi {{ $subnav['judul'] }}"
     class="mx-auto mb-8 flex w-fit max-w-full flex-wrap items-stretch justify-center gap-1 rounded-2xl border border-white/60 bg-white/45 p-1.5 shadow-lg shadow-brand/10 ring-1 ring-black/[0.04] backdrop-blur-xl">
    @foreach ($subnav['items'] as $tab)
        @php($aktif = $sekarang === $tab['href'])
        <a href="{{ $tab['href'] }}"
           @if ($aktif) aria-current="page" @endif
           class="relative flex items-center justify-center rounded-xl px-3.5 py-2.5 text-center text-sm font-semibold leading-snug transition-colors duration-200 sm:px-4 {{
               $aktif
                   ? 'bg-gradient-to-br from-brand to-brand-dark text-white shadow-md shadow-brand/40 ring-1 ring-white/20'
                   : 'text-slate-600 hover:bg-white/60 hover:text-slate-900'
           }}">
            <span class="hidden sm:inline">{{ $tab['label'] }}</span>
            <span class="sm:hidden">{{ $tab['pendek'] ?? $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
