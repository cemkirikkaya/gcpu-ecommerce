"use client";

import Link from "next/link";
import { useCallback, useEffect, useMemo, useState } from "react";

import { ProductImage } from "@/components/catalog/product-image";
import { CarouselControls } from "@/components/ui/carousel-controls";
import { formatPrice } from "@/lib/api";
import { resolveImageSrc } from "@/lib/media";
import type { Product } from "@/lib/types";

type HeroBannerSliderProps = {
  shopName: string;
  reservationMinutes: number;
  products: Product[];
};

type Slide = {
  id: string;
  eyebrow: string;
  title: string;
  subtitle: string;
  href: string;
  cta: string;
  imageSrc: string | null;
  price?: string;
};

const ROTATE_MS = 6500;

export function HeroBannerSlider({
  shopName,
  reservationMinutes,
  products,
}: HeroBannerSliderProps) {
  const slides = useMemo<Slide[]>(() => {
    const productSlides = products.slice(0, 5).map((product) => ({
      id: `product-${product.id}`,
      eyebrow: product.category?.name ?? "Yeni koleksiyon",
      title: product.name,
      subtitle: "Seçkin parçalar, ferah bir vitrin deneyimi.",
      href: `/products/${product.id}`,
      cta: "Parçayı İncele",
      imageSrc: resolveImageSrc(product.image_url),
      price: formatPrice(product.price),
    }));

    if (productSlides.length > 0) {
      return productSlides;
    }

    return [
      {
        id: "fallback",
        eyebrow: shopName,
        title: "Zamansız lüks koleksiyon",
        subtitle: `${reservationMinutes} dakikalık stok rezervasyonu ile güvenle alışveriş yapın.`,
        href: "/products",
        cta: "Koleksiyonu Keşfet",
        imageSrc: null,
      },
    ];
  }, [products, reservationMinutes, shopName]);

  const [activeIndex, setActiveIndex] = useState(0);
  const activeSlide = slides[activeIndex % slides.length];

  const goTo = useCallback(
    (index: number) => {
      setActiveIndex((index + slides.length) % slides.length);
    },
    [slides.length],
  );

  const goNext = useCallback(() => goTo(activeIndex + 1), [activeIndex, goTo]);
  const goPrev = useCallback(() => goTo(activeIndex - 1), [activeIndex, goTo]);

  useEffect(() => {
    if (slides.length <= 1) {
      return;
    }

    const timer = window.setInterval(goNext, ROTATE_MS);

    return () => window.clearInterval(timer);
  }, [goNext, slides.length]);

  return (
    <section className="relative overflow-hidden bg-[linear-gradient(180deg,#fdfcfa_0%,#f7f3ec_100%)]">
      <div className="pointer-events-none absolute -left-32 top-20 h-96 w-96 rounded-full bg-gold/10 blur-3xl" />
      <div className="pointer-events-none absolute -right-24 bottom-0 h-80 w-80 rounded-full bg-accent-soft/40 blur-3xl" />

      <div className="relative mx-auto max-w-7xl px-6 pb-20 pt-12 lg:px-10 lg:pb-28 lg:pt-16">
        <div className="grid items-center gap-12 lg:grid-cols-[1fr_1.05fr] lg:gap-20">
          <div className="max-w-xl">
            <p className="text-[11px] uppercase tracking-[0.5em] text-muted">{shopName}</p>

            <div className="relative mt-8 min-h-[220px]">
              {slides.map((slide, index) => (
                <div
                  key={slide.id}
                  className={`transition-all duration-700 ease-out ${
                    index === activeIndex
                      ? "relative translate-y-0 opacity-100"
                      : "pointer-events-none absolute inset-0 translate-y-4 opacity-0"
                  }`}
                  aria-hidden={index !== activeIndex}
                >
                  <p className="text-[10px] uppercase tracking-[0.4em] text-gold">
                    {slide.eyebrow}
                  </p>
                  <h1 className="mt-5 font-display text-5xl font-light leading-[1.02] text-foreground sm:text-6xl lg:text-[4.25rem]">
                    {slide.title}
                  </h1>
                  <p className="mt-6 text-base leading-8 text-muted lg:text-lg">
                    {slide.subtitle}
                  </p>
                  {slide.price && (
                    <p className="mt-4 font-display text-3xl text-accent">{slide.price}</p>
                  )}
                  <Link
                    href={slide.href}
                    className="mt-8 inline-flex rounded-full border border-line bg-surface px-8 py-3.5 text-sm uppercase tracking-[0.2em] text-foreground shadow-sm transition hover:border-gold hover:text-gold"
                  >
                    {slide.cta}
                  </Link>
                </div>
              ))}
            </div>

            <div className="mt-10 flex items-center gap-4">
              {slides.length > 1 && <CarouselControls onPrevious={goPrev} onNext={goNext} />}
              <div className="flex gap-2">
                {slides.map((slide, index) => (
                  <button
                    key={slide.id}
                    type="button"
                    aria-label={`Slayt ${index + 1}`}
                    onClick={() => goTo(index)}
                    className={`h-1.5 rounded-full transition-all duration-500 ${
                      index === activeIndex ? "w-8 bg-gold" : "w-2 bg-line hover:bg-gold/50"
                    }`}
                  />
                ))}
              </div>
            </div>
          </div>

          <div className="relative mx-auto w-full max-w-xl lg:max-w-none">
            <div className="relative aspect-[4/5] overflow-hidden rounded-[2rem] border border-line/80 bg-surface shadow-[0_40px_100px_-60px_rgba(28,25,23,0.18)]">
              {slides.map((slide, index) => (
                <Link
                  key={slide.id}
                  href={slide.href}
                  className={`absolute inset-0 transition-all duration-1000 ease-out ${
                    index === activeIndex ? "scale-100 opacity-100" : "scale-105 opacity-0"
                  }`}
                  aria-hidden={index !== activeIndex}
                >
                  {slide.imageSrc ? (
                    <ProductImage
                      src={slide.imageSrc}
                      alt={slide.title}
                      priority={index === 0}
                      className="object-cover"
                      sizes="(max-width: 1024px) 100vw, 540px"
                    />
                  ) : (
                    <div className="flex h-full items-center justify-center bg-[linear-gradient(145deg,#f0ebe3,#faf8f5)]">
                      <span className="font-display text-8xl font-light text-stone-300">G</span>
                    </div>
                  )}
                  <div className="absolute inset-0 bg-gradient-to-t from-white/10 via-transparent to-transparent" />
                </Link>
              ))}
            </div>
            <div className="absolute -bottom-6 -left-6 hidden rounded-2xl border border-line bg-surface/95 px-6 py-4 shadow-lg backdrop-blur sm:block">
              <p className="text-[10px] uppercase tracking-[0.35em] text-muted">Rezervasyon</p>
              <p className="mt-1 font-display text-2xl text-foreground">{reservationMinutes} dk</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
