<x-mail::message>
# {{ $returnRequest->type->label() }} talebiniz onaylandı

Merhaba {{ $customerName }},

**#{{ $order->id }}** numaralı siparişiniz için {{ $returnRequest->type->label() }} talebiniz onaylandı. Ürünü göndermek için aşağıdaki kargo etiketini kullanın.

@if ($returnRequest->return_tracking_number)
Takip no: **{{ $returnRequest->return_tracking_number }}**
@endif

@if ($adminNote ?? $returnRequest->admin_note)
## Yönetici notu

{{ $returnRequest->admin_note }}
@endif

@if ($returnRequest->return_label_url)
<x-mail::button :url="$returnRequest->return_label_url">
Kargo etiketini indir
</x-mail::button>
@endif

@if ($returnRequest->return_tracking_url)
<x-mail::button :url="$returnRequest->return_tracking_url">
Kargoyu takip et
</x-mail::button>
@endif

<x-mail::button :url="$orderUrl">
Siparişi görüntüle
</x-mail::button>

Ürün bize ulaştıktan sonra {{ $returnRequest->isExchange() ? 'değişim kargonuz hazırlanacak' : 'ödemeniz iade edilecek' }}.

Teşekkürler,<br>
{{ config('app.name') }}
</x-mail::message>
