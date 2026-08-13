{{-- Kerangka surel. Gaya ditulis INLINE karena klien surel (Gmail, Outlook)
     membuang atau mengabaikan <style> di <head>. --}}
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:24px 12px;background:#f2f6fa;font-family:Arial,Helvetica,sans-serif;color:#1a2733">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #dde5ec">
    <tr>
      <td style="background:linear-gradient(135deg,#1b4b72,#2176bd);padding:20px 24px;color:#ffffff">
        <div style="font-size:17px;font-weight:bold">SAIBATIN</div>
        <div style="font-size:12px;opacity:.85">Disdukcapil Kabupaten Pesisir Barat</div>
      </td>
    </tr>
    <tr><td style="padding:24px">@yield('isi')</td></tr>
    <tr>
      <td style="padding:14px 24px;background:#f7fafc;border-top:1px solid #dde5ec;font-size:11px;color:#5b6b7a">
        Surel ini dikirim otomatis — mohon tidak dibalas.
      </td>
    </tr>
  </table>
</body>
</html>
