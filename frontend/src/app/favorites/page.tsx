"use client";

import { useEffect, useState } from "react";

import { ProductCard } from "@/components/catalog/product-card";
import { useAuth } from "@/context/auth-context";
import { useWishlist } from "@/context/wishlist-context";
import { api } from "@/lib/api";
import type { Product } from "@/lib/types";

export default function FavoritesPage() {
  const { token, loading: authLoading } = useAuth();
  const { productIds } = useWishlist();
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (authLoading) {
      return;
    }

    if (!token) {
      window.location.href = "/login";
      return;
    }

    setLoading(true);

    api
      .wishlist(token)
      .then((items) => setProducts(items))
      .catch((err) =>
        setError(err instanceof Error ? err.message : "Favoriler yüklenemedi."),
      )
      .finally(() => setLoading(false));
  }, [token, authLoading]);

  const visibleProducts = products.filter((product) => productIds.includes(product.id));

  if (authLoading || loading) {
    return <p className="px-6 py-20 text-sm text-muted">Yükleniyor...</p>;
  }

  return (
    <div className="mx-auto max-w-7xl px-6 py-16 lg:px-10">
      <p className="text-xs uppercase tracking-[0.35em] text-muted">Hesabım</p>
      <h1 className="mt-3 font-display text-4xl font-semibold">Favorilerim</h1>
      <p className="mt-3 max-w-2xl text-sm leading-7 text-muted">
        Beğendiğiniz ürünleri burada saklayın ve istediğiniz zaman sepete ekleyin.
      </p>

      {error && <p className="mt-6 text-sm text-red-600">{error}</p>}

      {visibleProducts.length === 0 ? (
        <p className="mt-12 rounded-[1.5rem] border border-line bg-surface px-6 py-10 text-sm text-muted">
          Henüz favori ürününüz yok. Koleksiyondaki kalp ikonuna tıklayarak ekleyebilirsiniz.
        </p>
      ) : (
        <div className="mt-12 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
          {visibleProducts.map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>
      )}
    </div>
  );
}
