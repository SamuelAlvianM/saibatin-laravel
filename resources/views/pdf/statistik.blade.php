{{--
  Laporan Statistik Kependudukan — dirender dompdf (StatistikPdf).
  Templat mengikuti unduhan portal Disdukcapil Kota Tual.

  🔴 CSS-nya sengaja kuno, sama alasannya dengan pdf/permohonan: dompdf tidak
  mengenal flexbox/grid — tata letak memakai <table>. Font DejaVu Sans (bawaan
  dompdf) supaya "–", "·", dan huruf beraksen tidak jadi kotak.
--}}
@use('App\Services\StatistikKependudukan', 'S')
@php
    $aksen = '#1B4B72';
    $r = $s['ringkas'];
    $w = $s['wilayah'];
    $kecamatan = collect($s['kecamatan'])->sortByDesc(fn ($k) => $k['penduduk'] ?? -1)->values();
    $nilai = $kecamatan->pluck('penduduk')->filter(fn ($n) => $n !== null);
    $min = (float) ($nilai->min() ?? 0);
    $maks = (float) ($nilai->max() ?? 0);
    $dipilih = $w['tingkat'] === 'kecamatan' ? $w['kode'] : ($w['tingkat'] === 'desa' ? ($w['induk'] ?? null) : null);
    $selisih = ($s['pembanding'] && $r['penduduk'] !== null) ? $r['penduduk'] - $s['pembanding']['penduduk'] : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Statistik Kependudukan {{ S::judulWilayah($w) }} — {{ $s['labelPeriode'] }}</title>
    <style>
        @page { margin: 14mm 12mm 16mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #0f172a; }
        table { border-collapse: collapse; }
        .kop { width: 100%; border-bottom: 1px solid {{ $aksen }}; padding-bottom: 6px; }
        .kop td { vertical-align: top; }
        .dinas { font-size: 12.5px; font-weight: bold; line-height: 1.35; }
        .kecil { font-size: 7.5px; color: #64748b; }
        .label { font-size: 7.5px; font-weight: bold; color: {{ $aksen }}; letter-spacing: 1.2px; margin-top: 12px; }
        h1 { font-size: 20px; margin: 4px 0 2px; }
        h2 { font-size: 12px; margin: 16px 0 3px; }
        .sub { color: #475569; font-size: 8.5px; }
        .kartu { width: 100%; margin-top: 10px; }
        .kartu td { width: 25%; padding: 0 4px; }
        .kartu .isi { border: 1px solid #e2e8f0; border-radius: 5px; padding: 7px 8px; height: 54px; }
        .angka { font-size: 15px; font-weight: bold; }
        .satuan { font-size: 7.5px; font-weight: bold; color: #64748b; }
        .data { width: 100%; margin-top: 4px; }
        .data th { background: #f1f5f9; color: #475569; font-size: 6.8px; text-transform: uppercase; letter-spacing: .3px; padding: 6px 5px; text-align: left; }
        .data td { padding: 5px; border-bottom: 1px solid #e2e8f0; }
        .kanan, .data .kanan { text-align: right; }
        .batang { background: #eef2f5; height: 4px; border-radius: 2px; }
        .batang div { background: {{ $aksen }}; height: 4px; border-radius: 2px; }
        .kotak { display: inline-block; width: 7px; height: 7px; margin-right: 4px; }
        .jumlah td { font-weight: bold; }
        .catatan { font-size: 7px; color: #64748b; margin-top: 4px; }
        .utuh { page-break-inside: avoid; }
    </style>
</head>
<body>

{{-- Kop --}}
<table class="kop">
    <tr>
        @if ($logo)
            <td style="width: 50px"><img src="{{ $logo }}" style="height: 44px"></td>
        @endif
        <td>
            <div class="dinas">Dinas Kependudukan dan<br>Pencatatan Sipil {{ S::namaKabupaten() }}</div>
            <div class="kecil" style="margin-top: 3px">{{ S::namaKabupaten() }}, Lampung</div>
        </td>
        <td class="kanan kecil" style="width: 210px">
            Diunduh {{ $diunduh }}<br>{{ $url }}
        </td>
    </tr>
</table>

<div class="label">LAPORAN STATISTIK KEPENDUDUKAN</div>
<h1>{{ S::judulWilayah($w) }}</h1>
<div class="sub">Keadaan {{ $s['labelPeriode'] }} · Data Konsolidasi Bersih (DKB), agregat tanpa identitas individu</div>

{{-- Kartu --}}
<table class="kartu">
    <tr>
        <td><div class="isi">
            <div class="kecil">Jumlah penduduk</div>
            <div style="margin-top: 5px"><span class="angka">{{ S::angka($r['penduduk']) }}</span> <span class="satuan">jiwa</span></div>
            <div class="kecil" style="margin-top: 4px; color: {{ $s['pembanding'] ? '#047857' : '#64748b' }}">
                {{ $selisih !== null ? ($selisih >= 0 ? '+' : '').S::angka($selisih).' dibanding '.str_replace('Semester ', 'S', $s['pembanding']['label']) : 'Periode data pertama' }}
            </div>
        </div></td>
        <td><div class="isi">
            <div class="kecil">Laki-laki / perempuan</div>
            <div style="margin-top: 5px"><span class="angka" style="font-size: 12.5px">{{ S::angka($r['laki']) }} / {{ S::angka($r['perempuan']) }}</span></div>
            <div class="kecil" style="margin-top: 4px">
                @if ($r['penduduk'] && $r['laki'] !== null && $r['perempuan'])
                    {{ S::persen($r['laki'], $r['penduduk']) }} / {{ S::persen($r['perempuan'], $r['penduduk']) }} · rasio JK {{ S::desimal($r['laki'] / $r['perempuan'] * 100) }}
                @else — @endif
            </div>
        </div></td>
        <td><div class="isi">
            <div class="kecil">Kepala keluarga</div>
            <div style="margin-top: 5px"><span class="angka">{{ S::angka($r['kk']) }}</span> <span class="satuan">KK</span></div>
            <div class="kecil" style="margin-top: 4px">{{ ($r['kk'] && $r['penduduk']) ? 'Rata-rata '.S::desimal($r['penduduk'] / $r['kk']).' jiwa per KK' : '—' }}</div>
        </div></td>
        <td><div class="isi">
            <div class="kecil">Wajib KTP-el</div>
            <div style="margin-top: 5px"><span class="angka">{{ S::angka($r['wajibKtp']) }}</span> <span class="satuan">jiwa</span></div>
            <div class="kecil" style="margin-top: 4px">{{ ($r['wajibKtp'] !== null && $r['penduduk']) ? S::persen($r['wajibKtp'], $r['penduduk']).' dari jumlah penduduk' : '—' }}</div>
        </div></td>
    </tr>
</table>

{{-- Peta + peringkat --}}
<div class="utuh">
    <h2>Peta demografi per kecamatan</h2>
    <div class="catatan" style="margin-top: 0">{{ S::namaKabupaten() }}, {{ $s['labelPeriode'] }}. Warna makin gelap berarti jumlah penduduk makin banyak. Wilayah abu-abu di sekitarnya adalah kabupaten tetangga.</div>
    <table style="width: 100%; margin-top: 6px">
        <tr>
            <td style="width: 270px; vertical-align: top">
                @if ($peta)
                    <img src="{{ $peta }}" style="width: 262px; height: 262px">
                @endif
            </td>
            <td style="vertical-align: top; padding-left: 14px">
                <div style="font-weight: bold">Jumlah penduduk (jiwa)</div>
                <table style="width: 100%; margin-top: 5px"><tr>
                    @foreach (S::SKALA_WARNA as $warna)
                        <td style="background: {{ $warna }}; height: 6px"></td>
                    @endforeach
                </tr></table>
                <table style="width: 100%"><tr>
                    <td class="kecil">{{ S::angka($min) }}</td><td class="kecil kanan">{{ S::angka($maks) }}</td>
                </tr></table>
                <div style="font-weight: bold; margin-top: 12px">Peringkat jumlah penduduk</div>
                <table style="width: 100%; margin-top: 4px">
                    @foreach ($kecamatan as $i => $k)
                        <tr style="{{ $k['kode'] === $dipilih ? 'background: #fdf6d8; font-weight: bold' : '' }}">
                            <td class="kecil" style="width: 12px; padding: 2px 0">{{ $i + 1 }}</td>
                            <td style="padding: 2px 0"><span class="kotak" style="background: {{ S::warnaUntuk($k['penduduk'], $min, $maks) }}"></span>{{ S::namaCantik($k['nama']) }}</td>
                            <td class="kanan" style="padding: 2px 0">{{ $k['penduduk'] === null ? '—' : S::angka($k['penduduk']).' jiwa' }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>
</div>

{{-- Tabel kecamatan --}}
<div class="utuh">
    <table class="data" style="margin-top: 10px">
        <tr>
            <th>Kecamatan</th><th class="kanan">Penduduk</th><th class="kanan">KK</th><th class="kanan">Jiwa/KK</th>
            <th class="kanan">Laki-laki</th><th class="kanan">Perempuan</th><th class="kanan">Rasio JK</th>
        </tr>
        @foreach ($kecamatan as $k)
            <tr>
                <td><span class="kotak" style="background: {{ S::warnaUntuk($k['penduduk'], $min, $maks) }}"></span>{{ S::namaCantik($k['nama']) }}</td>
                <td class="kanan">{{ S::angka($k['penduduk']) }}</td>
                <td class="kanan">{{ S::angka($k['kk']) }}</td>
                <td class="kanan">{{ S::formatUkuran(S::nilaiUkuran($k, 'jiwa-kk'), 'jiwa-kk') }}</td>
                <td class="kanan">{{ S::angka($k['laki']) }}</td>
                <td class="kanan">{{ S::angka($k['perempuan']) }}</td>
                <td class="kanan">{{ S::formatUkuran(S::nilaiUkuran($k, 'rasio-jk'), 'rasio-jk') }}</td>
            </tr>
        @endforeach
    </table>
    <div class="catatan">Rasio JK = laki-laki per 100 perempuan. Peta: batas kecamatan BPS 2015, disederhanakan. Sumber: github.com/ArrayAccess/Indonesia-Postal-And-Area (lisensi MIT). Batas bersifat indikatif, bukan acuan batas wilayah resmi.</div>
</div>

{{-- Kelompok usia --}}
@if ($s['usia'])
    @php $u = $s['usia']; @endphp
    <div class="utuh">
        <h2>Kelompok usia</h2>
        <div class="catatan" style="margin-top: 0">
            Anak (0–14): {{ S::persen($u['anak'], $u['jumlah']) }} · Usia produktif (15–64): {{ S::persen($u['produktif'], $u['jumlah']) }} ·
            Lansia (65+): {{ S::persen($u['lansia'], $u['jumlah']) }}
            @if ($u['produktif']) · Rasio ketergantungan: {{ S::desimal(($u['anak'] + $u['lansia']) / $u['produktif'] * 100) }} @endif
        </div>
        @include('pdf.statistik-tabel', ['judulKolom' => 'Usia (tahun)', 'butir' => $u['butir'], 'jumlah' => $u['jumlah'], 'denganJumlah' => false])
    </div>
@endif

{{-- Komposisi --}}
@foreach ($s['komposisi'] as $k)
    <div class="utuh">
        <h2>{{ $k['judul'] }}</h2>
        @include('pdf.statistik-tabel', ['judulKolom' => 'Kategori', 'butir' => $k['butir'], 'jumlah' => $k['jumlah'], 'denganJumlah' => true])
        @foreach ($k['catatan'] as $c)
            <div class="catatan">{{ $c }}</div>
        @endforeach
    </div>
@endforeach

{{-- Perkembangan --}}
<div class="utuh">
    <h2>Perkembangan jumlah penduduk</h2>
    <table class="data">
        <tr><th>Periode</th><th class="kanan">Penduduk</th><th class="kanan">Perubahan</th></tr>
        @foreach ($s['tren'] as $i => $t)
            @php
                $lalu = $s['tren'][$i - 1] ?? null;
                $d = $lalu ? $t['penduduk'] - $lalu['penduduk'] : null;
                $kini = $t['periode'] == $s['periode'];
            @endphp
            <tr style="{{ $kini ? 'background: #fdf6e3; font-weight: bold' : '' }}">
                <td>{{ $t['label'] }}</td>
                <td class="kanan">{{ S::angka($t['penduduk']) }}</td>
                <td class="kanan" style="color: {{ $d === null ? '#64748b' : ($d >= 0 ? '#047857' : '#b91c1c') }}">
                    {{ $d === null ? '–' : ($d >= 0 ? '+' : '').S::angka($d).' ('.($d >= 0 ? '+' : '').S::desimal($d / $lalu['penduduk'] * 100).'%)' }}
                </td>
            </tr>
        @endforeach
    </table>
    <div class="catatan" style="margin-top: 8px">Sumber: Data Konsolidasi Bersih (DKB) Dinas Kependudukan dan Pencatatan Sipil {{ S::namaKabupaten() }}, {{ $s['labelPeriode'] }}. Persentase dibulatkan satu angka di belakang koma.</div>
</div>
</body>
</html>
