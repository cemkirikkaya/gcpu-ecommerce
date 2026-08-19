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

type StockAlertContextValue = {
  variantIds: number[];
  loading: boolean;
  isSubscribed: (variantId: number) => boolean;
  toggleStockAlert: (variantId: number) => Promise<void>;
  refreshStockAlerts: () => Promise<void>;
};

const StockAlertContext = createContext<StockAlertContextValue | null>(null);

export function StockAlertProvider({ children }: { children: React.ReactNode }) {
  const { token, user } = useAuth();
  const [variantIds, setVariantIds] = useState<number[]>([]);
  const [loading, setLoading] = useState(false);

  const refreshStockAlerts = useCallback(async () => {
    if (!token || user?.role !== "customer") {
      setVariantIds([]);
      return;
    }

    setLoading(true);

    try {
      const ids = await api.stockAlertVariantIds(token);
      setVariantIds(ids);
    } catch {
      setVariantIds([]);
    } finally {
      setLoading(false);
    }
  }, [token, user?.role]);

  useEffect(() => {
    void refreshStockAlerts();
  }, [refreshStockAlerts]);

  const isSubscribed = useCallback(
    (variantId: number) => variantIds.includes(variantId),
    [variantIds],
  );

  const toggleStockAlert = useCallback(
    async (variantId: number) => {
      if (!token) {
        window.location.href = "/login";
        return;
      }

      const currentlySubscribed = variantIds.includes(variantId);

      setVariantIds((current) =>
        currentlySubscribed
          ? current.filter((id) => id !== variantId)
          : [...current, variantId],
      );

      try {
        if (currentlySubscribed) {
          await api.unsubscribeStockAlert(token, variantId);
        } else {
          await api.subscribeStockAlert(token, variantId);
        }
      } catch {
        setVariantIds((current) =>
          currentlySubscribed
            ? [...current, variantId]
            : current.filter((id) => id !== variantId),
        );
        throw new Error("Stok bildirimi güncellenemedi.");
      }
    },
    [token, variantIds],
  );

  const value = useMemo(
    () => ({
      variantIds,
      loading,
      isSubscribed,
      toggleStockAlert,
      refreshStockAlerts,
    }),
    [variantIds, loading, isSubscribed, toggleStockAlert, refreshStockAlerts],
  );

  return (
    <StockAlertContext.Provider value={value}>{children}</StockAlertContext.Provider>
  );
}

export function useStockAlerts(): StockAlertContextValue {
  const context = useContext(StockAlertContext);
  if (!context) {
    throw new Error("useStockAlerts must be used within StockAlertProvider");
  }
  return context;
}
