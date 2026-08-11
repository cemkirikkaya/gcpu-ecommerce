type ProductRatingStarsProps = {
  rating: number;
  size?: "sm" | "md";
  showValue?: boolean;
  reviewCount?: number;
};

export function ProductRatingStars({
  rating,
  size = "md",
  showValue = false,
  reviewCount,
}: ProductRatingStarsProps) {
  const starSize = size === "sm" ? "h-3.5 w-3.5" : "h-4 w-4";
  const rounded = Math.max(0, Math.min(5, rating));

  return (
    <div className="flex flex-wrap items-center gap-2">
      <div className="flex items-center gap-0.5" aria-label={`${rounded} / 5 puan`}>
        {Array.from({ length: 5 }).map((_, index) => {
          const filled = index + 1 <= Math.round(rounded);

          return (
            <svg
              key={index}
              viewBox="0 0 20 20"
              className={`${starSize} ${filled ? "fill-accent text-accent" : "fill-none stroke-line text-line"}`}
              strokeWidth={filled ? 0 : 1.5}
              aria-hidden="true"
            >
              <path d="M10 1.5l2.47 5.01 5.53.8-4 3.9.94 5.5L10 14.77l-4.94 2.94.94-5.5-4-3.9 5.53-.8L10 1.5z" />
            </svg>
          );
        })}
      </div>
      {showValue && (
        <span className={`text-muted ${size === "sm" ? "text-xs" : "text-sm"}`}>
          {rounded.toFixed(1)}
          {reviewCount !== undefined && reviewCount > 0 && (
            <span className="text-muted/80"> ({reviewCount})</span>
          )}
        </span>
      )}
    </div>
  );
}
