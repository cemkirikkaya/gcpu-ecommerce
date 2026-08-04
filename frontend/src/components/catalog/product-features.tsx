import { isColorAttribute, isLightColor, resolveColor } from "@/lib/color-utils";
import type { ProductVariant } from "@/lib/types";

type AggregatedAttribute = {
  name: string;
  values: string[];
};

function aggregateVariantAttributes(
  variants: ProductVariant[],
): AggregatedAttribute[] {
  const attributeMap = new Map<string, Set<string>>();

  for (const variant of variants) {
    for (const attribute of variant.attributes) {
      const values = attributeMap.get(attribute.name) ?? new Set<string>();
      values.add(attribute.value);
      attributeMap.set(attribute.name, values);
    }
  }

  return Array.from(attributeMap.entries()).map(([name, values]) => ({
    name,
    values: Array.from(values),
  }));
}

function FeatureColorDot({ color, label }: { color: string; label: string }) {
  const isTransparent = color === "transparent";
  const needsBorder = isTransparent || isLightColor(color);

  return (
    <span
      title={label}
      aria-hidden="true"
      className={`inline-block h-4 w-4 shrink-0 rounded-sm border ${
        needsBorder ? "border-stone-300" : "border-transparent"
      }`}
      style={
        isTransparent
          ? {
              backgroundImage:
                "linear-gradient(45deg, #d6d3d1 25%, transparent 25%), linear-gradient(-45deg, #d6d3d1 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #d6d3d1 75%), linear-gradient(-45deg, transparent 75%, #d6d3d1 75%)",
              backgroundSize: "6px 6px",
              backgroundPosition: "0 0, 0 3px, 3px -3px, -3px 0",
              backgroundColor: "#fafaf9",
            }
          : { backgroundColor: color }
      }
    />
  );
}

function ColorValueList({ values }: { values: string[] }) {
  return (
    <div className="flex flex-wrap items-center gap-3">
      {values.map((value) => (
        <span key={value} className="inline-flex items-center gap-1.5">
          <FeatureColorDot color={resolveColor(value)} label={value} />
          <span>{value}</span>
        </span>
      ))}
    </div>
  );
}

function TextValueList({ values }: { values: string[] }) {
  return <span>{values.join(" · ")}</span>;
}

export function ProductFeatures({ variants }: { variants: ProductVariant[] }) {
  const attributes = aggregateVariantAttributes(variants);

  if (attributes.length === 0) {
    return null;
  }

  return (
    <div className="mt-6">
      <p className="text-xs uppercase tracking-[0.28em] text-muted">Özellikler</p>
      <dl className="mt-4 divide-y divide-line rounded-2xl border border-line bg-background/60">
        {attributes.map((attribute: AggregatedAttribute) => (
          <div
            key={attribute.name}
            className="flex flex-col gap-2 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between sm:gap-4"
          >
            <dt className="text-muted">{attribute.name}</dt>
            <dd className="font-medium text-foreground">
              {isColorAttribute(attribute.name) ? (
                <ColorValueList values={attribute.values} />
              ) : (
                <TextValueList values={attribute.values} />
              )}
            </dd>
          </div>
        ))}
      </dl>
    </div>
  );
}

export function getProductVariants(product: {
  variant_groups?: { variants: ProductVariant[] }[];
  variants?: ProductVariant[];
}): ProductVariant[] {
  const fromGroups = product.variant_groups?.flatMap((group) => group.variants) ?? [];

  if (fromGroups.length > 0) {
    return fromGroups;
  }

  return product.variants ?? [];
}
