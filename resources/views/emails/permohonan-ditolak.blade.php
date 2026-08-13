@extends('emails.layout')

@section('isi')
  <p style="margin:0 0 12px;font-size:14px">Halo <strong>{{ $nama }}</strong>,</p>

  <p style="margin:0 0 12px;font-size:14px;line-height:1.6">
    Mohon maaf, permohonan berikut <strong style="color:#dc2626">DITOLAK</strong>:
  </p>

  <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background:#fef2f2;border-radius:10px;margin:8px 0">
    <tr><td style="padding:14px 18px;font-size:13px;color:#334155;line-height:1.8">
      <strong>No. Register:</strong> {{ $noregister }}<br>
      <strong>Jenis Layanan:</strong> {{ $jenis }}
    </td></tr>
  </table>

  @if (filled($catatan ?? null))
    <p style="margin:0 0 12px;background:#fef2f2;border-left:3px solid #dc2626;padding:10px 14px;border-radius:6px;font-size:14px;line-height:1.6;white-space:pre-line">
      <strong>Alasan penolakan:</strong> {{ $catatan }}
    </p>
  @endif

  <p style="margin:0 0 12px;font-size:14px;line-height:1.6">
    Anda dapat <strong>mengajukan permohonan kembali (revisi)</strong> dengan
    melengkapi/memperbaiki berkas sesuai alasan penolakan di atas.
  </p>

  <p style="margin:0 0 4px">
    <a href="{{ url('/user/pengajuan/baru') }}"
       style="display:inline-block;background:#2176bd;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:8px;font-size:14px">
      Ajukan Revisi Permohonan
    </a>
  </p>
@endsection
