"use client";

import { FormEvent, useEffect, useState } from "react";
import { useRouter } from "next/navigation";

import { ProductGalleryUpload } from "@/components/admin/product-gallery-upload";
import { VariantFields } from "@/components/admin/variant-fields";
import { Button, ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api } from "@/lib/api";
import { categoryOptionLabel, emptyVariant, sortedCategoryOptions } from "@/lib/admin-products";
import type { AdminCategory, AdminProduct, CatalogVariantInput } from "@/lib/types";

type EditProductClientProps = {
  productId: string;
  merged: boolean;
};

export function EditProductClient({ productId, merged }: EditProductClientProps) {
  const router = useRouter();
  const parsedProductId = Number(productId);
  const { token } = useAuth();
  const [product, setProduct] = useState<AdminProduct | null>(null);
  const [categories, setCategories] = useState<AdminCategory[]>([]);
  const [variants, setVariants] = useState<CatalogVariantInput[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!token) {
      return;
    }

    if (!productId || Number.isNaN(parsedProductId)) {
      setError("Geçersiz ürün.");
      return;
    }

    Promise.all([
      api.adminProduct(token, parsedProductId),
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
            size: variant.size ?? "",
          })) ?? [emptyVariant()],
        );
      })
      .catch((err) => setError(err instanceof Error ? err.message : "Ürün yüklenemedi"));
  }, [parsedProductId, productId, token]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!token || !product) {
      return;
    }

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

  if (!product) {
    return <p className="text-sm text-muted">{error ?? "Yükleniyor..."}</p>;
  }

  return (
    <div>
      <p className="text-xs uppercase tracking-[0.35em] text-muted">Düzenle</p>
      <h1 className="mt-3 font-display text-4xl font-semibold">{product.name}</h1>

      {merged && (
        <p className="mt-4 rounded-[1rem] border border-accent/30 bg-accent/5 px-4 py-3 text-sm">
          Varyantlar mevcut ürüne eklendi. Tüm seçenekleri aşağıda görebilirsiniz.
        </p>
      )}

      <form onSubmit={handleSubmit} className="mt-8 max-w-3xl space-y-6">
        <ProductGalleryUpload
          productId={product.id}
          productName={product.name}
          images={product.images ?? []}
          onChange={(images, imageUrl) =>
            setProduct((current) =>
              current ? { ...current, images, image_url: imageUrl } : current,
            )
          }
          onUpload={async (file) => {
            if (!token) {
              throw new Error("Oturum bulunamadı");
            }

            const response = await api.adminUploadProductImage(token, product.id, file);

            return {
              images: response.product.images ?? [],
              image_url: response.product.image_url ?? null,
            };
          }}
          onDelete={async (imageId) => {
            if (!token) {
              throw new Error("Oturum bulunamadı");
            }

            const response = await api.adminDeleteProductImage(token, product.id, imageId);

            return {
              images: response.product.images ?? [],
              image_url: response.product.image_url ?? null,
            };
          }}
          onSetCover={async (imageId) => {
            if (!token) {
              throw new Error("Oturum bulunamadı");
            }

            const response = await api.adminSetProductCoverImage(token, product.id, imageId);

            return {
              images: response.product.images ?? [],
              image_url: response.product.image_url ?? null,
            };
          }}
        />

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
          {sortedCategoryOptions(categories).map((category) => (
            <option key={category.id} value={category.id}>
              {categoryOptionLabel(category, categories)}
            </option>
          ))}
        </select>

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
