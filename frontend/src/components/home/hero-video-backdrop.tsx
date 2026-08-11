"use client";

type HeroVideoBackdropProps = {
  videoUrl?: string | null;
};

export function HeroVideoBackdrop({ videoUrl }: HeroVideoBackdropProps) {
  const source = videoUrl ?? process.env.NEXT_PUBLIC_HERO_VIDEO_URL;

  if (!source) {
    return null;
  }

  return (
    <div className="pointer-events-none absolute inset-0 overflow-hidden">
      <video
        autoPlay
        muted
        loop
        playsInline
        preload="metadata"
        aria-hidden="true"
        className="absolute inset-0 h-full w-full object-cover opacity-[0.18] mix-blend-multiply"
      >
        <source src={source} type="video/mp4" />
      </video>
      <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(247,245,242,0.55)_0%,rgba(247,245,242,0.92)_100%)]" />
    </div>
  );
}
