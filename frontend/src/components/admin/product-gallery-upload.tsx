"use client";

import { useEffect, useRef, useState } from "react";

import { ProductImage } from "@/components/catalog/product-image";
import { Button } from "@/components/ui/button";
import { resolveImageSrc } from "@/lib/media";
import type { ProductGalleryImage } from "@/lib/types";

type ProductGalleryUploadProps = {
  productId: number;
  productName: string;
  images: ProductGalleryImage[];
  onChange: (images: ProductGalleryImage[], imageUrl: string | null) => void;
  onUpload: (file: File) => Promise<{ images: ProductGalleryImage[]; image_url: string | null }>;
  onDelete: (imageId: number) => Promise<{ images: ProductGalleryImage[]; image_url: string | null }>;
  onSetCover: (imageId: number) => Promise<{ images: ProductGalleryImage[]; image_url: string | null }>;
};

export function ProductGalleryUpload({
  productId,
  productName,
  images,
  onChange,
  onUpload,
  onDelete,
  onSetCover,
}: ProductGalleryUploadProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [uploading, setUploading] = useState(false);
  const [busyImageId, setBusyImageId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [gallery, setGallery] = useState<ProductGalleryImage[]>(images);

  useEffect(() => {
    setGallery(images);
  }, [images]);

  async function handleFileChange(event: React.ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];

    if (!file) {
      return;
    }

    setUploading(true);
    setError(null);

    try {
      const result = await onUpload(file);
      setGallery(result.images);
      onChange(result.images, result.image_url);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Görsel yüklenemedi");
    } finally {
      setUploading(false);
      event.target.value = "";
    }
  }

  async function handleDelete(imageId: number) {
    setBusyImageId(imageId);
    setError(null);

    try {
      const result = await onDelete(imageId);
      setGallery(result.images);
      onChange(result.images, result.image_url);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Görsel silinemedi");
    } finally {
      setBusyImageId(null);
    }
  }

  async function handleSetCover(imageId: number) {
    setBusyImageId(imageId);
    setError(null);

    try {
      const result = await onSetCover(imageId);
      setGallery(result.images);
      onChange(result.images, result.image_url);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Kapak görseli güncellenemedi");
    } finally {
      setBusyImageId(null);
    }
  }

  return (
    <div className="space-y-4 rounded-[1.5rem] border border-line bg-surface p-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="font-medium">Ürün Görselleri</h2>
          <p className="mt-1 text-sm text-muted">
            Vitrin ve detay sayfasında gösterilecek fotoğraflar. İlk kapak görseli listede öne çıkar.
          </p>
        </div>
        <Button
          type="button"
          variant="secondary"
          disabled={uploading}
          onClick={() => inputRef.current?.click()}
        >
          {uploading ? "Yükleniyor..." : "Görsel Ekle"}
        </Button>
      </div>

      <input
        ref={inputRef}
        type="file"
        accept="image/*"
        className="hidden"
        onChange={handleFileChange}
      />

      {gallery.length > 0 ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {gallery.map((image) => {
            const displayUrl = resolveImageSrc(image.url);
            const busy = busyImageId === image.id;

            return (
              <div
                key={image.id}
                className="overflow-hidden rounded-[1.25rem] border border-line bg-background"
              >
                <div className="relative aspect-[4/5]">
                  {displayUrl ? (
                    <ProductImage
                      src={displayUrl}
                      alt={productName}
                      className="object-cover"
                      sizes="240px"
                    />
                  ) : (
                    <div className="flex h-full items-center justify-center text-sm text-muted">
                      Görsel yok
                    </div>
                  )}

                  {image.is_cover && (
                    <span className="absolute left-3 top-3 rounded-full bg-accent px-3 py-1 text-xs font-medium text-white">
                      Kapak
                    </span>
                  )}
                </div>

                <div className="flex flex-wrap gap-2 p-3">
                  {!image.is_cover && (
                    <Button
                      type="button"
                      variant="secondary"
                      disabled={busy}
                      onClick={() => handleSetCover(image.id)}
                    >
                      Kapak Yap
                    </Button>
                  )}
                  <Button
                    type="button"
                    variant="secondary"
                    disabled={busy}
                    onClick={() => handleDelete(image.id)}
                  >
                    Sil
                  </Button>
                </div>
              </div>
            );
          })}
        </div>
      ) : (
        <div className="rounded-[1.25rem] border border-dashed border-line px-6 py-10 text-center text-sm text-muted">
          Henüz görsel yok. Detay sayfasında kaydırılabilir galeri için birden fazla fotoğraf ekleyin.
        </div>
      )}

      {error && <p className="text-sm text-red-600">{error}</p>}
      <p className="text-xs text-muted">Ürün #{productId}</p>
    </div>
  );
}
