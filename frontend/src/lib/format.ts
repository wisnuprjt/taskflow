export function formatDate(value: string | null | undefined, withTime = false): string {
  if (!value) return "—";
  return new Date(value).toLocaleString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    ...(withTime && { hour: "2-digit", minute: "2-digit" }),
  });
}

export function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 ** 2) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / 1024 ** 2).toFixed(1)} MB`;
}

/** Flatten Laravel 422 `errors` to a field -> first message map. */
export function firstErrors(errors?: Record<string, string[]>): Record<string, string> {
  return Object.fromEntries(Object.entries(errors ?? {}).map(([k, v]) => [k, v[0]]));
}
