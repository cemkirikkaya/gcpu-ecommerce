<x-mail::message>
# Siparişiniz alındı

Merhaba {{ $customerName }},

**#{{ $order->id }}** numaralı siparişiniz için ödemeniz başarıyla tamamlandı. Siparişiniz hazırlanmaya alındı.

## Sipariş özeti

@foreach ($order->items as $item)
@php
    $product = $item->cartItem?->productVariant?->product;
    $productName = $product?->name ?? 'Ürün';
@endphp
- **{{ $productName }}** — {{ $item->quantity }} adet × {{ number_format((float) $item->price, 2, ',', '.') }} ₺
@endforeach

**Toplam:** {{ number_format($paidTotal, 2, ',', '.') }} ₺

@if ($order->address)
## Teslimat adresi

{{ $order->address->fullName() }}  
{{ $order->address->fullAddress() }}

@if ($order->address->phone)
Telefon: {{ $order->address->phone }}
@endif
@endif

@if ($paidAt)
Ödeme zamanı: {{ $paidAt->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
@endif

<x-mail::button :url="$orderUrl">
Siparişi görüntüle
</x-mail::button>

Sorularınız için bu e-postayı yanıtlayabilir veya hesabınızdaki siparişler bölümünü ziyaret edebilirsiniz.

Teşekkürler,<br>
{{ config('app.name') }}
</x-mail::message>
