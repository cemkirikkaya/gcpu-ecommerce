<x-mail::message>
@if ($returnRequest->isExchange())
# Değişim kargonuz yola çıktı
@else
# İadeniz tamamlandı
@endif

Merhaba {{ $customerName }},

**#{{ $order->id }}** numaralı siparişiniz için gönderdiğiniz ürün teslim alındı.

@if ($returnRequest->isExchange())
Stok güncellendi ve değişim ürününüz kargoya verildi.

@if ($returnRequest->exchange_tracking_number)
Takip no: **{{ $returnRequest->exchange_tracking_number }}**
@endif

@if ($returnRequest->exchange_tracking_url)
<x-mail::button :url="$returnRequest->exchange_tracking_url">
Değişim kargosunu takip et
</x-mail::button>
@endif
@else
Ödemeniz iade edildi.

@if ($returnRequest->refund_amount)
İade tutarı: **{{ number_format((float) $returnRequest->refund_amount, 2, ',', '.') }} TL**
@endif

@if ($returnRequest->refund_reference)
İade referansı: **{{ $returnRequest->refund_reference }}**
@endif
@endif

<x-mail::button :url="$orderUrl">
Siparişi görüntüle
</x-mail::button>

@if ($returnRequest->isReturn())
İade tutarının hesabınıza yansıması bankanıza bağlı olarak birkaç iş günü sürebilir.
@endif

Teşekkürler,<br>
{{ config('app.name') }}
</x-mail::message>
