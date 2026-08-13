{{--
  Paginasi halaman publik — bentuknya mengikuti `app/media/berita/page.tsx`
  (tombol bulat, halaman aktif berlatar brand).

  🔴 Ini <a href> sungguhan, bukan tombol yang mengganti state. Setiap halaman
  jadi punya URL sendiri sehingga bisa di-bookmark, dibagikan, dan diindeks —
  hal yang tidak mungkin di versi Next.js-nya.

  `elements` bisa berisi string (elipsis) atau larik nomor→URL; keduanya harus
  ditangani, kalau tidak deretannya putus di daftar yang panjang.
--}}

@if ($paginator->hasPages())
    <nav class="flex items-center justify-center gap-1.5" aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-300"
                  aria-hidden="true">
                <x-ikon nama="panah-kanan" class="h-4 w-4 rotate-180" />
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya"
               class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition-colors hover:border-brand/40">
                <x-ikon nama="panah-kanan" class="h-4 w-4 rotate-180" />
            </a>
        @endif

        @foreach ($elements as $elemen)
            @if (is_string($elemen))
                <span class="px-1 text-slate-400">{{ $elemen }}</span>
            @endif

            @if (is_array($elemen))
                @foreach ($elemen as $halaman => $url)
                    @if ($halaman == $paginator->currentPage())
                        <span aria-current="page"
                              class="h-9 min-w-9 rounded-xl bg-brand px-3 text-sm font-semibold leading-9 text-white shadow-md shadow-brand/20">
                            {{ $halaman }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                           class="h-9 min-w-9 rounded-xl border border-slate-200 bg-white px-3 text-center text-sm font-semibold leading-9 text-slate-600 transition-colors hover:border-brand/40">
                            {{ $halaman }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya"
               class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition-colors hover:border-brand/40">
                <x-ikon nama="panah-kanan" class="h-4 w-4" />
            </a>
        @else
            <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-300"
                  aria-hidden="true">
                <x-ikon nama="panah-kanan" class="h-4 w-4" />
            </span>
        @endif
    </nav>
@endif
