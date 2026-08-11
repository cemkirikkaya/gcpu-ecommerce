import Link from "next/link";

import { ProductImage } from "@/components/catalog/product-image";
import { resolveImageSrc } from "@/lib/media";
import type { CatalogCategoryOption, Product } from "@/lib/types";

type CategoryShowcaseProps = {
  categories: CatalogCategoryOption[];
  productsByCategory: Record<string, Product[]>;
};

export function CategoryShowcase({
  categories,
  productsByCategory,
}: CategoryShowcaseProps) {
  if (categories.length === 0) {
    return null;
  }

  const visibleCategories = categories.slice(0, 6);

  return (
    <section className="mx-auto max-w-7xl px-6 py-20 lg:px-10 lg:py-24">
      <div className="flex flex-wrap items-end justify-between gap-6">
        <div className="max-w-2xl">
          <p className="text-xs uppercase tracking-[0.35em] text-muted">Keşfet</p>
          <h2 className="mt-3 font-display text-4xl font-semibold text-foreground sm:text-5xl">
            Kategorilere göz atın
          </h2>
          <p className="mt-4 text-base leading-7 text-muted">
            İlgilendiğiniz vitrinle başlayın; filtreler koleksiyon sayfasında sizi
            bekliyor.
          </p>
        </div>
        <Link
          href="/products"
          className="rounded-full border border-line bg-surface px-5 py-2.5 text-sm text-foreground transition hover:border-accent hover:text-accent"
        >
          Tüm koleksiyon
        </Link>
      </div>

      <div className="mt-12 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        {visibleCategories.map((category) => {
          const preview = productsByCategory[category.slug]?.[0];
          const imageSrc = preview ? resolveImageSrc(preview.image_url) : null;
          const productCount = productsByCategory[category.slug]?.length ?? 0;

          return (
            <Link
              key={category.id}
              href={`/products?category=${category.slug}`}
              className="group relative overflow-hidden rounded-[1.75rem] border border-line bg-surface shadow-[0_20px_60px_-45px_rgba(28,25,23,0.35)] transition duration-500 hover:-translate-y-1"
            >
              <div className="relative aspect-[5/4] overflow-hidden bg-[linear-gradient(145deg,#f3eee8,#faf8f5)]">
                {imageSrc ? (
                  <ProductImage
                    src={imageSrc}
                    alt={category.name}
                    className="object-cover transition duration-700 group-hover:scale-[1.05]"
                    sizes="(max-width: 640px) 100vw, 33vw"
                  />
                ) : (
                  <div className="flex h-full items-center justify-center">
                    <span className="font-display text-6xl text-stone-300">
                      {category.name.slice(0, 1)}
                    </span>
                  </div>
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-stone-950/70 via-stone-950/15 to-transparent" />
              </div>
              <div className="absolute inset-x-0 bottom-0 p-6 text-white">
                <p className="text-[10px] uppercase tracking-[0.25em] text-white/70">
                  Kategori
                </p>
                <h3 className="mt-2 font-display text-3xl leading-tight">{category.name}</h3>
                <p className="mt-2 text-sm text-white/80">
                  {productCount > 0 ? `${productCount}+ ürün` : "Koleksiyonu gör"}
                </p>
              </div>
            </Link>
          );
        })}
      </div>
    </section>
  );
}
