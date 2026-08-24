"use client";

import Link from "next/link";
import { useRef } from "react";

import { ProductImage } from "@/components/catalog/product-image";
import { CarouselControls } from "@/components/ui/carousel-controls";
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

  function scrollBy(direction: "prev" | "next") {
    const rail = railRef.current;
    if (!rail) {
      return;
    }

    const amount = direction === "next" ? rail.clientWidth * 0.75 : -rail.clientWidth * 0.75;
    rail.scrollBy({ left: amount, behavior: "smooth" });
  }

  return (
    <section id="discover" className="relative scroll-mt-24 py-20 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-10">
        <div className="flex flex-wrap items-end justify-between gap-6">
          <div>
            <p className="text-[10px] uppercase tracking-[0.5em] text-muted">Kategoriler</p>
            <h2 className="mt-4 font-display text-5xl font-light text-foreground sm:text-6xl">
              Koleksiyon
              <span className="italic text-accent"> rayları</span>
            </h2>
          </div>
          <div className="flex items-center gap-4">
            <CarouselControls
              onPrevious={() => scrollBy("prev")}
              onNext={() => scrollBy("next")}
            />
            <Link
              href="/products"
              className="text-sm uppercase tracking-[0.3em] text-muted transition hover:text-gold"
            >
              Tümü →
            </Link>
          </div>
        </div>
      </div>

      <div
        ref={railRef}
        className="mt-12 flex snap-x snap-mandatory gap-6 overflow-x-auto px-6 pb-4 pt-2 [scrollbar-width:none] lg:gap-8 lg:px-[max(1.5rem,calc((100vw-80rem)/2+2.5rem))] [&::-webkit-scrollbar]:hidden"
      >
        {visibleCategories.map((category, index) => {
          const preview = productsByCategory[category.slug]?.[0];
          const imageSrc = preview ? resolveImageSrc(preview.image_url) : null;
          const productCount = productsByCategory[category.slug]?.length ?? 0;

          return (
            <Link
              key={category.id}
              href={`/categories/${category.slug}`}
              className="group w-[78vw] shrink-0 snap-center overflow-hidden rounded-[1.75rem] border border-line/80 bg-surface shadow-[0_24px_60px_-45px_rgba(28,25,23,0.12)] transition duration-500 hover:-translate-y-1 hover:border-gold/40 sm:w-[320px] lg:w-[360px]"
            >
              <div className="relative aspect-[4/5] overflow-hidden bg-accent-soft/30">
                {imageSrc ? (
                  <ProductImage
                    src={imageSrc}
                    alt={category.name}
                    className="object-cover transition duration-[1.2s] ease-out group-hover:scale-105"
                    sizes="360px"
                  />
                ) : (
                  <div className="flex h-full items-center justify-center bg-gradient-to-br from-accent-soft to-surface">
                    <span className="font-display text-8xl font-light text-stone-300">
                      {category.name.slice(0, 1)}
                    </span>
                  </div>
                )}
              </div>

              <div className="border-t border-line/60 px-6 py-6">
                <p className="text-[10px] uppercase tracking-[0.35em] text-gold">
                  0{index + 1} · Kategori
                </p>
                <h3 className="mt-3 font-display text-3xl font-light text-foreground">
                  {category.name}
                </h3>
                <p className="mt-2 text-sm text-muted">
                  {productCount > 0 ? `${productCount}+ parça` : "Koleksiyonu aç"}
                </p>
              </div>
            </Link>
          );
        })}
      </div>
    </section>
  );
}
