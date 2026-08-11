"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";

import { ProductRatingStars } from "@/components/catalog/product-rating-stars";
import { Button } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatOrderDate } from "@/lib/api";
import type { ProductReview, ProductReviewSummary } from "@/lib/types";

type ProductReviewsProps = {
  productId: number;
  initialSummary?: ProductReviewSummary;
};

type ReviewFormState = {
  rating: number;
  comment: string;
};

const emptyForm: ReviewFormState = {
  rating: 5,
  comment: "",
};

function StarPicker({
  value,
  onChange,
}: {
  value: number;
  onChange: (rating: number) => void;
}) {
  return (
    <div className="flex items-center gap-1">
      {Array.from({ length: 5 }).map((_, index) => {
        const star = index + 1;
        const active = star <= value;

        return (
          <button
            key={star}
            type="button"
            aria-label={`${star} yıldız`}
            onClick={() => onChange(star)}
            className="rounded p-0.5 transition hover:scale-110"
          >
            <svg
              viewBox="0 0 20 20"
              className={`h-6 w-6 ${active ? "fill-accent text-accent" : "fill-none stroke-line"}`}
              strokeWidth={active ? 0 : 1.5}
            >
              <path d="M10 1.5l2.47 5.01 5.53.8-4 3.9.94 5.5L10 14.77l-4.94 2.94.94-5.5-4-3.9 5.53-.8L10 1.5z" />
            </svg>
          </button>
        );
      })}
    </div>
  );
}

export function ProductReviews({ productId, initialSummary }: ProductReviewsProps) {
  const { token, user } = useAuth();
  const [reviews, setReviews] = useState<ProductReview[]>([]);
  const [summary, setSummary] = useState<ProductReviewSummary>(
    initialSummary ?? { average: 0, count: 0 },
  );
  const [ownReview, setOwnReview] = useState<ProductReview | null>(null);
  const [form, setForm] = useState<ReviewFormState>(emptyForm);
  const [editing, setEditing] = useState(false);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);

  const loadReviews = useCallback(async (pageNumber = 1) => {
    const response = await api.productReviews(productId, pageNumber);
    setReviews(response.reviews);
    setSummary(response.summary);
    setPage(response.meta.current_page);
    setLastPage(response.meta.last_page);
  }, [productId]);

  useEffect(() => {
    setLoading(true);
    setError(null);

    Promise.all([
      loadReviews(1),
      token && user?.role === "customer"
        ? api.myProductReview(token, productId)
        : Promise.resolve(null),
    ])
      .then(([, mine]) => {
        setOwnReview(mine);
        if (mine) {
          setForm({ rating: mine.rating, comment: mine.comment });
        }
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : "Yorumlar yüklenemedi.");
      })
      .finally(() => setLoading(false));
  }, [productId, token, user?.role, loadReviews]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!token) {
      window.location.href = "/login";
      return;
    }

    setSubmitting(true);
    setError(null);
    setMessage(null);

    try {
      if (ownReview && editing) {
        const updated = await api.updateProductReview(token, productId, ownReview.id, form);
        setOwnReview(updated);
        setEditing(false);
        setMessage("Yorumunuz güncellendi.");
      } else {
        const created = await api.createProductReview(token, productId, form);
        setOwnReview(created);
        setEditing(false);
        setMessage("Yorumunuz kaydedildi.");
      }

      await loadReviews(1);
      setForm(emptyForm);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Yorum kaydedilemedi.");
    } finally {
      setSubmitting(false);
    }
  }

  async function handleDelete() {
    if (!token || !ownReview) {
      return;
    }

    setSubmitting(true);
    setError(null);
    setMessage(null);

    try {
      await api.deleteProductReview(token, productId, ownReview.id);
      setOwnReview(null);
      setForm(emptyForm);
      setEditing(false);
      setMessage("Yorumunuz silindi.");
      await loadReviews(1);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Yorum silinemedi.");
    } finally {
      setSubmitting(false);
    }
  }

  if (loading) {
    return <p className="mt-16 text-sm text-muted">Yorumlar yükleniyor...</p>;
  }

  return (
    <section className="mt-20 border-t border-line pt-16">
      <div className="flex flex-wrap items-end justify-between gap-6">
        <div>
          <p className="text-xs uppercase tracking-[0.35em] text-muted">Değerlendirmeler</p>
          <h2 className="mt-3 font-display text-4xl font-semibold">Müşteri yorumları</h2>
        </div>
        {summary.count > 0 && (
          <ProductRatingStars
            rating={summary.average}
            showValue
            reviewCount={summary.count}
          />
        )}
      </div>

      {error && <p className="mt-6 text-sm text-red-600">{error}</p>}
      {message && <p className="mt-6 text-sm text-green-700">{message}</p>}

      {user?.role === "customer" && token && (
        <div className="mt-10 rounded-[1.75rem] border border-line bg-surface p-6 sm:p-8">
          {ownReview && !editing ? (
            <div>
              <p className="text-xs uppercase tracking-[0.25em] text-muted">Yorumunuz</p>
              <div className="mt-4">
                <ProductRatingStars rating={ownReview.rating} />
                <p className="mt-4 text-sm leading-7 text-foreground">{ownReview.comment}</p>
              </div>
              <div className="mt-6 flex flex-wrap gap-3">
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => {
                    if (ownReview) {
                      setForm({ rating: ownReview.rating, comment: ownReview.comment });
                    }
                    setEditing(true);
                  }}
                >
                  Düzenle
                </Button>
                <Button type="button" onClick={() => void handleDelete()} disabled={submitting}>
                  Sil
                </Button>
              </div>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-5">
              <h3 className="font-display text-2xl font-semibold">
                {ownReview ? "Yorumu düzenle" : "Yorum yaz"}
              </h3>
              <div>
                <p className="text-xs uppercase tracking-[0.25em] text-muted">Puanınız</p>
                <div className="mt-3">
                  <StarPicker
                    value={form.rating}
                    onChange={(rating) => setForm((current) => ({ ...current, rating }))}
                  />
                </div>
              </div>
              <textarea
                value={form.comment}
                onChange={(event) =>
                  setForm((current) => ({ ...current, comment: event.target.value }))
                }
                required
                minLength={10}
                maxLength={2000}
                rows={4}
                placeholder="Ürün hakkındaki deneyiminizi paylaşın..."
                className="w-full rounded-[1.25rem] border border-line bg-background px-5 py-4 text-sm outline-none focus:border-accent"
              />
              <div className="flex flex-wrap gap-3">
                <Button type="submit" disabled={submitting}>
                  {submitting ? "Kaydediliyor..." : ownReview ? "Güncelle" : "Gönder"}
                </Button>
                {ownReview && editing && (
                  <Button
                    type="button"
                    variant="secondary"
                    onClick={() => {
                      setEditing(false);
                      setForm({ rating: ownReview.rating, comment: ownReview.comment });
                    }}
                  >
                    İptal
                  </Button>
                )}
              </div>
            </form>
          )}
        </div>
      )}

      {!token && (
        <p className="mt-10 text-sm text-muted">
          Yorum yazmak için{" "}
          <a href="/login" className="text-accent underline-offset-2 hover:underline">
            giriş yapın
          </a>
          .
        </p>
      )}

      <div className="mt-10 space-y-4">
        {reviews.length === 0 ? (
          <p className="rounded-[1.5rem] border border-line bg-surface px-6 py-8 text-sm text-muted">
            Henüz yorum yok. İlk değerlendirmeyi siz yapın.
          </p>
        ) : (
          reviews.map((review) => (
            <article
              key={review.id}
              className="rounded-[1.5rem] border border-line bg-surface p-6"
            >
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <p className="font-medium">{review.user?.name ?? "Müşteri"}</p>
                  <p className="mt-1 text-xs text-muted">
                    {formatOrderDate(review.created_at)}
                  </p>
                </div>
                <ProductRatingStars rating={review.rating} size="sm" />
              </div>
              <p className="mt-4 text-sm leading-7 text-foreground">{review.comment}</p>
            </article>
          ))
        )}
      </div>

      {lastPage > 1 && (
        <div className="mt-8 flex items-center justify-center gap-3">
          <Button
            type="button"
            variant="secondary"
            disabled={page <= 1}
            onClick={() => void loadReviews(page - 1)}
          >
            Önceki
          </Button>
          <span className="text-sm text-muted">
            Sayfa {page} / {lastPage}
          </span>
          <Button
            type="button"
            variant="secondary"
            disabled={page >= lastPage}
            onClick={() => void loadReviews(page + 1)}
          >
            Sonraki
          </Button>
        </div>
      )}
    </section>
  );
}
