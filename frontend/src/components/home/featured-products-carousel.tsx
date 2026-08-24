"use client";

import Link from "next/link";
import { useRef } from "react";

import { ProductCard } from "@/components/catalog/product-card";
import { CarouselControls } from "@/components/ui/carousel-controls";
import type { Product } from "@/lib/types";

type FeaturedProductsCarouselProps = {
  products: Product[];
};

export function FeaturedProductsCarousel({ products }: FeaturedProductsCarouselProps) {
  const railRef = useRef<HTMLDivElement>(null);

  if (products.length === 0) {
    return null;
  }

  function scrollBy(direction: "prev" | "next") {
    const rail = railRef.current;
    if (!rail) {
      return;
    }

    const amount = direction === "next" ? rail.clientWidth * 0.85 : -rail.clientWidth * 0.85;
    rail.scrollBy({ left: amount, behavior: "smooth" });
  }

  return (
    <section className="bg-surface/60 py-20 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-10">
        <div className="flex flex-wrap items-end justify-between gap-6">
          <div>
            <p className="text-[10px] uppercase tracking-[0.5em] text-muted">Seçilmiş parçalar</p>
            <h2 className="mt-4 font-display text-5xl font-light text-foreground sm:text-6xl">
              Öne çıkan
              <span className="italic text-accent"> koleksiyon</span>
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

        <div
          ref={railRef}
          className="mt-12 flex snap-x snap-mandatory gap-6 overflow-x-auto pb-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
        >
          {products.map((product) => (
            <div
              key={product.id}
              className="w-[280px] shrink-0 snap-start sm:w-[300px] lg:w-[320px]"
            >
              <ProductCard product={product} />
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
