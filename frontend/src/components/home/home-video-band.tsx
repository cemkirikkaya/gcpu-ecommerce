"use client";

import { useEffect, useRef, useState } from "react";

import { ButtonLink } from "@/components/ui/button";

type HomeVideoBandProps = {
  shopName: string;
};

export function HomeVideoBand({ shopName }: HomeVideoBandProps) {
  const sectionRef = useRef<HTMLElement>(null);
  const videoRef = useRef<HTMLVideoElement>(null);
  const [visible, setVisible] = useState(false);
  const [parallax, setParallax] = useState(0);

  const source =
    process.env.NEXT_PUBLIC_HOME_VIDEO_URL ??
    process.env.NEXT_PUBLIC_HERO_VIDEO_URL ??
    null;

  useEffect(() => {
    const section = sectionRef.current;
    if (!section) {
      return;
    }

    const observer = new IntersectionObserver(
      ([entry]) => {
        setVisible(entry?.isIntersecting ?? false);
        if (entry?.isIntersecting) {
          void videoRef.current?.play().catch(() => undefined);
        } else {
          videoRef.current?.pause();
        }
      },
      { threshold: 0.25 },
    );

    observer.observe(section);

    const onScroll = () => {
      const rect = section.getBoundingClientRect();
      const centerOffset = rect.top + rect.height / 2 - window.innerHeight / 2;
      setParallax(centerOffset * 0.08);
    };

    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });

    return () => {
      observer.disconnect();
      window.removeEventListener("scroll", onScroll);
    };
  }, []);

  return (
    <section
      ref={sectionRef}
      className="relative h-[min(78vh,820px)] overflow-hidden bg-luxury-dark"
    >
      <div
        className="absolute inset-0 scale-110 transition-transform duration-75 will-change-transform"
        style={{ transform: `translateY(${parallax}px) scale(1.12)` }}
      >
        {source ? (
          <video
            ref={videoRef}
            muted
            loop
            playsInline
            preload="metadata"
            aria-hidden="true"
            className="h-full w-full object-cover opacity-70"
          >
            <source src={source} type="video/mp4" />
          </video>
        ) : (
          <div className="h-full w-full animate-gradient-flow bg-[linear-gradient(120deg,#12100e,#2a2218,#12100e,#1a1510)] bg-[length:300%_300%]" />
        )}
      </div>

      <div className="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(18,16,14,0.65)_100%)]" />
      <div className="absolute inset-0 bg-gradient-to-r from-black/60 via-black/25 to-black/60" />

      <div
        className={`relative z-10 flex h-full flex-col items-center justify-center px-6 text-center transition duration-1000 ${
          visible ? "translate-y-0 opacity-100 blur-0" : "translate-y-10 opacity-0 blur-sm"
        }`}
      >
        <p className="text-[10px] uppercase tracking-[0.55em] text-gold-soft">{shopName} Studio</p>
        <h2 className="mt-6 max-w-3xl font-display text-5xl font-light leading-tight text-white sm:text-6xl lg:text-7xl">
          Her karede
          <span className="block font-semibold italic text-gold"> zarafet ve hareket</span>
        </h2>
        <p className="mt-6 max-w-xl text-base leading-8 text-white/60">
          Koleksiyonumuz canlı vitrinler, yumuşak geçişler ve editoryal bir deneyimle
          sunuluyor.
        </p>
        <div className="mt-10">
          <ButtonLink
            href="/products"
            className="!rounded-sm !bg-white/10 !px-10 !text-white backdrop-blur-md hover:!bg-gold hover:!text-luxury-dark"
          >
            Vitrini Gezin
          </ButtonLink>
        </div>
      </div>
    </section>
  );
}
