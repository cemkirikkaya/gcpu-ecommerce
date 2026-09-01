<x-mail::message>
# {{ $returnRequest->type->label() }} talebiniz reddedildi

Merhaba {{ $customerName }},

**#{{ $order->id }}** numaralı siparişiniz için oluşturduğunuz {{ $returnRequest->type->label() }} talebi reddedildi.

@if ($returnRequest->admin_note)
## Yönetici notu

{{ $returnRequest->admin_note }}
@endif

<x-mail::button :url="$orderUrl">
Siparişi görüntüle
</x-mail::button>

Sorularınız için bizimle iletişime geçebilirsiniz.

Teşekkürler,<br>
{{ config('app.name') }}
</x-mail::message>
