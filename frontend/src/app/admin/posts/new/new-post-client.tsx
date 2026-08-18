"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";

import { PostFormFields } from "@/components/admin/post-form-fields";
import { Button, ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api } from "@/lib/api";
import { fromDateLocalValue, nowDateLocalValue, slugify } from "@/lib/slugify";

export function NewPostClient() {
  const router = useRouter();
  const { token } = useAuth();
  const [title, setTitle] = useState("");
  const [slug, setSlug] = useState("");
  const [slugTouched, setSlugTouched] = useState(false);
  const [excerpt, setExcerpt] = useState("");
  const [content, setContent] = useState("");
  const [publishedAt, setPublishedAt] = useState(() => nowDateLocalValue());
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!token) {
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const response = await api.adminCreatePost(token, {
        title,
        slug,
        excerpt: excerpt || null,
        content,
        published_at: fromDateLocalValue(publishedAt),
      });

      router.push(`/admin/posts/${response.post.id}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Yazı oluşturulamadı.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      <Link href="/admin/posts" className="text-sm text-muted transition hover:text-accent">
        ← Blog yazıları
      </Link>
      <p className="mt-8 text-xs uppercase tracking-[0.35em] text-muted">Blog</p>
      <h1 className="mt-3 font-display text-4xl font-semibold">Yeni Yazı</h1>

      <form onSubmit={handleSubmit} className="mt-8 max-w-3xl space-y-6">
        <PostFormFields
          title={title}
          slug={slug}
          excerpt={excerpt}
          content={content}
          publishedAt={publishedAt}
          onTitleChange={setTitle}
          onSlugChange={(value) => {
            setSlugTouched(true);
            setSlug(value);
          }}
          onExcerptChange={setExcerpt}
          onContentChange={setContent}
          onPublishedAtChange={setPublishedAt}
          onTitleBlur={() => {
            if (!slugTouched && title.trim()) {
              setSlug(slugify(title));
            }
          }}
        />

        {error && <p className="text-sm text-red-600">{error}</p>}

        <div className="flex flex-wrap gap-3">
          <Button type="submit" disabled={loading}>
            {loading ? "Kaydediliyor..." : "Yazıyı Kaydet"}
          </Button>
          <ButtonLink href="/admin/posts" variant="secondary">
            İptal
          </ButtonLink>
        </div>
      </form>
    </div>
  );
}
