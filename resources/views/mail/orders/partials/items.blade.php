@foreach ($order->items as $item)
@php
    $product = $item->cartItem?->productVariant?->product;
    $productName = $product?->name ?? 'Ürün';
@endphp
- **{{ $productName }}** — {{ $item->quantity }} adet × {{ number_format((float) $item->price, 2, ',', '.') }} ₺
@endforeach
