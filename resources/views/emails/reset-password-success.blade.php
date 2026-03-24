@extends('emails.layout')

@section('top_bar', 'ACCOUNT SECURITY')

@section('header_right')
  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:12px; color:#888; padding-bottom:4px;">Security Update</div>
  <div style="display:inline-block; background-color:#e6faf5; color:#02b290; font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; padding:4px 12px; border-radius:20px;">
    &#10003; Updated
  </div>
@endsection

@section('content')
  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:26px; font-weight:700; color:#0d0d0d; line-height:1.3; padding-bottom:12px;">
    Password<br>updated.
  </div>
  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; color:#555; line-height:1.8; padding-bottom:24px;">
    Your password has been successfully updated. You can now sign in with your new password and continue shopping.
  </div>

  <a href="{{ rtrim(env('FRONTEND_URL', config('app.url')), '/') }}/signin" style="display:inline-block; background-color:#02b290; color:#ffffff; font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:14px; font-weight:700; letter-spacing:0.5px; text-decoration:none; padding:14px 32px; border-radius:4px; text-transform:uppercase;">
    SIGN IN &rarr;
  </a>

  <div style="border-top:1px solid #e8e8e4; margin:28px 0 20px;"></div>

  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; color:#888; line-height:1.7;">
    If you didn't make this change, please contact our support team immediately to secure your account.
  </div>
@endsection

@section('bottom_bar', 'YOUR SECURITY IS OUR PRIORITY')
