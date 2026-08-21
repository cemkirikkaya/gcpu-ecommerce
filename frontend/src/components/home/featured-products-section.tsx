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
    <section className="luxury-grain relative overflow-hidden bg-luxury-dark py-24 lg:py-32">
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_30%_0%,rgba(184,149,107,0.12)_0%,transparent_55%),radial-gradient(ellipse_at_80%_100%,rgba(184,149,107,0.08)_0%,transparent_45%)]" />
      <div className="pointer-events-none absolute left-0 top-0 h-px w-full bg-gradient-to-r from-transparent via-gold/35 to-transparent" />

      <div className="relative mx-auto max-w-7xl px-6 lg:px-10">
        <div className="flex flex-wrap items-end justify-between gap-8">
          <div className="max-w-xl">
            <div className="flex items-center gap-4">
              <span className="h-px w-10 bg-gold/60" aria-hidden="true" />
              <p className="text-[10px] uppercase tracking-[0.45em] text-gold-soft">Editoryal seçki</p>
            </div>
            <h2 className="mt-6 font-display text-5xl font-light leading-[1.05] text-white sm:text-6xl">
              Öne çıkan
              <span className="block font-semibold italic text-gold"> parçalar</span>
            </h2>
            <p className="mt-6 max-w-md text-base leading-8 text-white/55">
              En yeni eklenen seçkin ürünler — her biri vitrinimizde özel bir yerde.
            </p>
          </div>
          <Link
            href="/products"
            className="group inline-flex items-center gap-3 border-b border-gold/40 pb-1 text-sm uppercase tracking-[0.25em] text-gold-soft transition hover:border-gold hover:text-gold"
          >
            Tümünü gör
            <span className="transition group-hover:translate-x-1" aria-hidden="true">
              →
            </span>
          </Link>
        </div>

        <div className="mt-16 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
          {products.map((product) => (
            <div
              key={product.id}
              className="rounded-sm ring-1 ring-white/5 transition duration-500 hover:ring-gold/25"
            >
              <ProductCard product={product} />
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
