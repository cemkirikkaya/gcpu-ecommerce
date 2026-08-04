import type { CatalogVariantInput } from "@/lib/types";

type VariantFieldsProps = {
  variants: CatalogVariantInput[];
  onChange: (variants: CatalogVariantInput[]) => void;
  allowRemove?: boolean;
};

export function VariantFields({
  variants,
  onChange,
  allowRemove = true,
}: VariantFieldsProps) {
  function updateVariant(index: number, field: keyof CatalogVariantInput, value: string) {
    onChange(
      variants.map((variant, variantIndex) =>
        variantIndex === index
          ? {
              ...variant,
              [field]: field === "stock" ? Number(value) : value,
            }
          : variant,
      ),
    );
  }

  function removeVariant(index: number) {
    onChange(variants.filter((_, variantIndex) => variantIndex !== index));
  }

  return (
    <div className="space-y-4">
      {variants.map((variant, index) => (
        <div
          key={index}
          className="space-y-3 rounded-[1.25rem] border border-line bg-background p-4"
        >
          <div className="flex items-center justify-between gap-3">
            <p className="text-xs uppercase tracking-[0.2em] text-muted">
              Varyant {index + 1}
            </p>
            {allowRemove && variants.length > 1 && (
              <button
                type="button"
                onClick={() => removeVariant(index)}
                className="text-xs text-red-600 transition hover:text-red-700"
              >
                Kaldır
              </button>
            )}
          </div>

          <div className="grid gap-3 md:grid-cols-2">
            <input
              required
              value={variant.sku}
              onChange={(event) => updateVariant(index, "sku", event.target.value)}
              placeholder="SKU (örn. HOOD-BLACK-M)"
              className="rounded-full border border-line bg-surface px-4 py-2 text-sm outline-none focus:border-accent"
            />
            <input
              type="number"
              min={0}
              required
              value={variant.stock}
              onChange={(event) => updateVariant(index, "stock", event.target.value)}
              placeholder="Stok"
              className="rounded-full border border-line bg-surface px-4 py-2 text-sm outline-none focus:border-accent"
            />
            <input
              value={variant.color ?? ""}
              onChange={(event) => updateVariant(index, "color", event.target.value)}
              placeholder="Renk"
              className="rounded-full border border-line bg-surface px-4 py-2 text-sm outline-none focus:border-accent"
            />
            <input
              value={variant.memory ?? ""}
              onChange={(event) => updateVariant(index, "memory", event.target.value)}
              placeholder="Hafıza"
              className="rounded-full border border-line bg-surface px-4 py-2 text-sm outline-none focus:border-accent"
            />
            <input
              value={variant.model ?? ""}
              onChange={(event) => updateVariant(index, "model", event.target.value)}
              placeholder="Model"
              className="rounded-full border border-line bg-surface px-4 py-2 text-sm outline-none focus:border-accent"
            />
            <input
              value={variant.size ?? ""}
              onChange={(event) => updateVariant(index, "size", event.target.value)}
              placeholder="Beden"
              className="rounded-full border border-line bg-surface px-4 py-2 text-sm outline-none focus:border-accent"
            />
          </div>
        </div>
      ))}
    </div>
  );
}
