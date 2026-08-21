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

const ROTATE_MS = 5000;

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

  useEffect(() => {
    if (products.length <= 1) {
      return;
    }

    const timer = window.setInterval(() => {
      setActiveIndex((current) => (current + 1) % products.length);
    }, ROTATE_MS);

    return () => window.clearInterval(timer);
  }, [products.length]);

  if (products.length === 0) {
    return (
      <div className="relative mx-auto w-full max-w-md rounded-sm border border-gold/30 bg-surface/80 p-12 shadow-[0_40px_100px_-60px_rgba(18,16,14,0.45)] backdrop-blur-sm">
        <p className="text-[10px] uppercase tracking-[0.4em] text-gold">Editoryal vitrin</p>
        <p className="mt-4 font-display text-4xl font-light leading-tight text-foreground">
          Koleksiyonunuz burada parlayacak.
        </p>
      </div>
    );
  }

  const activeProduct = products[activeIndex % products.length];
  const imageSrc = resolveImageSrc(activeProduct.image_url);
  const nextProduct = products[(activeIndex + 1) % products.length];

  return (
    <div className="relative mx-auto w-full max-w-[420px] lg:mx-0 lg:ml-auto">
      <div
        aria-hidden="true"
        className="absolute -right-4 top-8 z-0 hidden w-[72%] overflow-hidden rounded-sm border border-line/80 bg-surface/60 opacity-60 shadow-[0_30px_80px_-50px_rgba(18,16,14,0.5)] lg:block"
      >
        <div className="relative aspect-[4/5] bg-[linear-gradient(145deg,#ece4d8,#faf7f2)]">
          {resolveImageSrc(nextProduct.image_url) ? (
            <ProductImage
              src={resolveImageSrc(nextProduct.image_url)!}
              alt=""
              className="object-cover opacity-80"
              sizes="280px"
            />
          ) : null}
        </div>
      </div>

      <div
        key={activeProduct.id}
        className="animate-fade-up relative z-10 overflow-hidden rounded-sm border border-gold/25 bg-surface shadow-[0_50px_120px_-60px_rgba(18,16,14,0.55)]"
      >
        <div className="luxury-shimmer pointer-events-none absolute inset-0 z-20" />

        <div className="relative aspect-[4/5] w-full overflow-hidden bg-[linear-gradient(145deg,#e8dfd3,#faf7f2)]">
          <Link href={`/products/${activeProduct.id}`} className="absolute inset-0">
            {imageSrc ? (
              <ProductImage
                src={imageSrc}
                alt={activeProduct.name}
                priority
                className="object-cover animate-ken-burns"
                sizes="(max-width: 640px) 100vw, 420px"
              />
            ) : (
              <div className="flex h-full w-full items-center justify-center">
                <span className="font-display text-6xl font-light text-stone-300">
                  {activeProduct.name.slice(0, 1)}
                </span>
              </div>
            )}
          </Link>

          <div className="pointer-events-none absolute inset-4 border border-white/20" />

          <span className="pointer-events-none absolute left-5 top-5 z-10 rounded-sm border border-white/25 bg-black/20 px-3 py-1 text-[9px] uppercase tracking-[0.35em] text-white/90 backdrop-blur-md">
            Seçili parça
          </span>

          <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-luxury-dark/85 via-luxury-dark/10 to-transparent" />

          <div className="pointer-events-none absolute inset-x-0 bottom-0 z-10 p-6 text-white sm:p-8">
            {activeProduct.category && (
              <p className="text-[10px] uppercase tracking-[0.35em] text-gold-soft">
                {activeProduct.category.name}
              </p>
            )}
            <p className="mt-3 font-display text-3xl font-light leading-tight sm:text-4xl">
              {activeProduct.name}
            </p>
            <p className="mt-3 font-display text-xl text-gold-soft">
              {formatPrice(activeProduct.price)}
            </p>
          </div>
        </div>
      </div>

      {products.length > 1 && (
        <div className="mt-6 flex items-center justify-center gap-2">
          {products.slice(0, Math.min(products.length, 6)).map((product, index) => (
            <button
              key={product.id}
              type="button"
              aria-label={`${product.name} göster`}
              onClick={() => setActiveIndex(index)}
              className={`h-1.5 rounded-full transition-all duration-500 ${
                index === activeIndex % products.length
                  ? "w-8 bg-gold"
                  : "w-1.5 bg-line hover:bg-gold/50"
              }`}
            />
          ))}
        </div>
      )}
    </div>
  );
}
