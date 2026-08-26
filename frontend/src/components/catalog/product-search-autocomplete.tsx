"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  KeyboardEvent,
  useCallback,
  useEffect,
  useId,
  useRef,
  useState,
} from "react";

import { ProductImage } from "@/components/catalog/product-image";
import { api, formatPrice } from "@/lib/api";
import { resolveImageSrc } from "@/lib/media";
import type { PopularSearch, ProductSearchSuggestion } from "@/lib/types";

type ProductSearchAutocompleteProps = {
  value: string;
  onChange: (value: string) => void;
  onSubmit: (value: string) => void;
  inputId?: string;
  placeholder?: string;
  className?: string;
  inputClassName?: string;
  showSubmitButton?: boolean;
  submitLabel?: string;
};

export function ProductSearchAutocomplete({
  value,
  onChange,
  onSubmit,
  inputId,
  placeholder = "Ürün ara...",
  className = "",
  inputClassName = "",
  showSubmitButton = true,
  submitLabel = "Ara",
}: ProductSearchAutocompleteProps) {
  const generatedId = useId();
  const router = useRouter();
  const resolvedInputId = inputId ?? generatedId;
  const listboxId = `${resolvedInputId}-listbox`;
  const containerRef = useRef<HTMLDivElement>(null);
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [suggestions, setSuggestions] = useState<ProductSearchSuggestion[]>([]);
  const [popular, setPopular] = useState<PopularSearch[]>([]);
  const [activeIndex, setActiveIndex] = useState(-1);

  const trimmed = value.trim();
  const hasQuery = trimmed.length >= 2;
  const showSuggestions = open && hasQuery && suggestions.length > 0;
  const showPopular = open && !hasQuery && popular.length > 0;

  useEffect(() => {
    if (!open || hasQuery) {
      return;
    }

    let cancelled = false;

    api
      .productSearchPopular()
      .then((response) => {
        if (!cancelled) {
          setPopular(response.popular);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setPopular([]);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [open, hasQuery]);

  useEffect(() => {
    if (!open || !hasQuery) {
      setSuggestions([]);
      setLoading(false);
      return;
    }

    let cancelled = false;
    const timeout = window.setTimeout(() => {
      setLoading(true);

      api
        .productSearchSuggest(trimmed)
        .then((response) => {
          if (!cancelled) {
            setSuggestions(response.suggestions);
            setActiveIndex(-1);
          }
        })
        .catch(() => {
          if (!cancelled) {
            setSuggestions([]);
          }
        })
        .finally(() => {
          if (!cancelled) {
            setLoading(false);
          }
        });
    }, 250);

    return () => {
      cancelled = true;
      window.clearTimeout(timeout);
    };
  }, [open, hasQuery, trimmed]);

  useEffect(() => {
    function handlePointerDown(event: MouseEvent) {
      if (!containerRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    }

    document.addEventListener("mousedown", handlePointerDown);

    return () => {
      document.removeEventListener("mousedown", handlePointerDown);
    };
  }, []);

  const selectTerm = useCallback(
    (term: string) => {
      onChange(term);
      onSubmit(term);
      setOpen(false);
    },
    [onChange, onSubmit],
  );

  const selectProduct = useCallback(
    (product: ProductSearchSuggestion) => {
      setOpen(false);
      router.push(`/products/${product.id}`);
    },
    [router],
  );

  const selectableItems = showSuggestions
    ? suggestions.map((item) => ({ type: "product" as const, item }))
    : showPopular
      ? popular.map((item) => ({ type: "popular" as const, item }))
      : [];

  function submitSearch() {
    selectTerm(trimmed);
  }

  function handleKeyDown(event: KeyboardEvent<HTMLInputElement>) {
    if (event.key === "Enter" && activeIndex < 0) {
      event.preventDefault();
      submitSearch();
      return;
    }

    if (!showSuggestions && !showPopular) {
      if (event.key === "Escape") {
        setOpen(false);
      }

      return;
    }

    if (event.key === "ArrowDown") {
      event.preventDefault();
      setActiveIndex((current) => Math.min(current + 1, selectableItems.length - 1));
      return;
    }

    if (event.key === "ArrowUp") {
      event.preventDefault();
      setActiveIndex((current) => Math.max(current - 1, -1));
      return;
    }

    if (event.key === "Enter" && activeIndex >= 0) {
      event.preventDefault();
      const selected = selectableItems[activeIndex];

      if (selected.type === "product") {
        selectProduct(selected.item);
      } else {
        selectTerm(selected.item.term);
      }

      return;
    }

    if (event.key === "Escape") {
      setOpen(false);
    }
  }

  return (
    <div ref={containerRef} className={`relative ${className}`}>
      <div
        role="search"
        aria-label="Ürün ara"
        className="flex w-full items-center gap-2"
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
          id={resolvedInputId}
          type="search"
          value={value}
          onChange={(event) => {
            onChange(event.target.value);
            setOpen(true);
          }}
          onFocus={() => setOpen(true)}
          onKeyDown={handleKeyDown}
          placeholder={placeholder}
          aria-label="Ürün ara"
          aria-expanded={open && (showSuggestions || showPopular)}
          aria-controls={showSuggestions || showPopular ? listboxId : undefined}
          aria-autocomplete="list"
          autoComplete="off"
          className={`min-w-0 flex-1 bg-transparent py-2 text-sm outline-none placeholder:text-muted/80 ${inputClassName}`}
        />

        {showSubmitButton && (
          <button
            type="button"
            onClick={submitSearch}
            className="shrink-0 rounded-full bg-accent px-4 py-2 text-xs font-medium tracking-wide text-white transition hover:bg-stone-800"
          >
            {submitLabel}
          </button>
        )}
      </div>

      {(showSuggestions || showPopular || (open && hasQuery && loading)) && (
        <div
          id={listboxId}
          role="listbox"
          className="absolute left-0 right-0 top-[calc(100%+0.5rem)] z-50 overflow-hidden rounded-2xl border border-line bg-surface shadow-[0_20px_50px_-24px_rgba(28,25,23,0.35)]"
        >
          {open && hasQuery && loading && (
            <p className="px-4 py-3 text-sm text-muted">Aranıyor...</p>
          )}

          {showPopular && (
            <div className="p-2">
              <p className="px-3 py-2 text-[11px] font-medium uppercase tracking-[0.2em] text-muted">
                Popüler aramalar
              </p>
              {popular.map((item, index) => (
                <button
                  key={item.term}
                  type="button"
                  role="option"
                  aria-selected={activeIndex === index}
                  onMouseDown={(event) => event.preventDefault()}
                  onClick={() => selectTerm(item.term)}
                  className={`flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm transition hover:bg-accent-soft/70 ${
                    activeIndex === index ? "bg-accent-soft/70" : ""
                  }`}
                >
                  <span>{item.term}</span>
                  <span className="text-xs text-muted">{item.count}</span>
                </button>
              ))}
            </div>
          )}

          {showSuggestions && (
            <div className="p-2">
              <p className="px-3 py-2 text-[11px] font-medium uppercase tracking-[0.2em] text-muted">
                Ürün önerileri
              </p>
              {suggestions.map((product, index) => {
                const imageSrc = resolveImageSrc(product.image_url);

                return (
                <button
                  key={product.id}
                  type="button"
                  role="option"
                  aria-selected={activeIndex === index}
                  onMouseDown={(event) => event.preventDefault()}
                  onClick={() => selectProduct(product)}
                  className={`flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-accent-soft/70 ${
                    activeIndex === index ? "bg-accent-soft/70" : ""
                  }`}
                >
                  <div className="relative h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-stone-100">
                    {imageSrc ? (
                      <ProductImage
                        src={imageSrc}
                        alt=""
                        sizes="40px"
                        className="object-cover"
                      />
                    ) : (
                      <div className="flex h-full w-full items-center justify-center text-[10px] text-muted">
                        G
                      </div>
                    )}
                  </div>
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium">{product.name}</p>
                    {product.category?.name && (
                      <p className="truncate text-xs text-muted">{product.category.name}</p>
                    )}
                  </div>
                  <span className="shrink-0 text-sm text-accent">{formatPrice(product.price)}</span>
                </button>
                );
              })}
              <Link
                href={`/products?search=${encodeURIComponent(trimmed)}`}
                onClick={() => setOpen(false)}
                className="mt-1 block rounded-xl px-3 py-2.5 text-center text-sm text-accent transition hover:bg-accent-soft/70"
              >
                &quot;{trimmed}&quot; için tüm sonuçları gör
              </Link>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
