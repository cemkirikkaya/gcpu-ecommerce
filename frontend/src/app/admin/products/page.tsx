"use client";

import { useEffect, useState } from "react";

import { Button, ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatPrice } from "@/lib/api";
import type { AdminProduct } from "@/lib/types";

export default function AdminProductsPage() {
  const { token } = useAuth();
  const [products, setProducts] = useState<AdminProduct[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [stockDrafts, setStockDrafts] = useState<Record<number, string>>({});
  const [savingStockId, setSavingStockId] = useState<number | null>(null);

  useEffect(() => {
    if (!token) return;

    api
      .adminProducts(token)
      .then((items) => {
        setProducts(items);
        const drafts: Record<number, string> = {};
        items.forEach((product) => {
          product.variants?.forEach((variant) => {
            if (variant.stock_id) {
              drafts[variant.stock_id] = String(variant.quantity);
            }
          });
        });
        setStockDrafts(drafts);
      })
      .catch((err) => setError(err instanceof Error ? err.message : "Ürünler yüklenemedi"))
      .finally(() => setLoading(false));
  }, [token]);

  async function handleStockUpdate(stockId: number) {
    if (!token) return;

    const quantity = Number(stockDrafts[stockId]);
    if (Number.isNaN(quantity) || quantity < 0) {
      setError("Geçerli bir stok miktarı girin.");
      return;
    }

    setSavingStockId(stockId);
    setError(null);

    try {
      await api.adminUpdateStock(token, stockId, quantity);
      setProducts((current) =>
        current.map((product) => ({
          ...product,
          variants: product.variants?.map((variant) =>
            variant.stock_id === stockId ? { ...variant, quantity } : variant,
          ),
        })),
      );
    } catch (err) {
      setError(err instanceof Error ? err.message : "Stok güncellenemedi");
    } finally {
      setSavingStockId(null);
    }
  }

  async function handleDelete(productId: number) {
    if (!token || !confirm("Bu ürünü silmek istediğinize emin misiniz?")) return;

    try {
      await api.adminDeleteProduct(token, productId);
      setProducts((current) => current.filter((product) => product.id !== productId));
    } catch (err) {
      setError(err instanceof Error ? err.message : "Ürün silinemedi");
    }
  }

  return (
    <div>
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-xs uppercase tracking-[0.35em] text-muted">Ürünler</p>
          <h1 className="mt-3 font-display text-4xl font-semibold">Stok Yönetimi</h1>
        </div>
        <ButtonLink href="/admin/products/new">Yeni Ürün</ButtonLink>
      </div>

      {error && <p className="mt-6 text-sm text-red-600">{error}</p>}

      {loading ? (
        <p className="mt-8 text-sm text-muted">Yükleniyor...</p>
      ) : (
        <div className="mt-8 space-y-6">
          {products.map((product) => (
            <div
              key={product.id}
              className="rounded-[1.5rem] border border-line bg-surface p-6"
            >
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <h2 className="text-xl font-semibold">{product.name}</h2>
                  <p className="mt-1 text-sm text-muted">
                    {product.category?.name ?? "Kategorisiz"} · {formatPrice(product.price)}
                  </p>
                </div>
                <div className="flex gap-2">
                  <ButtonLink href={`/admin/products/${product.id}`} variant="secondary">
                    Düzenle
                  </ButtonLink>
                  <Button variant="ghost" onClick={() => handleDelete(product.id)}>
                    Sil
                  </Button>
                </div>
              </div>

              <div className="mt-6 overflow-x-auto">
                <table className="min-w-full text-sm">
                  <thead>
                    <tr className="border-b border-line text-left text-muted">
                      <th className="py-2 pr-4">SKU</th>
                      <th className="py-2 pr-4">Varyant</th>
                      <th className="py-2 pr-4">Stok</th>
                      <th className="py-2">İşlem</th>
                    </tr>
                  </thead>
                  <tbody>
                    {product.variants?.map((variant) => (
                      <tr key={variant.id} className="border-b border-line/60">
                        <td className="py-3 pr-4">{variant.sku}</td>
                        <td className="py-3 pr-4">{variant.label || "—"}</td>
                        <td className="py-3 pr-4">
                          {variant.stock_id ? (
                            <input
                              type="number"
                              min={0}
                              value={stockDrafts[variant.stock_id] ?? variant.quantity}
                              onChange={(event) =>
                                setStockDrafts((current) => ({
                                  ...current,
                                  [variant.stock_id!]: event.target.value,
                                }))
                              }
                              className="w-24 rounded-full border border-line bg-background px-3 py-2"
                            />
                          ) : (
                            variant.quantity
                          )}
                        </td>
                        <td className="py-3">
                          {variant.stock_id && (
                            <Button
                              variant="secondary"
                              disabled={savingStockId === variant.stock_id}
                              onClick={() => handleStockUpdate(variant.stock_id!)}
                            >
                              {savingStockId === variant.stock_id ? "Kaydediliyor..." : "Güncelle"}
                            </Button>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          ))}

          {products.length === 0 && (
            <div className="rounded-[1.5rem] border border-dashed border-line p-10 text-center">
              <p className="text-muted">Henüz ürün eklenmemiş.</p>
              <ButtonLink href="/admin/products/new" className="mt-4">
                İlk Ürünü Ekle
              </ButtonLink>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
