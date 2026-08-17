<x-mail::message>
# Siparişiniz teslim edildi

Merhaba {{ $customerName }},

**#{{ $order->id }}** numaralı siparişiniz teslim edildi. Alışverişiniz için teşekkür ederiz.

## Sipariş özeti

@include('mail.orders.partials.items', ['order' => $order])

<x-mail::button :url="$orderUrl">
Siparişi görüntüle
</x-mail::button>

Ürünlerden memnun kaldıysanız yorum bırakarak diğer müşterilere yardımcı olabilirsiniz.

Teşekkürler,<br>
{{ config('app.name') }}
</x-mail::message>
