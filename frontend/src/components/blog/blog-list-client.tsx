"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { api, formatPublishDate } from "@/lib/api";
import type { PostSummary } from "@/lib/types";

export function BlogListClient() {
  const [posts, setPosts] = useState<PostSummary[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);

    api
      .posts(page)
      .then((response) => {
        setPosts(response.posts);
        setLastPage(response.meta.last_page);
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : "Yazılar yüklenemedi.");
      })
      .finally(() => setLoading(false));
  }, [page]);

  if (loading) {
    return <p className="mt-16 text-sm text-muted">Yükleniyor...</p>;
  }

  if (error) {
    return <p className="mt-16 text-sm text-red-600">{error}</p>;
  }

  if (posts.length === 0) {
    return (
      <p className="mt-16 rounded-[1.5rem] border border-line bg-surface p-8 text-sm text-muted">
        Henüz yayınlanmış bir yazı yok.
      </p>
    );
  }

  return (
    <div className="mt-16">
      <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        {posts.map((post) => (
          <article
            key={post.id}
            className="group flex flex-col rounded-[1.5rem] border border-line bg-surface p-6 transition hover:-translate-y-0.5 hover:border-accent/40 hover:shadow-[0_20px_50px_-40px_rgba(28,25,23,0.35)]"
          >
            <p className="text-xs uppercase tracking-[0.25em] text-muted">
              {formatPublishDate(post.published_at)}
            </p>
            <h2 className="mt-3 font-display text-2xl font-semibold leading-snug transition group-hover:text-accent">
              <Link href={`/blog/${post.slug}`}>{post.title}</Link>
            </h2>
            {post.excerpt && (
              <p className="mt-3 flex-1 text-sm leading-7 text-muted">{post.excerpt}</p>
            )}
            <div className="mt-6 flex items-center justify-between gap-3 text-sm">
              {post.author_name && <span className="text-muted">{post.author_name}</span>}
              <Link
                href={`/blog/${post.slug}`}
                className="font-medium text-accent transition hover:underline"
              >
                Devamını oku
              </Link>
            </div>
          </article>
        ))}
      </div>

      {lastPage > 1 && (
        <div className="mt-10 flex flex-wrap items-center justify-center gap-3">
          <button
            type="button"
            disabled={page <= 1}
            onClick={() => setPage((current) => Math.max(1, current - 1))}
            className="rounded-full border border-line px-5 py-2 text-sm transition hover:border-accent disabled:cursor-not-allowed disabled:opacity-50"
          >
            Önceki
          </button>
          <span className="text-sm text-muted">
            Sayfa {page} / {lastPage}
          </span>
          <button
            type="button"
            disabled={page >= lastPage}
            onClick={() => setPage((current) => Math.min(lastPage, current + 1))}
            className="rounded-full border border-line px-5 py-2 text-sm transition hover:border-accent disabled:cursor-not-allowed disabled:opacity-50"
          >
            Sonraki
          </button>
        </div>
      )}
    </div>
  );
}
