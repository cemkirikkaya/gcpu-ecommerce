"use client";

import { FormEvent, useEffect, useState } from "react";
import { useParams, useRouter, useSearchParams } from "next/navigation";

import { Button, ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api } from "@/lib/api";
import type { AdminCategory, AdminProduct, CatalogVariantInput } from "@/lib/types";

export default function EditProductPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const searchParams = useSearchParams();
  const merged = searchParams.get("merged") === "1";
  const { token } = useAuth();
  const [product, setProduct] = useState<AdminProduct | null>(null);
  const [categories, setCategories] = useState<AdminCategory[]>([]);
  const [variants, setVariants] = useState<CatalogVariantInput[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!token) return;

    Promise.all([
      api.adminProduct(token, Number(params.id)),
      api.adminCategories(token),
    ])
      .then(([loadedProduct, loadedCategories]) => {
        setProduct(loadedProduct);
        setCategories(loadedCategories);
        setVariants(
          loadedProduct.variants?.map((variant) => ({
            sku: variant.sku,
            stock: variant.quantity,
            color: variant.color ?? "",
            memory: variant.memory ?? "",
            model: variant.model ?? "",
          })) ?? [],
        );
      })
      .catch((err) => setError(err instanceof Error ? err.message : "Ürün yüklenemedi"));
  }, [params.id, token]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!token || !product) return;

    setLoading(true);
    setError(null);

    const formData = new FormData(event.currentTarget);

    try {
      await api.adminUpdateProduct(token, product.id, {
        name: String(formData.get("name")),
        description: String(formData.get("description") || ""),
        price: Number(formData.get("price")),
        category_id: formData.get("category_id")
          ? Number(formData.get("category_id"))
          : null,
        catalog_variants: variants.filter((variant) => variant.sku.trim() !== ""),
      });
      router.push("/admin/products");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Ürün güncellenemedi");
    } finally {
      setLoading(false);
    }
  }

  function updateVariant(index: number, field: keyof CatalogVariantInput, value: string) {
    setVariants((current) =>
      current.map((variant, variantIndex) =>
        variantIndex === index
          ? {
              ...variant,
              [field]: field === "stock" ? Number(value) : value,
            }
          : variant,
      ),
    );
  }

  if (!product) {
    return <p className="text-sm text-muted">{error ?? "Yükleniyor..."}</p>;
  }

  return (
    <div>
      <p className="text-xs uppercase tracking-[0.35em] text-muted">Düzenle</p>
      <h1 className="mt-3 font-display text-4xl font-semibold">{product.name}</h1>

      {merged && (
        <p className="mt-4 rounded-[1rem] border border-accent/30 bg-accent/5 px-4 py-3 text-sm">
          Varyantlar mevcut ürüne eklendi. Tüm renk ve hafıza seçeneklerini aşağıda
          görebilirsiniz.
        </p>
      )}

      <form onSubmit={handleSubmit} className="mt-8 max-w-3xl space-y-6">
        <input
          name="name"
          required
          defaultValue={product.name}
          placeholder="Ürün adı"
          className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
        <textarea
          name="description"
          defaultValue={product.description ?? ""}
          placeholder="Açıklama"
          rows={4}
          className="w-full rounded-[1.5rem] border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
        <input
          name="price"
          type="number"
          min={0}
          step="0.01"
          required
          defaultValue={product.price}
          placeholder="Fiyat"
          className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
        <select
          name="category_id"
          defaultValue={product.category?.id ?? ""}
          className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        >
          <option value="">Kategori seçin</option>
          {categories.map((category) => (
            <option key={category.id} value={category.id}>
              {category.name}
            </option>
          ))}
        </select>

        <div className="space-y-4 rounded-[1.5rem] border border-line bg-surface p-6">
          <h2 className="font-medium">Varyantlar</h2>
          {variants.map((variant, index) => (
            <div key={index} className="grid gap-3 md:grid-cols-2">
              <input
                required
                value={variant.sku}
                onChange={(event) => updateVariant(index, "sku", event.target.value)}
                placeholder="örn. HOOD-BLACK-M"
                className="rounded-full border border-line bg-background px-4 py-2 text-sm"
              />
              <input
                type="number"
                min={0}
                required
                value={variant.stock}
                onChange={(event) => updateVariant(index, "stock", event.target.value)}
                placeholder="Stok"
                className="rounded-full border border-line bg-background px-4 py-2 text-sm"
              />
              <input
                value={variant.color ?? ""}
                onChange={(event) => updateVariant(index, "color", event.target.value)}
                placeholder="Renk"
                className="rounded-full border border-line bg-background px-4 py-2 text-sm"
              />
              <input
                value={variant.memory ?? ""}
                onChange={(event) => updateVariant(index, "memory", event.target.value)}
                placeholder="Hafıza"
                className="rounded-full border border-line bg-background px-4 py-2 text-sm"
              />
            </div>
          ))}
        </div>

        {error && <p className="text-sm text-red-600">{error}</p>}

        <div className="flex gap-3">
          <Button type="submit" disabled={loading}>
            {loading ? "Kaydediliyor..." : "Değişiklikleri Kaydet"}
          </Button>
          <ButtonLink href="/admin/products" variant="secondary">
            Geri
          </ButtonLink>
        </div>
      </form>
    </div>
  );
}
