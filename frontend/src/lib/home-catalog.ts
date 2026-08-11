import type { Catalog, CatalogCategoryOption, Category, Product } from "@/lib/types";

export function collectCatalogProducts(catalog: Catalog): Product[] {
  const fromCategories = catalog.categories.flatMap((category) =>
    collectCategoryProducts(category),
  );

  return [...fromCategories, ...catalog.uncategorized];
}

function collectCategoryProducts(category: Category): Product[] {
  const childProducts = (category.children ?? []).flatMap((child) =>
    collectCategoryProducts(child),
  );

  return [...category.products, ...childProducts];
}

export function groupProductsByCategorySlug(
  products: Product[],
): Record<string, Product[]> {
  return products.reduce<Record<string, Product[]>>((groups, product) => {
    const slug = product.category?.slug;
    if (!slug) {
      return groups;
    }

    groups[slug] = [...(groups[slug] ?? []), product];
    return groups;
  }, {});
}

export function buildCategoryOptions(catalog: Catalog): CatalogCategoryOption[] {
  const options = new Map<number, CatalogCategoryOption>();

  function walkCategory(category: Category): void {
    if (category.products.length > 0) {
      options.set(category.id, {
        id: category.id,
        name: category.name,
        slug: category.slug,
      });
    }

    for (const child of category.children ?? []) {
      walkCategory(child);
    }
  }

  for (const category of catalog.categories) {
    walkCategory(category);
  }

  return Array.from(options.values());
}

export function pickSpotlightProducts(products: Product[], limit = 4): Product[] {
  if (products.length === 0) {
    return [];
  }

  const withImages = products.filter((product) => product.image_url);

  if (withImages.length > 0) {
    return withImages.slice(0, limit);
  }

  return products.slice(0, limit);
}
