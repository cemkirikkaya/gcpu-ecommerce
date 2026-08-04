import { isLightColor } from "@/lib/color-utils";

type ColorSwatchProps = {
  color: string;
  label: string;
  selected?: boolean;
  disabled?: boolean;
  onClick?: () => void;
  size?: "sm" | "md";
};

export function ColorSwatch({
  color,
  label,
  selected = false,
  disabled = false,
  onClick,
  size = "sm",
}: ColorSwatchProps) {
  const dimension = size === "sm" ? "h-6 w-6" : "h-8 w-8";
  const isTransparent = color === "transparent";
  const needsBorder = isTransparent || isLightColor(color);

  return (
    <button
      type="button"
      disabled={disabled}
      onClick={onClick}
      title={label}
      aria-label={label}
      aria-pressed={selected}
      className={`${dimension} rounded-md border-2 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/40 ${
        selected
          ? "border-accent ring-2 ring-accent/25"
          : needsBorder
            ? "border-stone-300 hover:border-accent/50"
            : "border-transparent hover:border-accent/50"
      } ${disabled ? "cursor-not-allowed opacity-40" : "cursor-pointer"}`}
      style={
        isTransparent
          ? {
              backgroundImage:
                "linear-gradient(45deg, #d6d3d1 25%, transparent 25%), linear-gradient(-45deg, #d6d3d1 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #d6d3d1 75%), linear-gradient(-45deg, transparent 75%, #d6d3d1 75%)",
              backgroundSize: "8px 8px",
              backgroundPosition: "0 0, 0 4px, 4px -4px, -4px 0",
              backgroundColor: "#fafaf9",
            }
          : { backgroundColor: color }
      }
    />
  );
}
