<x-mail::message>
# Siparişiniz kargoya verildi

Merhaba {{ $customerName }},

**#{{ $order->id }}** numaralı siparişiniz kargoya verildi. Kısa süre içinde adresinize ulaşacaktır.

@if ($trackingNumber)
**Takip numarası:** {{ $trackingNumber }}
@endif

## Sipariş özeti

@include('mail.orders.partials.items', ['order' => $order])

@if ($trackingUrl)
<x-mail::button :url="$trackingUrl">
Kargoyu takip et
</x-mail::button>
@endif

<x-mail::button :url="$orderUrl">
Siparişi görüntüle
</x-mail::button>

@if (! $trackingUrl)
Kargo durumunu hesabınızdaki siparişler bölümünden takip edebilirsiniz.
@endif

Teşekkürler,<br>
{{ config('app.name') }}
</x-mail::message>
