<x-mail::message>
# İptal talebiniz onaylandı

Merhaba {{ $customerName }},

**#{{ $order->id }}** numaralı siparişiniz için oluşturduğunuz iptal talebi onaylandı. Ödemeniz iade edildi ve siparişiniz iptal edildi.

## Sipariş özeti

@include('mail.orders.partials.items', ['order' => $order])

@if ($adminNote)
## Yönetici notu

{{ $adminNote }}
@endif

@if ($refundReference)
İade referansı: **{{ $refundReference }}**
@endif

<x-mail::button :url="$orderUrl">
Siparişi görüntüle
</x-mail::button>

İade tutarının hesabınıza yansıması bankanıza bağlı olarak birkaç iş günü sürebilir.

Teşekkürler,<br>
{{ config('app.name') }}
</x-mail::message>
