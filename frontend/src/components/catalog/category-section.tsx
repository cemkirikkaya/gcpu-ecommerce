import { ProductCard } from "@/components/catalog/product-card";
import type { Category } from "@/lib/types";

export function CategorySection({
  category,
  depth = 0,
}: {
  category: Category;
  depth?: number;
}) {
  const padding = depth > 0 ? "ml-4 border-l border-line pl-8" : "";

  return (
    <section className={`space-y-10 ${padding}`}>
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div className="max-w-2xl">
          <p className="text-xs uppercase tracking-[0.35em] text-muted">
            Kategori
          </p>
          <h2 className="mt-2 font-display text-4xl font-semibold text-foreground">
            {category.name}
          </h2>
          {category.description && (
            <p className="mt-3 text-base leading-7 text-muted">
              {category.description}
            </p>
          )}
        </div>
        <span className="rounded-full bg-accent-soft px-4 py-2 text-xs uppercase tracking-[0.2em] text-accent">
          {category.products.length} ürün
        </span>
      </div>

      {category.products.length > 0 && (
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {category.products.map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>
      )}

      {category.children?.map((child) => (
        <CategorySection key={child.id} category={child} depth={depth + 1} />
      ))}
    </section>
  );
}
