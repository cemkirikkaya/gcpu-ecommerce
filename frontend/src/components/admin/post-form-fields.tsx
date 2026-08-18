type PostFormFieldsProps = {
  title: string;
  slug: string;
  excerpt: string;
  content: string;
  publishedAt: string;
  onTitleChange: (value: string) => void;
  onSlugChange: (value: string) => void;
  onExcerptChange: (value: string) => void;
  onContentChange: (value: string) => void;
  onPublishedAtChange: (value: string) => void;
  onTitleBlur?: () => void;
};

export function PostFormFields({
  title,
  slug,
  excerpt,
  content,
  publishedAt,
  onTitleChange,
  onSlugChange,
  onExcerptChange,
  onContentChange,
  onPublishedAtChange,
  onTitleBlur,
}: PostFormFieldsProps) {
  return (
    <div className="space-y-4">
      <input
        value={title}
        onChange={(event) => onTitleChange(event.target.value)}
        onBlur={onTitleBlur}
        required
        placeholder="Başlık"
        className="w-full rounded-[1rem] border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
      />
      <input
        value={slug}
        onChange={(event) => onSlugChange(event.target.value)}
        required
        placeholder="slug-ornek-yazi"
        className="w-full rounded-[1rem] border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
      />
      <textarea
        value={excerpt}
        onChange={(event) => onExcerptChange(event.target.value)}
        rows={3}
        placeholder="Kısa özet (liste ve SEO için)"
        className="w-full rounded-[1rem] border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
      />
      <textarea
        value={content}
        onChange={(event) => onContentChange(event.target.value)}
        required
        rows={14}
        placeholder="İçerik (HTML desteklenir: <p>, <h2>, <ul> vb.)"
        className="w-full rounded-[1rem] border border-line bg-background px-5 py-3 font-mono text-sm outline-none focus:border-accent"
      />
      <label className="block text-sm">
        <span className="mb-2 block text-muted">
          Varsayılan olarak bugünün tarihi seçilir. Taslak kaydetmek için temizleyin.
        </span>
        <input
          type="date"
          value={publishedAt}
          onChange={(event) => onPublishedAtChange(event.target.value)}
          className="w-full max-w-sm rounded-[1rem] border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
      </label>
    </div>
  );
}
