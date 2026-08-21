"use client";

import Link from "next/link";
import { useRef } from "react";

import { ProductImage } from "@/components/catalog/product-image";
import { resolveImageSrc } from "@/lib/media";
import type { CatalogCategoryOption, Product } from "@/lib/types";

type CategoryRailProps = {
  categories: CatalogCategoryOption[];
  productsByCategory: Record<string, Product[]>;
};

export function CategoryRail({ categories, productsByCategory }: CategoryRailProps) {
  const railRef = useRef<HTMLDivElement>(null);

  if (categories.length === 0) {
    return null;
  }

  const visibleCategories = categories.slice(0, 8);

  return (
    <section id="discover" className="relative scroll-mt-24 py-24 lg:py-32">
      <div className="mx-auto max-w-7xl px-6 lg:px-10">
        <div className="flex flex-wrap items-end justify-between gap-6">
          <div>
            <p className="text-[10px] uppercase tracking-[0.55em] text-muted">Kaydırarak keşfet</p>
            <h2 className="mt-4 font-display text-5xl font-light text-foreground sm:text-6xl">
              Koleksiyon
              <span className="italic text-accent"> rayları</span>
            </h2>
          </div>
          <Link
            href="/products"
            className="text-sm uppercase tracking-[0.3em] text-muted transition hover:text-gold"
          >
            Tümü →
          </Link>
        </div>
      </div>

      <div
        ref={railRef}
        className="mt-12 flex snap-x snap-mandatory gap-5 overflow-x-auto px-6 pb-4 pt-2 [scrollbar-width:none] lg:gap-6 lg:px-[max(1.5rem,calc((100vw-80rem)/2+2.5rem))] [&::-webkit-scrollbar]:hidden"
      >
        {visibleCategories.map((category, index) => {
          const preview = productsByCategory[category.slug]?.[0];
          const imageSrc = preview ? resolveImageSrc(preview.image_url) : null;
          const productCount = productsByCategory[category.slug]?.length ?? 0;

          return (
            <Link
              key={category.id}
              href={`/categories/${category.slug}`}
              className="group relative aspect-[3/4] w-[78vw] shrink-0 snap-center overflow-hidden rounded-lg bg-luxury-dark shadow-[0_40px_100px_-60px_rgba(0,0,0,0.65)] transition duration-700 hover:-translate-y-2 sm:w-[340px] lg:w-[380px]"
              style={{ transitionDelay: `${index * 40}ms` }}
            >
              <div className="absolute inset-0">
                {imageSrc ? (
                  <ProductImage
                    src={imageSrc}
                    alt={category.name}
                    className="object-cover transition duration-[1.4s] ease-out group-hover:scale-110"
                    sizes="380px"
                  />
                ) : (
                  <div className="flex h-full items-center justify-center bg-gradient-to-br from-stone-800 to-stone-950">
                    <span className="font-display text-8xl font-light text-white/15">
                      {category.name.slice(0, 1)}
                    </span>
                  </div>
                )}
              </div>

              <div className="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-black/10 transition duration-700 group-hover:from-black/90" />
              <div className="absolute inset-0 opacity-0 transition duration-700 group-hover:opacity-100">
                <div className="absolute inset-0 bg-gold/10 mix-blend-overlay" />
              </div>

              <div className="absolute inset-x-0 bottom-0 p-7 text-white">
                <p className="text-[10px] uppercase tracking-[0.4em] text-gold-soft">
                  0{index + 1} · Kategori
                </p>
                <h3 className="mt-3 font-display text-4xl font-light leading-none">
                  {category.name}
                </h3>
                <p className="mt-3 translate-y-2 text-sm text-white/65 opacity-0 transition duration-500 group-hover:translate-y-0 group-hover:opacity-100">
                  {productCount > 0 ? `${productCount}+ parça · Görüntüle` : "Koleksiyonu aç"}
                </p>
              </div>
            </Link>
          );
        })}
      </div>
    </section>
  );
}
