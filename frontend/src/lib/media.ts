export function getMediaBaseUrl(): string {
  if (process.env.NEXT_PUBLIC_MEDIA_URL) {
    return process.env.NEXT_PUBLIC_MEDIA_URL.replace(/\/$/, "");
  }

  const apiUrl = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost/api";

  return apiUrl.replace(/\/api\/?$/, "");
}

export function resolveImageSrc(url: string | null | undefined): string | null {
  if (!url) {
    return null;
  }

  if (url.startsWith("/storage/")) {
    return `${getMediaBaseUrl()}${url}`;
  }

  if (url.startsWith("/media/")) {
    return `${getMediaBaseUrl()}${url.replace(/^\/media/, "/storage")}`;
  }

  try {
    const parsed = new URL(url);
    const path = `${parsed.pathname}${parsed.search}`;

    if (path.startsWith("/storage/")) {
      return `${getMediaBaseUrl()}${path}`;
    }
  } catch {
    return url;
  }

  return url;
}
