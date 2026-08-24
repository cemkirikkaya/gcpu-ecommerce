"use client";

type CarouselControlsProps = {
  onPrevious: () => void;
  onNext: () => void;
  className?: string;
};

export function CarouselControls({
  onPrevious,
  onNext,
  className = "",
}: CarouselControlsProps) {
  return (
    <div className={`flex items-center gap-2 ${className}`}>
      <button
        type="button"
        onClick={onPrevious}
        aria-label="Önceki"
        className="flex h-11 w-11 items-center justify-center rounded-full border border-line bg-surface text-foreground shadow-sm transition hover:border-gold hover:text-gold"
      >
        ←
      </button>
      <button
        type="button"
        onClick={onNext}
        aria-label="Sonraki"
        className="flex h-11 w-11 items-center justify-center rounded-full border border-line bg-surface text-foreground shadow-sm transition hover:border-gold hover:text-gold"
      >
        →
      </button>
    </div>
  );
}
