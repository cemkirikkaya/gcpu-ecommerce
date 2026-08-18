export function slugify(value: string): string {
  return value
    .trim()
    .toLocaleLowerCase("tr-TR")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^a-z0-9\s-]/g, "")
    .replace(/\s+/g, "-")
    .replace(/-+/g, "-")
    .replace(/^-|-$/g, "");
}

export function nowDateLocalValue(date: Date = new Date()): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

export function toDateLocalValue(value: string | null | undefined): string {
  if (!value) {
    return nowDateLocalValue();
  }

  const date = new Date(value);
  if (!Number.isNaN(date.getTime())) {
    return nowDateLocalValue(date);
  }

  const match = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (!match) {
    return nowDateLocalValue();
  }

  const [, year, month, day] = match;

  return `${year}-${month}-${day}`;
}

export function fromDateLocalValue(value: string): string | null {
  if (!value.trim()) {
    return null;
  }

  return value;
}
