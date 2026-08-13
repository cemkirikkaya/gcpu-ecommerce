"use client";

import { FormEvent, useEffect, useId, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";

export function HeaderSearch() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const inputId = useId();
  const [query, setQuery] = useState("");

  useEffect(() => {
    setQuery(searchParams.get("search") ?? "");
  }, [searchParams]);

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const trimmed = query.trim();

    if (!trimmed) {
      router.push("/products");
      return;
    }

    router.push(`/products?search=${encodeURIComponent(trimmed)}`);
  }

  return (
    <form
      onSubmit={handleSubmit}
      role="search"
      aria-label="Ürün ara"
      className="flex w-full items-center gap-2 rounded-full border border-line bg-surface/95 py-1 pl-4 pr-1.5 shadow-[0_8px_24px_-20px_rgba(28,25,23,0.35)] transition focus-within:border-accent/60 focus-within:shadow-[0_12px_32px_-20px_rgba(28,25,23,0.25)]"
    >
      <svg
        viewBox="0 0 24 24"
        aria-hidden="true"
        className="h-4 w-4 shrink-0 text-muted"
        fill="none"
        stroke="currentColor"
        strokeWidth={2}
      >
        <circle cx="11" cy="11" r="7" />
        <path d="M20 20l-3.5-3.5" strokeLinecap="round" />
      </svg>

      <input
        id={inputId}
        type="search"
        value={query}
        onChange={(event) => setQuery(event.target.value)}
        placeholder="Ürün ara..."
        aria-label="Ürün ara"
        className="min-w-0 flex-1 bg-transparent py-2 text-sm outline-none placeholder:text-muted/80"
      />

      <button
        type="submit"
        className="shrink-0 rounded-full bg-accent px-4 py-2 text-xs font-medium tracking-wide text-white transition hover:bg-stone-800"
      >
        Ara
      </button>
    </form>
  );
}
