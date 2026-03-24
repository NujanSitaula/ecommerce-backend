@extends('emails.layout')

@section('top_bar', 'SECURE VERIFICATION')

@section('header_right')
  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:12px; color:#888; padding-bottom:4px;">Account Security</div>
  <div style="display:inline-block; background-color:#e6faf5; color:#02b290; font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; padding:4px 12px; border-radius:20px;">
    Verification
  </div>
@endsection

@section('content')
  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:26px; font-weight:700; color:#0d0d0d; line-height:1.3; padding-bottom:12px;">
    Verify your<br>email address.
  </div>
  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; color:#555; line-height:1.8; padding-bottom:24px;">
    Hi {{ $user->name ?? 'there' }}, use the verification code below to complete your sign up and start shopping with {{ $appName ?? config('app.name', 'BoroBazar') }}.
  </div>

  {{-- OTP Code Box --}}
  <div style="background-color:#e6faf5; border-radius:8px; padding:24px 32px; text-align:center; border:1px solid #b2ece0; margin-bottom:24px;">
    <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:10px; text-transform:uppercase; letter-spacing:3px; color:#02b290; font-weight:700; margin-bottom:8px;">
      Your one-time verification code
    </div>
    <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:36px; font-weight:800; letter-spacing:0.3em; color:#047857;">
      {{ $code }}
    </div>
  </div>

  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:14px; color:#555; line-height:1.8; padding-bottom:20px;">
    This code will expire in <strong style="color:#1a1a1a;">10 minutes</strong>. For your security, please do not share this code with anyone.
  </div>

  <div style="border-top:1px solid #e8e8e4; margin:20px 0;"></div>

  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; color:#888; line-height:1.7;">
    If you didn't request this email, you can safely ignore it. Your account will not be created without confirming this code.
  </div>
@endsection

@section('bottom_bar', 'YOUR SECURITY IS OUR PRIORITY')
