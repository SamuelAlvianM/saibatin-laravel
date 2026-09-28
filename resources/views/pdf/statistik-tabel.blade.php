{{-- Tabel kategori + batang persen di kanan (pola laporan Tual). Dipakai pdf/statistik. --}}
@use('App\Services\StatistikKependudukan', 'S')
@php $maks = max(1, max(array_column($butir, 'nilai') ?: [1])); @endphp
<table class="data">
    <tr>
        <th style="width: 34%">{{ $judulKolom }}</th>
        <th class="kanan" style="width: 14%">Jumlah</th>
        <th class="kanan" style="width: 10%">%</th>
        <th style="width: 42%"></th>
    </tr>
    @foreach ($butir as $b)
        <tr>
            <td>{{ $b['label'] }}</td>
            <td class="kanan">{{ S::angka($b['nilai']) }}</td>
            <td class="kanan">{{ S::persen($b['nilai'], $jumlah) }}</td>
            <td><div class="batang"><div style="width: {{ max($b['nilai'] > 0 ? 1 : 0, round($b['nilai'] / $maks * 100, 1)) }}%"></div></div></td>
        </tr>
    @endforeach
    @if ($denganJumlah)
        <tr class="jumlah"><td>Jumlah</td><td class="kanan">{{ S::angka($jumlah) }}</td><td class="kanan">100%</td><td></td></tr>
    @endif
</table>
