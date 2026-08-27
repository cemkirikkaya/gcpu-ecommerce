"use client";

import { useEffect, useState } from "react";

import { AdminOnlyGuard } from "@/components/admin/admin-only-guard";
import { SearchAnalyticsPanel } from "@/components/admin/search-analytics-panel";
import { useAuth } from "@/context/auth-context";
import { api } from "@/lib/api";
import type { SearchAnalytics } from "@/lib/types";

function AdminSearchAnalyticsPageContent() {
  const { token } = useAuth();
  const [analytics, setAnalytics] = useState<SearchAnalytics | null>(null);
  const [daysFilter, setDaysFilter] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!token) {
      return;
    }

    setLoading(true);
    setError(null);

    api
      .adminSearchAnalytics(token, {
        limit: 20,
        days: daysFilter ?? undefined,
      })
      .then(setAnalytics)
      .catch((err) =>
        setError(err instanceof Error ? err.message : "Arama analitiği yüklenemedi."),
      )
      .finally(() => setLoading(false));
  }, [token, daysFilter]);

  return (
    <div>
      <p className="text-xs uppercase tracking-[0.35em] text-muted">Analitik</p>
      <h1 className="mt-3 font-display text-4xl font-semibold">Arama Analitiği</h1>

      {error && <p className="mt-6 text-sm text-red-600">{error}</p>}

      <div className="mt-8">
        <SearchAnalyticsPanel
          analytics={analytics}
          loading={loading}
          daysFilter={daysFilter}
          onDaysFilterChange={setDaysFilter}
        />
      </div>
    </div>
  );
}

export default function AdminSearchAnalyticsPage() {
  return (
    <AdminOnlyGuard>
      <AdminSearchAnalyticsPageContent />
    </AdminOnlyGuard>
  );
}
