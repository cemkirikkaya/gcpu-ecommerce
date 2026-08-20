"use client";

import { useEffect, useId, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";

import { ProductSearchAutocomplete } from "@/components/catalog/product-search-autocomplete";

export function HeaderSearch() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const inputId = useId();
  const [query, setQuery] = useState("");

  useEffect(() => {
    setQuery(searchParams.get("search") ?? "");
  }, [searchParams]);

  function handleSubmit(value: string) {
    const trimmed = value.trim();

    if (!trimmed) {
      router.push("/products");
      return;
    }

    router.push(`/products?search=${encodeURIComponent(trimmed)}`);
  }

  return (
    <ProductSearchAutocomplete
      value={query}
      onChange={setQuery}
      onSubmit={handleSubmit}
      inputId={inputId}
      className="rounded-full border border-line bg-surface/95 py-1 pl-4 pr-1.5 shadow-[0_8px_24px_-20px_rgba(28,25,23,0.35)] transition focus-within:border-accent/60 focus-within:shadow-[0_12px_32px_-20px_rgba(28,25,23,0.25)]"
    />
  );
}
