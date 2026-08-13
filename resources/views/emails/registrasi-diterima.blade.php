@extends('emails.layout')

@section('isi')
  <p style="margin:0 0 12px;font-size:14px">Halo <strong>{{ $nama }}</strong>,</p>

  <p style="margin:0 0 12px;font-size:14px;line-height:1.6">
    Pendaftaran akun Anda di portal SAIBATIN sudah kami terima dan sedang
    <strong>menunggu verifikasi petugas</strong>. Anda belum bisa masuk sampai
    akun diaktifkan.
  </p>

  <p style="margin:0 0 12px;font-size:14px;line-height:1.6">
    Status pendaftaran bisa dipantau kapan saja lewat halaman
    <a href="{{ url('/cek-status') }}" style="color:#2176bd">Cek Status Pendaftaran</a>
    dengan memasukkan NIK Anda.
  </p>

  <p style="margin:0;font-size:13px;color:#5b6b7a">
    Bila pendaftaran ditolak, alasannya akan tertera di halaman tersebut beserta
    bagian data yang perlu diperbaiki — Anda dapat mengajukan ulang dari sana.
  </p>
@endsection
