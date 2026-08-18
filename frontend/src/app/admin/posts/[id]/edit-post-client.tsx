"use client";

import Link from "next/link";
import { FormEvent, useEffect, useState } from "react";
import { useRouter } from "next/navigation";

import { PostFormFields } from "@/components/admin/post-form-fields";
import { Button, ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatPublishDate } from "@/lib/api";
import { fromDateLocalValue, slugify, toDateLocalValue } from "@/lib/slugify";
import type { AdminPost } from "@/lib/types";

export function EditPostClient({ postId }: { postId: number }) {
  const router = useRouter();
  const { token } = useAuth();
  const [post, setPost] = useState<AdminPost | null>(null);
  const [title, setTitle] = useState("");
  const [slug, setSlug] = useState("");
  const [excerpt, setExcerpt] = useState("");
  const [content, setContent] = useState("");
  const [publishedAt, setPublishedAt] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    if (!token) {
      return;
    }

    api
      .adminPost(token, postId)
      .then((data) => {
        setPost(data);
        setTitle(data.title);
        setSlug(data.slug);
        setExcerpt(data.excerpt ?? "");
        setContent(data.content);
        setPublishedAt(toDateLocalValue(data.published_at));
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : "Yazı yüklenemedi.");
      })
      .finally(() => setLoading(false));
  }, [token, postId]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!token) {
      return;
    }

    setSaving(true);
    setError(null);

    try {
      await api.adminUpdatePost(token, postId, {
        title,
        slug,
        excerpt: excerpt || null,
        content,
        published_at: fromDateLocalValue(publishedAt),
      });

      router.push("/admin/posts");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Yazı güncellenemedi.");
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete() {
    if (!token || !confirm("Bu blog yazısını silmek istediğinize emin misiniz?")) {
      return;
    }

    setDeleting(true);
    setError(null);

    try {
      await api.adminDeletePost(token, postId);
      router.push("/admin/posts");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Yazı silinemedi.");
      setDeleting(false);
    }
  }

  if (loading) {
    return <p className="text-sm text-muted">Yükleniyor...</p>;
  }

  if (!post) {
    return (
      <div>
        <p className="text-muted">{error ?? "Yazı bulunamadı."}</p>
        <ButtonLink href="/admin/posts" className="mt-6">
          Listeye Dön
        </ButtonLink>
      </div>
    );
  }

  return (
    <div>
      <Link href="/admin/posts" className="text-sm text-muted transition hover:text-accent">
        ← Blog yazıları
      </Link>
      <p className="mt-8 text-xs uppercase tracking-[0.35em] text-muted">Blog</p>
      <div className="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-display text-4xl font-semibold">Yazıyı Düzenle</h1>
          <p className="mt-2 text-sm text-muted">
            Son güncelleme: {formatPublishDate(post.updated_at)}
            {post.is_published ? " · Yayında" : " · Taslak"}
          </p>
        </div>
        {post.is_published && (
          <Link
            href={`/blog/${post.slug}`}
            target="_blank"
            className="rounded-full border border-line px-4 py-2 text-sm transition hover:border-accent hover:text-accent"
          >
            Vitrinde Gör
          </Link>
        )}
      </div>

      <form onSubmit={handleSubmit} className="mt-8 max-w-3xl space-y-6">
        <PostFormFields
          title={title}
          slug={slug}
          excerpt={excerpt}
          content={content}
          publishedAt={publishedAt}
          onTitleChange={setTitle}
          onSlugChange={setSlug}
          onExcerptChange={setExcerpt}
          onContentChange={setContent}
          onPublishedAtChange={setPublishedAt}
        />

        {error && <p className="text-sm text-red-600">{error}</p>}

        <div className="flex flex-wrap gap-3">
          <Button type="submit" disabled={saving}>
            {saving ? "Kaydediliyor..." : "Değişiklikleri Kaydet"}
          </Button>
          <Button type="button" variant="ghost" disabled={deleting} onClick={handleDelete}>
            {deleting ? "Siliniyor..." : "Sil"}
          </Button>
        </div>
      </form>
    </div>
  );
}
