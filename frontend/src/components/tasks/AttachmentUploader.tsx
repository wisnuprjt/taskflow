"use client";

import { useRef, useState, type DragEvent } from "react";
import { useToast } from "@/components/providers/ToastProvider";
import { ApiError, uploadChunked, uploadFile } from "@/lib/api";
import type { Attachment } from "@/lib/types";

// Mirrors backend StoreAttachmentRequest for instant feedback; the server remains the source of truth.
const ALLOWED = ["jpg", "jpeg", "png", "webp", "pdf", "doc", "docx", "xlsx", "txt", "mp4", "webm"];
/** Single-request limit; larger files are sent in chunks up to CHUNKED_MAX_BYTES. */
const MAX_BYTES = 20 * 1024 * 1024;
const CHUNKED_MAX_BYTES = 500 * 1024 * 1024;

interface Upload {
  id: number;
  name: string;
  progress: number;
  error?: string;
}

export function AttachmentUploader({ taskId, onUploaded }: { taskId: number; onUploaded: (a: Attachment) => void }) {
  const toast = useToast();
  const inputRef = useRef<HTMLInputElement>(null);
  const [dragging, setDragging] = useState(false);
  const [uploads, setUploads] = useState<Upload[]>([]);

  const patch = (id: number, data: Partial<Upload>) => setUploads((all) => all.map((u) => (u.id === id ? { ...u, ...data } : u)));

  function validate(file: File): string | null {
    const ext = file.name.split(".").pop()?.toLowerCase() ?? "";
    if (!ALLOWED.includes(ext)) return `File type .${ext} is not allowed.`;
    if (file.size > CHUNKED_MAX_BYTES) return "File exceeds the 500 MB limit.";
    return null;
  }

  async function upload(file: File) {
    const id = Date.now() + Math.random();
    const error = validate(file);
    setUploads((all) => [...all, { id, name: file.name, progress: 0, error: error ?? undefined }]);
    if (error) return;

    try {
      const onProgress = (progress: number) => patch(id, { progress });
      const res =
        file.size > MAX_BYTES
          ? await uploadChunked<{ data: Attachment }>(taskId, file, onProgress)
          : await uploadFile<{ data: Attachment }>(`/tasks/${taskId}/attachments`, file, onProgress);
      onUploaded(res.data);
      toast(`Uploaded ${file.name}`);
      setUploads((all) => all.filter((u) => u.id !== id));
    } catch (err) {
      const apiErr = err as ApiError;
      patch(id, { error: Object.values(apiErr.errors ?? {})[0]?.[0] ?? apiErr.message });
    }
  }

  function handleFiles(files: FileList | null) {
    Array.from(files ?? []).forEach(upload);
  }

  function onDrop(e: DragEvent) {
    e.preventDefault();
    setDragging(false);
    handleFiles(e.dataTransfer.files);
  }

  return (
    <div className="space-y-3">
      <div
        role="button"
        tabIndex={0}
        onClick={() => inputRef.current?.click()}
        onKeyDown={(e) => (e.key === "Enter" || e.key === " ") && inputRef.current?.click()}
        onDragOver={(e) => {
          e.preventDefault();
          setDragging(true);
        }}
        onDragLeave={() => setDragging(false)}
        onDrop={onDrop}
        className={`cursor-pointer rounded-lg border-2 border-dashed px-4 py-8 text-center text-sm transition-colors ${
          dragging ? "border-indigo-500 bg-indigo-50 text-indigo-700" : "border-slate-300 text-slate-500 hover:border-slate-400"
        }`}
      >
        <p className="font-medium">Drag & drop files here, or click to browse</p>
        <p className="mt-1 text-xs">Images, PDF, Office docs, TXT, MP4/WebM · max 500 MB (files over 20 MB upload in chunks)</p>
        <input
          ref={inputRef}
          type="file"
          multiple
          hidden
          accept={ALLOWED.map((e) => `.${e}`).join(",")}
          onChange={(e) => {
            handleFiles(e.target.files);
            e.target.value = "";
          }}
        />
      </div>

      {uploads.map((u) => (
        <div key={u.id} className="rounded-md border border-slate-200 bg-white p-3 text-sm">
          <div className="flex items-center justify-between gap-2">
            <span className="truncate">{u.name}</span>
            {u.error ? (
              <button onClick={() => setUploads((all) => all.filter((x) => x.id !== u.id))} className="text-slate-400 hover:text-slate-600" aria-label="Dismiss">
                ✕
              </button>
            ) : (
              <span className="text-xs text-slate-500">{u.progress}%</span>
            )}
          </div>
          {u.error ? (
            <p className="mt-1 text-xs text-red-600">{u.error}</p>
          ) : (
            <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow={u.progress} aria-valuemin={0} aria-valuemax={100}>
              <div className="h-full bg-indigo-600 transition-all" style={{ width: `${u.progress}%` }} />
            </div>
          )}
        </div>
      ))}
    </div>
  );
}
