{{--
  Daftar tanya-jawab `/pusat-bantuan/faq` — port `components/shared/faq-list.tsx`.

  Isinya dari blok CMS `pusat-bantuan.faq` (Dashboard → Konten Halaman), jadi
  petugas bisa menambah/mengubah pertanyaan tanpa deploy.

  🔴 Ditulis dengan `<details>/<summary>` BAWAAN, bukan island React. Bedanya
  bukan selera: pertanyaan & jawabannya justru isi yang paling dicari lewat
  mesin pencari, dan `<details>` menaruh keduanya di HTML sejak awal — versi
  React hanya mengirim daftar kosong ke crawler. Buka-tutupnya pun jalan tanpa
  JS sama sekali. Yang hilang cuma animasi tinggi; sebagai gantinya panah
  penanda diputar lewat CSS `[open]`.

  Yang PERTAMA dibiarkan terbuka supaya halaman tidak terbaca sebagai tumpukan
  baris tertutup yang tak jelas bisa diklik — sama seperti aslinya.

  @param array $faq  [['pertanyaan' => …, 'jawaban' => …], …]
--}}
@php($daftar = collect($faq)->filter(fn ($f) => filled($f['pertanyaan'] ?? null))->values())

<div class="pt-2">
    @if ($daftar->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-10 text-center">
            <x-ikon nama="tanya" class="mx-auto h-8 w-8 text-slate-300" />
            <p class="mt-3 text-sm text-slate-500">Daftar pertanyaan belum diisi.</p>
            <p class="mt-1 text-xs text-slate-400">
                Petugas dapat mengisinya lewat Dashboard → Konten Halaman → &ldquo;Pusat Bantuan — Daftar FAQ&rdquo;.
            </p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($daftar as $i => $item)
                <details class="group overflow-hidden rounded-2xl border border-slate-200/70 bg-white transition-colors open:border-brand/30 open:shadow-sm"
                         @if ($i === 0) open @endif>
                    <summary class="flex cursor-pointer list-none items-start gap-3 px-5 py-4 text-left [&::-webkit-details-marker]:hidden">
                        <span class="mt-0.5 flex h-6 w-6 flex-none items-center justify-center rounded-lg bg-slate-100 text-[0.7rem] font-bold text-slate-500 group-open:bg-brand group-open:text-white">
                            {{ $i + 1 }}
                        </span>
                        <span class="min-w-0 flex-1 text-sm font-medium text-slate-900">{{ $item['pertanyaan'] }}</span>
                        <span class="mt-0.5 flex-none text-slate-400 transition-transform duration-200 group-open:rotate-90 group-open:text-brand">
                            <x-ikon nama="chevron-kanan" class="h-4 w-4" />
                        </span>
                    </summary>
                    <div class="border-t border-slate-100 px-5 py-4 pl-14 text-sm leading-relaxed text-slate-600">
                        {!! nl2br(e($item['jawaban'] ?? '—')) !!}
                    </div>
                </details>
            @endforeach
        </div>
    @endif
</div>
