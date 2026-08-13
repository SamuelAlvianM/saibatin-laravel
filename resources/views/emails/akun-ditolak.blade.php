@extends('emails.layout')

@section('isi')
  <p style="margin:0 0 12px;font-size:14px">Halo <strong>{{ $nama }}</strong>,</p>

  <p style="margin:0 0 12px;font-size:14px;line-height:1.6">
    Mohon maaf, pendaftaran akun Anda di portal SAIBATIN
    <strong style="color:#dc2626">ditolak</strong>.
  </p>

  @if (filled($alasan ?? null))
    {{-- Alasan ditampilkan utuh (termasuk baris "Data yang perlu diperbaiki")
         supaya warga tahu bagian mana yang harus dibetulkan, bukan cuma bahwa
         pendaftarannya gagal. --}}
    <p style="margin:0 0 12px;background:#fef2f2;border-left:3px solid #dc2626;padding:10px 14px;border-radius:6px;font-size:14px;line-height:1.6;white-space:pre-line">
      <strong>Alasan:</strong> {{ $alasan }}
    </p>
  @endif

  <p style="margin:0 0 12px;font-size:14px;line-height:1.6">
    Anda dapat <strong>mendaftar kembali</strong> dengan data yang sudah diperbaiki
    sesuai catatan di atas.
  </p>

  <p style="margin:0 0 4px">
    <a href="{{ url('/register') }}"
       style="display:inline-block;background:#2176bd;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:8px;font-size:14px">
      Daftar Ulang
    </a>
  </p>
@endsection
