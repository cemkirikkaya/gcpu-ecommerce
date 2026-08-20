"use client";

import { useCallback, useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";

import { ProductCard } from "@/components/catalog/product-card";
import { ProductSearchAutocomplete } from "@/components/catalog/product-search-autocomplete";
import { Button } from "@/components/ui/button";
import { api } from "@/lib/api";
import type { CatalogCategoryOption, ProductListResponse } from "@/lib/types";

const sortOptions = [
  { value: "latest", label: "En yeni" },
  { value: "price_asc", label: "Fiyat (artan)" },
  { value: "price_desc", label: "Fiyat (azalan)" },
  { value: "name_asc", label: "İsim (A-Z)" },
] as const;

export function ProductsCatalogClient() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [data, setData] = useState<ProductListResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState(() => searchParams.get("search") ?? "");
  const [minPrice, setMinPrice] = useState("");
  const [maxPrice, setMaxPrice] = useState("");
  const [sort, setSort] = useState<(typeof sortOptions)[number]["value"]>("latest");
  const [page, setPage] = useState(1);
  const [categories, setCategories] = useState<CatalogCategoryOption[]>([]);

  useEffect(() => {
    const categoryParam = searchParams.get("category");
    if (categoryParam) {
      router.replace(`/categories/${categoryParam}`);
    }
  }, [router, searchParams]);

  const loadProducts = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await api.products({
        search: search || undefined,
        min_price: minPrice ? Number(minPrice) : undefined,
        max_price: maxPrice ? Number(maxPrice) : undefined,
        sort,
        page,
        per_page: 12,
      });

      setData(response);
      setCategories(response.categories);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Ürünler yüklenemedi.");
    } finally {
      setLoading(false);
    }
  }, [search, minPrice, maxPrice, sort, page]);

  useEffect(() => {
    if (searchParams.get("category")) {
      return;
    }

    setSearch(searchParams.get("search") ?? "");
    setPage(1);
  }, [searchParams]);

  useEffect(() => {
    if (searchParams.get("category")) {
      return;
    }

    void loadProducts();
  }, [loadProducts, searchParams]);

  function handleCategoryChange(slug: string) {
    if (slug) {
      router.push(`/categories/${slug}`);
      return;
    }

    router.push("/products");
  }

  function handleSearchSubmit(value: string) {
    setPage(1);
    setSearch(value);

    const params = new URLSearchParams();
    const trimmed = value.trim();

    if (trimmed) {
      params.set("search", trimmed);
    }

    const query = params.toString();
    router.push(query ? `/products?${query}` : "/products");
  }

  return (
    <div>
      <div className="mt-16 grid gap-4 rounded-[2rem] border border-line bg-surface p-6 lg:grid-cols-[2fr_1fr_1fr_1fr_1fr_auto]">
        <ProductSearchAutocomplete
          value={search}
          onChange={setSearch}
          onSubmit={handleSearchSubmit}
          showSubmitButton={false}
          className="rounded-full border border-line bg-background px-5 py-1"
          inputClassName="py-2"
        />
        <select
          defaultValue=""
          onChange={(event) => handleCategoryChange(event.target.value)}
          className="rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        >
          <option value="">Kategori seç...</option>
          {categories.map((item) => (
            <option key={item.id} value={item.slug}>
              {item.name}
            </option>
          ))}
        </select>
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
        <Button type="button" onClick={() => handleSearchSubmit(search)}>
          Filtrele
        </Button>
      </div>

      {loading && <p className="mt-10 text-sm text-muted">Yükleniyor...</p>}
      {error && <p className="mt-10 text-sm text-red-600">{error}</p>}

      {!loading && !error && data && (
        <>
          <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {data.products.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>

          {data.products.length === 0 && (
            <p className="mt-10 text-sm text-muted">Aramanıza uygun ürün bulunamadı.</p>
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
