"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { ProductCard } from "@/components/catalog/product-card";
import { api } from "@/lib/api";
import type { Product } from "@/lib/types";

type RelatedProductsProps = {
  productId: number;
  categorySlug?: string | null;
  categoryName?: string | null;
};

export function RelatedProducts({
  productId,
  categorySlug,
  categoryName,
}: RelatedProductsProps) {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      try {
        let items: Product[] = [];

        if (categorySlug) {
          const response = await api.products({ category: categorySlug, per_page: 8 });
          items = response.products.filter((product) => product.id !== productId);
        }

        if (items.length < 4) {
          const response = await api.products({ per_page: 12, sort: "latest" });
          const extras = response.products.filter(
            (product) =>
              product.id !== productId && !items.some((item) => item.id === product.id),
          );
          items = [...items, ...extras];
        }

        if (!cancelled) {
          setProducts(items.slice(0, 4));
        }
      } catch {
        if (!cancelled) {
          setProducts([]);
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    }

    void load();

    return () => {
      cancelled = true;
    };
  }, [productId, categorySlug]);

  if (loading) {
    return <p className="mt-20 text-sm text-muted">Benzer ürünler yükleniyor...</p>;
  }

  if (products.length === 0) {
    return null;
  }

  const title = categoryName ? `${categoryName} kategorisinden` : "Benzer ürünler";

  return (
    <section className="mt-20 border-t border-line pt-16">
      <div className="flex flex-wrap items-end justify-between gap-6">
        <div>
          <p className="text-xs uppercase tracking-[0.35em] text-muted">Keşfet</p>
          <h2 className="mt-3 font-display text-4xl font-semibold">{title}</h2>
        </div>
        {categorySlug && (
          <Link
            href={`/categories/${categorySlug}`}
            className="rounded-full border border-line bg-background px-5 py-2.5 text-sm transition hover:border-accent hover:text-accent"
          >
            Kategoriye git
          </Link>
        )}
      </div>

      <div className="mt-10 grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
        {products.map((product) => (
          <ProductCard key={product.id} product={product} />
        ))}
      </div>
    </section>
  );
}
