"use client";

import { FormEvent, useMemo, useState } from "react";

import { Button } from "@/components/ui/button";
import { api, formatPrice } from "@/lib/api";
import type { Order, OrderReturnRequest } from "@/lib/types";

type OrderReturnPanelProps = {
  token: string;
  order: Order;
  onUpdated: (requests: OrderReturnRequest[]) => void;
};

type SelectedItem = {
  selected: boolean;
  quantity: number;
  replacement_product_variant_id: number | null;
};

function canRequestReturn(order: Order): boolean {
  const hasReturnableItems = (order.items ?? []).some(
    (item) => (item.returnable_quantity ?? 0) > 0,
  );

  return (
    ["paid", "partially_refunded"].includes(order.payment_status) &&
    order.status === "delivered" &&
    hasReturnableItems
  );
}

export function OrderReturnPanel({ token, order, onUpdated }: OrderReturnPanelProps) {
  const [type, setType] = useState<"return" | "exchange">("return");
  const [message, setMessage] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [feedback, setFeedback] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [selections, setSelections] = useState<Record<number, SelectedItem>>(() => {
    const initial: Record<number, SelectedItem> = {};

    for (const item of order.items ?? []) {
      initial[item.id] = {
        selected: (item.returnable_quantity ?? 0) > 0,
        quantity: Math.max(1, item.returnable_quantity ?? item.quantity),
        replacement_product_variant_id: item.product_variant_id ?? null,
      };
    }

    return initial;
  });

  const existingRequests = order.return_requests ?? [];
  const showForm = canRequestReturn(order);

  const selectedItems = useMemo(
    () =>
      (order.items ?? []).filter((item) => {
        const selection = selections[item.id];
        return selection?.selected && (item.returnable_quantity ?? 0) > 0;
      }),
    [order.items, selections],
  );

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);
    setFeedback(null);

    try {
      const response = await api.requestOrderReturn(token, order.id, {
        type,
        message: message.trim(),
        items: selectedItems.map((item) => ({
          order_item_id: item.id,
          quantity: selections[item.id]?.quantity ?? 1,
          replacement_product_variant_id:
            type === "exchange"
              ? (selections[item.id]?.replacement_product_variant_id ?? item.product_variant_id)
              : null,
        })),
      });

      onUpdated([response.return_request, ...existingRequests]);
      setMessage("");
      setFeedback(response.message);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Talep gönderilemedi.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="mt-10 space-y-6">
      {existingRequests.map((request) => (
        <ReturnRequestCard key={request.id} request={request} />
      ))}

      {showForm && (
        <div className="rounded-[2rem] border border-line bg-surface p-8">
          <p className="text-xs uppercase tracking-[0.35em] text-muted">İade / Değişim</p>
          <h2 className="mt-3 font-display text-2xl font-semibold">Teslim sonrası talep</h2>
          <p className="mt-3 text-sm leading-7 text-muted">
            Teslimattan sonra {order.return_window_days ?? 14} gün içinde iade veya değişim
            talep edebilirsiniz. Onay sonrası kargo etiketi e-posta ve bu sayfada görünür.
          </p>

          <div className="mt-6 flex flex-wrap gap-3">
            <button
              type="button"
              onClick={() => setType("return")}
              className={`rounded-full px-5 py-2 text-sm transition ${
                type === "return"
                  ? "bg-accent text-white"
                  : "border border-line bg-background text-stone-700"
              }`}
            >
              İade
            </button>
            <button
              type="button"
              onClick={() => setType("exchange")}
              className={`rounded-full px-5 py-2 text-sm transition ${
                type === "exchange"
                  ? "bg-accent text-white"
                  : "border border-line bg-background text-stone-700"
              }`}
            >
              Değişim
            </button>
          </div>

          <form onSubmit={(event) => void handleSubmit(event)} className="mt-6 space-y-5">
            <ul className="space-y-3">
              {(order.items ?? []).map((item) => {
                const returnable = item.returnable_quantity ?? 0;
                const selection = selections[item.id];
                const disabled = returnable <= 0;

                return (
                  <li
                    key={item.id}
                    className="rounded-[1.5rem] border border-line bg-background px-5 py-4"
                  >
                    <label className="flex items-start gap-3">
                      <input
                        type="checkbox"
                        className="mt-1"
                        disabled={disabled}
                        checked={Boolean(selection?.selected) && !disabled}
                        onChange={(event) =>
                          setSelections((current) => ({
                            ...current,
                            [item.id]: {
                              selected: event.target.checked,
                              quantity: current[item.id]?.quantity ?? returnable,
                              replacement_product_variant_id:
                                current[item.id]?.replacement_product_variant_id ??
                                item.product_variant_id ??
                                null,
                            },
                          }))
                        }
                      />
                      <span className="flex-1">
                        <span className="block font-medium">
                          {[item.product_name, item.variant_label].filter(Boolean).join(" · ") ||
                            "Ürün"}
                        </span>
                        <span className="mt-1 block text-sm text-muted">
                          {disabled
                            ? "İade edilebilir adet kalmadı"
                            : `${returnable} adet iade/değişim yapılabilir · ${formatPrice(item.subtotal)}`}
                        </span>
                      </span>
                    </label>

                    {selection?.selected && !disabled && (
                      <div className="mt-4 flex flex-wrap gap-4 pl-7">
                        <label className="text-sm">
                          Adet
                          <input
                            type="number"
                            min={1}
                            max={returnable}
                            value={selection.quantity}
                            onChange={(event) =>
                              setSelections((current) => ({
                                ...current,
                                [item.id]: {
                                  ...current[item.id],
                                  quantity: Math.min(
                                    returnable,
                                    Math.max(1, Number(event.target.value) || 1),
                                  ),
                                },
                              }))
                            }
                            className="ml-2 w-20 rounded-full border border-line bg-surface px-3 py-1.5 text-sm outline-none focus:border-accent"
                          />
                        </label>

                        {type === "exchange" && (item.exchange_variants?.length ?? 0) > 0 && (
                          <label className="text-sm">
                            Yeni varyant
                            <select
                              value={
                                selection.replacement_product_variant_id ??
                                item.product_variant_id ??
                                ""
                              }
                              onChange={(event) =>
                                setSelections((current) => ({
                                  ...current,
                                  [item.id]: {
                                    ...current[item.id],
                                    replacement_product_variant_id: Number(event.target.value),
                                  },
                                }))
                              }
                              className="ml-2 rounded-full border border-line bg-surface px-3 py-1.5 text-sm outline-none focus:border-accent"
                            >
                              {item.exchange_variants?.map((variant) => (
                                <option key={variant.id} value={variant.id}>
                                  {variant.label}
                                </option>
                              ))}
                            </select>
                          </label>
                        )}
                      </div>
                    )}
                  </li>
                );
              })}
            </ul>

            <textarea
              value={message}
              onChange={(event) => setMessage(event.target.value)}
              required
              minLength={10}
              maxLength={1000}
              rows={4}
              placeholder={
                type === "exchange"
                  ? "Değişim gerekçenizi yazın..."
                  : "İade gerekçenizi yazın..."
              }
              className="w-full rounded-[1.5rem] border border-line bg-background px-5 py-4 text-sm outline-none focus:border-accent"
            />

            <Button
              type="submit"
              disabled={submitting || message.trim().length < 10 || selectedItems.length === 0}
            >
              {submitting
                ? "Gönderiliyor..."
                : type === "exchange"
                  ? "Değişim Talebi Gönder"
                  : "İade Talebi Gönder"}
            </Button>
          </form>

          {feedback && <p className="mt-4 text-sm text-accent">{feedback}</p>}
          {error && <p className="mt-4 text-sm text-red-600">{error}</p>}
        </div>
      )}
    </div>
  );
}

function ReturnRequestCard({ request }: { request: OrderReturnRequest }) {
  return (
    <div className="rounded-[2rem] border border-line bg-surface p-8">
      <p className="text-xs uppercase tracking-[0.35em] text-muted">{request.type_label}</p>
      <h2 className="mt-3 font-display text-2xl font-semibold">{request.status_label}</h2>
      <p className="mt-4 text-sm leading-7 text-muted">{request.message}</p>

      {request.items && request.items.length > 0 && (
        <ul className="mt-4 space-y-2 text-sm">
          {request.items.map((item) => (
            <li key={item.id}>
              {item.quantity}× {[item.product_name, item.variant_label].filter(Boolean).join(" · ")}
              {request.type === "exchange" && item.replacement_variant_label
                ? ` → ${item.replacement_variant_label}`
                : ""}
            </li>
          ))}
        </ul>
      )}

      {request.admin_note && (
        <p className="mt-4 rounded-2xl bg-accent-soft/50 px-4 py-3 text-sm text-foreground">
          Yönetici notu: {request.admin_note}
        </p>
      )}

      {request.status === "pending" && (
        <p className="mt-4 text-sm text-muted">
          Talebiniz inceleniyor. Onaylandığında iade kargo etiketi hazırlanacak.
        </p>
      )}

      {request.status === "approved" && (
        <div className="mt-5 space-y-3">
          <p className="text-sm text-accent">
            Talebiniz onaylandı. Ürünü aşağıdaki etiketle gönderin; depo teslim alınca işlem
            tamamlanır.
          </p>
          {request.return_tracking_number && (
            <p className="text-sm">Takip no: {request.return_tracking_number}</p>
          )}
          <div className="flex flex-wrap gap-3">
            {request.return_label_url && (
              <a
                href={request.return_label_url}
                target="_blank"
                rel="noreferrer"
                className="inline-flex rounded-full bg-accent px-5 py-3 text-sm font-medium text-white transition hover:bg-stone-800"
              >
                Kargo etiketini indir
              </a>
            )}
            {request.return_tracking_url && (
              <a
                href={request.return_tracking_url}
                target="_blank"
                rel="noreferrer"
                className="inline-flex rounded-full border border-line px-5 py-3 text-sm font-medium transition hover:border-accent"
              >
                İade kargosunu takip et
              </a>
            )}
          </div>
        </div>
      )}

      {request.status === "completed" && request.type === "return" && (
        <p className="mt-4 text-sm text-accent">
          İade tamamlandı.
          {request.refund_amount != null ? ` ${formatPrice(request.refund_amount)} iade edildi.` : ""}
        </p>
      )}

      {request.status === "completed" && request.type === "exchange" && (
        <div className="mt-4 space-y-3">
          <p className="text-sm text-accent">Değişim tamamlandı. Yeni ürün kargoya verildi.</p>
          {request.exchange_tracking_url && (
            <a
              href={request.exchange_tracking_url}
              target="_blank"
              rel="noreferrer"
              className="inline-flex rounded-full bg-accent px-5 py-3 text-sm font-medium text-white transition hover:bg-stone-800"
            >
              Değişim kargosunu takip et
            </a>
          )}
        </div>
      )}
    </div>
  );
}
