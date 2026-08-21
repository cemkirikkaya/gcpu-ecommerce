"use client";

import Link from "next/link";

import { ProductImage } from "@/components/catalog/product-image";
import { formatPrice } from "@/lib/api";
import { resolveImageSrc } from "@/lib/media";
import type { Product } from "@/lib/types";

type HeroProductFilmstripProps = {
  products: Product[];
};

export function HeroProductFilmstrip({ products }: HeroProductFilmstripProps) {
  if (products.length === 0) {
    return null;
  }

  const loop = [...products, ...products];

  return (
    <div className="overflow-hidden border-t border-white/10 bg-black/20 pb-8 pt-5 backdrop-blur-sm [mask-image:linear-gradient(to_right,transparent,black_6%,black_94%,transparent)]">
      <div className="animate-filmstrip flex w-max gap-4 px-4">
        {loop.map((product, index) => {
          const imageSrc = resolveImageSrc(product.image_url);

          return (
            <Link
              key={`${product.id}-${index}`}
              href={`/products/${product.id}`}
              className="group relative h-32 w-24 shrink-0 overflow-hidden rounded-md border border-white/20 bg-black/40 shadow-lg transition duration-500 hover:-translate-y-1 hover:border-gold/50 sm:h-36 sm:w-28"
            >
              <div className="relative h-full w-full">
                {imageSrc ? (
                  <ProductImage
                    src={imageSrc}
                    alt={product.name}
                    className="object-cover transition duration-700 group-hover:scale-110"
                    sizes="112px"
                  />
                ) : (
                  <div className="flex h-full items-center justify-center bg-white/5">
                    <span className="font-display text-2xl text-white/30">
                      {product.name.slice(0, 1)}
                    </span>
                  </div>
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-black/85 via-transparent to-transparent" />
                <div className="absolute inset-x-0 bottom-0 p-2">
                  <p className="truncate text-[9px] uppercase tracking-[0.18em] text-gold-soft">
                    {product.category?.name ?? "Koleksiyon"}
                  </p>
                  <p className="mt-0.5 truncate text-[11px] font-medium text-white">
                    {product.name}
                  </p>
                </div>
              </div>
            </Link>
          );
        })}
      </div>
    </div>
  );
}
