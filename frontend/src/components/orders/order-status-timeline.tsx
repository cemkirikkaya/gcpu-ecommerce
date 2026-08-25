type OrderStatusTimelineProps = {
  status: string;
  statusLabel: string;
};

const FULFILLMENT_STEPS = [
  {
    key: "pending",
    label: "Sipariş alındı",
    description: "Ödemeniz onaylandı, siparişiniz kaydedildi.",
  },
  {
    key: "processing",
    label: "Hazırlanıyor",
    description: "Ürünleriniz depoda hazırlanıyor.",
  },
  {
    key: "shipped",
    label: "Kargoda",
    description: "Siparişiniz kargoya verildi.",
  },
  {
    key: "delivered",
    label: "Teslim edildi",
    description: "Siparişiniz adresinize ulaştı.",
  },
] as const;

function stepState(
  stepIndex: number,
  currentIndex: number,
): "complete" | "current" | "upcoming" {
  if (currentIndex < 0) {
    return "upcoming";
  }

  if (stepIndex < currentIndex) {
    return "complete";
  }

  if (stepIndex === currentIndex) {
    if (currentIndex === FULFILLMENT_STEPS.length - 1) {
      return "complete";
    }

    return "current";
  }

  return "upcoming";
}

export function OrderStatusTimeline({ status, statusLabel }: OrderStatusTimelineProps) {
  if (status === "cancelled") {
    return (
      <div className="mt-8 rounded-[1.5rem] border border-line bg-surface px-6 py-5 text-left">
        <p className="text-xs uppercase tracking-[0.25em] text-muted">Durum</p>
        <p className="mt-3 font-display text-2xl text-stone-700">Sipariş iptal edildi</p>
        <p className="mt-2 text-sm leading-6 text-muted">
          Bu sipariş ({statusLabel}) artık işlenmiyor.
        </p>
      </div>
    );
  }

  const currentIndex = FULFILLMENT_STEPS.findIndex((step) => step.key === status);

  return (
    <div className="mt-8 rounded-[1.5rem] border border-line bg-surface px-6 py-6 sm:px-8">
      <p className="text-xs uppercase tracking-[0.25em] text-muted">Sipariş durumu</p>

      <ol className="mt-6 space-y-0">
        {FULFILLMENT_STEPS.map((step, index) => {
          const state = stepState(index, currentIndex);
          const isLast = index === FULFILLMENT_STEPS.length - 1;

          return (
            <li key={step.key} className="relative flex gap-4 pb-8 last:pb-0 sm:gap-5">
              {!isLast && (
                <span
                  aria-hidden="true"
                  className={`absolute left-[15px] top-8 h-[calc(100%-1rem)] w-px sm:left-4 ${
                    state === "complete" ? "bg-accent" : "bg-line"
                  }`}
                />
              )}

              <span
                aria-hidden="true"
                className={`relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-xs font-semibold sm:h-8 sm:w-8 ${
                  state === "complete"
                    ? "border-accent bg-accent text-white"
                    : state === "current"
                      ? "border-accent bg-surface text-accent"
                      : "border-line bg-background text-muted"
                }`}
              >
                {state === "complete" ? (
                  <svg viewBox="0 0 20 20" className="h-4 w-4 fill-current" aria-hidden="true">
                    <path d="M7.5 13.6 3.9 10l1.4-1.4 2.2 2.2 6.2-6.2L15.1 6z" />
                  </svg>
                ) : (
                  index + 1
                )}
              </span>

              <div className="min-w-0 pt-0.5">
                <p
                  className={`font-medium ${
                    state === "upcoming" ? "text-muted" : "text-foreground"
                  }`}
                >
                  {step.label}
                </p>
                <p className="mt-1 text-sm leading-6 text-muted">{step.description}</p>
                {state === "current" && (
                  <p className="mt-2 text-xs uppercase tracking-[0.2em] text-accent">
                    Şu anki durum
                  </p>
                )}
              </div>
            </li>
          );
        })}
      </ol>
    </div>
  );
}
