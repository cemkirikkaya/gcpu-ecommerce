<x-mail::message>
# Stoğa döndü

Merhaba {{ $customerName }},

Takip ettiğiniz **{{ $productName }}** ürünü tekrar stokta.

@if ($variantLabel)
**Varyant:** {{ $variantLabel }}
@endif

Ürünü görüntüleyip sepete ekleyebilirsiniz.

<x-mail::button :url="$productUrl">
Ürüne git
</x-mail::button>

Teşekkürler,<br>
{{ config('app.name') }}
</x-mail::message>
