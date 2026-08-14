import { formatPrice } from "@/lib/api";
import type { AdminCharts } from "@/lib/types";

type ChartBar = {
  label: string;
  value: number;
  hint?: string;
};

function maxValue(values: number[]): number {
  return Math.max(...values, 1);
}

function BarChart({
  title,
  subtitle,
  bars,
  valueFormatter = (value) => String(value),
}: {
  title: string;
  subtitle?: string;
  bars: ChartBar[];
  valueFormatter?: (value: number) => string;
}) {
  const peak = maxValue(bars.map((bar) => bar.value));

  if (bars.length === 0) {
    return (
      <div className="rounded-[1.5rem] border border-line bg-surface p-6">
        <p className="text-sm text-muted">{title}</p>
        <p className="mt-6 text-sm text-muted">Henüz veri yok.</p>
      </div>
    );
  }

  return (
    <div className="rounded-[1.5rem] border border-line bg-surface p-6">
      <div className="flex items-end justify-between gap-4">
        <div>
          <p className="text-sm text-muted">{title}</p>
          {subtitle && <p className="mt-1 text-xs text-muted">{subtitle}</p>}
        </div>
      </div>

      <div className="mt-8 flex h-56 items-end gap-2 sm:gap-3">
        {bars.map((bar) => {
          const height = Math.max(8, Math.round((bar.value / peak) * 100));

          return (
            <div key={bar.label} className="group flex min-w-0 flex-1 flex-col items-center gap-2">
              <span className="text-[10px] font-medium text-accent opacity-0 transition group-hover:opacity-100">
                {valueFormatter(bar.value)}
              </span>
              <div className="flex h-full w-full items-end">
                <div
                  title={bar.hint ?? `${bar.label}: ${valueFormatter(bar.value)}`}
                  className="w-full rounded-t-2xl bg-[linear-gradient(180deg,#c9a96e,#8f7348)] transition hover:opacity-90"
                  style={{ height: `${height}%` }}
                />
              </div>
              <span className="truncate text-[10px] text-muted">{bar.label}</span>
            </div>
          );
        })}
      </div>
    </div>
  );
}

function HorizontalBarChart({
  title,
  bars,
  valueFormatter = (value) => String(value),
}: {
  title: string;
  bars: ChartBar[];
  valueFormatter?: (value: number) => string;
}) {
  const peak = maxValue(bars.map((bar) => bar.value));

  if (bars.length === 0) {
    return (
      <div className="rounded-[1.5rem] border border-line bg-surface p-6">
        <p className="text-sm text-muted">{title}</p>
        <p className="mt-6 text-sm text-muted">Henüz veri yok.</p>
      </div>
    );
  }

  return (
    <div className="rounded-[1.5rem] border border-line bg-surface p-6">
      <p className="text-sm text-muted">{title}</p>
      <div className="mt-6 space-y-4">
        {bars.map((bar) => {
          const width = Math.max(6, Math.round((bar.value / peak) * 100));

          return (
            <div key={bar.label}>
              <div className="mb-2 flex items-center justify-between gap-3 text-sm">
                <span className="truncate">{bar.label}</span>
                <span className="shrink-0 text-muted">{valueFormatter(bar.value)}</span>
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

type AdminChartsSectionProps = {
  charts: AdminCharts | null;
  loading: boolean;
};

export function AdminChartsSection({ charts, loading }: AdminChartsSectionProps) {
  if (loading) {
    return (
      <div className="mt-10 grid gap-4 xl:grid-cols-2">
        {[0, 1, 2].map((item) => (
          <div
            key={item}
            className="h-72 animate-pulse rounded-[1.5rem] border border-line bg-surface"
          />
        ))}
      </div>
    );
  }

  if (!charts) {
    return null;
  }

  const revenueBars: ChartBar[] = charts.revenue_trend.map((point) => ({
    label: point.label,
    value: point.revenue,
    hint: `${point.orders} sipariş`,
  }));

  const statusBars: ChartBar[] = charts.orders_by_status.map((item) => ({
    label: item.label,
    value: item.count,
  }));

  const productBars: ChartBar[] = charts.top_products.map((product) => ({
    label: product.name,
    value: product.revenue,
    hint: `${product.quantity} adet`,
  }));

  return (
    <div className="mt-10 grid gap-4 xl:grid-cols-2">
      <div className="xl:col-span-2">
        <BarChart
          title="Son 14 gün ciro"
          subtitle="Ödenmiş siparişler"
          bars={revenueBars}
          valueFormatter={(value) => formatPrice(value)}
        />
      </div>
      <HorizontalBarChart
        title="Sipariş durumları"
        bars={statusBars}
        valueFormatter={(value) => `${value} sipariş`}
      />
      <HorizontalBarChart
        title="En çok satan ürünler"
        bars={productBars}
        valueFormatter={(value) => formatPrice(value)}
      />
    </div>
  );
}
