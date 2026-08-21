"use client";

import { useEffect, useRef, useState } from "react";

type RevealSectionProps = {
  children: React.ReactNode;
  className?: string;
  delayMs?: number;
};

export function RevealSection({
  children,
  className = "",
  delayMs = 0,
}: RevealSectionProps) {
  const ref = useRef<HTMLDivElement>(null);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const node = ref.current;
    if (!node) {
      return;
    }

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry?.isIntersecting) {
          setVisible(true);
          observer.disconnect();
        }
      },
      { threshold: 0.08, rootMargin: "0px 0px -60px 0px" },
    );

    observer.observe(node);

    return () => observer.disconnect();
  }, []);

  return (
    <div
      ref={ref}
      className={`transition-all duration-1000 ease-out ${
        visible
          ? "translate-y-0 scale-100 opacity-100 blur-0"
          : "translate-y-14 scale-[0.98] opacity-0 blur-sm"
      } ${className}`}
      style={{ transitionDelay: visible ? `${delayMs}ms` : undefined }}
    >
      {children}
    </div>
  );
}
