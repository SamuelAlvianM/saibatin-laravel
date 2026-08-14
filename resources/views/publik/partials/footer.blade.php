{{--
  Footer situs publik — port `components/shared/footer.tsx`.

  Isinya statis semua, jadi ditulis sebagai Blade, bukan island: tautan dan
  alamat kantor termasuk yang paling berguna dibaca mesin pencari, dan tidak
  ada alasan mengirim React untuk menampilkannya. Satu-satunya bagian yang
  hidup adalah penghitung pengunjung di bawah.

  🔴 Kembarannya untuk halaman Inertia ada di `Components/FooterPublik.jsx`.
  Markup-nya memang dua (Blade tidak bisa mengimpor JS), tapi ISINYA satu:
  `config/footer.php`. Menambah tautan atau mengganti alamat cukup di sana.
--}}
<footer class="bg-gradient-to-b from-slate-900 to-[#0d1b2a] text-slate-400">
    <div class="container mx-auto px-4 py-14 md:px-8 lg:px-16 lg:py-16">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-8">
            {{-- Brand --}}
            <div class="space-y-5 lg:col-span-5">
                <div class="flex items-center gap-3">
                    <img src="/logo-saibatin.png" alt="Logo SAIBATIN"
                         class="h-11 w-11 flex-shrink-0 object-contain">
                    <div>
                        <span class="text-lg font-bold tracking-wide text-white">SAIBATIN</span>
                        <p class="text-xs text-slate-400">Disdukcapil Kabupaten Pesisir Barat</p>
                    </div>
                </div>
                <p class="max-w-md text-sm leading-relaxed">
                    Portal layanan administrasi kependudukan dan pencatatan sipil
                    Kabupaten Pesisir Barat. Melayani masyarakat secara profesional,
                    akuntabel, dan prima.
                </p>
            </div>

            {{-- Kelompok tautan --}}
            <div class="grid grid-cols-2 gap-8 lg:col-span-3">
                @foreach (config('footer.tautan') as $grup)
                    <div>
                        <h4 class="mb-4 text-sm font-semibold uppercase tracking-wider text-white">{{ $grup['judul'] }}</h4>
                        <ul class="space-y-2.5">
                            @foreach ($grup['items'] as $item)
                                <li>
                                    <a href="{{ $item['href'] }}" class="text-sm transition-colors hover:text-white">{{ $item['label'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            {{-- Kantor Kami --}}
            <div class="lg:col-span-4">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-wider text-white">Kantor Kami</h4>
                    <ul class="space-y-3 text-sm">
                        @foreach (config('footer.kantor') as $baris)
                            <li class="flex items-start gap-3">
                                <x-ikon :nama="$baris['ikon']" class="mt-0.5 h-4 w-4 flex-shrink-0 text-yellow-400" />
                                <span>{!! nl2br(e($baris['teks'])) !!}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Bilah bawah --}}
    <div class="border-t border-white/10">
        <div class="container mx-auto flex justify-center border-b border-white/5 px-4 py-4 md:px-8 lg:px-16">
            <div data-island="HitungPengunjung"></div>
        </div>
        <div class="container mx-auto px-4 py-5 md:px-8 lg:px-16">
            <div class="flex flex-col items-center justify-between gap-3 text-xs text-slate-500 md:flex-row">
                <p>
                    &copy; {{ now()->year }}
                    <span class="font-medium text-slate-300">SAIBATIN</span> —
                    Disdukcapil Kabupaten Pesisir Barat
                </p>
                <div class="flex items-center gap-4">
                    @foreach (config('footer.legal') as $i => $item)
                        @if ($i > 0)
                            <span class="text-slate-700">|</span>
                        @endif
                        <a href="{{ $item['href'] }}" class="transition-colors hover:text-white">{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</footer>
