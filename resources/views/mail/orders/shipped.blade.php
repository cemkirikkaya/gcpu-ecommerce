<x-mail::message>
# Siparişiniz kargoya verildi

Merhaba {{ $customerName }},

**#{{ $order->id }}** numaralı siparişiniz kargoya verildi. Kısa süre içinde adresinize ulaşacaktır.

## Sipariş özeti

@include('mail.orders.partials.items', ['order' => $order])

<x-mail::button :url="$orderUrl">
Siparişi görüntüle
</x-mail::button>

Kargo durumunu hesabınızdaki siparişler bölümünden takip edebilirsiniz.

Teşekkürler,<br>
{{ config('app.name') }}
</x-mail::message>
