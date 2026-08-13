@extends('emails.layout')

@section('isi')
  <p style="margin:0 0 12px;font-size:14px">Halo <strong>{{ $nama }}</strong>,</p>

  <p style="margin:0 0 12px;font-size:14px;line-height:1.6">
    Permohonan Anda telah
    <strong style="color:#16a34a">SELESAI diproses dan disetujui</strong>:
  </p>

  <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background:#f0fdf4;border-radius:10px;margin:8px 0">
    <tr><td style="padding:14px 18px;font-size:13px;color:#334155;line-height:1.8">
      <strong>No. Register:</strong> {{ $noregister }}<br>
      <strong>Jenis Layanan:</strong> {{ $jenis }}
    </td></tr>
  </table>

  @if (filled($catatan ?? null))
    <p style="margin:0 0 12px;font-size:14px;line-height:1.6">
      <strong>Catatan petugas:</strong> {{ $catatan }}
    </p>
  @endif

  <p style="margin:0 0 12px;font-size:14px;line-height:1.6">
    Silakan cek detail dan dokumen hasil pada halaman riwayat permohonan Anda.
  </p>

  <p style="margin:0 0 4px">
    <a href="{{ url('/user/pengajuan') }}"
       style="display:inline-block;background:#2176bd;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:8px;font-size:14px">
      Lihat Riwayat Permohonan
    </a>
  </p>
@endsection
