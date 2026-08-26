"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { ColorSwatch } from "@/components/catalog/color-swatch";
import { ProductFavoriteButton } from "@/components/catalog/product-favorite-button";
import { StockAlertButton } from "@/components/catalog/stock-alert-button";
import { ProductFeatures, getProductVariants } from "@/components/catalog/product-features";
import { ProductImageGallery } from "@/components/catalog/product-image-gallery";
import { ProductRatingStars } from "@/components/catalog/product-rating-stars";
import { ProductReviews } from "@/components/catalog/product-reviews";
import { RelatedProducts } from "@/components/catalog/related-products";
import { Button, ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatPrice } from "@/lib/api";
import { resolveImageSrc } from "@/lib/media";
import {
  getVariantColorHex,
  resolveColor,
  variantSecondaryLabel,
} from "@/lib/color-utils";
import type { Product, ProductVariant } from "@/lib/types";

export default function ProductDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const [product, setProduct] = useState<Product | null>(null);
  const [selectedVariant, setSelectedVariant] = useState<ProductVariant | null>(null);
  const [quantity, setQuantity] = useState(1);
  const [message, setMessage] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const { token } = useAuth();

  useEffect(() => {
    params.then(({ id }) => {
      api
        .product(Number(id))
        .then((loaded) => {
          setProduct(loaded);
          const variants =
            loaded.variant_groups?.flatMap((group) => group.variants) ??
            loaded.variants ??
            [];
          setSelectedVariant(
            variants.find((variant) => variant.available_quantity > 0) ??
              variants[0] ??
              null,
          );
        })
        .finally(() => setLoading(false));
    });
  }, [params]);

  async function handleAddToCart() {
    if (!selectedVariant || !token) {
      window.location.href = "/login";
      return;
    }

    try {
      await api.addToCart(token, selectedVariant.id, quantity);
      setMessage("Sepete eklendi");
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Hata");
    }
  }

  if (loading) {
    return (
      <div className="mx-auto max-w-7xl px-6 py-24 text-center text-muted lg:px-10">
        Yükleniyor...
      </div>
    );
  }

  if (!product) {
    return (
      <div className="mx-auto max-w-7xl px-6 py-24 text-center lg:px-10">
        Ürün bulunamadı.
      </div>
    );
  }

  const groups = product.variant_groups ?? [];
  const allVariants = getProductVariants(product);
  const isColorGrouped = product.base_variant === "Renk";
  const activeGroup =
    groups.find((group) =>
      group.variants.some((variant) => variant.id === selectedVariant?.id),
    ) ?? groups[0];

  function selectColorGroup(groupLabel: string) {
    const group = groups.find((item) => item.label === groupLabel);

    if (!group) {
      return;
    }

    const nextVariant =
      group.variants.find((variant) => variant.available_quantity > 0) ??
      group.variants[0] ??
      null;

    setSelectedVariant(nextVariant);
  }

  const displayImageUrl = resolveImageSrc(
    selectedVariant?.image_url ?? product.image_url,
  );
  const galleryImages = product.images ?? [];

  return (
    <div className="mx-auto max-w-7xl px-6 py-16 lg:px-10 lg:py-24">
      <Link href="/products" className="text-sm text-muted transition hover:text-accent">
        ← Koleksiyona dön
      </Link>

      <div className="mt-10 grid gap-12 lg:grid-cols-2 lg:gap-16">
        <ProductImageGallery
          images={galleryImages}
          fallbackImageUrl={displayImageUrl}
          alt={product.name}
          favoriteButton={
            <ProductFavoriteButton
              productId={product.id}
              className="absolute right-5 top-5 z-10"
            />
          }
        />

        <div>
          <p className="text-xs uppercase tracking-[0.35em] text-muted">
            {product.category?.name ?? "Ürün"}
          </p>
          <h1 className="mt-4 font-display text-5xl font-semibold leading-tight">
            {product.name}
          </h1>
          {product.review_summary && product.review_summary.count > 0 && (
            <div className="mt-4">
              <ProductRatingStars
                rating={product.review_summary.average}
                showValue
                reviewCount={product.review_summary.count}
              />
            </div>
          )}
          <p className="mt-6 text-2xl text-accent">{formatPrice(product.price)}</p>
          <ProductFeatures variants={allVariants} />

          <div className="mt-10 space-y-8">
            {isColorGrouped ? (
              <>
                <div>
                  <p className="text-xs uppercase tracking-[0.28em] text-muted">Renk</p>
                  <div className="mt-4 flex flex-wrap gap-2">
                    {groups.map((group) => {
                      const disabled = group.variants.every(
                        (variant) => variant.available_quantity <= 0,
                      );
                      const selected = activeGroup?.label === group.label;

                      return (
                        <ColorSwatch
                          key={group.label}
                          color={resolveColor(group.label)}
                          label={group.label}
                          selected={selected}
                          disabled={disabled}
                          size="md"
                          onClick={() => selectColorGroup(group.label)}
                        />
                      );
                    })}
                  </div>
                </div>

                {activeGroup && activeGroup.variants.length > 1 && (
                  <div>
                    <p className="text-xs uppercase tracking-[0.28em] text-muted">
                      Seçenek
                    </p>
                    <div className="mt-4 flex flex-wrap gap-2">
                      {activeGroup.variants.map((variant) => (
                        <button
                          key={variant.id}
                          type="button"
                          disabled={variant.available_quantity <= 0}
                          onClick={() => setSelectedVariant(variant)}
                          className={`rounded-full border px-4 py-2 text-sm ${
                            selectedVariant?.id === variant.id
                              ? "border-accent bg-accent text-white"
                              : "border-line bg-surface"
                          } ${variant.available_quantity <= 0 ? "cursor-not-allowed opacity-40" : ""}`}
                        >
                          {variantSecondaryLabel(variant)}
                        </button>
                      ))}
                    </div>
                  </div>
                )}
              </>
            ) : (
              groups.map((group) => (
                <div key={group.label}>
                  <p className="text-xs uppercase tracking-[0.28em] text-muted">
                    {group.label}
                  </p>
                  <div className="mt-4 flex flex-wrap gap-2">
                    {group.variants.map((variant) => {
                      const colorHex = getVariantColorHex(variant);

                      if (colorHex) {
                        return (
                          <ColorSwatch
                            key={variant.id}
                            color={colorHex}
                            label={variant.label}
                            selected={selectedVariant?.id === variant.id}
                            disabled={variant.available_quantity <= 0}
                            size="md"
                            onClick={() => setSelectedVariant(variant)}
                          />
                        );
                      }

                      return (
                        <button
                          key={variant.id}
                          type="button"
                          disabled={variant.available_quantity <= 0}
                          onClick={() => setSelectedVariant(variant)}
                          className={`rounded-full border px-4 py-2 text-sm ${
                            selectedVariant?.id === variant.id
                              ? "border-accent bg-accent text-white"
                              : "border-line bg-surface"
                          } ${variant.available_quantity <= 0 ? "cursor-not-allowed opacity-40" : ""}`}
                        >
                          {variant.label}
                        </button>
                      );
                    })}
                  </div>
                </div>
              ))
            )}
          </div>

          <div className="mt-10 flex flex-wrap gap-3 border-t border-line pt-10">
            {(selectedVariant?.available_quantity ?? 0) > 0 ? (
              <>
                <input
                  type="number"
                  min={1}
                  max={Math.min(99, selectedVariant?.available_quantity ?? 1)}
                  value={quantity}
                  onChange={(event) => setQuantity(Number(event.target.value))}
                  className="w-20 rounded-full border border-line px-4 py-3 text-center text-sm"
                />
                <Button onClick={handleAddToCart}>Sepete Ekle</Button>
                <ButtonLink href="/cart" variant="secondary">
                  Sepete Git
                </ButtonLink>
              </>
            ) : selectedVariant ? (
              <StockAlertButton variantId={selectedVariant.id} />
            ) : null}
          </div>

          {message && <p className="mt-4 text-sm text-accent">{message}</p>}
        </div>
      </div>

      <RelatedProducts
        productId={product.id}
        categorySlug={product.category?.slug}
        categoryName={product.category?.name}
      />

      <ProductReviews
        productId={product.id}
        initialSummary={product.review_summary}
      />
    </div>
  );
}
