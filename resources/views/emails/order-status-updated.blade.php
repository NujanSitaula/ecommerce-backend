@extends('emails.layout')

@php
  $statusLower = strtolower($status ?? $order->status ?? 'pending');
  $statusLabels = [
    'processing' => ['label' => 'Processing', 'color' => '#02b290', 'bg' => '#e6faf5'],
    'shipped'    => ['label' => 'Shipped',    'color' => '#2563eb', 'bg' => '#eff6ff'],
    'delivered'  => ['label' => 'Delivered',  'color' => '#02b290', 'bg' => '#e6faf5'],
    'cancelled'  => ['label' => 'Cancelled',  'color' => '#dc2626', 'bg' => '#fef2f2'],
    'refunded'   => ['label' => 'Refunded',   'color' => '#e67e00', 'bg' => '#fff3e6'],
    'pending'    => ['label' => 'Pending',    'color' => '#888',    'bg' => '#f4f4f0'],
  ];
  $badge = $statusLabels[$statusLower] ?? $statusLabels['pending'];
@endphp

@section('header_right')
  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:12px; color:#888; padding-bottom:4px;">Order Update</div>
  <div style="display:inline-block; background-color:{{ $badge['bg'] }}; color:{{ $badge['color'] }}; font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; padding:4px 12px; border-radius:20px;">
    {{ $badge['label'] }}
  </div>
@endsection

@section('content')
  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:26px; font-weight:700; color:#0d0d0d; line-height:1.3; padding-bottom:12px;">
    Update on<br>your order.
  </div>
  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; color:#555; line-height:1.8; padding-bottom:8px;">
    Hi {{ $customerName ?? 'there' }}, we wanted to let you know that your order status has been updated.
  </div>

  {{-- Status card --}}
  <div style="background-color:{{ $badge['bg'] }}; border-radius:8px; padding:20px 24px; margin:16px 0 24px; border-left:4px solid {{ $badge['color'] }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td>
          <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:10px; letter-spacing:3px; text-transform:uppercase; color:{{ $badge['color'] }}; font-weight:700; margin-bottom:4px;">
            Current Status
          </div>
          <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:20px; font-weight:700; color:{{ $badge['color'] }};">
            {{ ucfirst($status ?? $order->status ?? 'Pending') }}
          </div>
        </td>
        <td style="text-align:right; vertical-align:top;">
          <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; color:#888;">
            Order #{{ $order->id }}
          </div>
        </td>
      </tr>
    </table>
    @if(!empty($reason))
      <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; color:#555; margin-top:10px; line-height:1.6;">
        <strong>Reason:</strong> {{ $reason }}
      </div>
    @endif
  </div>

  <a href="{{ rtrim(env('FRONTEND_URL', config('app.url')), '/') }}/my-account/orders" style="display:inline-block; background-color:#02b290; color:#ffffff; font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:14px; font-weight:700; letter-spacing:0.5px; text-decoration:none; padding:14px 32px; border-radius:4px; text-transform:uppercase;">
    VIEW YOUR ORDERS &rarr;
  </a>

  <div style="border-top:1px solid #e8e8e4; margin:28px 0 20px;"></div>

  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; color:#888; line-height:1.7;">
    Have questions about this update? Reply to this email and we'll get back to you promptly.
  </div>
@endsection
