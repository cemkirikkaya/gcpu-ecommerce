"use client";

import { useEffect, useRef, useState } from "react";

import { ProductImage } from "@/components/catalog/product-image";
import { Button } from "@/components/ui/button";
import { resolveImageSrc } from "@/lib/media";

type ProductCoverUploadProps = {
  productId: number;
  imageUrl?: string | null;
  productName: string;
  onUploaded: (imageUrl: string) => void;
  onUpload: (file: File) => Promise<{ image_url: string | null }>;
};

export function ProductCoverUpload({
  productId,
  imageUrl,
  productName,
  onUploaded,
  onUpload,
}: ProductCoverUploadProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(imageUrl ?? null);

  useEffect(() => {
    setPreviewUrl(imageUrl ?? null);
  }, [imageUrl]);

  async function handleFileChange(event: React.ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];

    if (!file) {
      return;
    }

    setUploading(true);
    setError(null);

    try {
      const result = await onUpload(file);
      const nextUrl = result.image_url ?? previewUrl;
      setPreviewUrl(nextUrl);

      if (nextUrl) {
        onUploaded(nextUrl);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : "Görsel yüklenemedi");
    } finally {
      setUploading(false);
      event.target.value = "";
    }
  }

  const displayUrl = resolveImageSrc(previewUrl);

  return (
    <div className="space-y-4 rounded-[1.5rem] border border-line bg-surface p-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="font-medium">Kapak Görseli</h2>
          <p className="mt-1 text-sm text-muted">
            Vitrinde görünecek ana ürün görseli.
          </p>
        </div>
        <Button
          type="button"
          variant="secondary"
          disabled={uploading}
          onClick={() => inputRef.current?.click()}
        >
          {uploading ? "Yükleniyor..." : "Görsel Seç"}
        </Button>
      </div>

      <input
        ref={inputRef}
        type="file"
        accept="image/*"
        className="hidden"
        onChange={handleFileChange}
      />

      <div className="relative aspect-[4/5] max-w-xs overflow-hidden rounded-[1.25rem] border border-line bg-background">
        {displayUrl ? (
          <ProductImage
            src={displayUrl}
            alt={productName}
            className="object-cover"
            sizes="320px"
          />
        ) : (
          <div className="flex h-full items-center justify-center text-sm text-muted">
            Henüz görsel yok
          </div>
        )}
      </div>

      {error && <p className="text-sm text-red-600">{error}</p>}
      <p className="text-xs text-muted">Ürün #{productId}</p>
    </div>
  );
}
