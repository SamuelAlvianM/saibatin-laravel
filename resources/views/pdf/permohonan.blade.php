{{--
  Tanda terima permohonan — dirender dompdf.

  🔴 CSS-nya sengaja kuno: dompdf tidak mengenal flexbox maupun grid, dan
  diam-diam menjatuhkannya jadi tumpukan blok. Tata letak dua kolom di sini
  memakai <table>, bukan karena selera, tapi karena itu satu-satunya yang
  dirender dompdf sama persis dengan yang terlihat di peramban.

  🔴 Font bawaan dompdf (DejaVu Sans) memang memuat karakter Latin lengkap —
  jangan diganti ke font sistem seperti Segoe UI: font itu tidak ada di server
  cPanel dan dompdf akan diam-diam mundur ke Helvetica yang kehilangan
  karakter beraksen (mis. "–" pada rentang tanggal jadi kotak).
--}}
@php
    $warna = ['aksen' => '#1B4B72', 'muda' => '#EAF0F6', 'garis' => '#C9D6E2'];
    $lencana = [
        'MENUNGGU' => ['#FEF3C7', '#92400E'],
        'DIPROSES' => ['#DBEAFE', '#1E40AF'],
        'SELESAI' => ['#D1FAE5', '#065F46'],
        'DITOLAK' => ['#FEE2E2', '#991B1B'],
    ][$p->status] ?? ['#E5E7EB', '#374151'];

    // `$logo` datang sudah jadi dari controller (data URI, sudah dikecilkan) —
    // lihat PermohonanPdfController::logoKop().
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Permohonan {{ $p->no_register }}</title>
    <style>
        @page { margin: 26mm 16mm 20mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1F2937; }

        .kop { width: 100%; border-bottom: 2px solid {{ $warna['aksen'] }}; padding-bottom: 8px; }
        .kop td { vertical-align: middle; }
        .kop .pemerintah { font-size: 11px; letter-spacing: .5px; }
        .kop .dinas { font-size: 14px; font-weight: bold; color: {{ $warna['aksen'] }}; }
        .kop .alamat { font-size: 8px; color: #6B7280; }

        h1 { font-size: 13px; text-align: center; margin: 14px 0 2px; letter-spacing: .5px; }
        .sub { text-align: center; font-size: 9px; color: #6B7280; margin-bottom: 12px; }

        .ringkas { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .ringkas td { border: 1px solid {{ $warna['garis'] }}; padding: 6px 8px; }
        .ringkas .label { background: {{ $warna['muda'] }}; font-weight: bold; width: 26%; }

        .lencana { display: inline-block; padding: 2px 8px; border-radius: 8px;
                   background: {{ $lencana[0] }}; color: {{ $lencana[1] }}; font-weight: bold; }

        h2 { font-size: 10px; text-transform: uppercase; letter-spacing: .6px;
             color: {{ $warna['aksen'] }}; border-bottom: 1px solid {{ $warna['garis'] }};
             padding-bottom: 3px; margin: 16px 0 6px; }

        table.isi { width: 100%; border-collapse: collapse; }
        table.isi td { padding: 4px 6px; border-bottom: 1px solid #EEF2F6; vertical-align: top; }
        table.isi td.k { width: 34%; color: #6B7280; }
        table.isi td.v { font-weight: bold; }

        table.lampiran { width: 100%; border-collapse: separate; border-spacing: 6px 8px; }
        table.lampiran td { width: 50%; vertical-align: top; }
        .judul-lampiran { font-size: 8px; font-weight: bold; margin-bottom: 3px; color: #374151; }
        table.lampiran img {
            width: 100%;
            border: 1px solid {{ $warna['garis'] }};
            /* 🔴 TANPA max-height. dompdf mengabaikannya pada <img> dan justru
               memotong gambarnya alih-alih menskalakan — lebih baik biarkan
               proporsinya utuh dan halaman yang memanjang. */
        }
        .tanpa-gambar { border: 1px dashed {{ $warna['garis'] }}; padding: 14px 8px;
                        text-align: center; font-size: 8px; color: #9CA3AF; }

        .catatan { background: {{ $warna['muda'] }}; border-left: 3px solid {{ $warna['aksen'] }};
                   padding: 7px 9px; margin-top: 6px; }

        .kaki { position: fixed; bottom: -12mm; left: 0; right: 0;
                font-size: 7.5px; color: #9CA3AF; border-top: 1px solid #E5E7EB; padding-top: 4px; }
        .kaki td { color: #9CA3AF; }
    </style>
</head>
<body>

<table class="kop">
    <tr>
        @if ($logo)
            <td style="width: 58px;"><img src="{{ $logo }}" style="height: 52px;" alt=""></td>
        @endif
        <td style="text-align: center;">
            <div class="pemerintah">PEMERINTAH KABUPATEN PESISIR BARAT</div>
            <div class="dinas">DINAS KEPENDUDUKAN DAN PENCATATAN SIPIL</div>
            <div class="alamat">Pasar Mulya Timur 01, Pasar Krui, Kec. Pesisir Tengah &middot; Telp (0728) 21XXX</div>
        </td>
        @if ($logo)
            <td style="width: 58px;"></td>
        @endif
    </tr>
</table>

<h1>TANDA TERIMA PERMOHONAN LAYANAN</h1>
<div class="sub">
    Bukti pengajuan layanan administrasi kependudukan melalui Portal SAIBATIN.
    Dokumen ini <strong>bukan</strong> dokumen kependudukan resmi.
</div>

<table class="ringkas">
    <tr>
        <td class="label">No. Registrasi</td>
        <td style="font-weight: bold; font-size: 12px;">{{ $p->no_register }}</td>
        <td class="label">Status</td>
        <td><span class="lencana">{{ $p->status }}</span></td>
    </tr>
    <tr>
        <td class="label">Jenis Layanan</td>
        <td>{{ $p->jenis->nama ?? '-' }}</td>
        <td class="label">Tanggal Diajukan</td>
        <td>{{ $p->created_at?->translatedFormat('d F Y, H.i') ?? '-' }} WIB</td>
    </tr>
    <tr>
        <td class="label">Pemohon</td>
        <td>{{ $p->user->user_fullname ?: ($p->user->user_id ?? '-') }}</td>
        <td class="label">
            {{-- Kolom terakhir berubah arti mengikuti status: sebelum diproses
                 belum ada petugas yang bisa disebut, jadi ruangnya dipakai
                 kontak pemohon alih-alih dibiarkan bergaris kosong. --}}
            {{ $p->proses_at ? 'Diperbarui' : 'Kontak' }}
        </td>
        <td>
            @if ($p->proses_at)
                {{ $p->proses_at->translatedFormat('d F Y, H.i') }} WIB
                @if ($p->proses_by_name) <br><span style="color:#6B7280">oleh {{ $p->proses_by_name }}</span> @endif
            @else
                {{ $p->user->user_hp ?: ($p->user->user_email ?: '-') }}
            @endif
        </td>
    </tr>
</table>

<h2>Data Permohonan</h2>
@if (count($data))
    <table class="isi">
        @foreach ($data as $baris)
            <tr>
                <td class="k">{{ $baris['label'] }}</td>
                <td class="v">{{ $baris['nilai'] }}</td>
            </tr>
        @endforeach
    </table>
@else
    <p style="color:#6B7280">Tidak ada rincian data pada permohonan ini.</p>
@endif

<h2>Berkas Dilampirkan ({{ count($berkas) }})</h2>
@if (count($berkas))
    {{-- Dua lampiran per baris. <table>, bukan flex/grid: dompdf tidak
         mengenal keduanya dan akan menumpuknya jadi satu kolom. --}}
    <table class="lampiran">
        @foreach (array_chunk($berkas, 2, true) as $baris)
            <tr>
                @foreach ($baris as $i => $b)
                    <td>
                        <div class="judul-lampiran">{{ $loop->parent->index * 2 + $loop->index + 1 }}. {{ $b['label'] }}</div>
                        @if ($b['gambar'])
                            <img src="{{ $b['gambar'] }}" alt="{{ $b['label'] }}">
                        @else
                            {{-- Jujur soal alasannya: berkas hilang, terlalu besar,
                                 atau formatnya bukan gambar. Kotak kosong tanpa
                                 keterangan terbaca seperti kerusakan. --}}
                            <div class="tanpa-gambar">Pratinjau tidak tersedia — buka berkasnya melalui portal.</div>
                        @endif
                    </td>
                @endforeach
                {{-- Baris ganjil: sel penyeimbang supaya gambar terakhir tidak
                     melebar jadi selebar halaman. --}}
                @if (count($baris) === 1)
                    <td></td>
                @endif
            </tr>
        @endforeach
    </table>
    <p style="color:#6B7280; margin-top:6px;">
        Pratinjau di atas diperkecil untuk keperluan cetak. Berkas beresolusi penuh
        hanya dapat dibuka melalui portal oleh pemohon dan petugas.
    </p>
@else
    <p style="color:#6B7280">Tidak ada berkas yang dilampirkan.</p>
@endif

@if (filled($p->catatan))
    <h2>Catatan Petugas</h2>
    <div class="catatan">{{ $p->catatan }}</div>
@endif

<table class="kaki">
    <tr>
        <td>Dicetak {{ now()->translatedFormat('d F Y, H.i') }} WIB oleh {{ $dicetakOleh }}</td>
        <td style="text-align: right;">
            Portal SAIBATIN &middot; disdukcapil.pesisirbaratkab.go.id &middot;
            {{ $p->no_register }}
        </td>
    </tr>
</table>

</body>
</html>
