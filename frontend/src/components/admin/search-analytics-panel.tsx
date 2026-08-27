"use client";

import Link from "next/link";

import { formatOrderDate } from "@/lib/api";
import type { SearchAnalytics } from "@/lib/types";

type SearchAnalyticsPanelProps = {
  analytics: SearchAnalytics | null;
  loading: boolean;
  compact?: boolean;
  daysFilter?: number | null;
  onDaysFilterChange?: (days: number | null) => void;
};

function maxCount(values: number[]): number {
  return Math.max(...values, 1);
}

export function SearchAnalyticsPanel({
  analytics,
  loading,
  compact = false,
  daysFilter = null,
  onDaysFilterChange,
}: SearchAnalyticsPanelProps) {
  if (loading) {
    return (
      <div className="rounded-[1.5rem] border border-line bg-surface p-6">
        <p className="text-sm text-muted">Arama verileri yükleniyor...</p>
      </div>
    );
  }

  if (!analytics) {
    return null;
  }

  const peak = maxCount(analytics.top_terms.map((term) => term.count));

  return (
    <div className="rounded-[1.5rem] border border-line bg-surface p-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p className="text-xs uppercase tracking-[0.3em] text-muted">Arama</p>
          <h2 className="mt-2 font-display text-2xl font-semibold">
            {compact ? "En Çok Arananlar" : "Arama Analitiği"}
          </h2>
          {!compact && (
            <p className="mt-2 text-sm text-muted">
              Müşterilerin vitrin aramasında kullandığı terimler.
            </p>
          )}
        </div>

        <div className="flex flex-wrap items-center gap-2">
          {!compact && onDaysFilterChange && (
            <div className="flex flex-wrap gap-2">
              {[
                { label: "Tümü", value: null },
                { label: "7 gün", value: 7 },
                { label: "30 gün", value: 30 },
              ].map((option) => (
                <button
                  key={option.label}
                  type="button"
                  onClick={() => onDaysFilterChange(option.value)}
                  className={`rounded-full px-4 py-2 text-sm transition ${
                    daysFilter === option.value
                      ? "bg-accent text-white"
                      : "border border-line bg-background text-stone-700 hover:border-accent"
                  }`}
                >
                  {option.label}
                </button>
              ))}
            </div>
          )}

          {compact && (
            <Link
              href="/admin/search-analytics"
              className="rounded-full border border-line bg-background px-5 py-2.5 text-sm transition hover:border-accent"
            >
              Tümünü gör
            </Link>
          )}
        </div>
      </div>

      {!compact && (
        <div className="mt-6 grid gap-4 md:grid-cols-3">
          <StatCard label="Toplam arama" value={analytics.summary.total_searches} />
          <StatCard label="Benzersiz terim" value={analytics.summary.unique_terms} />
          <StatCard
            label="Son 7 günde aktif terim"
            value={analytics.summary.active_terms_last_7_days}
          />
        </div>
      )}

      <div className="mt-6 space-y-4">
        {analytics.top_terms.length === 0 && (
          <p className="text-sm text-muted">Henüz arama verisi yok.</p>
        )}

        {analytics.top_terms.map((term, index) => {
          const width = Math.max(6, Math.round((term.count / peak) * 100));

          return (
            <div key={term.term}>
              <div className="mb-2 flex items-center justify-between gap-3 text-sm">
                <div className="min-w-0">
                  <span className="mr-2 text-muted">#{index + 1}</span>
                  <Link
                    href={`/products?search=${encodeURIComponent(term.term)}`}
                    className="font-medium transition hover:text-accent"
                  >
                    {term.term}
                  </Link>
                </div>
                <div className="shrink-0 text-right text-muted">
                  <p>{term.count} arama</p>
                  {!compact && term.last_searched_at && (
                    <p className="text-xs">{formatOrderDate(term.last_searched_at)}</p>
                  )}
                </div>
              </div>
              <div className="h-2.5 overflow-hidden rounded-full bg-background">
                <div
                  className="h-full rounded-full bg-accent transition-all"
                  style={{ width: `${width}%` }}
                />
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}

function StatCard({ label, value }: { label: string; value: number }) {
  return (
    <div className="rounded-[1.25rem] border border-line bg-background p-5">
      <p className="text-sm text-muted">{label}</p>
      <p className="mt-2 text-3xl font-semibold">{value}</p>
    </div>
  );
}
