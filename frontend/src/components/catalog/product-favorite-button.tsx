"use client";

import { useState } from "react";

import { useWishlist } from "@/context/wishlist-context";

type ProductFavoriteButtonProps = {
  productId: number;
  className?: string;
};

export function ProductFavoriteButton({
  productId,
  className = "",
}: ProductFavoriteButtonProps) {
  const { isFavorite, toggleFavorite } = useWishlist();
  const [loading, setLoading] = useState(false);
  const favorite = isFavorite(productId);

  async function handleClick() {
    setLoading(true);

    try {
      await toggleFavorite(productId);
    } catch {
      // ignore — optimistic state is reverted in context
    } finally {
      setLoading(false);
    }
  }

  return (
    <button
      type="button"
      aria-label={favorite ? "Favorilerden çıkar" : "Favorilere ekle"}
      aria-pressed={favorite}
      disabled={loading}
      onClick={(event) => {
        event.preventDefault();
        event.stopPropagation();
        void handleClick();
      }}
      className={`flex h-10 w-10 items-center justify-center rounded-full border border-line/80 bg-surface/90 text-accent shadow-sm backdrop-blur transition hover:scale-105 hover:border-accent disabled:opacity-60 ${className}`}
    >
      <svg
        viewBox="0 0 24 24"
        aria-hidden="true"
        className={`h-5 w-5 transition ${favorite ? "fill-accent" : "fill-none stroke-current"}`}
        strokeWidth={favorite ? 0 : 1.75}
      >
        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
      </svg>
    </button>
  );
}
