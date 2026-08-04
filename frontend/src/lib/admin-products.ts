import type { AdminCategory, CatalogVariantInput } from "@/lib/types";

export function emptyVariant(): CatalogVariantInput {
  return {
    sku: "",
    stock: 0,
    color: "",
    memory: "",
    model: "",
    size: "",
  };
}

export function categoryOptionLabel(
  category: AdminCategory,
  categories: AdminCategory[],
): string {
  if (!category.parent_id) {
    return category.name;
  }

  const parent = categories.find((item) => item.id === category.parent_id);

  return parent ? `${parent.name} › ${category.name}` : category.name;
}

export function sortedCategoryOptions(categories: AdminCategory[]): AdminCategory[] {
  return [...categories].sort((left, right) => {
    const leftLabel = categoryOptionLabel(left, categories);
    const rightLabel = categoryOptionLabel(right, categories);

    return leftLabel.localeCompare(rightLabel, "tr");
  });
}
