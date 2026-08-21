"use client";

type AnimatedMeshBackgroundProps = {
  variant?: "light" | "dark";
};

export function AnimatedMeshBackground({ variant = "light" }: AnimatedMeshBackgroundProps) {
  const isDark = variant === "dark";

  return (
    <div className="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
      <div className="animate-mesh-a absolute -left-[10%] top-[-20%] h-[70%] w-[55%] rounded-full bg-[radial-gradient(circle,rgba(184,149,107,0.35)_0%,transparent_70%)] blur-3xl" />
      <div className="animate-mesh-b absolute -right-[5%] top-[10%] h-[60%] w-[50%] rounded-full bg-[radial-gradient(circle,rgba(92,79,66,0.25)_0%,transparent_70%)] blur-3xl" />
      <div className="animate-mesh-c absolute bottom-[-15%] left-[20%] h-[55%] w-[60%] rounded-full bg-[radial-gradient(circle,rgba(217,201,168,0.28)_0%,transparent_70%)] blur-3xl" />
      <div
        className={
          isDark
            ? "absolute inset-0 bg-[linear-gradient(180deg,rgba(18,16,14,0.2)_0%,rgba(18,16,14,0.75)_70%,#f5f2ed_100%)]"
            : "absolute inset-0 bg-[linear-gradient(180deg,rgba(18,16,14,0.15)_0%,rgba(245,242,237,0.92)_88%,#f5f2ed_100%)]"
        }
      />
    </div>
  );
}
