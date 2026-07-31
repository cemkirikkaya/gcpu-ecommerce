@php
    $padding = $depth > 0 ? 'ml-4 border-l-2 border-shop-200 pl-4' : '';
    $products = $category->directProducts ?? $category->products ?? collect();
@endphp

<section class="mb-12 {{ $padding }}">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-semibold text-stone-900">{{ $category->name }}</h2>
            @if ($category->description)
                <p class="mt-1 text-sm text-stone-600">{{ $category->description }}</p>
            @endif
        </div>
        <span class="rounded-full bg-shop-100 px-3 py-1 text-xs font-medium text-shop-700">
            {{ $products->count() }} ürün
        </span>
    </div>

    @if ($products->isNotEmpty())
        <div class="mb-8 grid gap-6 lg:grid-cols-2">
            @foreach ($products as $product)
                @include('products.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    @endif

    @foreach ($category->children as $childCategory)
        @include('products.partials.category-section', ['category' => $childCategory, 'depth' => $depth + 1])
    @endforeach
</section>
