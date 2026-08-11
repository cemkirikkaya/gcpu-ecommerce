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
      { threshold: 0.12, rootMargin: "0px 0px -40px 0px" },
    );

    observer.observe(node);

    return () => observer.disconnect();
  }, []);

  return (
    <div
      ref={ref}
      className={`transition duration-700 ${
        visible ? "animate-reveal-up opacity-100" : "translate-y-8 opacity-0"
      } ${className}`}
      style={{ animationDelay: visible ? `${delayMs}ms` : undefined }}
    >
      {children}
    </div>
  );
}
