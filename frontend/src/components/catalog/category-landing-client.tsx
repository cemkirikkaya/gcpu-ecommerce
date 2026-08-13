"use client";

import Link from "next/link";
import { FormEvent, useCallback, useEffect, useState } from "react";

import { ProductCard } from "@/components/catalog/product-card";
import { Button } from "@/components/ui/button";
import { api } from "@/lib/api";
import type { CategoryDetail, ProductListResponse } from "@/lib/types";

const sortOptions = [
  { value: "latest", label: "En yeni" },
  { value: "price_asc", label: "Fiyat (artan)" },
  { value: "price_desc", label: "Fiyat (azalan)" },
  { value: "name_asc", label: "İsim (A-Z)" },
] as const;

type CategoryLandingClientProps = {
  slug: string;
};

export function CategoryLandingClient({ slug }: CategoryLandingClientProps) {
  const [category, setCategory] = useState<CategoryDetail | null>(null);
  const [data, setData] = useState<ProductListResponse | null>(null);
  const [loadingCategory, setLoadingCategory] = useState(true);
  const [loadingProducts, setLoadingProducts] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState("");
  const [minPrice, setMinPrice] = useState("");
  const [maxPrice, setMaxPrice] = useState("");
  const [sort, setSort] = useState<(typeof sortOptions)[number]["value"]>("latest");
  const [page, setPage] = useState(1);

  useEffect(() => {
    let cancelled = false;

    async function loadCategory() {
      setLoadingCategory(true);
      setError(null);

      try {
        const loaded = await api.category(slug);
        if (!cancelled) {
          setCategory(loaded);
        }
      } catch (err) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : "Kategori yüklenemedi.");
          setCategory(null);
        }
      } finally {
        if (!cancelled) {
          setLoadingCategory(false);
        }
      }
    }

    void loadCategory();

    return () => {
      cancelled = true;
    };
  }, [slug]);

  const loadProducts = useCallback(async () => {
    setLoadingProducts(true);
    setError(null);

    try {
      const response = await api.products({
        category: slug,
        search: search || undefined,
        min_price: minPrice ? Number(minPrice) : undefined,
        max_price: maxPrice ? Number(maxPrice) : undefined,
        sort,
        page,
        per_page: 12,
      });

      setData(response);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Ürünler yüklenemedi.");
    } finally {
      setLoadingProducts(false);
    }
  }, [slug, search, minPrice, maxPrice, sort, page]);

  useEffect(() => {
    if (!loadingCategory && category) {
      void loadProducts();
    }
  }, [loadProducts, loadingCategory, category]);

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPage(1);
    void loadProducts();
  }

  if (loadingCategory) {
    return <p className="mt-16 text-sm text-muted">Kategori yükleniyor...</p>;
  }

  if (!category) {
    return (
      <div className="mt-16 rounded-[2rem] border border-line bg-surface p-10 text-center">
        <p className="text-sm text-muted">{error ?? "Kategori bulunamadı."}</p>
        <Link
          href="/products"
          className="mt-6 inline-block text-sm text-accent transition hover:underline"
        >
          Koleksiyona dön
        </Link>
      </div>
    );
  }

  return (
    <div>
      <nav className="mt-8 flex flex-wrap items-center gap-2 text-sm text-muted">
        <Link href="/products" className="transition hover:text-accent">
          Koleksiyon
        </Link>
        {category.parent && (
          <>
            <span aria-hidden="true">/</span>
            <Link
              href={`/categories/${category.parent.slug}`}
              className="transition hover:text-accent"
            >
              {category.parent.name}
            </Link>
          </>
        )}
        <span aria-hidden="true">/</span>
        <span className="text-foreground">{category.name}</span>
      </nav>

      <div className="mt-8 flex flex-wrap items-end justify-between gap-6">
        <div className="max-w-3xl">
          <p className="text-xs uppercase tracking-[0.45em] text-muted">Kategori</p>
          <h1 className="mt-4 font-display text-5xl font-semibold leading-tight text-foreground sm:text-6xl">
            {category.name}
          </h1>
          {category.description && (
            <p className="mt-6 text-lg leading-8 text-muted">{category.description}</p>
          )}
        </div>
        <span className="rounded-full bg-accent-soft px-5 py-2.5 text-xs uppercase tracking-[0.2em] text-accent">
          {category.products_count} ürün
        </span>
      </div>

      {category.children.length > 0 && (
        <div className="mt-10 flex flex-wrap gap-3">
          {category.children.map((child) => (
            <Link
              key={child.id}
              href={`/categories/${child.slug}`}
              className="rounded-full border border-line bg-surface px-5 py-2.5 text-sm transition hover:border-accent hover:text-accent"
            >
              {child.name}
              {child.products_count > 0 && (
                <span className="ml-2 text-muted">({child.products_count})</span>
              )}
            </Link>
          ))}
        </div>
      )}

      <form
        onSubmit={handleSubmit}
        className="mt-12 grid gap-4 rounded-[2rem] border border-line bg-surface p-6 lg:grid-cols-[2fr_1fr_1fr_1fr_auto]"
      >
        <input
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          placeholder={`${category.name} içinde ara...`}
          className="rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
        <input
          value={minPrice}
          onChange={(event) => setMinPrice(event.target.value)}
          type="number"
          min="0"
          placeholder="Min fiyat"
          className="rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
        <input
          value={maxPrice}
          onChange={(event) => setMaxPrice(event.target.value)}
          type="number"
          min="0"
          placeholder="Max fiyat"
          className="rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
        <select
          value={sort}
          onChange={(event) => setSort(event.target.value as typeof sort)}
          className="rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        >
          {sortOptions.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
        <Button type="submit">Filtrele</Button>
      </form>

      {loadingProducts && <p className="mt-10 text-sm text-muted">Ürünler yükleniyor...</p>}
      {error && !loadingProducts && <p className="mt-10 text-sm text-red-600">{error}</p>}

      {!loadingProducts && !error && data && (
        <>
          <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {data.products.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>

          {data.products.length === 0 && (
            <p className="mt-10 text-sm text-muted">
              Bu kategoride aramanıza uygun ürün bulunamadı.
            </p>
          )}

          {data.meta.last_page > 1 && (
            <div className="mt-12 flex items-center justify-center gap-3">
              <Button
                type="button"
                variant="secondary"
                disabled={page <= 1}
                onClick={() => setPage((current) => Math.max(1, current - 1))}
              >
                Önceki
              </Button>
              <span className="text-sm text-muted">
                Sayfa {data.meta.current_page} / {data.meta.last_page}
              </span>
              <Button
                type="button"
                variant="secondary"
                disabled={page >= data.meta.last_page}
                onClick={() => setPage((current) => current + 1)}
              >
                Sonraki
              </Button>
            </div>
          )}
        </>
      )}
    </div>
  );
}
