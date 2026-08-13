@extends('emails.layout')

@section('isi')
  <p style="margin:0 0 12px;font-size:14px">Halo <strong>{{ $nama }}</strong>,</p>

  <p style="margin:0 0 16px;font-size:14px;line-height:1.6">
    Kami menerima permintaan penyetelan ulang kata sandi akun SAIBATIN Anda.
    Klik tombol di bawah untuk membuat sandi baru.
  </p>

  <p style="margin:0 0 16px;text-align:center">
    <a href="{{ $tautan }}"
       style="display:inline-block;padding:11px 22px;background:#2176bd;color:#ffffff;border-radius:8px;font-weight:bold;font-size:14px;text-decoration:none">
      Setel Ulang Kata Sandi
    </a>
  </p>

  <p style="margin:0 0 12px;font-size:12px;color:#5b6b7a;word-break:break-all">
    Bila tombol tidak berfungsi, salin tautan ini ke peramban:<br>{{ $tautan }}
  </p>

  <p style="margin:0;font-size:13px;color:#5b6b7a">
    Tautan berlaku <strong>1 jam</strong>. Jika Anda tidak meminta penyetelan ulang,
    abaikan surel ini — sandi Anda tidak berubah.
  </p>
@endsection
