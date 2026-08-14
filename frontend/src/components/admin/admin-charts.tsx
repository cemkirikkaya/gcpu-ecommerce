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

function RevenueLineChart({
  title,
  subtitle,
  points,
}: {
  title: string;
  subtitle?: string;
  points: Array<{ label: string; revenue: number; orders: number }>;
}) {
  const width = 640;
  const height = 220;
  const padding = { top: 16, right: 12, bottom: 28, left: 12 };
  const chartWidth = width - padding.left - padding.right;
  const chartHeight = height - padding.top - padding.bottom;
  const peak = maxValue(points.map((point) => point.revenue));
  const periodRevenue = points.reduce((sum, point) => sum + point.revenue, 0);
  const periodOrders = points.reduce((sum, point) => sum + point.orders, 0);

  if (points.length === 0) {
    return (
      <div className="rounded-[1.5rem] border border-line bg-surface p-6">
        <p className="text-sm text-muted">{title}</p>
        <p className="mt-6 text-sm text-muted">Henüz veri yok.</p>
      </div>
    );
  }

  const coordinates = points.map((point, index) => {
    const x =
      points.length === 1
        ? padding.left + chartWidth / 2
        : padding.left + (index / (points.length - 1)) * chartWidth;
    const y = padding.top + chartHeight - (point.revenue / peak) * chartHeight;

    return { ...point, x, y };
  });

  const linePath = coordinates
    .map((point, index) => `${index === 0 ? "M" : "L"} ${point.x} ${point.y}`)
    .join(" ");

  const areaPath = `${linePath} L ${coordinates.at(-1)?.x ?? padding.left} ${
    padding.top + chartHeight
  } L ${coordinates[0]?.x ?? padding.left} ${padding.top + chartHeight} Z`;

  return (
    <div className="rounded-[1.5rem] border border-line bg-surface p-6">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-sm text-muted">{title}</p>
          {subtitle && <p className="mt-1 text-xs text-muted">{subtitle}</p>}
        </div>
        <div className="text-right">
          <p className="text-2xl font-semibold">{formatPrice(periodRevenue)}</p>
          <p className="mt-1 text-xs text-muted">{periodOrders} sipariş</p>
        </div>
      </div>

      <div className="mt-6 overflow-x-auto">
        <svg
          viewBox={`0 0 ${width} ${height}`}
          className="min-w-full"
          role="img"
          aria-label={`${title} grafiği`}
        >
          <defs>
            <linearGradient id="revenue-area" x1="0" x2="0" y1="0" y2="1">
              <stop offset="0%" stopColor="#c9a96e" stopOpacity="0.35" />
              <stop offset="100%" stopColor="#c9a96e" stopOpacity="0.02" />
            </linearGradient>
          </defs>

          {[0, 0.25, 0.5, 0.75, 1].map((ratio) => {
            const y = padding.top + chartHeight * (1 - ratio);

            return (
              <line
                key={ratio}
                x1={padding.left}
                x2={width - padding.right}
                y1={y}
                y2={y}
                stroke="currentColor"
                className="text-line/60"
                strokeDasharray="4 6"
              />
            );
          })}

          <path d={areaPath} fill="url(#revenue-area)" />
          <path
            d={linePath}
            fill="none"
            stroke="#c9a96e"
            strokeWidth="2.5"
            strokeLinecap="round"
            strokeLinejoin="round"
          />

          {coordinates.map((point) => (
            <g key={point.label}>
              <circle cx={point.x} cy={point.y} r="4.5" fill="#c9a96e" />
              <circle cx={point.x} cy={point.y} r="8" fill="#c9a96e" fillOpacity="0.15" />
              <title>
                {point.label}: {formatPrice(point.revenue)} ({point.orders} sipariş)
              </title>
            </g>
          ))}

          {coordinates.map((point, index) => {
            if (points.length > 10 && index % 2 !== 0 && index !== points.length - 1) {
              return null;
            }

            return (
              <text
                key={`${point.label}-label`}
                x={point.x}
                y={height - 6}
                textAnchor="middle"
                className="fill-muted text-[10px]"
              >
                {point.label}
              </text>
            );
          })}
        </svg>
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

  const revenuePoints = charts.revenue_trend.map((point) => ({
    label: point.label,
    revenue: point.revenue,
    orders: point.orders,
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
        <RevenueLineChart
          title="Son 14 gün ciro"
          subtitle="Ödenmiş siparişler"
          points={revenuePoints}
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
