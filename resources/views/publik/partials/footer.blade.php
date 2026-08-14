{{--
  Footer situs publik — port `components/shared/footer.tsx`.

  Isinya statis semua, jadi ditulis sebagai Blade, bukan island: tautan dan
  alamat kantor termasuk yang paling berguna dibaca mesin pencari, dan tidak
  ada alasan mengirim React untuk menampilkannya. Satu-satunya bagian yang
  hidup adalah penghitung pengunjung di bawah.
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
                <div>
                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-wider text-white">Layanan</h4>
                    <ul class="space-y-2.5">
                        @foreach ([
                            ['Ajukan Permohonan', '/user/pengajuan/baru'],
                            ['Riwayat Permohonan', '/user/pengajuan'],
                            ['Pengaduan & Konsultasi', '/pusat-bantuan/pengaduan-konsultasi'],
                            ['Survei Kepuasan', '/survei-kepuasan'],
                        ] as [$label, $href])
                            <li>
                                <a href="{{ $href }}" class="text-sm transition-colors hover:text-white">{{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-wider text-white">Informasi</h4>
                    <ul class="space-y-2.5">
                        {{-- "Hubungi Kami" tidak lagi di navbar (mengikuti SIDAKO),
                             jadi footer inilah satu-satunya jalan masuk tetapnya. --}}
                        @foreach ([
                            ['Berita', '/media/berita'],
                            ['Galeri', '/galeri'],
                            ['Informasi Produk', '/produk/produk-disdukcapil'],
                            ['PPID', '/ppid/profil-ppid'],
                            ['Hubungi Kami', '/hubungi-kami'],
                        ] as [$label, $href])
                            <li>
                                <a href="{{ $href }}" class="text-sm transition-colors hover:text-white">{{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Kantor Kami --}}
            <div class="lg:col-span-4">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-wider text-white">Kantor Kami</h4>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start gap-3">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1116 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span>
                                Komplek Perkantoran Pemda Kabupaten Pesisir Barat,<br>
                                Kec. Pesisir Tengah, Kabupaten Pesisir Barat, Lampung
                            </span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="h-4 w-4 flex-shrink-0 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <rect x="2" y="4" width="20" height="16" rx="2"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m22 7-10 6L2 7"/>
                            </svg>
                            <span>disdukcapil@pesisirbaratkab.go.id</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="h-4 w-4 flex-shrink-0 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>
                            </svg>
                            <span>Senin – Jumat: 08.00 – 16.00 WIB</span>
                        </li>
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
                    <a href="/kebijakan-privasi" class="transition-colors hover:text-white">Kebijakan Privasi</a>
                    <span class="text-slate-700">|</span>
                    <a href="/syarat" class="transition-colors hover:text-white">Syarat &amp; Ketentuan</a>
                    <span class="text-slate-700">|</span>
                    <a href="/sitemap" class="transition-colors hover:text-white">Sitemap</a>
                </div>
            </div>
        </div>
    </div>
</footer>
