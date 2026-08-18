<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Fatura #{{ $order->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1c1917;
            line-height: 1.5;
        }

        .header {
            width: 100%;
            margin-bottom: 28px;
        }

        .header td {
            vertical-align: top;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 6px;
        }

        .muted {
            color: #57534e;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin: 0 0 8px;
            color: #44403c;
        }

        .box {
            border: 1px solid #e7e5e4;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 18px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        table.items th,
        table.items td {
            border-bottom: 1px solid #e7e5e4;
            padding: 10px 8px;
            text-align: left;
        }

        table.items th {
            background: #fafaf9;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #57534e;
        }

        .text-right {
            text-align: right;
        }

        .totals {
            width: 100%;
            margin-top: 12px;
        }

        .totals td {
            padding: 4px 0;
        }

        .total-label {
            font-weight: bold;
            font-size: 14px;
        }

        .total-value {
            font-weight: bold;
            font-size: 16px;
            text-align: right;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <p class="title">Fatura</p>
                <p class="muted">Fatura No: INV-{{ $order->id }}</p>
                <p class="muted">Sipariş No: #{{ $order->id }}</p>
                <p class="muted">Tarih: {{ $order->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</p>
            </td>
            <td class="text-right">
                <p class="section-title">Satıcı</p>
                <p><strong>{{ $shop['invoice']['legal_name'] ?? config('shop.name') }}</strong></p>
                @if (! empty($shop['invoice']['tax_office']))
                    <p class="muted">Vergi Dairesi: {{ $shop['invoice']['tax_office'] }}</p>
                @endif
                @if (! empty($shop['invoice']['tax_number']))
                    <p class="muted">VKN/TCKN: {{ $shop['invoice']['tax_number'] }}</p>
                @endif
                @if (! empty($shop['invoice']['address']))
                    <p class="muted">{{ $shop['invoice']['address'] }}</p>
                @endif
                @if (! empty($shop['invoice']['email']))
                    <p class="muted">{{ $shop['invoice']['email'] }}</p>
                @endif
            </td>
        </tr>
    </table>

    <table class="header">
        <tr>
            <td style="width: 50%; padding-right: 12px;">
                <div class="box">
                    <p class="section-title">Müşteri</p>
                    <p><strong>{{ $customer?->name ?? 'Müşteri' }}</strong></p>
                    @if ($customer?->email)
                        <p class="muted">{{ $customer->email }}</p>
                    @endif
                </div>
            </td>
            <td style="width: 50%; padding-left: 12px;">
                @if ($order->address)
                    <div class="box">
                        <p class="section-title">Teslimat Adresi</p>
                        <p><strong>{{ $order->address->fullName() }}</strong></p>
                        <p class="muted">{{ $order->address->fullAddress() }}</p>
                        @if ($order->address->phone)
                            <p class="muted">Tel: {{ $order->address->phone }}</p>
                        @endif
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <p class="section-title">Kalemler</p>
    <table class="items">
        <thead>
            <tr>
                <th>Ürün</th>
                <th class="text-right">Adet</th>
                <th class="text-right">Birim Fiyat</th>
                <th class="text-right">Tutar</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                @php
                    $variant = $item->cartItem?->productVariant;
                    $product = $variant?->product;
                    $productName = $product?->name ?? 'Ürün';
                    $variantLabel = $variant?->displayLabel();
                @endphp
                <tr>
                    <td>
                        <strong>{{ $productName }}</strong>
                        @if ($variantLabel)
                            <br><span class="muted">{{ $variantLabel }}</span>
                        @endif
                    </td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format((float) $item->price, 2, ',', '.') }} ₺</td>
                    <td class="text-right">{{ number_format($item->subtotal(), 2, ',', '.') }} ₺</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="total-label">Ödenen Tutar</td>
            <td class="total-value">{{ number_format($paidTotal, 2, ',', '.') }} ₺</td>
        </tr>
    </table>

    <div class="box" style="margin-top: 24px;">
        <p class="section-title">Ödeme Bilgileri</p>
        <p>Durum: <strong>{{ $order->payment_status->label() }}</strong></p>
        @if ($order->paymentProvider())
            <p class="muted">Ödeme yöntemi: {{ strtoupper($order->paymentProvider()) }}</p>
        @endif
        @if ($order->installment)
            <p class="muted">Taksit: {{ $order->installment }}</p>
        @endif
        @if ($paidAt)
            <p class="muted">Ödeme zamanı: {{ $paidAt->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</p>
        @endif
        @if ($order->iyzico_payment_id)
            <p class="muted">İşlem referansı: {{ $order->iyzico_payment_id }}</p>
        @elseif ($order->stripe_payment_intent_id)
            <p class="muted">İşlem referansı: {{ $order->stripe_payment_intent_id }}</p>
        @endif
    </div>
</body>
</html>
