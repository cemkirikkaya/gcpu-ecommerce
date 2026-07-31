"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatPrice } from "@/lib/api";
import type { AdminProduct } from "@/lib/types";

export default function AdminDashboardPage() {
  const { token } = useAuth();
  const [products, setProducts] = useState<AdminProduct[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!token) return;

    api
      .adminProducts(token)
      .then(setProducts)
      .finally(() => setLoading(false));
  }, [token]);

  const totalStock = products.reduce(
    (sum, product) =>
      sum +
      (product.variants?.reduce((variantSum, variant) => variantSum + variant.quantity, 0) ??
        0),
    0,
  );

  const lowStockCount = products.reduce((count, product) => {
    const lowVariants =
      product.variants?.filter((variant) => variant.quantity <= 5).length ?? 0;
    return count + lowVariants;
  }, 0);

  return (
    <div>
      <p className="text-xs uppercase tracking-[0.35em] text-muted">Panel</p>
      <h1 className="mt-3 font-display text-4xl font-semibold">Yönetim Özeti</h1>

      <div className="mt-8 grid gap-4 md:grid-cols-3">
        <div className="rounded-[1.5rem] border border-line bg-surface p-6">
          <p className="text-sm text-muted">Toplam Ürün</p>
          <p className="mt-2 text-3xl font-semibold">{loading ? "—" : products.length}</p>
        </div>
        <div className="rounded-[1.5rem] border border-line bg-surface p-6">
          <p className="text-sm text-muted">Toplam Stok</p>
          <p className="mt-2 text-3xl font-semibold">{loading ? "—" : totalStock}</p>
        </div>
        <div className="rounded-[1.5rem] border border-line bg-surface p-6">
          <p className="text-sm text-muted">Düşük Stoklu Varyant</p>
          <p className="mt-2 text-3xl font-semibold">{loading ? "—" : lowStockCount}</p>
        </div>
      </div>

      <div className="mt-10 flex flex-wrap gap-3">
        <ButtonLink href="/admin/products/new">Yeni Ürün Ekle</ButtonLink>
        <ButtonLink href="/admin/products" variant="secondary">
          Tüm Ürünleri Gör
        </ButtonLink>
      </div>

      <div className="mt-10">
        <h2 className="font-display text-2xl font-semibold">Son Ürünler</h2>
        <div className="mt-4 space-y-3">
          {loading && <p className="text-sm text-muted">Yükleniyor...</p>}
          {!loading && products.length === 0 && (
            <p className="text-sm text-muted">Henüz ürün yok.</p>
          )}
          {products.slice(0, 5).map((product) => (
            <Link
              key={product.id}
              href={`/admin/products/${product.id}`}
              className="flex items-center justify-between rounded-[1.25rem] border border-line bg-surface px-5 py-4 transition hover:border-accent"
            >
              <div>
                <p className="font-medium">{product.name}</p>
                <p className="text-sm text-muted">
                  {product.variants?.length ?? 0} varyant
                </p>
              </div>
              <p className="font-medium">{formatPrice(product.price)}</p>
            </Link>
          ))}
        </div>
      </div>
    </div>
  );
}
