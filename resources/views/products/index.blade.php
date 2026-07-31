@extends('layouts.app')

@section('title', 'Ürünler')

@section('content')
    <section class="mb-10 overflow-hidden rounded-2xl bg-gradient-to-br from-shop-600 to-shop-700 px-6 py-10 text-white shadow-lg sm:px-10">
        <div class="max-w-2xl">
            <p class="text-sm font-medium uppercase tracking-widest text-shop-100">Yeni sezon</p>
            <h1 class="mt-2 text-3xl font-semibold sm:text-4xl">Kategorilere göre keşfet, seçeneğini sepete ekle</h1>
            <p class="mt-4 text-shop-100">
                Renk, beden ve model seçenekleriyle alışveriş yapın. Sepete eklediğiniz ürünler
                {{ config('shop.reservation_minutes') }} dakika boyunca sizin için ayrılır.
            </p>
        </div>
    </section>

    @php
        $hasProducts = $categories->isNotEmpty() || $uncategorizedProducts->isNotEmpty();
    @endphp

    @if (! $hasProducts)
        <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center text-stone-600">
            Henüz ürün bulunmuyor. Admin panelinden ürün ekleyebilirsiniz.
        </div>
    @else
        @foreach ($categories as $category)
            @include('products.partials.category-section', ['category' => $category, 'depth' => 0])
        @endforeach

        @if ($uncategorizedProducts->isNotEmpty())
            <section class="mb-12">
                <h2 class="mb-6 border-b border-stone-200 pb-2 text-2xl font-semibold text-stone-800">
                    Kategorisiz Ürünler
                </h2>

                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach ($uncategorizedProducts as $product)
                        @include('products.partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            </section>
        @endif
    @endif
@endsection
