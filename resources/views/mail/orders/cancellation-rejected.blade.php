<x-mail::message>
# İptal talebiniz reddedildi

Merhaba {{ $customerName }},

**#{{ $order->id }}** numaralı siparişiniz için oluşturduğunuz iptal talebi incelendi ve reddedildi. Siparişiniz mevcut durumuyla devam edecektir.

@if ($adminNote)
## Yönetici notu

{{ $adminNote }}
@endif

<x-mail::button :url="$orderUrl">
Siparişi görüntüle
</x-mail::button>

Sorularınız için bu e-postayı yanıtlayabilir veya hesabınızdaki siparişler bölümünü ziyaret edebilirsiniz.

Teşekkürler,<br>
{{ config('app.name') }}
</x-mail::message>
