@extends('emails.layout')

@section('isi')
  <p style="margin:0 0 14px;font-size:14px">Kode verifikasi pendaftaran akun Anda:</p>

  <div style="margin:0 0 16px;padding:14px;text-align:center;background:#eef4f9;border:1px dashed #2176bd;border-radius:10px">
    <span style="font-size:30px;font-weight:bold;letter-spacing:8px;color:#1b4b72">{{ $kode }}</span>
  </div>

  <p style="margin:0 0 8px;font-size:13px;color:#5b6b7a">Kode berlaku <strong>5 menit</strong>.</p>
  <p style="margin:0;font-size:13px;color:#b3261e">
    <strong>JANGAN berikan kode ini kepada siapa pun</strong>, termasuk yang mengaku petugas Disdukcapil.
  </p>
@endsection
