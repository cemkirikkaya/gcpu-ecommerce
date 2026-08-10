"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";

import { useAuth } from "@/context/auth-context";
import { api } from "@/lib/api";

type WishlistContextValue = {
  productIds: number[];
  loading: boolean;
  isFavorite: (productId: number) => boolean;
  toggleFavorite: (productId: number) => Promise<void>;
  refreshWishlist: () => Promise<void>;
};

const WishlistContext = createContext<WishlistContextValue | null>(null);

export function WishlistProvider({ children }: { children: React.ReactNode }) {
  const { token, user } = useAuth();
  const [productIds, setProductIds] = useState<number[]>([]);
  const [loading, setLoading] = useState(false);

  const refreshWishlist = useCallback(async () => {
    if (!token || user?.role !== "customer") {
      setProductIds([]);
      return;
    }

    setLoading(true);

    try {
      const ids = await api.wishlistIds(token);
      setProductIds(ids);
    } catch {
      setProductIds([]);
    } finally {
      setLoading(false);
    }
  }, [token, user?.role]);

  useEffect(() => {
    void refreshWishlist();
  }, [refreshWishlist]);

  const isFavorite = useCallback(
    (productId: number) => productIds.includes(productId),
    [productIds],
  );

  const toggleFavorite = useCallback(
    async (productId: number) => {
      if (!token) {
        window.location.href = "/login";
        return;
      }

      const currentlyFavorite = productIds.includes(productId);

      setProductIds((current) =>
        currentlyFavorite
          ? current.filter((id) => id !== productId)
          : [...current, productId],
      );

      try {
        if (currentlyFavorite) {
          await api.removeFromWishlist(token, productId);
        } else {
          await api.addToWishlist(token, productId);
        }
      } catch {
        setProductIds((current) =>
          currentlyFavorite
            ? [...current, productId]
            : current.filter((id) => id !== productId),
        );
        throw new Error("Favori güncellenemedi.");
      }
    },
    [token, productIds],
  );

  const value = useMemo(
    () => ({
      productIds,
      loading,
      isFavorite,
      toggleFavorite,
      refreshWishlist,
    }),
    [productIds, loading, isFavorite, toggleFavorite, refreshWishlist],
  );

  return (
    <WishlistContext.Provider value={value}>{children}</WishlistContext.Provider>
  );
}

export function useWishlist(): WishlistContextValue {
  const context = useContext(WishlistContext);
  if (!context) {
    throw new Error("useWishlist must be used within WishlistProvider");
  }
  return context;
}
