@extends('layouts.app')

@section('title', 'Sipariş #'.$order->id)

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="rounded-2xl border border-green-200 bg-green-50 p-6 text-center">
            <p class="text-sm font-medium uppercase tracking-widest text-green-700">Sipariş alındı</p>
            <h1 class="mt-2 text-3xl font-semibold text-green-900">Teşekkürler!</h1>
            <p class="mt-2 text-green-800">
                Sipariş numaranız <strong>#{{ $order->id }}</strong>. Stok güncellendi ve sepetiniz temizlendi.
            </p>
        </div>

        <div class="mt-8 grid gap-6">
            <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-900">Sipariş Detayı</h2>

                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-stone-500">Durum</dt>
                        <dd class="font-medium capitalize">{{ $order->status->value }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Ödeme</dt>
                        <dd class="font-medium capitalize">{{ $order->payment_status->value }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Toplam</dt>
                        <dd class="text-lg font-semibold text-shop-700">{{ number_format($order->total_price, 2, ',', '.') }} ₺</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Tarih</dt>
                        <dd class="font-medium">{{ $order->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</dd>
                    </div>
                </dl>
            </section>

            @if ($order->address)
                <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Teslimat Adresi</h2>
                    <p class="mt-3 text-sm text-stone-700">{{ $order->address->fullName() }}</p>
                    <p class="mt-1 text-sm text-stone-600">{{ $order->address->fullAddress() }}</p>
                </section>
            @endif

            <section class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                <div class="border-b border-stone-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-stone-900">Ürünler</h2>
                </div>

                <div class="divide-y divide-stone-200">
                    @foreach ($order->items as $item)
                        @php
                            $variant = $item->cartItem?->productVariant;
                            $product = $variant?->product;
                        @endphp
                        <div class="flex items-center justify-between gap-4 px-6 py-4 text-sm">
                            <div>
                                <p class="font-medium text-stone-900">{{ $product?->name }}</p>
                                <p class="text-stone-600">{{ $variant?->displayLabel() }}</p>
                                <p class="text-stone-500">{{ $item->quantity }} adet × {{ number_format($item->price, 2, ',', '.') }} ₺</p>
                            </div>
                            <p class="font-semibold text-shop-700">{{ number_format($item->subtotal(), 2, ',', '.') }} ₺</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('products.index') }}" class="rounded-xl bg-shop-600 px-5 py-3 text-sm font-semibold text-white hover:bg-shop-700">
                    Alışverişe Devam Et
                </a>
                <a href="{{ route('cart.index') }}" class="rounded-xl border border-stone-300 px-5 py-3 text-sm font-semibold text-stone-700 hover:bg-stone-50">
                    Sepete Git
                </a>
            </div>
        </div>
    </div>
@endsection
