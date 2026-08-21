import Link from "next/link";

import { ProductCard } from "@/components/catalog/product-card";
import { ProductImage } from "@/components/catalog/product-image";
import { formatPrice } from "@/lib/api";
import { resolveImageSrc } from "@/lib/media";
import type { Product } from "@/lib/types";

type FeaturedBentoProps = {
  products: Product[];
};

export function FeaturedBento({ products }: FeaturedBentoProps) {
  if (products.length === 0) {
    return null;
  }

  const [hero, ...rest] = products;
  const heroImage = resolveImageSrc(hero.image_url);
  const sideProducts = rest.slice(0, 2);
  const gridProducts = rest.slice(2, 6);

  return (
    <section className="relative bg-background py-24 lg:py-32">
      <div className="mx-auto max-w-7xl px-6 lg:px-10">
        <div className="mb-12 flex flex-wrap items-end justify-between gap-6">
          <div>
            <p className="text-[10px] uppercase tracking-[0.55em] text-muted">Editoryal seçki</p>
            <h2 className="mt-4 font-display text-5xl font-light text-foreground sm:text-6xl">
              Öne çıkan
              <span className="italic text-accent"> parçalar</span>
            </h2>
          </div>
          <Link
            href="/products"
            className="text-sm uppercase tracking-[0.3em] text-muted transition hover:text-gold"
          >
            Tüm koleksiyon →
          </Link>
        </div>

        <div className="grid gap-6 lg:grid-cols-12">
          <Link
            href={`/products/${hero.id}`}
            className="group relative block min-h-[360px] overflow-hidden rounded-xl bg-luxury-dark lg:col-span-7 lg:min-h-[520px]"
          >
            <div className="relative h-full min-h-[360px] lg:min-h-[520px]">
              {heroImage ? (
                <ProductImage
                  src={heroImage}
                  alt={hero.name}
                  priority
                  className="object-cover transition duration-[1.2s] ease-out group-hover:scale-[1.03]"
                  sizes="(max-width: 1024px) 100vw, 58vw"
                />
              ) : (
                <div className="flex h-full min-h-[360px] items-center justify-center lg:min-h-[520px]">
                  <span className="font-display text-7xl font-light text-white/20">
                    {hero.name.slice(0, 1)}
                  </span>
                </div>
              )}
            </div>
            <div className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/10 to-transparent" />
            <div className="absolute inset-x-0 bottom-0 p-6 text-white lg:p-8">
              <p className="text-[10px] uppercase tracking-[0.45em] text-gold-soft">
                {hero.category?.name ?? "Seçili parça"}
              </p>
              <h3 className="mt-3 font-display text-3xl font-light leading-tight lg:text-5xl">
                {hero.name}
              </h3>
              <p className="mt-3 font-display text-xl text-gold-soft lg:text-2xl">
                {formatPrice(hero.price)}
              </p>
            </div>
          </Link>

          <div className="grid gap-6 sm:grid-cols-2 lg:col-span-5 lg:grid-cols-1">
            {sideProducts.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>
        </div>

        {gridProducts.length > 0 && (
          <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
            {gridProducts.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>
        )}
      </div>
    </section>
  );
}
