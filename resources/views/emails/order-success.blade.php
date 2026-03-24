@extends('emails.layout')

@section('header_right')
  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:12px; color:#888; padding-bottom:4px;">Order Confirmation</div>
  <div style="display:inline-block; background-color:#e6faf5; color:#02b290; font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; padding:4px 12px; border-radius:20px;">
    &#10003; Confirmed
  </div>
@endsection

@section('content')
  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:26px; font-weight:700; color:#0d0d0d; line-height:1.3; padding-bottom:12px;">
    Thank you,<br>{{ $customerName ?? 'there' }}.
  </div>
  <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; color:#555; line-height:1.8; padding-bottom:20px;">
    Your order has been received and is being prepared with care. We'll send you an update as soon as it ships.
  </div>

  <a href="{{ rtrim(env('FRONTEND_URL', config('app.url')), '/') }}/my-account/orders" style="display:inline-block; background-color:#02b290; color:#ffffff; font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:14px; font-weight:700; letter-spacing:0.5px; text-decoration:none; padding:14px 32px; border-radius:4px; text-transform:uppercase;">
    VIEW YOUR ORDER &rarr;
  </a>
@endsection

@section('sections')
  {{-- Spacer --}}
  <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; margin:0 auto;">
    <tr><td style="background-color:#f4f4f0; height:20px; font-size:0;">&nbsp;</td></tr>
  </table>

  {{-- ============ ORDER SUMMARY ============ --}}
  <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; margin:0 auto; background-color:#ffffff;">
    <tr>
      <td class="pad-mobile" style="padding:32px 48px 12px;">
        <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:10px; letter-spacing:3px; text-transform:uppercase; color:#02b290; font-weight:700; padding-bottom:16px;">
          Order Summary
        </div>
      </td>
    </tr>

    {{-- Product rows --}}
    @foreach($order->items ?? [] as $item)
    <tr>
      <td class="pad-mobile" style="padding:0 48px 14px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td style="width:60%; vertical-align:top;">
              <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; font-weight:700; color:#1a1a1a;">
                {{ $item->product_name ?? 'Item' }}
              </div>
              <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; color:#777; margin-top:2px;">
                Qty: {{ $item->quantity ?? 1 }}
              </div>
            </td>
            <td style="width:40%; vertical-align:top; text-align:right;">
              <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; font-weight:700; color:#02b290;">
                ${{ number_format((float) (($item->subtotal ?? ($item->price ?? 0) * ($item->quantity ?? 1))), 2) }}
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    @endforeach

    {{-- Divider --}}
    <tr>
      <td class="pad-mobile" style="padding:4px 48px;">
        <div style="border-top:1px solid #e8e8e4;"></div>
      </td>
    </tr>

    {{-- Subtotal --}}
    @php
      $subtotal = 0;
      foreach ($order->items ?? [] as $item) {
        $subtotal += ($item->subtotal ?? ($item->price ?? 0) * ($item->quantity ?? 1));
      }
      $shipping = $order->shipping_cost ?? 0;
      $total = $order->total ?? $subtotal + $shipping;
    @endphp
    <tr>
      <td class="pad-mobile" style="padding:8px 48px 4px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td style="width:60%;"><div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; color:#888;">Subtotal</div></td>
            <td style="width:40%; text-align:right;"><div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; color:#888;">${{ number_format((float) $subtotal, 2) }}</div></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td class="pad-mobile" style="padding:4px 48px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td style="width:60%;"><div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; color:#888;">Shipping</div></td>
            <td style="width:40%; text-align:right;">
              <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; color:{{ $shipping > 0 ? '#888' : '#02b290' }};">
                {{ $shipping > 0 ? '$' . number_format((float) $shipping, 2) : 'Free' }}
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td class="pad-mobile" style="padding:8px 48px 28px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td style="width:60%;"><div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:16px; font-weight:700; color:#0d0d0d;">Total</div></td>
            <td style="width:40%; text-align:right;"><div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:16px; font-weight:700; color:#0d0d0d;">${{ number_format((float) $total, 2) }}</div></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  {{-- Spacer --}}
  <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; margin:0 auto;">
    <tr><td style="background-color:#f4f4f0; height:20px; font-size:0;">&nbsp;</td></tr>
  </table>

  {{-- ============ ORDER DETAILS ============ --}}
  <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; margin:0 auto; background-color:#ffffff;">
    <tr>
      <td class="pad-mobile" style="padding:32px 48px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td class="stack-column" style="width:50%; vertical-align:top; padding-bottom:16px;">
              <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:10px; letter-spacing:3px; text-transform:uppercase; color:#02b290; font-weight:700; padding-bottom:10px;">
                Order Details
              </div>
              <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:14px; color:#444; line-height:1.8;">
                Order #: {{ $order->id }}<br>
                Date: {{ $order->created_at ? $order->created_at->format('d M Y') : now()->format('d M Y') }}<br>
                Status: {{ ucfirst($order->status ?? 'pending') }}
                @if($order->delivery_date)
                  <br>Est. Delivery: {{ $order->delivery_date->format('d M Y') }}
                @endif
              </div>
            </td>
            @if($order->shipping_address ?? $order->guest_name ?? false)
            <td class="stack-column" style="width:50%; vertical-align:top;">
              <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:10px; letter-spacing:3px; text-transform:uppercase; color:#02b290; font-weight:700; padding-bottom:10px;">
                Shipping To
              </div>
              <div style="font-family:'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:14px; color:#444; line-height:1.8;">
                {{ $customerName ?? 'Customer' }}<br>
                @if(is_array($order->shipping_address))
                  {{ $order->shipping_address['street'] ?? '' }}<br>
                  {{ $order->shipping_address['city'] ?? '' }}{{ !empty($order->shipping_address['zip']) ? ', ' . $order->shipping_address['zip'] : '' }}<br>
                  {{ $order->shipping_address['country'] ?? '' }}
                @endif
              </div>
            </td>
            @endif
          </tr>
        </table>
      </td>
    </tr>
  </table>
@endsection
