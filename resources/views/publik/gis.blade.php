@extends('publik.layout')

@section('judul', 'GIS Dukcapil — Statistik Kependudukan · Disdukcapil Pesisir Barat')
@section('deskripsi', 'Jumlah dan komposisi penduduk Kabupaten Pesisir Barat per kecamatan dan desa, berdasarkan Data Konsolidasi Bersih (DKB).')

@use('App\Services\StatistikKependudukan', 'S')

@php
    /*
     * GIS Dukcapil — halaman statistik kependudukan (28 Sep 2026), menggantikan
     * peta lingkaran Leaflet. Susunan mengikuti portal Disdukcapil Kota Tual,
     * diporting dari portal Next.js saudara (app/media/gis/page.tsx).
     *
     * Angka, kartu, tabel: HTML dari server (terbaca mesin pencari & tanpa JS).
     * Peta, grafik, dropdown: island React (Highcharts) di atas HTML itu.
     */
    $kueri = function (array $ubah = []) use ($s, $ukuran) {
        $q = [];
        if ($s) {
            $q['periode'] = S::kunciPeriode($s['periode']);
            $q['wilayah'] = $s['wilayah']['kode'];
        }
        if ($ukuran !== 'penduduk') {
            $q['ukuran'] = $ukuran;
        }
        foreach ($ubah as $k => $v) {
            if ($k === 'ukuran' && $v === 'penduduk') {
                unset($q[$k]);
            } else {
                $q[$k] = $v;
            }
        }

        return '?'.http_build_query($q);
    };
@endphp

@section('konten')
<div class="min-h-screen bg-slate-50">
    <x-kepala-publik label="GIS Dukcapil · Data agregat"
                     judul="Statistik kependudukan"
                     ket="Jumlah dan komposisi penduduk berdasarkan Data Konsolidasi Bersih (DKB). Data bersifat agregat dan tidak memuat identitas individu."
                     ikon="peta" />

    <div class="container mx-auto px-4 py-10 md:px-8 lg:px-16">
        @if (! $s)
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">
                Data kependudukan belum tersedia.
            </div>
        @else
            @php
                $r = $s['ringkas'];
                $w = $s['wilayah'];
                $info = S::UKURAN[$ukuran];
                $selisih = ($s['pembanding'] && $r['penduduk'] !== null) ? $r['penduduk'] - $s['pembanding']['penduduk'] : null;
                $kecDipilih = $w['tingkat'] === 'kecamatan' ? $w['kode'] : ($w['tingkat'] === 'desa' ? ($w['induk'] ?? null) : null);
                $peringkat = collect($s['kecamatan'])
                    ->map(fn ($k) => ['k' => $k, 'n' => S::nilaiUkuran($k, $ukuran)])
                    ->sortByDesc(fn ($p) => $p['n'] ?? -1)->values();
                $nilaiAda = $peringkat->pluck('n')->filter(fn ($n) => $n !== null);
                $adaKosong = $peringkat->contains(fn ($p) => $p['n'] === null);
                $rincian = collect($s['kecamatan'])->firstWhere('kode', $kecDipilih);
                $usia = $s['usia'];
                $rasioTergantung = ($usia && $usia['produktif']) ? ($usia['anak'] + $usia['lansia']) / $usia['produktif'] * 100 : null;

                /*
                 * 🔴 Props island dirakit DI SINI, bukan di dalam `@json([...])`
                 * multi-baris: parser Blade salah membaca kurung pada argumen
                 * direktif yang memuat `fn () =>` / pemanggilan closure, dan view
                 * gagal dikompilasi ("Unclosed '['") — halaman 500.
                 */
                $propsFilter = [
                    'periode' => S::kunciPeriode($s['periode']),
                    'daftarPeriode' => $s['periodeTersedia'],
                    'wilayah' => $w['kode'],
                    'daftarWilayah' => $s['daftarWilayah'],
                    'hrefPdf' => '/media/gis/unduh'.$kueri(),
                ];
                $propsPeta = [
                    'kecamatan' => $s['kecamatan'],
                    'ukuran' => $ukuran,
                    'satuan' => $info['satuan'],
                    'dipilih' => $kecDipilih,
                    'hrefUntuk' => collect($s['kecamatan'])->mapWithKeys(fn ($k) => [$k['kode'] => $kueri(['wilayah' => $k['kode']])])->all(),
                ];
            @endphp

            {{-- Filter --}}
            <div data-island="FilterStatistik" data-props='@json($propsFilter)'>
                <div class="h-12"></div>
            </div>

            {{-- Judul wilayah + kartu --}}
            <section class="mt-10">
                <p class="text-sm text-slate-500">{{ S::labelTingkat($w) }}</p>
                <h2 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">{{ S::judulWilayah($w) }}</h2>
                <p class="mt-1 text-sm text-slate-500">Keadaan {{ $s['labelPeriode'] }}</p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Jumlah penduduk</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ S::angka($r['penduduk']) }} <span class="text-sm font-medium text-slate-500">jiwa</span></p>
                        @if ($selisih !== null)
                            <p class="mt-2 text-xs font-medium {{ $selisih >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $selisih >= 0 ? '▲ +' : '▼ ' }}{{ S::angka($selisih) }} dibanding {{ str_replace('Semester ', 'S', $s['pembanding']['label']) }}
                            </p>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Laki-laki &amp; perempuan</p>
                        <div class="mt-2 flex items-end justify-between gap-2">
                            <div>
                                <p class="text-xl font-semibold text-sky-700">{{ S::angka($r['laki']) }}</p>
                                <p class="text-xs text-slate-500">Laki-laki · {{ $r['penduduk'] ? S::persen($r['laki'] ?? 0, $r['penduduk']) : '—' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-semibold text-rose-600">{{ S::angka($r['perempuan']) }}</p>
                                <p class="text-xs text-slate-500">Perempuan · {{ $r['penduduk'] ? S::persen($r['perempuan'] ?? 0, $r['penduduk']) : '—' }}</p>
                            </div>
                        </div>
                        @if ($r['laki'] !== null && $r['perempuan'])
                            <div class="mt-3 flex h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="bg-sky-500" style="width: {{ $r['laki'] / ($r['laki'] + $r['perempuan']) * 100 }}%"></div>
                                <div class="flex-1 bg-rose-400"></div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">Rasio jenis kelamin <b class="text-slate-700">{{ S::desimal($r['laki'] / $r['perempuan'] * 100) }}</b> (laki-laki per 100 perempuan)</p>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Kepala keluarga</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ S::angka($r['kk']) }} <span class="text-sm font-medium text-slate-500">KK</span></p>
                        @if ($r['kk'] && $r['penduduk'])
                            <p class="mt-2 text-xs text-slate-500">Rata-rata {{ S::desimal($r['penduduk'] / $r['kk']) }} jiwa per keluarga</p>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Wajib KTP-el</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ S::angka($r['wajibKtp']) }} <span class="text-sm font-medium text-slate-500">jiwa</span></p>
                        @if ($r['wajibKtp'] !== null && $r['penduduk'])
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-brand" style="width: {{ min(100, $r['wajibKtp'] / $r['penduduk'] * 100) }}%"></div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">{{ S::persen($r['wajibKtp'], $r['penduduk']) }} dari jumlah penduduk</p>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Peta --}}
            <section class="mt-12">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Peta demografi per kecamatan</h2>
                <p class="mt-1 text-sm text-slate-500">{{ S::namaKabupaten() }}, {{ $s['labelPeriode'] }}. Pilih ukuran, lalu klik kecamatan untuk melihat rinciannya.</p>

                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap gap-2 border-b border-slate-100 p-3">
                        @foreach (S::UKURAN as $id => $u)
                            <a href="{{ $kueri(['ukuran' => $id]) }}"
                               class="rounded-full px-3.5 py-1.5 text-sm transition {{ $id === $ukuran ? 'bg-brand-dark text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $u['label'] }}</a>
                        @endforeach
                    </div>

                    {{-- Panel kanan ramping (320 px), tinggi peta dikunci di desktop: kolom
                         proporsional membuat panel melebar dan menyisakan ruang kosong. --}}
                    <div class="grid lg:grid-cols-[minmax(0,1fr)_320px]">
                        <div class="aspect-square w-full lg:aspect-auto lg:h-[640px]"
                             data-island="PetaStatistik" data-props='@json($propsPeta)'></div>

                        <div class="flex flex-col border-t border-slate-100 p-5 lg:border-l lg:border-t-0">
                            <p class="text-sm font-medium text-slate-700">{{ $info['label'] }} ({{ $info['satuan'] }})</p>
                            <div class="mt-2 flex h-2.5 overflow-hidden rounded-full">
                                @foreach (S::SKALA_WARNA as $warna)
                                    <div class="flex-1" style="background: {{ $warna }}"></div>
                                @endforeach
                            </div>
                            <div class="mt-1 flex justify-between text-xs text-slate-500">
                                <span>{{ $nilaiAda->isNotEmpty() ? S::formatUkuran($nilaiAda->min(), $ukuran) : '—' }}</span>
                                <span>{{ $nilaiAda->isNotEmpty() ? S::formatUkuran($nilaiAda->max(), $ukuran) : '—' }}</span>
                            </div>
                            @if ($adaKosong)
                                <p class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                                    <span class="inline-block h-3 w-5 rounded-sm border border-slate-300" style="background: {{ S::WARNA_TANPA_DATA }}"></span> belum ada data
                                </p>
                            @endif

                            <p class="mt-6 text-sm font-medium text-slate-700">Peringkat {{ mb_strtolower($info['label']) }}</p>
                            <ol class="mt-2 space-y-1">
                                @foreach ($peringkat as $i => $p)
                                    {{-- data-sorot-kode: kursor di baris ini menyalakan kecamatannya di peta. --}}
                                    <li data-sorot-kode="{{ $p['k']['kode'] }}">
                                        <a href="{{ $kueri(['wilayah' => $p['k']['kode']]) }}"
                                           class="flex items-center gap-3 rounded-lg px-2 py-1.5 text-sm transition hover:bg-slate-50 {{ $p['k']['kode'] === $kecDipilih ? 'bg-amber-50 ring-1 ring-amber-200' : '' }}">
                                            <span class="w-5 text-right text-xs text-slate-400">{{ $i + 1 }}</span>
                                            <span class="flex-1 text-slate-700">{{ S::namaCantik($p['k']['nama']) }}</span>
                                            <span class="font-medium tabular-nums text-slate-900">{{ $p['n'] === null ? '—' : S::formatUkuran($p['n'], $ukuran) }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ol>

                            @if ($rincian)
                                <div class="mt-auto rounded-xl border border-amber-200 bg-amber-50/50 p-4">
                                    <p class="text-xs font-medium uppercase tracking-wide text-amber-700">Rincian kecamatan</p>
                                    <p class="mt-0.5 font-semibold text-slate-900">{{ S::namaCantik($rincian['nama']) }}</p>
                                    <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5">
                                        @foreach ([
                                            'Penduduk' => S::angka($rincian['penduduk']).' jiwa',
                                            'Kepala keluarga' => S::angka($rincian['kk']).' KK',
                                            'Laki-laki' => S::angka($rincian['laki']),
                                            'Perempuan' => S::angka($rincian['perempuan']),
                                            'Jiwa per KK' => S::formatUkuran(S::nilaiUkuran($rincian, 'jiwa-kk'), 'jiwa-kk'),
                                            'Rasio JK' => S::formatUkuran(S::nilaiUkuran($rincian, 'rasio-jk'), 'rasio-jk'),
                                        ] as $l => $v)
                                            <div>
                                                <dt class="text-[11px] text-slate-500">{{ $l }}</dt>
                                                <dd class="text-sm font-semibold tabular-nums text-slate-900">{{ $v }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>
                            @else
                                <p class="mt-auto rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-3 text-xs leading-relaxed text-slate-500">
                                    Klik kecamatan di peta atau di daftar peringkat untuk melihat rinciannya.
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="overflow-x-auto border-t border-slate-100">
                        <table class="w-full min-w-[640px] text-sm">
                            <caption class="sr-only">Data kependudukan per kecamatan, {{ $s['labelPeriode'] }}</caption>
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Kecamatan</th>
                                    <th class="px-4 py-3 text-right font-semibold">Penduduk</th>
                                    <th class="px-4 py-3 text-right font-semibold">KK</th>
                                    <th class="px-4 py-3 text-right font-semibold">Jiwa/KK</th>
                                    <th class="px-4 py-3 text-right font-semibold">Laki-laki</th>
                                    <th class="px-4 py-3 text-right font-semibold">Perempuan</th>
                                    <th class="px-4 py-3 text-right font-semibold">Rasio JK</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach (collect($s['kecamatan'])->sortByDesc(fn ($k) => $k['penduduk'] ?? -1) as $k)
                                    <tr data-sorot-kode="{{ $k['kode'] }}" class="transition-colors hover:bg-slate-50 {{ $k['kode'] === $kecDipilih ? 'bg-amber-50/60' : '' }}">
                                        <td class="px-4 py-2.5"><a href="{{ $kueri(['wilayah' => $k['kode']]) }}" class="text-slate-800 hover:text-brand">{{ S::namaCantik($k['nama']) }}</a></td>
                                        <td class="px-4 py-2.5 text-right tabular-nums">{{ S::angka($k['penduduk']) }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums">{{ S::angka($k['kk']) }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums">{{ S::formatUkuran(S::nilaiUkuran($k, 'jiwa-kk'), 'jiwa-kk') }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums">{{ S::angka($k['laki']) }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums">{{ S::angka($k['perempuan']) }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums">{{ S::formatUkuran(S::nilaiUkuran($k, 'rasio-jk'), 'rasio-jk') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    Peta: batas kecamatan BPS 2015, disederhanakan. Sumber: github.com/ArrayAccess/Indonesia-Postal-And-Area (lisensi MIT). Batas bersifat indikatif, bukan acuan batas wilayah resmi.
                </p>
            </section>

            {{-- Komposisi --}}
            <section class="mt-12">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Komposisi penduduk</h2>
                <p class="mt-1 text-sm text-slate-500">{{ S::judulWilayah($w) }}, {{ $s['labelPeriode'] }}.</p>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    @if ($usia)
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                            <h3 class="font-semibold text-slate-900">Kelompok usia</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ S::angka($usia['jumlah']) }} jiwa menurut kelompok umur lima tahunan.
                                @if ($rasioTergantung !== null)
                                    Rasio ketergantungan <b class="text-slate-700">{{ S::desimal($rasioTergantung) }}</b> (usia non-produktif per 100 usia produktif).
                                @endif
                            </p>
                            <div class="mt-3 flex flex-wrap gap-4 text-sm">
                                <span>Anak (0–14) <b>{{ S::persen($usia['anak'], $usia['jumlah']) }}</b></span>
                                <span>Usia produktif (15–64) <b>{{ S::persen($usia['produktif'], $usia['jumlah']) }}</b></span>
                                <span>Lansia (65+) <b>{{ S::persen($usia['lansia'], $usia['jumlah']) }}</b></span>
                            </div>
                            <div class="mt-4 min-h-[280px]" data-island="GrafikUsia" data-props='@json(['butir' => $usia['butir'], 'jumlah' => $usia['jumlah']])'></div>
                        </div>
                    @endif

                    @foreach ($s['komposisi'] as $k)
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-baseline justify-between gap-3">
                                <h3 class="font-semibold text-slate-900">{{ $k['judul'] }}</h3>
                                <span class="text-xs text-slate-500">{{ S::angka($k['jumlah']) }} jiwa</span>
                            </div>
                            {{-- Daftar teks dulu (terbaca tanpa JS & oleh mesin pencari); island
                                 Highcharts menggantikannya begitu dimuat. --}}
                            <div class="mt-3" style="min-height: {{ 34 * count($k['butir']) + 24 }}px"
                                 data-island="GrafikBatang" data-props='@json(['butir' => $k['butir'], 'jumlah' => $k['jumlah']])'>
                                <ul class="space-y-1 text-sm text-slate-600">
                                    @foreach ($k['butir'] as $b)
                                        <li>{{ $b['label'] }}: {{ S::angka($b['nilai']) }} ({{ S::persen($b['nilai'], $k['jumlah']) }})</li>
                                    @endforeach
                                </ul>
                            </div>
                            @foreach ($k['catatan'] as $c)
                                <p class="mt-3 text-xs text-slate-500">{{ $c }}</p>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Tren --}}
            <section class="mt-12">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Perkembangan jumlah penduduk</h2>
                <p class="mt-1 text-sm text-slate-500">{{ S::judulWilayah($w) }}, per periode data.</p>
                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="min-h-[240px]" data-island="GrafikTren" data-props='@json(['tren' => $s['tren'], 'kini' => $s['labelPeriode']])'></div>
                </div>
                @if (count($s['tren']) < 2)
                    <p class="mt-3 text-xs text-slate-500">Perbandingan antar-periode tampil setelah data semester berikutnya diimpor.</p>
                @endif
            </section>

            <p class="mt-10 border-t border-slate-200 pt-4 text-xs text-slate-500">
                Sumber: Data Konsolidasi Bersih (DKB) Dinas Kependudukan dan Pencatatan Sipil {{ S::namaKabupaten() }}, {{ $s['labelPeriode'] }}. Persentase dibulatkan satu angka di belakang koma.
            </p>
        @endif
    </div>
</div>
@endsection
