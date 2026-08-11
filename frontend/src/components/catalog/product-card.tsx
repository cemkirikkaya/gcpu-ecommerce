"use client";

import Link from "next/link";
import { useState } from "react";

import { ColorSwatch } from "@/components/catalog/color-swatch";
import { ProductFavoriteButton } from "@/components/catalog/product-favorite-button";
import { ProductImage } from "@/components/catalog/product-image";
import { ProductRatingStars } from "@/components/catalog/product-rating-stars";
import { Button } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatPrice } from "@/lib/api";
import { resolveImageSrc } from "@/lib/media";
import {
  getVariantColor,
  getVariantColorHex,
  uniqueVariantsByColor,
} from "@/lib/color-utils";
import type { Product, ProductVariant } from "@/lib/types";

export function ProductCard({ product }: { product: Product }) {
  const groups = product.variant_groups ?? [];
  const flatVariants =
    groups.length > 0
      ? groups.flatMap((group) => group.variants)
      : (product.variants ?? []);

  const colorVariants = uniqueVariantsByColor(flatVariants);
  const hasColorSwatches = colorVariants.length > 0;

  const [selectedVariant, setSelectedVariant] = useState<ProductVariant | null>(
    flatVariants.find((v) => v.available_quantity > 0) ?? flatVariants[0] ?? null,
  );

  function selectColorVariant(colorValue: string) {
    const matchingVariant =
      flatVariants.find(
        (variant) =>
          getVariantColor(variant) === colorValue && variant.available_quantity > 0,
      ) ??
      flatVariants.find((variant) => getVariantColor(variant) === colorValue) ??
      null;

    setSelectedVariant(matchingVariant);
  }
  const [quantity, setQuantity] = useState(1);
  const [message, setMessage] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const { token } = useAuth();

  async function handleAddToCart() {
    if (!selectedVariant) {
      return;
    }

    if (!token) {
      window.location.href = "/login";
      return;
    }

    setLoading(true);
    setMessage(null);

    try {
      await api.addToCart(token, selectedVariant.id, quantity);
      setMessage("Sepete eklendi");
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Hata oluştu");
    } finally {
      setLoading(false);
    }
  }

  const imageSrc = resolveImageSrc(product.image_url);

  return (
    <article className="group flex h-full flex-col overflow-hidden rounded-[1.75rem] border border-line bg-surface shadow-[0_20px_60px_-40px_rgba(28,25,23,0.35)] transition duration-500 hover:-translate-y-1 hover:shadow-[0_30px_80px_-40px_rgba(28,25,23,0.25)]">
      <div className="relative block aspect-[4/5] overflow-hidden bg-[linear-gradient(145deg,#f3eee8,#faf8f5)]">
        <Link href={`/products/${product.id}`} className="absolute inset-0">
          {imageSrc ? (
            <ProductImage
              src={imageSrc}
              alt={product.name}
              className="object-cover opacity-95 transition duration-700 group-hover:scale-[1.03]"
              sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw"
            />
          ) : (
            <div className="flex h-full items-center justify-center">
              <span className="font-display text-5xl text-stone-300">
                {product.name.slice(0, 1)}
              </span>
            </div>
          )}
        </Link>
        {product.category && (
          <span className="pointer-events-none absolute left-4 top-4 rounded-full border border-line bg-surface/90 px-3 py-1 text-[10px] uppercase tracking-[0.2em] text-muted backdrop-blur">
            {product.category.name}
          </span>
        )}
        <ProductFavoriteButton
          productId={product.id}
          className="absolute right-4 top-4 z-10"
        />
      </div>

      <div className="flex flex-1 flex-col p-5">
        <div className="flex items-start justify-between gap-3">
          <Link href={`/products/${product.id}`} className="min-w-0 flex-1">
            <h3 className="font-display text-xl font-semibold leading-tight text-foreground transition hover:text-accent">
              {product.name}
            </h3>
          </Link>
          <p className="shrink-0 text-base font-medium text-accent">
            {formatPrice(product.price)}
          </p>
        </div>

        {product.description && (
          <p className="mt-2 line-clamp-2 text-sm leading-6 text-muted">
            {product.description}
          </p>
        )}

        {product.review_summary && product.review_summary.count > 0 && (
          <div className="mt-3">
            <ProductRatingStars
              rating={product.review_summary.average}
              size="sm"
              showValue
              reviewCount={product.review_summary.count}
            />
          </div>
        )}

        {flatVariants.length > 0 && (
          <div className="mt-4 space-y-2">
            {hasColorSwatches ? (
              <div className="flex flex-wrap items-center gap-2">
                {colorVariants.map((variant) => {
                  const colorValue = getVariantColor(variant);
                  const colorHex = getVariantColorHex(variant);
                  const active =
                    selectedVariant !== null &&
                    getVariantColor(selectedVariant) === colorValue;
                  const disabled = !flatVariants.some(
                    (item) =>
                      getVariantColor(item) === colorValue &&
                      item.available_quantity > 0,
                  );

                  if (!colorValue || !colorHex) {
                    return null;
                  }

                  return (
                    <ColorSwatch
                      key={colorValue}
                      color={colorHex}
                      label={colorValue}
                      selected={active}
                      disabled={disabled}
                      onClick={() => selectColorVariant(colorValue)}
                    />
                  );
                })}
              </div>
            ) : (
              <div className="flex flex-wrap gap-1.5">
                {flatVariants.map((variant) => {
                  const active = selectedVariant?.id === variant.id;
                  const disabled = variant.available_quantity <= 0;

                  return (
                    <button
                      key={variant.id}
                      type="button"
                      disabled={disabled}
                      onClick={() => setSelectedVariant(variant)}
                      className={`rounded-full border px-3 py-1 text-xs transition ${
                        active
                          ? "border-accent bg-accent text-white"
                          : "border-line bg-background text-stone-700 hover:border-accent/40"
                      } ${disabled ? "cursor-not-allowed opacity-40" : ""}`}
                    >
                      {variant.label}
                    </button>
                  );
                })}
              </div>
            )}
          </div>
        )}

        <div className="mt-auto flex flex-wrap items-center gap-2 border-t border-line pt-4">
          <input
            type="number"
            min={1}
            max={Math.min(99, selectedVariant?.available_quantity ?? 1)}
            value={quantity}
            onChange={(event) => setQuantity(Number(event.target.value))}
            className="w-16 rounded-full border border-line bg-background px-3 py-2 text-center text-sm outline-none focus:border-accent"
          />
          <Button
            onClick={handleAddToCart}
            disabled={
              loading ||
              !selectedVariant ||
              selectedVariant.available_quantity <= 0
            }
            className="min-w-0 flex-1 px-4 py-2 text-sm"
          >
            {loading ? "..." : "Sepete Ekle"}
          </Button>
        </div>

        {message && <p className="mt-2 text-xs text-accent">{message}</p>}
      </div>
    </article>
  );
}
