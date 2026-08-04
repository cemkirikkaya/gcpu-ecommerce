"use client";

import { FormEvent, useEffect, useRef, useState } from "react";
import { useRouter } from "next/navigation";

import { VariantFields } from "@/components/admin/variant-fields";
import { Button, ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api } from "@/lib/api";
import { categoryOptionLabel, emptyVariant, sortedCategoryOptions } from "@/lib/admin-products";
import type { AdminCategory, CatalogVariantInput } from "@/lib/types";

export default function NewProductPage() {
  const router = useRouter();
  const { token } = useAuth();
  const coverInputRef = useRef<HTMLInputElement>(null);
  const [categories, setCategories] = useState<AdminCategory[]>([]);
  const [variants, setVariants] = useState<CatalogVariantInput[]>([emptyVariant()]);
  const [coverFile, setCoverFile] = useState<File | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!token) {
      return;
    }

    api.adminCategories(token).then(setCategories).catch(() => undefined);
  }, [token]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!token) {
      return;
    }

    setLoading(true);
    setError(null);

    const formData = new FormData(event.currentTarget);

    try {
      const response = await api.adminCreateProduct(token, {
        name: String(formData.get("name")),
        description: String(formData.get("description") || ""),
        price: Number(formData.get("price")),
        category_id: formData.get("category_id")
          ? Number(formData.get("category_id"))
          : null,
        catalog_variants: variants.filter((variant) => variant.sku.trim() !== ""),
      });

      if (coverFile) {
        await api.adminUploadProductCover(token, response.product.id, coverFile);
      }

      if (response.merged) {
        router.push(`/admin/products/${response.product.id}?merged=1`);
        return;
      }

      router.push(`/admin/products/${response.product.id}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Ürün oluşturulamadı");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      <p className="text-xs uppercase tracking-[0.35em] text-muted">Yeni Ürün</p>
      <h1 className="mt-3 font-display text-4xl font-semibold">Ürün Ekle</h1>

      <form onSubmit={handleSubmit} className="mt-8 max-w-3xl space-y-6">
        <input
          name="name"
          required
          placeholder="Ürün adı (örn. iPhone 17)"
          className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
        <p className="text-sm text-muted">
          Aynı isimle ürün eklediğinizde yeni kayıt oluşturulmaz; varyantlar mevcut
          ürüne eklenir.
        </p>
        <textarea
          name="description"
          placeholder="Vitrin açıklaması"
          rows={4}
          className="w-full rounded-[1.5rem] border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
        <input
          name="price"
          type="number"
          min={0}
          step="0.01"
          required
          placeholder="Fiyat"
          className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
        <select
          name="category_id"
          className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        >
          <option value="">Kategori seçin</option>
          {sortedCategoryOptions(categories).map((category) => (
            <option key={category.id} value={category.id}>
              {categoryOptionLabel(category, categories)}
            </option>
          ))}
        </select>

        <div className="space-y-4 rounded-[1.5rem] border border-line bg-surface p-6">
          <div className="flex items-center justify-between gap-3">
            <div>
              <h2 className="font-medium">Kapak Görseli</h2>
              <p className="mt-1 text-sm text-muted">İsteğe bağlı. Kayıttan sonra da eklenebilir.</p>
            </div>
            <Button
              type="button"
              variant="secondary"
              onClick={() => coverInputRef.current?.click()}
            >
              Görsel Seç
            </Button>
          </div>
          <input
            ref={coverInputRef}
            type="file"
            accept="image/*"
            className="hidden"
            onChange={(event) => setCoverFile(event.target.files?.[0] ?? null)}
          />
          {coverFile && (
            <p className="text-sm text-muted">Seçilen dosya: {coverFile.name}</p>
          )}
        </div>

        <div className="space-y-4 rounded-[1.5rem] border border-line bg-surface p-6">
          <div className="flex items-center justify-between gap-3">
            <h2 className="font-medium">Varyantlar</h2>
            <Button
              type="button"
              variant="secondary"
              onClick={() => setVariants((current) => [...current, emptyVariant()])}
            >
              Varyant Ekle
            </Button>
          </div>

          <VariantFields variants={variants} onChange={setVariants} />
        </div>

        {error && <p className="text-sm text-red-600">{error}</p>}

        <div className="flex gap-3">
          <Button type="submit" disabled={loading}>
            {loading ? "Kaydediliyor..." : "Ürünü Kaydet"}
          </Button>
          <ButtonLink href="/admin/products" variant="secondary">
            İptal
          </ButtonLink>
        </div>
      </form>
    </div>
  );
}
