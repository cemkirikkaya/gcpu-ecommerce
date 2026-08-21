"use client";

type HeroVideoBackdropProps = {
  videoUrl?: string | null;
  cinematic?: boolean;
};

export function HeroVideoBackdrop({ videoUrl, cinematic = false }: HeroVideoBackdropProps) {
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
        preload="auto"
        aria-hidden="true"
        className={`absolute inset-0 h-full w-full object-cover ${
          cinematic ? "opacity-55 saturate-[1.1]" : "opacity-[0.18] mix-blend-multiply saturate-[0.85]"
        }`}
      >
        <source src={source} type="video/mp4" />
      </video>
      {cinematic ? (
        <div className="absolute inset-0 bg-black/25" />
      ) : (
        <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(250,247,242,0.65)_0%,rgba(240,235,227,0.94)_100%)]" />
      )}
    </div>
  );
}
