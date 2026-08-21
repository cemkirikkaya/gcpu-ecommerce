"use client";

import { useEffect, useRef, useState } from "react";

import { AnimatedMeshBackground } from "@/components/home/animated-mesh-background";
import { HeroActions } from "@/components/home/hero-actions";
import { HeroProductFilmstrip } from "@/components/home/hero-product-filmstrip";
import { HeroVideoBackdrop } from "@/components/home/hero-video-backdrop";
import type { Product } from "@/lib/types";

type CinematicHeroProps = {
  shopName: string;
  reservationMinutes: number;
  spotlightProducts: Product[];
};

export function CinematicHero({
  shopName,
  reservationMinutes,
  spotlightProducts,
}: CinematicHeroProps) {
  const sectionRef = useRef<HTMLElement>(null);
  const [scrollProgress, setScrollProgress] = useState(0);

  useEffect(() => {
    const section = sectionRef.current;
    if (!section) {
      return;
    }

    const onScroll = () => {
      const rect = section.getBoundingClientRect();
      const progress = Math.min(Math.max(-rect.top / rect.height, 0), 1);
      setScrollProgress(progress);
    };

    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });

    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  const contentOpacity = 1 - scrollProgress * 1.4;
  const contentTranslate = scrollProgress * -48;

  return (
    <section
      ref={sectionRef}
      className="relative flex min-h-[100svh] flex-col overflow-hidden bg-luxury-dark"
    >
      <div
        className="pointer-events-none absolute inset-0 scale-105 transition-transform duration-100 ease-out will-change-transform"
        style={{ transform: `scale(${1.05 + scrollProgress * 0.08}) translateY(${scrollProgress * 60}px)` }}
      >
        <AnimatedMeshBackground variant="dark" />
        <HeroVideoBackdrop cinematic />
      </div>

      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_50%_20%,transparent_0%,rgba(18,16,14,0.55)_100%)]" />
      <div className="pointer-events-none absolute inset-0 bg-gradient-to-b from-black/30 via-black/10 to-background" />

      <div
        className="relative z-10 mx-auto flex w-full max-w-7xl flex-1 flex-col justify-center px-6 pt-28 lg:px-10"
        style={{
          opacity: Math.max(contentOpacity, 0),
          transform: `translateY(${contentTranslate}px)`,
        }}
      >
        <div className="max-w-4xl">
          <p className="animate-blur-in text-[11px] uppercase tracking-[0.55em] text-gold-soft">
            {shopName}
          </p>
          <h1 className="animate-blur-in mt-8 font-display text-[clamp(3rem,8vw,6.5rem)] font-light leading-[0.92] tracking-[-0.03em] text-white [animation-delay:120ms]">
            Modern lüks,
            <span className="mt-2 block bg-gradient-to-r from-gold-soft via-white to-gold bg-clip-text font-semibold italic text-transparent">
              hareket halinde.
            </span>
          </h1>
          <p className="animate-blur-in mt-8 max-w-xl text-base leading-8 text-white/65 [animation-delay:220ms] lg:text-lg">
            Sinematik vitrin, akıcı geçişler ve {reservationMinutes} dakikalık özel
            rezervasyon ile seçkin parçaları keşfedin.
          </p>
          <div className="animate-blur-in [animation-delay:320ms]">
            <HeroActions />
          </div>

          <a
            href="#discover"
            className="mt-12 inline-flex flex-col items-start gap-2 text-white/55 transition hover:text-gold"
            aria-label="Aşağı kaydır"
          >
            <span className="text-[10px] uppercase tracking-[0.4em]">Keşfet</span>
            <span className="animate-scroll-hint flex h-10 w-6 items-start justify-center rounded-full border border-white/30 p-1.5">
              <span className="h-2 w-1 rounded-full bg-gold animate-scroll-dot" />
            </span>
          </a>
        </div>
      </div>

      <div className="relative z-10 mt-8 shrink-0 lg:mt-10">
        <div className="pointer-events-none absolute inset-x-0 -top-16 h-16 bg-gradient-to-b from-transparent to-luxury-dark/80" />
        <HeroProductFilmstrip products={spotlightProducts} />
      </div>
    </section>
  );
}
