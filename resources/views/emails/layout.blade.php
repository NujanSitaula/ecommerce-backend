@php
  $brandColor = '#02b290';
  $bgColor = '#f4f4f0';
  $darkBg = '#0d0d0d';
  $textColor = '#1a1a1a';
  $mutedColor = '#555';
  $lightMuted = '#888';
  $dividerColor = '#e8e8e4';
  $badgeBg = '#e6faf5';
  $bodyFont = "'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
  $brandFont = "'Playfair Display', Georgia, 'Times New Roman', serif";
  $frontendUrl = rtrim(env('FRONTEND_URL', config('app.url')), '/');
  $storeName = $appName ?? config('app.name', 'BoroBazar');
  $contactEmail = config('mail.from.address', 'hello@example.com');
@endphp
<!doctype html>
<html lang="en" dir="auto" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <title>{{ $storeName }}</title>
  <!--[if !mso]><!-->
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <!--<![endif]-->
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!--[if !mso]><!-->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <style>@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap');</style>
  <!--<![endif]-->
  <!--[if mso]>
  <noscript><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
  <![endif]-->
  <style type="text/css">
    #outlook a { padding: 0; }
    body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
    @media only screen and (max-width: 599px) {
      .email-container { width: 100% !important; }
      .fluid { max-width: 100% !important; height: auto !important; }
      .stack-column { display: block !important; width: 100% !important; }
      .pad-mobile { padding-left: 24px !important; padding-right: 24px !important; }
    }
  </style>
</head>
<body style="margin:0; padding:0; background-color:{{ $bgColor }}; font-family:{{ $bodyFont }}; -webkit-font-smoothing:antialiased;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $bgColor }};">
<tr><td align="center" style="padding: 0;">

  {{-- ============ TOP ACCENT BAR ============ --}}
  <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; margin:0 auto;">
    <tr>
      <td style="background-color:{{ $brandColor }}; padding:8px 24px; text-align:center;">
        <span style="font-family:{{ $bodyFont }}; font-size:11px; color:#ffffff; letter-spacing:2px; text-transform:uppercase;">
          @yield('top_bar', 'HANDCRAFTED WITH CARE')
        </span>
      </td>
    </tr>
  </table>

  {{-- ============ HEADER ============ --}}
  <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; margin:0 auto; background-color:#ffffff;">
    <tr>
      <td class="pad-mobile" style="padding:32px 48px 8px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td style="vertical-align:top;">
              <div style="font-family:{{ $brandFont }}; font-size:24px; font-weight:700; color:{{ $brandColor }}; letter-spacing:2px; text-transform:uppercase; line-height:1.2;">
                {{ $storeName }}
              </div>
            </td>
            @hasSection('header_right')
            <td style="vertical-align:top; text-align:right;">
              @yield('header_right')
            </td>
            @endif
          </tr>
        </table>
      </td>
    </tr>
    {{-- Green accent divider --}}
    <tr>
      <td class="pad-mobile" style="padding:12px 48px 0;">
        <div style="border-top:2px solid {{ $brandColor }}; width:40px;"></div>
      </td>
    </tr>
  </table>

  {{-- ============ HERO / MAIN CONTENT ============ --}}
  <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; margin:0 auto; background-color:#ffffff;">
    <tr>
      <td class="pad-mobile" style="padding:28px 48px 40px;">
        @yield('content')
      </td>
    </tr>
  </table>

  {{-- ============ EXTRA SECTIONS (order items, delivery info, etc.) ============ --}}
  @yield('sections')

  {{-- ============ NEED HELP / DARK SECTION ============ --}}
  <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; margin:0 auto;">
    {{-- Spacer --}}
    <tr><td style="background-color:{{ $bgColor }}; height:20px; font-size:0;">&nbsp;</td></tr>
    <tr>
      <td style="background-color:{{ $darkBg }}; padding:36px 48px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td class="stack-column" style="vertical-align:middle; width:65%;">
              <div style="font-family:{{ $bodyFont }}; font-size:17px; font-weight:700; color:#ffffff; padding-bottom:6px;">
                Questions?
              </div>
              <div style="font-family:{{ $bodyFont }}; font-size:13px; color:#aaa; line-height:1.7;">
                Our team is here to help. We typically respond within a few hours.
              </div>
            </td>
            <td class="stack-column" style="vertical-align:middle; text-align:center; padding-top:12px;">
              <a href="mailto:{{ $contactEmail }}" style="display:inline-block; background-color:{{ $brandColor }}; color:#ffffff; font-family:{{ $bodyFont }}; font-size:12px; font-weight:700; letter-spacing:1px; text-transform:uppercase; text-decoration:none; padding:12px 24px; border-radius:4px;">
                CONTACT US
              </a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  {{-- ============ FOOTER ============ --}}
  <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; margin:0 auto;">
    {{-- Spacer --}}
    <tr><td style="background-color:{{ $bgColor }}; height:20px; font-size:0;">&nbsp;</td></tr>
    <tr>
      <td style="background-color:#ffffff; padding:28px 48px 12px; text-align:center;">
        <a href="{{ $frontendUrl }}" style="color:{{ $lightMuted }}; text-decoration:none; font-family:{{ $bodyFont }}; font-size:12px; letter-spacing:1.5px; margin:0 10px;">SHOP</a>
        &nbsp;&middot;&nbsp;
        <a href="{{ $frontendUrl }}/my-account/orders" style="color:{{ $lightMuted }}; text-decoration:none; font-family:{{ $bodyFont }}; font-size:12px; letter-spacing:1.5px; margin:0 10px;">ORDERS</a>
        &nbsp;&middot;&nbsp;
        <a href="{{ $frontendUrl }}/my-account" style="color:{{ $lightMuted }}; text-decoration:none; font-family:{{ $bodyFont }}; font-size:12px; letter-spacing:1.5px; margin:0 10px;">ACCOUNT</a>
      </td>
    </tr>
    <tr>
      <td style="background-color:#ffffff; padding:0 48px;">
        <div style="border-top:1px solid {{ $dividerColor }}; margin:12px 0;"></div>
      </td>
    </tr>
    <tr>
      <td style="background-color:#ffffff; padding:8px 48px 8px; text-align:center;">
        <div style="font-family:{{ $brandFont }}; font-size:16px; font-weight:700; color:{{ $brandColor }}; letter-spacing:2px; text-transform:uppercase;">
          {{ $storeName }}
        </div>
      </td>
    </tr>
    <tr>
      <td style="background-color:#ffffff; padding:4px 48px; text-align:center;">
        <div style="font-family:{{ $bodyFont }}; font-size:11px; color:#bbb; letter-spacing:2px; text-transform:uppercase;">
          {{ $contactEmail }}
        </div>
      </td>
    </tr>
    <tr>
      <td style="background-color:#ffffff; padding:12px 48px 20px; text-align:center;">
        <div style="font-family:{{ $bodyFont }}; font-size:11px; color:#bbb; line-height:1.7;">
          You are receiving this email because you have an account or placed an order with {{ $storeName }}.
          This is a transactional email related to your activity.
        </div>
      </td>
    </tr>
    <tr>
      <td style="background-color:#ffffff; padding:0 48px 24px; text-align:center;">
        <div style="font-family:{{ $bodyFont }}; font-size:11px; color:#ccc; line-height:1.7;">
          &copy; {{ date('Y') }} {{ $storeName }}. All rights reserved.<br>
          <a href="{{ $frontendUrl }}/privacy" style="color:#bbb; text-decoration:underline;">Privacy Policy</a>
          &nbsp;&middot;&nbsp;
          <a href="{{ $frontendUrl }}/terms" style="color:#bbb; text-decoration:underline;">Terms of Service</a>
        </div>
      </td>
    </tr>
  </table>

  {{-- ============ BOTTOM ACCENT BAR ============ --}}
  <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; margin:0 auto;">
    <tr>
      <td style="background-color:{{ $brandColor }}; padding:8px 24px; text-align:center;">
        <span style="font-family:{{ $bodyFont }}; font-size:10px; color:#e6faf5; letter-spacing:2px; text-transform:uppercase;">
          @yield('bottom_bar', 'CRAFTED WITH CARE &middot; DELIVERED WITH LOVE')
        </span>
      </td>
    </tr>
  </table>

</td></tr>
</table>
</body>
</html>
