"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { api, formatPublishDate } from "@/lib/api";
import type { Post } from "@/lib/types";

export function BlogPostClient({ slug }: { slug: string }) {
  const [post, setPost] = useState<Post | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    setError(null);

    api
      .post(slug)
      .then((data) => setPost(data))
      .catch((err) => {
        setPost(null);
        setError(err instanceof Error ? err.message : "Yazı yüklenemedi.");
      })
      .finally(() => setLoading(false));
  }, [slug]);

  if (loading) {
    return <p className="text-sm text-muted">Yükleniyor...</p>;
  }

  if (!post) {
    return (
      <div className="text-center">
        <p className="text-muted">{error ?? "Yazı bulunamadı."}</p>
        <Link href="/blog" className="mt-6 inline-block text-sm text-accent hover:underline">
          Bloga dön
        </Link>
      </div>
    );
  }

  return (
    <article>
      <Link href="/blog" className="text-sm text-muted transition hover:text-accent">
        ← Blog
      </Link>
      <p className="mt-8 text-xs uppercase tracking-[0.35em] text-muted">Blog</p>
      <h1 className="mt-3 font-display text-4xl font-semibold leading-tight sm:text-5xl">
        {post.title}
      </h1>
      <div className="mt-4 flex flex-wrap items-center gap-3 text-sm text-muted">
        {post.author_name && <span>{post.author_name}</span>}
        <span>{formatPublishDate(post.published_at)}</span>
      </div>
      {post.excerpt && (
        <p className="mt-8 rounded-[1.5rem] border border-line bg-surface p-6 text-lg leading-8 text-muted">
          {post.excerpt}
        </p>
      )}
      <div
        className="blog-content mt-10 space-y-4 text-base leading-8 text-stone-700 [&_a]:text-accent [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-line [&_blockquote]:pl-4 [&_blockquote]:italic [&_h2]:font-display [&_h2]:text-3xl [&_h2]:font-semibold [&_h3]:font-display [&_h3]:text-2xl [&_h3]:font-semibold [&_li]:ml-5 [&_ol]:list-decimal [&_p]:mt-4 [&_ul]:list-disc"
        dangerouslySetInnerHTML={{ __html: post.content }}
      />
    </article>
  );
}
