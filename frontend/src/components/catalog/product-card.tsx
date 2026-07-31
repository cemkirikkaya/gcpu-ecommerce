"use client";

import Image from "next/image";
import { useState } from "react";

import { Button, ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatPrice } from "@/lib/api";
import type { Product, ProductVariant } from "@/lib/types";

export function ProductCard({ product }: { product: Product }) {
  const groups = product.variant_groups ?? [];
  const flatVariants =
    groups.length > 0
      ? groups.flatMap((group) => group.variants)
      : (product.variants ?? []);

  const [selectedVariant, setSelectedVariant] = useState<ProductVariant | null>(
    flatVariants.find((v) => v.available_quantity > 0) ?? flatVariants[0] ?? null,
  );
  const [quantity, setQuantity] = useState(1);
  const [message, setMessage] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const { token } = useAuth();

  async function handleAddToCart() {
    if (!selectedVariant) return;

    if (!token) {
      window.location.href = "/login";
      return;
    }

    setLoading(true);
    setMessage(null);

    try {
      await api.addToCart(token, selectedVariant.id, quantity);
      setMessage("Sepete eklendi · 15 dk rezerve");
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Hata oluştu");
    } finally {
      setLoading(false);
    }
  }

  return (
    <article className="group overflow-hidden rounded-[2rem] border border-line bg-surface shadow-[0_20px_60px_-40px_rgba(28,25,23,0.35)] transition duration-500 hover:-translate-y-1 hover:shadow-[0_30px_80px_-40px_rgba(28,25,23,0.25)]">
      <div className="grid gap-0 lg:grid-cols-[1.1fr_0.9fr]">
        <div className="relative min-h-[320px] bg-[linear-gradient(145deg,#f3eee8,#faf8f5)] p-8 lg:p-10">
          {product.image_url ? (
            <Image
              src={product.image_url}
              alt={product.name}
              fill
              className="object-cover opacity-95 transition duration-700 group-hover:scale-[1.02]"
              sizes="(max-width: 1024px) 100vw, 50vw"
            />
          ) : (
            <div className="flex h-full min-h-[260px] items-center justify-center">
              <span className="font-display text-5xl text-stone-300">
                {product.name.slice(0, 1)}
              </span>
            </div>
          )}
          {product.category && (
            <span className="absolute left-8 top-8 rounded-full border border-line bg-surface/90 px-4 py-1.5 text-xs uppercase tracking-[0.25em] text-muted backdrop-blur">
              {product.category.name}
            </span>
          )}
        </div>

        <div className="flex flex-col justify-between p-8 lg:p-10">
          <div>
            <div className="flex items-start justify-between gap-4">
              <div>
                <h3 className="font-display text-3xl font-semibold leading-tight text-foreground">
                  {product.name}
                </h3>
                {product.description && (
                  <p className="mt-4 max-w-md text-sm leading-7 text-muted">
                    {product.description}
                  </p>
                )}
              </div>
              <p className="shrink-0 text-lg font-medium text-accent">
                {formatPrice(product.price)}
              </p>
            </div>

            {flatVariants.length > 0 && (
              <div className="mt-8 space-y-4">
                <p className="text-xs uppercase tracking-[0.28em] text-muted">
                  Seçenekler
                </p>
                <div className="flex flex-wrap gap-2">
                  {flatVariants.map((variant) => {
                    const active = selectedVariant?.id === variant.id;
                    const disabled = variant.available_quantity <= 0;

                    return (
                      <button
                        key={variant.id}
                        type="button"
                        disabled={disabled}
                        onClick={() => setSelectedVariant(variant)}
                        className={`rounded-full border px-4 py-2 text-sm transition ${
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

                {selectedVariant && (
                  <p className="text-sm text-muted">
                    {selectedVariant.available_quantity > 0
                      ? `Stokta ${selectedVariant.available_quantity} adet`
                      : "Stokta yok"}
                  </p>
                )}
              </div>
            )}
          </div>

          <div className="mt-8 flex flex-wrap items-center gap-3 border-t border-line pt-8">
            <input
              type="number"
              min={1}
              max={Math.min(99, selectedVariant?.available_quantity ?? 1)}
              value={quantity}
              onChange={(event) => setQuantity(Number(event.target.value))}
              className="w-20 rounded-full border border-line bg-background px-4 py-3 text-center text-sm outline-none focus:border-accent"
            />
            <Button
              onClick={handleAddToCart}
              disabled={
                loading ||
                !selectedVariant ||
                selectedVariant.available_quantity <= 0
              }
              className="flex-1 sm:flex-none"
            >
              {loading ? "Ekleniyor..." : "Sepete Ekle"}
            </Button>
            <ButtonLink href={`/products/${product.id}`} variant="secondary">
              Detay
            </ButtonLink>
          </div>

          {message && (
            <p className="mt-4 text-sm text-accent">{message}</p>
          )}
        </div>
      </div>
    </article>
  );
}
