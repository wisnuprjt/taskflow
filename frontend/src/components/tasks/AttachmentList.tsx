"use client";

import { useState } from "react";
import { useToast } from "@/components/providers/ToastProvider";
import { ConfirmDialog } from "@/components/ui/Modal";
import { api, ApiError, downloadFile } from "@/lib/api";
import { formatBytes, formatDate } from "@/lib/format";
import type { Attachment } from "@/lib/types";

interface Props {
  attachments: Attachment[];
  canDelete: boolean;
  onDeleted: (id: number) => void;
}

export function AttachmentList({ attachments, canDelete, onDeleted }: Props) {
  const toast = useToast();
  const [target, setTarget] = useState<Attachment | null>(null);
  const [busy, setBusy] = useState(false);

  async function download(a: Attachment) {
    try {
      await downloadFile(a.download_url, a.file_name);
    } catch (err) {
      toast((err as ApiError).message, "error");
    }
  }

  async function confirmDelete() {
    if (!target) return;
    setBusy(true);
    try {
      await api(`/attachments/${target.id}`, { method: "DELETE" });
      onDeleted(target.id);
      toast("Attachment deleted.");
      setTarget(null);
    } catch (err) {
      toast((err as ApiError).message, "error");
    } finally {
      setBusy(false);
    }
  }

  if (attachments.length === 0) return <p className="text-sm text-slate-500">No attachments yet.</p>;

  return (
    <>
      <ul className="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white">
        {attachments.map((a) => (
          <li key={a.id} className="flex items-center justify-between gap-3 px-4 py-3 text-sm">
            <div className="min-w-0">
              <p className="truncate font-medium text-slate-800">{a.file_name}</p>
              <p className="text-xs text-slate-500">
                {formatBytes(a.file_size)} · {formatDate(a.uploaded_at, true)}
              </p>
            </div>
            <div className="flex shrink-0 gap-3">
              <button onClick={() => download(a)} className="font-medium text-indigo-600 hover:text-indigo-800">
                Download
              </button>
              {canDelete && (
                <button onClick={() => setTarget(a)} className="font-medium text-red-600 hover:text-red-800">
                  Delete
                </button>
              )}
            </div>
          </li>
        ))}
      </ul>
      <ConfirmDialog
        open={target !== null}
        title="Delete attachment"
        message={
          <>
            Delete <strong>{target?.file_name}</strong>? This cannot be undone.
          </>
        }
        loading={busy}
        onConfirm={confirmDelete}
        onClose={() => setTarget(null)}
      />
    </>
  );
}
