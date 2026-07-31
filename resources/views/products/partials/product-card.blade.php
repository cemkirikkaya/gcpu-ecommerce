<article class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm transition hover:shadow-md">
    <div class="border-b border-stone-100 bg-stone-50/60 p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-xl font-semibold text-stone-900">{{ $product->name }}</h3>
                    @if ($product->category)
                        <span class="rounded-full bg-shop-100 px-2.5 py-0.5 text-xs font-medium text-shop-700">
                            {{ $product->category->name }}
                        </span>
                    @endif
                </div>

                @if ($product->description)
                    <p class="mt-2 text-sm leading-relaxed text-stone-600">{{ $product->description }}</p>
                @endif

                <p class="mt-4 text-2xl font-semibold text-shop-700">
                    {{ number_format($product->price, 2, ',', '.') }} ₺
                </p>

                @if ($product->baseVariant)
                    <p class="mt-1 text-xs uppercase tracking-wide text-stone-500">
                        Gruplama: {{ $product->baseVariant->name }}
                    </p>
                @endif
            </div>

            @if ($product->getFirstMediaUrl('product-images'))
                <img
                    src="{{ $product->getFirstMediaUrl('product-images') }}"
                    alt="{{ $product->name }}"
                    class="h-36 w-36 rounded-xl object-cover ring-1 ring-stone-200"
                >
            @endif
        </div>
    </div>

    @forelse ($product->variantsGroupedByBaseVariant() as $groupLabel => $variants)
        <div class="p-6 pt-4">
            <h4 class="mb-4 text-xs font-semibold uppercase tracking-widest text-stone-500">
                {{ $groupLabel }}
            </h4>

            <div class="space-y-3">
                @foreach ($variants as $variant)
                    @php
                        $available = $variant->availableQuantity();
                    @endphp
                    <div class="rounded-xl border border-stone-100 bg-stone-50/80 p-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex gap-4">
                                @if ($variant->images->first())
                                    <img
                                        src="{{ asset('storage/' . $variant->images->first()->image) }}"
                                        alt="{{ $variant->displayLabel() }}"
                                        class="h-20 w-20 rounded-lg object-cover ring-1 ring-stone-200"
                                    >
                                @else
                                    <div class="flex h-20 w-20 items-center justify-center rounded-lg bg-stone-200 text-xs text-stone-500">
                                        Görsel yok
                                    </div>
                                @endif

                                <div>
                                    <p class="font-medium text-stone-900">{{ $variant->displayLabel() }}</p>
                                    <p class="text-xs text-stone-500">SKU: {{ $variant->sku }}</p>

                                    <dl class="mt-2 grid gap-1 text-sm text-stone-600">
                                        @foreach ($variant->attributeList() as $attribute)
                                            <div class="flex gap-2">
                                                <dt class="font-medium">{{ $attribute['name'] }}:</dt>
                                                <dd>{{ $attribute['value'] }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>

                                    <p class="mt-2 text-sm font-medium {{ $available > 0 ? 'text-green-700' : 'text-red-600' }}">
                                        @if ($available > 0)
                                            Stokta {{ $available }} adet
                                        @else
                                            Stokta yok
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="shrink-0">
                                @auth
                                    @if (auth()->user()->isCustomer())
                                        @if ($available > 0)
                                            <form method="POST" action="{{ route('cart.items.store') }}" class="flex items-center gap-2">
                                                @csrf
                                                <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                                                <input
                                                    type="number"
                                                    name="quantity"
                                                    value="1"
                                                    min="1"
                                                    max="{{ min(99, $available) }}"
                                                    class="w-16 rounded-lg border border-stone-300 bg-white px-2 py-2 text-sm"
                                                >
                                                <button type="submit" class="rounded-lg bg-shop-600 px-4 py-2 text-sm font-semibold text-white hover:bg-shop-700">
                                                    Sepete Ekle
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-sm text-stone-500">Tükendi</span>
                                        @endif
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="text-sm font-medium text-shop-700 hover:underline">
                                        Sepete eklemek için giriş yapın
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="p-6 pt-0">
            <p class="rounded-xl border border-dashed border-stone-200 bg-stone-50 p-4 text-sm text-stone-500">
                Bu ürün için henüz satış seçeneği tanımlanmamış.
            </p>
        </div>
    @endforelse
</article>
