"use client";

import Link from "next/link";
import type { HTMLAttributes } from "react";
import { useEffect, useRef } from "react";

export type MarqueeItem = {
  label: string;
  href?: string;
};

type HomeMarqueeProps = {
  items: MarqueeItem[];
};

function MarqueeTrack({
  items,
  trackId,
  ...props
}: {
  items: MarqueeItem[];
  trackId: string;
} & HTMLAttributes<HTMLUListElement>) {
  return (
    <ul
      className="flex shrink-0 list-none items-center gap-10 pr-10"
      {...props}
    >
      {items.map((item, index) => (
        <li
          key={`${trackId}-${item.label}-${index}`}
          className="inline-flex shrink-0 items-center gap-10 whitespace-nowrap"
        >
          {item.href ? (
            <Link
              href={item.href}
              className="font-display text-2xl text-foreground/80 transition hover:text-accent sm:text-3xl"
            >
              {item.label}
            </Link>
          ) : (
            <span className="font-display text-2xl text-muted sm:text-3xl">{item.label}</span>
          )}
          <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-accent/50" aria-hidden="true" />
        </li>
      ))}
    </ul>
  );
}

export function HomeMarquee({ items }: HomeMarqueeProps) {
  const trackRef = useRef<HTMLDivElement>(null);
  const offsetRef = useRef(0);
  const pausedRef = useRef(false);

  const expandedItems = [...items, ...items, ...items];

  useEffect(() => {
    const track = trackRef.current;
    if (!track || items.length === 0) {
      return;
    }

    const motionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
    if (motionQuery.matches) {
      return;
    }

    let frameId = 0;
    let running = true;
    const speed = 0.75;

    const animate = () => {
      if (!running) {
        return;
      }

      const loopWidth = track.scrollWidth / 2;

      if (loopWidth > 0 && !pausedRef.current) {
        offsetRef.current += speed;

        if (offsetRef.current >= loopWidth) {
          offsetRef.current -= loopWidth;
        }

        track.style.transform = `translate3d(-${offsetRef.current}px, 0, 0)`;
      }

      frameId = window.requestAnimationFrame(animate);
    };

    frameId = window.requestAnimationFrame(animate);

    return () => {
      running = false;
      window.cancelAnimationFrame(frameId);
    };
  }, [items]);

  if (items.length === 0) {
    return null;
  }

  return (
    <section
      className="overflow-hidden border-y border-line/70 bg-surface/80 py-5"
      aria-label="Kategori vitrini"
    >
      <div
        ref={trackRef}
        className="flex w-max will-change-transform"
        onMouseEnter={() => {
          pausedRef.current = true;
        }}
        onMouseLeave={() => {
          pausedRef.current = false;
        }}
      >
        <MarqueeTrack items={expandedItems} trackId="a" />
        <MarqueeTrack items={expandedItems} trackId="b" aria-hidden />
      </div>
    </section>
  );
}
