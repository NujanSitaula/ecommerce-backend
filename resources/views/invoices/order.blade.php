<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice #{{ $order->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; line-height: 1.5; color: #1a1a1a; }
        .container { max-width: 700px; margin: 0 auto; padding: 40px 32px; }
        .header { display: table; width: 100%; margin-bottom: 40px; }
        .header-left { display: table-cell; vertical-align: top; width: 50%; }
        .header-right { display: table-cell; vertical-align: top; text-align: right; width: 50%; }
        .logo { max-height: 48px; max-width: 180px; margin-bottom: 8px; }
        .company-name { font-size: 18px; font-weight: 600; margin-bottom: 4px; }
        .company-details { font-size: 11px; color: #666; white-space: pre-line; }
        .invoice-title { font-size: 24px; font-weight: 600; margin-bottom: 4px; }
        .invoice-meta { font-size: 11px; color: #666; }
        .bill-to { margin-bottom: 32px; }
        .section-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #888; margin-bottom: 6px; }
        .bill-to-content { font-size: 12px; }
        .bill-to-content div { margin-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #888; font-weight: 500; padding: 12px 0 8px; border-bottom: 1px solid #e5e5e5; }
        td { padding: 12px 0; border-bottom: 1px solid #f0f0f0; font-size: 12px; }
        th:last-child, td:last-child { text-align: right; }
        th:nth-child(2), td:nth-child(2) { text-align: center; width: 60px; }
        .totals { margin-left: auto; width: 240px; }
        .totals-row { display: table; width: 100%; padding: 6px 0; font-size: 12px; }
        .totals-label { display: table-cell; color: #666; }
        .totals-value { display: table-cell; text-align: right; }
        .totals-row.total { font-size: 14px; font-weight: 600; padding-top: 12px; margin-top: 8px; border-top: 1px solid #e5e5e5; }
        .footer { margin-top: 48px; padding-top: 24px; border-top: 1px solid #eee; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo" class="logo">
                @endif
                <div class="company-name">{{ $companyName }}</div>
                @if($companyAddress || $companyEmail || $companyPhone)
                    <div class="company-details">{{ $companyAddress }}{{ $companyEmail ? "\n" . $companyEmail : '' }}{{ $companyPhone ? "\n" . $companyPhone : '' }}</div>
                @endif
            </div>
            <div class="header-right">
                <div class="invoice-title">Invoice</div>
                <div class="invoice-meta">#{{ $order->id }}</div>
                <div class="invoice-meta">{{ $order->created_at->format('M d, Y') }}</div>
            </div>
        </div>

        <div class="bill-to">
            <div class="section-label">Bill To</div>
            <div class="bill-to-content">
                <div><strong>{{ $billTo['name'] }}</strong></div>
                @foreach($billTo['lines'] as $line)
                    <div>{{ $line }}</div>
                @endforeach
                @if($billTo['email'])
                    <div>{{ $billTo['email'] }}</div>
                @endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->price, 2) }} {{ $currency }}</td>
                    <td>{{ number_format($item->subtotal, 2) }} {{ $currency }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span class="totals-label">Subtotal</span>
                <span class="totals-value">{{ number_format($order->subtotal, 2) }} {{ $currency }}</span>
            </div>
            @if($order->shipping_fee > 0)
            <div class="totals-row">
                <span class="totals-label">Shipping</span>
                <span class="totals-value">{{ number_format($order->shipping_fee, 2) }} {{ $currency }}</span>
            </div>
            @endif
            @if(($order->discount_amount + $order->shipping_discount) > 0)
            <div class="totals-row">
                <span class="totals-label">Discount</span>
                <span class="totals-value">-{{ number_format($order->discount_amount + $order->shipping_discount, 2) }} {{ $currency }}</span>
            </div>
            @endif
            @if($order->tax_amount > 0)
            <div class="totals-row">
                <span class="totals-label">Tax</span>
                <span class="totals-value">{{ number_format($order->tax_amount, 2) }} {{ $currency }}</span>
            </div>
            @endif
            <div class="totals-row total">
                <span class="totals-label">Total</span>
                <span class="totals-value">{{ number_format($order->total, 2) }} {{ $currency }}</span>
            </div>
        </div>

        <div class="footer">
            Thank you for your order.
        </div>
    </div>
</body>
</html>
