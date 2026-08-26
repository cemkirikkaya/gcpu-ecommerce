"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";

import { ProductImage } from "@/components/catalog/product-image";
import { CarouselControls } from "@/components/ui/carousel-controls";
import { resolveImageSrc } from "@/lib/media";
import type { ProductGalleryImage } from "@/lib/types";

type ProductImageGalleryProps = {
  images: ProductGalleryImage[];
  fallbackImageUrl?: string | null;
  alt: string;
  className?: string;
  favoriteButton?: React.ReactNode;
};

export function ProductImageGallery({
  images,
  fallbackImageUrl,
  alt,
  className = "",
  favoriteButton,
}: ProductImageGalleryProps) {
  const slides = useMemo(() => {
    const resolved = images
      .map((image) => ({
        id: image.id,
        src: resolveImageSrc(image.url),
      }))
      .filter((image): image is { id: number; src: string } => image.src !== null);

    if (resolved.length > 0) {
      return resolved;
    }

    const fallback = resolveImageSrc(fallbackImageUrl);

    if (fallback) {
      return [{ id: 0, src: fallback }];
    }

    return [];
  }, [fallbackImageUrl, images]);

  const [activeIndex, setActiveIndex] = useState(0);
  const touchStartX = useRef<number | null>(null);

  const goTo = useCallback(
    (index: number) => {
      if (slides.length === 0) {
        return;
      }

      setActiveIndex((index + slides.length) % slides.length);
    },
    [slides.length],
  );

  const goNext = useCallback(() => goTo(activeIndex + 1), [activeIndex, goTo]);
  const goPrev = useCallback(() => goTo(activeIndex - 1), [activeIndex, goTo]);

  useEffect(() => {
    if (activeIndex >= slides.length) {
      setActiveIndex(0);
    }
  }, [activeIndex, slides.length]);

  function handleTouchStart(event: React.TouchEvent<HTMLDivElement>) {
    touchStartX.current = event.touches[0]?.clientX ?? null;
  }

  function handleTouchEnd(event: React.TouchEvent<HTMLDivElement>) {
    if (touchStartX.current === null || slides.length <= 1) {
      return;
    }

    const touchEndX = event.changedTouches[0]?.clientX ?? touchStartX.current;
    const delta = touchEndX - touchStartX.current;

    if (Math.abs(delta) >= 40) {
      if (delta < 0) {
        goNext();
      } else {
        goPrev();
      }
    }

    touchStartX.current = null;
  }

  const activeSlide = slides[activeIndex];

  return (
    <div
      className={`relative min-h-[480px] overflow-hidden rounded-[2.5rem] border border-line bg-[linear-gradient(145deg,#f3eee8,#faf8f5)] ${className}`}
      onTouchStart={handleTouchStart}
      onTouchEnd={handleTouchEnd}
    >
      {favoriteButton}

      {activeSlide ? (
        <>
          <ProductImage
            key={activeSlide.id}
            src={activeSlide.src}
            alt={alt}
            className="object-cover"
            sizes="(max-width: 1024px) 100vw, 50vw"
          />

          {slides.length > 1 && (
            <>
              <div className="absolute inset-x-0 bottom-5 flex justify-center gap-2">
                {slides.map((slide, index) => (
                  <button
                    key={slide.id}
                    type="button"
                    aria-label={`Görsel ${index + 1}`}
                    onClick={() => goTo(index)}
                    className={`h-2.5 rounded-full transition ${
                      index === activeIndex
                        ? "w-8 bg-accent"
                        : "w-2.5 bg-white/70 hover:bg-white"
                    }`}
                  />
                ))}
              </div>

              <CarouselControls
                onPrevious={goPrev}
                onNext={goNext}
                className="absolute bottom-5 right-5"
              />
            </>
          )}
        </>
      ) : (
        <div className="flex h-full min-h-[480px] items-center justify-center font-display text-8xl text-stone-300">
          {alt.slice(0, 1)}
        </div>
      )}
    </div>
  );
}
