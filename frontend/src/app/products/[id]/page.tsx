"use client";

import Image from "next/image";
import Link from "next/link";
import { useEffect, useState } from "react";

import { Button, ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatPrice } from "@/lib/api";
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

  return (
    <div className="mx-auto max-w-7xl px-6 py-16 lg:px-10 lg:py-24">
      <Link href="/products" className="text-sm text-muted transition hover:text-accent">
        ← Koleksiyona dön
      </Link>

      <div className="mt-10 grid gap-12 lg:grid-cols-2 lg:gap-16">
        <div className="relative min-h-[480px] overflow-hidden rounded-[2.5rem] border border-line bg-[linear-gradient(145deg,#f3eee8,#faf8f5)]">
          {product.image_url ? (
            <Image
              src={product.image_url}
              alt={product.name}
              fill
              className="object-cover"
              sizes="(max-width: 1024px) 100vw, 50vw"
            />
          ) : (
            <div className="flex h-full items-center justify-center font-display text-8xl text-stone-300">
              {product.name.slice(0, 1)}
            </div>
          )}
        </div>

        <div>
          <p className="text-xs uppercase tracking-[0.35em] text-muted">
            {product.category?.name ?? "Ürün"}
          </p>
          <h1 className="mt-4 font-display text-5xl font-semibold leading-tight">
            {product.name}
          </h1>
          <p className="mt-6 text-2xl text-accent">{formatPrice(product.price)}</p>
          {product.description && (
            <p className="mt-6 text-base leading-8 text-muted">{product.description}</p>
          )}

          <div className="mt-10 space-y-8">
            {groups.map((group) => (
              <div key={group.label}>
                <p className="text-xs uppercase tracking-[0.28em] text-muted">
                  {group.label}
                </p>
                <div className="mt-4 flex flex-wrap gap-2">
                  {group.variants.map((variant) => (
                    <button
                      key={variant.id}
                      type="button"
                      disabled={variant.available_quantity <= 0}
                      onClick={() => setSelectedVariant(variant)}
                      className={`rounded-full border px-4 py-2 text-sm ${
                        selectedVariant?.id === variant.id
                          ? "border-accent bg-accent text-white"
                          : "border-line bg-surface"
                      }`}
                    >
                      {variant.label}
                    </button>
                  ))}
                </div>
              </div>
            ))}
          </div>

          <div className="mt-10 flex flex-wrap gap-3 border-t border-line pt-10">
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
          </div>

          {message && <p className="mt-4 text-sm text-accent">{message}</p>}
        </div>
      </div>
    </div>
  );
}
