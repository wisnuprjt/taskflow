"use client";

import { useCallback, useEffect, useState, type FormEvent } from "react";
import { useToast } from "@/components/providers/ToastProvider";
import { Button } from "@/components/ui/Button";
import { Textarea } from "@/components/ui/Field";
import { Spinner } from "@/components/ui/Spinner";
import { ErrorState } from "@/components/ui/States";
import { api, ApiError } from "@/lib/api";
import { formatDate } from "@/lib/format";
import type { Comment } from "@/lib/types";

export function CommentSection({ taskId }: { taskId: number }) {
  const toast = useToast();
  const [comments, setComments] = useState<Comment[] | null>(null);
  const [error, setError] = useState("");
  const [text, setText] = useState("");
  const [posting, setPosting] = useState(false);

  const load = useCallback(() => {
    api<{ data: Comment[] }>(`/tasks/${taskId}/comments`)
      .then((res) => {
        setComments(res.data);
        setError("");
      })
      .catch((err: ApiError) => setError(err.message));
  }, [taskId]);

  useEffect(load, [load]);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    if (!text.trim()) return;
    setPosting(true);
    try {
      const res = await api<{ data: Comment }>(`/tasks/${taskId}/comments`, { method: "POST", body: { comment: text.trim() } });
      setComments((all) => [...(all ?? []), res.data]);
      setText("");
    } catch (err) {
      const apiErr = err as ApiError;
      toast(apiErr.errors?.comment?.[0] ?? apiErr.message, "error");
    } finally {
      setPosting(false);
    }
  }

  return (
    <div className="space-y-4">
      {error ? (
        <ErrorState message={error} onRetry={load} />
      ) : comments === null ? (
        <Spinner className="h-5 w-5 text-indigo-600" />
      ) : comments.length === 0 ? (
        <p className="text-sm text-slate-500">No comments yet. Start the conversation.</p>
      ) : (
        <ul className="space-y-3">
          {comments.map((c) => (
            <li key={c.id} className="rounded-lg border border-slate-200 bg-white p-3">
              <div className="flex items-baseline justify-between gap-2 text-xs">
                <span className="font-medium text-slate-800">{c.user?.name ?? "Unknown"}</span>
                <span className="text-slate-400">{formatDate(c.created_at, true)}</span>
              </div>
              <p className="mt-1 text-sm whitespace-pre-line text-slate-700">{c.comment}</p>
            </li>
          ))}
        </ul>
      )}

      <form onSubmit={onSubmit} className="space-y-2">
        <Textarea rows={3} maxLength={2000} placeholder="Write a comment…" aria-label="Comment" value={text} onChange={(e) => setText(e.target.value)} />
        <div className="flex justify-end">
          <Button type="submit" loading={posting} disabled={!text.trim()}>
            Post comment
          </Button>
        </div>
      </form>
    </div>
  );
}
