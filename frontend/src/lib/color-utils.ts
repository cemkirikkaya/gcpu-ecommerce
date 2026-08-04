import type { ProductVariant, VariantAttribute } from "@/lib/types";

const COLOR_MAP: Record<string, string> = {
  siyah: "#1c1917",
  beyaz: "#f5f5f4",
  mavi: "#2563eb",
  pembe: "#ec4899",
  gümüş: "#c0c0c0",
  gumus: "#c0c0c0",
  yeşil: "#16a34a",
  yesil: "#16a34a",
  "uzay grisi": "#4b5563",
  haki: "#8b8b5a",
  lacivert: "#1e3a5f",
  bej: "#d4c4a8",
  gri: "#6b7280",
  krem: "#f5f0e1",
  kahverengi: "#78350f",
  kaplumbağa: "#8b5a2b",
  kaplumbaga: "#8b5a2b",
  şeffaf: "transparent",
  seffaf: "transparent",
  bakır: "#b87333",
  bakir: "#b87333",
  mor: "#7c3aed",
  kırmızı: "#dc2626",
  kirmizi: "#dc2626",
  turuncu: "#ea580c",
  sarı: "#eab308",
  sari: "#eab308",
  altın: "#ca8a04",
  altin: "#ca8a04",
  black: "#1c1917",
  white: "#f5f5f4",
  blue: "#2563eb",
  pink: "#ec4899",
  silver: "#c0c0c0",
  green: "#16a34a",
  gray: "#6b7280",
  grey: "#6b7280",
  navy: "#1e3a5f",
  beige: "#d4c4a8",
  brown: "#78350f",
  red: "#dc2626",
  orange: "#ea580c",
  yellow: "#eab308",
  purple: "#7c3aed",
};

export function isColorAttribute(name: string): boolean {
  const normalized = name.trim().toLowerCase();

  return normalized === "renk" || normalized === "color" || normalized === "colour";
}

export function resolveColor(value: string): string {
  const trimmed = value.trim();

  if (/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(trimmed)) {
    return trimmed;
  }

  if (/^rgb/i.test(trimmed)) {
    return trimmed;
  }

  return COLOR_MAP[trimmed.toLowerCase()] ?? "#a8a29e";
}

export function getVariantColor(variant: ProductVariant): string | null {
  const colorAttribute = variant.attributes.find((attribute: VariantAttribute) =>
    isColorAttribute(attribute.name),
  );

  return colorAttribute?.value ?? null;
}

export function getVariantColorHex(variant: ProductVariant): string | null {
  const color = getVariantColor(variant);

  return color ? resolveColor(color) : null;
}

export function isLightColor(hex: string): boolean {
  if (hex === "transparent") {
    return true;
  }

  const match = hex.match(/^#([0-9a-f]{6})$/i);

  if (!match) {
    return false;
  }

  const value = match[1];
  const red = Number.parseInt(value.slice(0, 2), 16);
  const green = Number.parseInt(value.slice(2, 4), 16);
  const blue = Number.parseInt(value.slice(4, 6), 16);
  const luminance = (0.299 * red + 0.587 * green + 0.114 * blue) / 255;

  return luminance > 0.78;
}

export function uniqueVariantsByColor(
  variants: ProductVariant[],
): ProductVariant[] {
  const seen = new Map<string, ProductVariant>();

  for (const variant of variants) {
    const color = getVariantColor(variant);

    if (!color) {
      continue;
    }

    if (!seen.has(color)) {
      seen.set(color, variant);
    }
  }

  return Array.from(seen.values());
}

export function variantSecondaryLabel(variant: ProductVariant): string {
  const parts = variant.attributes
    .filter((attribute) => !isColorAttribute(attribute.name))
    .map((attribute) => attribute.value);

  return parts.length > 0 ? parts.join(" · ") : variant.label;
}
