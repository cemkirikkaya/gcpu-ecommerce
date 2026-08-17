<x-mail::message>
# Düşük stok uyarısı

Merhaba {{ $vendorName }},

**{{ $productName }}** ürününün **{{ $sku }}** varyantında stok eşiğin altına düştü.

- **Kalan stok:** {{ $quantity }} adet
- **Uyarı eşiği:** {{ $threshold }} adet ve altı

Stok tükendiğinde ürün satışa kapanabilir. Lütfen envanterinizi kontrol edin.

<x-mail::button :url="$productsUrl">
Ürünleri yönet
</x-mail::button>

Teşekkürler,<br>
{{ config('app.name') }}
</x-mail::message>
