"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";

import { AdminOnlyGuard } from "@/components/admin/admin-only-guard";
import { ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatPublishDate } from "@/lib/api";
import type { AdminPost } from "@/lib/types";

function AdminPostsPageContent() {
  const { token } = useAuth();
  const [posts, setPosts] = useState<AdminPost[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [query, setQuery] = useState("");

  useEffect(() => {
    if (!token) {
      return;
    }

    api
      .adminPosts(token)
      .then(setPosts)
      .catch((err) => setError(err instanceof Error ? err.message : "Yazılar yüklenemedi."))
      .finally(() => setLoading(false));
  }, [token]);

  const filteredPosts = useMemo(() => {
    const normalizedQuery = query.trim().toLowerCase();

    if (!normalizedQuery) {
      return posts;
    }

    return posts.filter((post) =>
      [post.title, post.slug, post.excerpt, post.author_name]
        .filter(Boolean)
        .join(" ")
        .toLowerCase()
        .includes(normalizedQuery),
    );
  }, [posts, query]);

  return (
    <div>
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-xs uppercase tracking-[0.35em] text-muted">Blog</p>
          <h1 className="mt-3 font-display text-4xl font-semibold">Yazılar</h1>
        </div>
        <ButtonLink href="/admin/posts/new">Yeni Yazı</ButtonLink>
      </div>

      <input
        type="search"
        value={query}
        onChange={(event) => setQuery(event.target.value)}
        placeholder="Başlık veya slug ara..."
        className="mt-6 w-full max-w-xl rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
      />

      {error && <p className="mt-6 text-sm text-red-600">{error}</p>}

      {loading ? (
        <p className="mt-8 text-sm text-muted">Yükleniyor...</p>
      ) : (
        <div className="mt-8 space-y-4">
          {filteredPosts.map((post) => (
            <div
              key={post.id}
              className="rounded-[1.5rem] border border-line bg-surface p-6"
            >
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0 flex-1">
                  <div className="flex flex-wrap items-center gap-3">
                    <h2 className="text-xl font-semibold">{post.title}</h2>
                    <span
                      className={`rounded-full px-3 py-1 text-xs font-medium ${
                        post.is_published
                          ? "bg-green-100 text-green-800"
                          : "bg-stone-100 text-stone-600"
                      }`}
                    >
                      {post.is_published ? "Yayında" : "Taslak"}
                    </span>
                  </div>
                  <p className="mt-2 text-sm text-muted">/{post.slug}</p>
                  {post.excerpt && (
                    <p className="mt-3 text-sm leading-6 text-stone-600">{post.excerpt}</p>
                  )}
                  <p className="mt-3 text-xs text-muted">
                    {post.author_name ?? "—"} · Güncelleme: {formatPublishDate(post.updated_at)}
                  </p>
                </div>
                <div className="flex flex-wrap gap-2">
                  {post.is_published && (
                    <Link
                      href={`/blog/${post.slug}`}
                      target="_blank"
                      className="rounded-full border border-line px-4 py-2 text-sm transition hover:border-accent hover:text-accent"
                    >
                      Vitrin
                    </Link>
                  )}
                  <ButtonLink href={`/admin/posts/${post.id}`} variant="secondary">
                    Düzenle
                  </ButtonLink>
                </div>
              </div>
            </div>
          ))}

          {filteredPosts.length === 0 && (
            <div className="rounded-[1.5rem] border border-dashed border-line p-10 text-center">
              <p className="text-muted">
                {posts.length === 0
                  ? "Henüz blog yazısı eklenmemiş."
                  : "Aramanızla eşleşen yazı bulunamadı."}
              </p>
              {posts.length === 0 && (
                <ButtonLink href="/admin/posts/new" className="mt-4">
                  İlk Yazıyı Ekle
                </ButtonLink>
              )}
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export default function AdminPostsPage() {
  return (
    <AdminOnlyGuard>
      <AdminPostsPageContent />
    </AdminOnlyGuard>
  );
}
