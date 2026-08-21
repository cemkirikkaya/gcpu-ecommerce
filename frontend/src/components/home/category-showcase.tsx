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
    <section className="mx-auto max-w-7xl px-6 py-24 lg:px-10 lg:py-32">
      <div className="flex flex-wrap items-end justify-between gap-8">
        <div className="max-w-xl">
          <div className="flex items-center gap-4">
            <span className="h-px w-10 bg-gold/60" aria-hidden="true" />
            <p className="text-[10px] uppercase tracking-[0.45em] text-muted">Koleksiyonlar</p>
          </div>
          <h2 className="mt-6 font-display text-5xl font-light leading-[1.05] text-foreground sm:text-6xl">
            Kategorilere
            <span className="block font-semibold italic text-accent"> göz atın</span>
          </h2>
          <p className="mt-6 text-base leading-8 text-muted">
            Her vitrin özenle küratörlendi. İlgilendiğiniz dünyayla başlayın.
          </p>
        </div>
        <Link
          href="/products"
          className="group inline-flex items-center gap-3 border-b border-gold/40 pb-1 text-sm uppercase tracking-[0.25em] text-foreground transition hover:border-gold hover:text-gold"
        >
          Tüm koleksiyon
          <span className="transition group-hover:translate-x-1" aria-hidden="true">
            →
          </span>
        </Link>
      </div>

      <div className="mt-16 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
        {visibleCategories.map((category, index) => {
          const preview = productsByCategory[category.slug]?.[0];
          const imageSrc = preview ? resolveImageSrc(preview.image_url) : null;
          const productCount = productsByCategory[category.slug]?.length ?? 0;

          return (
            <Link
              key={category.id}
              href={`/categories/${category.slug}`}
              className={`group relative overflow-hidden rounded-sm border border-line/80 bg-surface shadow-[0_30px_80px_-55px_rgba(18,16,14,0.4)] transition duration-700 hover:-translate-y-1 hover:border-gold/30 hover:shadow-[0_40px_100px_-50px_rgba(18,16,14,0.35)] ${
                index === 0 ? "sm:col-span-2 xl:col-span-1 xl:row-span-1" : ""
              }`}
            >
              <div
                className={`relative overflow-hidden bg-[linear-gradient(145deg,#ece4d8,#faf7f2)] ${
                  index === 0 ? "aspect-[16/10] sm:aspect-[5/4]" : "aspect-[4/5]"
                }`}
              >
                {imageSrc ? (
                  <ProductImage
                    src={imageSrc}
                    alt={category.name}
                    className="object-cover transition duration-[1.2s] ease-out group-hover:scale-[1.06]"
                    sizes="(max-width: 640px) 100vw, 33vw"
                  />
                ) : (
                  <div className="flex h-full items-center justify-center">
                    <span className="font-display text-7xl font-light text-stone-300">
                      {category.name.slice(0, 1)}
                    </span>
                  </div>
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-luxury-dark/80 via-luxury-dark/15 to-transparent transition duration-700 group-hover:from-luxury-dark/90" />
                <div className="absolute inset-3 border border-white/10 transition duration-700 group-hover:inset-4" />
              </div>
              <div className="absolute inset-x-0 bottom-0 p-7 text-white">
                <p className="text-[10px] uppercase tracking-[0.35em] text-gold-soft">
                  Kategori · 0{index + 1}
                </p>
                <h3 className="mt-3 font-display text-4xl font-light leading-tight">
                  {category.name}
                </h3>
                <p className="mt-3 text-sm text-white/70">
                  {productCount > 0 ? `${productCount}+ parça` : "Koleksiyonu keşfet"}
                </p>
              </div>
            </Link>
          );
        })}
      </div>
    </section>
  );
}
