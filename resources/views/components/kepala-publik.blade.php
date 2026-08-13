@props(['judul', 'label' => null, 'ket' => null, 'ikon' => 'koran'])

{{--
  Kepala halaman publik: pita gradien brand + lengkung pemisah ke bawahnya.

  Dipakai bersama SELURUH halaman publik selain beranda (berita, galeri,
  produk, PPID, …) supaya ketiganya tidak menyalin markup yang sama — dan
  supaya perubahan warna/bentuk cukup dilakukan di satu tempat.

  `$slot` (opsional) diletakkan di bawah judul: dipakai halaman yang perlu
  menaruh tab atau pencarian di dalam pita.
--}}

<div class="relative overflow-hidden" style="background: linear-gradient(135deg, #143a5c 0%, #1b4b72 55%, #2176bd 100%)">
    <div class="absolute inset-0 opacity-[0.07]"
         style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 26px 26px"></div>

    <div class="container relative z-10 mx-auto px-4 py-12 md:px-8 md:py-14 lg:px-16">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/15 text-white backdrop-blur-sm">
                <x-ikon :nama="$ikon" class="h-7 w-7" />
            </div>
            <div>
                @if ($label)
                    <p class="mb-1 text-[0.7rem] font-bold uppercase tracking-widest text-white/70">{{ $label }}</p>
                @endif
                <h1 class="text-2xl font-bold tracking-tight text-white md:text-3xl">{!! $judul !!}</h1>
                @if ($ket)
                    <p class="mt-1 max-w-xl text-sm text-white/75">{{ $ket }}</p>
                @endif
            </div>
        </div>

        {{ $slot }}
    </div>

    <svg class="relative block w-full text-slate-50" viewBox="0 0 1440 40" fill="currentColor"
         preserveAspectRatio="none" aria-hidden="true">
        <path d="M0,40 C360,0 1080,0 1440,40 L1440,40 L0,40 Z" />
    </svg>
</div>
