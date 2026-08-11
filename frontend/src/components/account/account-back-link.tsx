import Link from "next/link";

export function AccountBackLink() {
  return (
    <Link href="/account" className="text-sm text-muted transition hover:text-accent">
      ← Hesabıma dön
    </Link>
  );
}
