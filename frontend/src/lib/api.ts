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
export function uploadFile<T>(
  path: string,
  file: Blob,
  onProgress: (percent: number) => void,
  fields: Record<string, string> = {},
): Promise<T> {
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
    Object.entries(fields).forEach(([key, value]) => form.append(key, value));
    form.append("file", file);
    xhr.send(form);
  });
}

const CHUNK_RETRIES = 3;

/**
 * Files above the single-request limit go up in server-sized chunks (init -> chunks -> complete).
 * A failed chunk is retried on its own instead of restarting the whole file.
 */
export async function uploadChunked<T>(taskId: number, file: File, onProgress: (percent: number) => void): Promise<T> {
  const init = await api<{ upload_id: string; chunk_size: number; total_chunks: number }>(`/tasks/${taskId}/attachments/chunked`, {
    method: "POST",
    body: { file_name: file.name, file_size: file.size },
  });
  const base = `/uploads/${init.upload_id}`;

  try {
    for (let index = 0; index < init.total_chunks; index++) {
      const start = index * init.chunk_size;
      const chunk = file.slice(start, start + init.chunk_size);
      const report = (percent: number) => onProgress(Math.round(((start + (chunk.size * percent) / 100) / file.size) * 100));

      for (let attempt = 1; ; attempt++) {
        try {
          await uploadFile(`${base}/chunks`, chunk, report, { index: String(index) });
          break;
        } catch (err) {
          // Only network errors (status 0) are worth retrying; validation/auth errors will not change.
          if ((err as ApiError).status !== 0 || attempt === CHUNK_RETRIES) throw err;
        }
      }
    }
    return await api<T>(`${base}/complete`, { method: "POST" });
  } catch (err) {
    api(base, { method: "DELETE" }).catch(() => undefined); // best-effort cleanup of stored chunks
    throw err;
  }
}

/** Private files need the Bearer header, so they are fetched as blobs (not plain <a>/<img> URLs). */
export async function fetchBlob(url: string): Promise<Blob> {
  const token = tokenStore.get();
  const res = await fetch(url, { headers: token ? { Authorization: `Bearer ${token}` } : {} });
  if (res.status === 401) handleUnauthorized();
  if (!res.ok) throw toApiError(res.status, await res.json().catch(() => null));
  return res.blob();
}

export async function downloadFile(url: string, fileName: string): Promise<void> {
  const href = URL.createObjectURL(await fetchBlob(url));
  const a = Object.assign(document.createElement("a"), { href, download: fileName });
  a.click();
  URL.revokeObjectURL(href);
}
