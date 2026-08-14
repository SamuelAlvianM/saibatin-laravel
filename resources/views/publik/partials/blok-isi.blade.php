{{--
  Isi satu blok halaman informasi: gambar → paragraf → butir → tautan.

  Dipisah dari `publik/info.blade.php` karena halaman layanan PPID
  (`ppid-layanan.blade.php`) menampilkan DUA blok seperti ini dalam satu
  halaman — dan menyalin markup-nya berarti dua tempat yang pelan-pelan
  menyimpang.

  @param array $isi  ['gambar'?, 'body'?, 'list'?, 'links'?]
--}}

@if (! empty($isi['gambar']))
    {{-- Infografis/alur. `loading="lazy"` karena gambar ini hampir selalu di
         bawah lipatan, dan sebagiannya poster besar. --}}
    <img src="{{ $isi['gambar'] }}" alt="{{ $isi['title'] ?? 'Infografis' }}" loading="lazy"
         class="w-full rounded-xl border border-slate-200 bg-slate-50 object-contain">
@endif

@foreach ($isi['body'] ?? [] as $paragraf)
    <p class="text-sm leading-relaxed text-slate-700">{{ $paragraf }}</p>
@endforeach

@if (! empty($isi['list']))
    <ul class="space-y-2 pt-2">
        @foreach ($isi['list'] as $butir)
            <li class="flex items-start gap-2 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                <span class="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-brand"></span>
                {{ $butir }}
            </li>
        @endforeach
    </ul>
@endif

@if (! empty($isi['links']))
    <div class="flex flex-wrap gap-3 pt-2">
        @foreach ($isi['links'] as $tautan)
            @if ($tautan['external'] ?? false)
                <a href="{{ $tautan['href'] }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-brand-dark">
                    {{ $tautan['label'] }}
                    <x-ikon nama="tautan-luar" class="h-4 w-4" />
                </a>
            @else
                <a href="{{ $tautan['href'] }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-brand/30 px-4 py-2.5 text-sm font-medium text-brand transition-colors hover:bg-brand/5">
                    {{ $tautan['label'] }}
                </a>
            @endif
        @endforeach
    </div>
@endif
