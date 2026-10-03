"use client";

import { useEffect, useState } from "react";
import { fetchBlob } from "@/lib/api";
import type { Attachment } from "@/lib/types";

const TYPE_STYLES: Record<string, string> = {
  PDF: "bg-red-50 text-red-700",
  DOC: "bg-blue-50 text-blue-700",
  DOCX: "bg-blue-50 text-blue-700",
  XLSX: "bg-emerald-50 text-emerald-700",
  MP4: "bg-violet-50 text-violet-700",
  WEBM: "bg-violet-50 text-violet-700",
};

const BOX = "flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border border-slate-200";

/** Small preview: the image itself for image files, a coloured extension badge otherwise. */
export function AttachmentThumb({ attachment }: { attachment: Attachment }) {
  const isImage = attachment.mime_type.startsWith("image/") && attachment.scan_status === "clean";
  const [src, setSrc] = useState<string | null>(null);

  useEffect(() => {
    if (!isImage) return;
    let url: string | null = null;
    let cancelled = false;
    // Prefer the small server thumbnail; fall back to the original right after upload.
    fetchBlob(attachment.thumbnail_url ?? attachment.download_url)
      .then((blob) => {
        if (cancelled) return;
        url = URL.createObjectURL(blob);
        setSrc(url);
      })
      .catch(() => undefined); // fall back to the badge
    return () => {
      cancelled = true;
      if (url) URL.revokeObjectURL(url);
    };
  }, [isImage, attachment.thumbnail_url, attachment.download_url]);

  if (isImage && src) {
    // eslint-disable-next-line @next/next/no-img-element -- blob: URL, next/image cannot optimise it
    return <img src={src} alt={attachment.file_name} className={`${BOX} object-cover`} />;
  }

  const ext = attachment.file_name.split(".").pop()?.toUpperCase() ?? "FILE";
  return (
    <div className={`${BOX} text-[10px] font-semibold ${isImage ? "animate-pulse bg-slate-100 text-slate-400" : TYPE_STYLES[ext] ?? "bg-slate-50 text-slate-600"}`}>
      {isImage ? "" : ext.slice(0, 4)}
    </div>
  );
}
