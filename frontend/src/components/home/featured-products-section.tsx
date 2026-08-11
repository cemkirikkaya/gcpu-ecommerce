import Link from "next/link";

import { ProductCard } from "@/components/catalog/product-card";
import type { Product } from "@/lib/types";

type FeaturedProductsSectionProps = {
  products: Product[];
};

export function FeaturedProductsSection({ products }: FeaturedProductsSectionProps) {
  if (products.length === 0) {
    return null;
  }

  return (
    <section className="border-y border-line/70 bg-surface/50">
      <div className="mx-auto max-w-7xl px-6 py-20 lg:px-10 lg:py-24">
        <div className="flex flex-wrap items-end justify-between gap-6">
          <div className="max-w-2xl">
            <p className="text-xs uppercase tracking-[0.35em] text-muted">Seçilmiş</p>
            <h2 className="mt-3 font-display text-4xl font-semibold text-foreground sm:text-5xl">
              Öne çıkan ürünler
            </h2>
            <p className="mt-4 text-base leading-7 text-muted">
              En yeni eklenen parçalar — favorilere ekleyin veya doğrudan sepete alın.
            </p>
          </div>
          <Link
            href="/products"
            className="rounded-full border border-line bg-background px-5 py-2.5 text-sm text-foreground transition hover:border-accent hover:text-accent"
          >
            Daha fazlasını gör
          </Link>
        </div>

        <div className="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
          {products.map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>
      </div>
    </section>
  );
}
