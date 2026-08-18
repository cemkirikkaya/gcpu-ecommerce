import type { Metadata } from "next";
import { Suspense } from "react";

import { BlogPostClient } from "@/components/blog/blog-post-client";
import { api } from "@/lib/api";

export const dynamic = "force-dynamic";

type BlogPostPageProps = {
  params: Promise<{ slug: string }>;
};

export async function generateMetadata({ params }: BlogPostPageProps): Promise<Metadata> {
  const { slug } = await params;

  try {
    const post = await api.post(slug);

    return {
      title: post.title,
      description: post.excerpt ?? `${post.title} — GCPU Blog`,
    };
  } catch {
    return {
      title: "Blog Yazısı",
    };
  }
}

export default async function BlogPostPage({ params }: BlogPostPageProps) {
  const { slug } = await params;

  return (
    <div className="mx-auto max-w-3xl px-6 py-16 lg:px-10 lg:py-24">
      <Suspense fallback={<p className="text-sm text-muted">Yükleniyor...</p>}>
        <BlogPostClient slug={slug} />
      </Suspense>
    </div>
  );
}
