@extends('emails.layout')

@section('isi')
  <p style="margin:0 0 12px;font-size:14px">Halo <strong>{{ $nama }}</strong>,</p>

  <p style="margin:0 0 12px;font-size:14px;line-height:1.6">
    Akun Anda di portal SAIBATIN telah
    <strong style="color:#16a34a">disetujui dan diaktifkan</strong>. Anda sekarang
    dapat masuk dan mengajukan permohonan layanan kependudukan secara online.
  </p>

  <p style="margin:0 0 4px">
    <a href="{{ url('/login') }}"
       style="display:inline-block;background:#2176bd;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:8px;font-size:14px">
      Masuk Sekarang
    </a>
  </p>
@endsection
