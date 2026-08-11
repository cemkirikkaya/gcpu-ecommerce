"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { ProductImage } from "@/components/catalog/product-image";
import { formatPrice } from "@/lib/api";
import { fetchAllProducts } from "@/lib/fetch-all-products";
import { resolveImageSrc } from "@/lib/media";
import type { Product } from "@/lib/types";

type HeroCollageProps = {
  initialProducts: Product[];
};

const ROTATE_MS = 4500;

export function HeroCollage({ initialProducts }: HeroCollageProps) {
  const [products, setProducts] = useState(initialProducts);
  const [activeIndex, setActiveIndex] = useState(0);

  useEffect(() => {
    let cancelled = false;

    const load = async () => {
      try {
        const allProducts = await fetchAllProducts({ sort: "latest" });
        if (!cancelled && allProducts.length > 0) {
          setProducts(allProducts);
          setActiveIndex(0);
        }
      } catch {
        if (!cancelled && initialProducts.length > 0) {
          setProducts(initialProducts);
        }
      }
    };

    void load();

    return () => {
      cancelled = true;
    };
  }, [initialProducts]);

  const items = products;

  useEffect(() => {
    if (items.length <= 1) {
      return;
    }

    const timer = window.setInterval(() => {
      setActiveIndex((current) => (current + 1) % items.length);
    }, ROTATE_MS);

    return () => window.clearInterval(timer);
  }, [items.length]);

  if (items.length === 0) {
    return (
      <div className="rounded-[1.75rem] border border-line bg-surface p-10 shadow-[0_40px_100px_-50px_rgba(28,25,23,0.35)]">
        <p className="text-xs uppercase tracking-[0.3em] text-muted">Canlı vitrin</p>
        <p className="mt-4 font-display text-4xl leading-tight">
          Koleksiyonunuz burada hayat bulacak.
        </p>
      </div>
    );
  }

  const activeProduct = items[activeIndex % items.length];
  const imageSrc = resolveImageSrc(activeProduct.image_url);

  return (
    <div
      key={activeProduct.id}
      className="animate-fade-up mx-auto w-full max-w-[260px] overflow-hidden rounded-[1.5rem] border border-line bg-surface shadow-[0_24px_60px_-40px_rgba(28,25,23,0.35)] sm:max-w-[290px] lg:mx-0 lg:ml-auto lg:max-w-[320px]"
    >
      <div className="relative aspect-[4/5] w-full overflow-hidden bg-[linear-gradient(145deg,#f3eee8,#faf8f5)]">
        <Link href={`/products/${activeProduct.id}`} className="absolute inset-0">
          {imageSrc ? (
            <ProductImage
              src={imageSrc}
              alt={activeProduct.name}
              priority
              className="object-cover animate-ken-burns"
              sizes="(max-width: 1024px) 65vw, 320px"
            />
          ) : (
            <div className="flex h-full w-full items-center justify-center">
              <span className="font-display text-5xl text-stone-300">
                {activeProduct.name.slice(0, 1)}
              </span>
            </div>
          )}
        </Link>

        <span className="pointer-events-none absolute left-3 top-3 z-10 rounded-full border border-line bg-surface/90 px-2 py-0.5 text-[8px] uppercase tracking-[0.14em] text-accent backdrop-blur">
          Canlı vitrin
        </span>

        <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-stone-950/75 via-stone-950/10 to-transparent" />

        <div className="pointer-events-none absolute inset-x-0 bottom-0 z-10 p-4 text-white">
          {activeProduct.category && (
            <p className="text-[9px] uppercase tracking-[0.22em] text-white/75">
              {activeProduct.category.name}
            </p>
          )}
          <p className="mt-1.5 font-display text-xl leading-tight sm:text-2xl">
            {activeProduct.name}
          </p>
          <p className="mt-1 text-xs text-white/85">{formatPrice(activeProduct.price)}</p>
        </div>
      </div>
    </div>
  );
}
