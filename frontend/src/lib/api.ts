const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";
const TOKEN_KEY = "token";

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public errors?: Record<string, string[]>,
  ) {
    super(message);
  }
}

export const tokenStore = {
  get: () => (typeof window === "undefined" ? null : localStorage.getItem(TOKEN_KEY)),
  set: (token: string) => localStorage.setItem(TOKEN_KEY, token),
  clear: () => localStorage.removeItem(TOKEN_KEY),
};

/** Session expired or invalid token: drop it and hard-redirect to /login (also resets all in-memory state). */
function handleUnauthorized() {
  tokenStore.clear();
  if (window.location.pathname !== "/login") window.location.replace("/login");
}

function toApiError(status: number, body: unknown): ApiError {
  const b = (body ?? {}) as { message?: string; errors?: Record<string, string[]> };
  return new ApiError(status, b.message ?? "Something went wrong.", b.errors);
}

type Query = Record<string, string | number | null | undefined>;

export async function api<T>(path: string, options: { method?: string; body?: unknown; query?: Query } = {}): Promise<T> {
  const url = new URL(API_URL + path);
  Object.entries(options.query ?? {}).forEach(([k, v]) => {
    if (v !== null && v !== undefined && v !== "") url.searchParams.set(k, String(v));
  });

  const token = tokenStore.get();
  let res: Response;
  try {
    res = await fetch(url, {
      method: options.method ?? "GET",
      headers: {
        Accept: "application/json",
        ...(options.body !== undefined && { "Content-Type": "application/json" }),
        ...(token && { Authorization: `Bearer ${token}` }),
      },
      body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
    });
  } catch {
    throw new ApiError(0, "Cannot reach the server. Is the API running?");
  }

  if (res.status === 204) return undefined as T;
  const data = await res.json().catch(() => null);

  if (!res.ok) {
    if (res.status === 401 && path !== "/auth/login") handleUnauthorized();
    throw toApiError(res.status, data);
  }
  return data as T;
}

/** Multipart upload via XHR so we can report upload progress (fetch cannot). */
export function uploadFile<T>(path: string, file: File, onProgress: (percent: number) => void): Promise<T> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", API_URL + path);
    xhr.setRequestHeader("Accept", "application/json");
    const token = tokenStore.get();
    if (token) xhr.setRequestHeader("Authorization", `Bearer ${token}`);

    xhr.upload.onprogress = (e) => e.lengthComputable && onProgress(Math.round((e.loaded / e.total) * 100));
    xhr.onerror = () => reject(new ApiError(0, "Upload failed: network error."));
    xhr.onload = () => {
      const data = (() => {
        try {
          return JSON.parse(xhr.responseText);
        } catch {
          return null;
        }
      })();
      if (xhr.status >= 200 && xhr.status < 300) return resolve(data as T);
      if (xhr.status === 401) handleUnauthorized();
      if (xhr.status === 413) return reject(new ApiError(413, "File is too large for the server."));
      reject(toApiError(xhr.status, data));
    };

    const form = new FormData();
    form.append("file", file);
    xhr.send(form);
  });
}

/** Downloads need the Bearer header, so fetch as a blob and trigger a save. */
export async function downloadFile(url: string, fileName: string): Promise<void> {
  const token = tokenStore.get();
  const res = await fetch(url, { headers: token ? { Authorization: `Bearer ${token}` } : {} });
  if (res.status === 401) handleUnauthorized();
  if (!res.ok) throw toApiError(res.status, await res.json().catch(() => null));

  const href = URL.createObjectURL(await res.blob());
  const a = Object.assign(document.createElement("a"), { href, download: fileName });
  a.click();
  URL.revokeObjectURL(href);
}
