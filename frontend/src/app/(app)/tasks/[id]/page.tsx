"use client";

import { useCallback, useEffect, useState, type ReactNode } from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { AttachmentList } from "@/components/tasks/AttachmentList";
import { AttachmentUploader } from "@/components/tasks/AttachmentUploader";
import { CommentSection } from "@/components/tasks/CommentSection";
import { DeleteTaskDialog } from "@/components/tasks/DeleteTaskDialog";
import { TaskFormModal } from "@/components/tasks/TaskFormModal";
import { PriorityBadge, StatusBadge } from "@/components/ui/Badges";
import { Button } from "@/components/ui/Button";
import { PageLoader } from "@/components/ui/Spinner";
import { ErrorState } from "@/components/ui/States";
import { api, ApiError } from "@/lib/api";
import { formatDate } from "@/lib/format";
import type { Task } from "@/lib/types";

function Section({ title, children }: { title: string; children: ReactNode }) {
  return (
    <section className="space-y-3">
      <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-500">{title}</h2>
      {children}
    </section>
  );
}

export default function TaskDetailPage() {
  const { id } = useParams<{ id: string }>();
  const router = useRouter();
  const [task, setTask] = useState<Task | null>(null);
  const [error, setError] = useState("");
  const [editOpen, setEditOpen] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState<Task | null>(null);

  const load = useCallback(() => {
    api<{ data: Task }>(`/tasks/${id}`)
      .then((res) => {
        setTask(res.data);
        setError("");
      })
      .catch((err: ApiError) => setError(err.message));
  }, [id]);

  useEffect(load, [load]);

  if (error) return <ErrorState message={error} onRetry={load} />;
  if (!task) return <PageLoader />;

  const attachments = task.attachments ?? [];

  return (
    <div className="space-y-8">
      <div>
        <Link href="/tasks" className="text-sm text-slate-500 hover:text-slate-700">
          ← Back to tasks
        </Link>
        <div className="mt-3 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold break-words">{task.title}</h1>
            <div className="mt-2 flex flex-wrap gap-2">
              <StatusBadge status={task.status} />
              <PriorityBadge priority={task.priority} />
            </div>
          </div>
          <div className="flex gap-2">
            {task.can.update && (
              <Button variant="secondary" onClick={() => setEditOpen(true)}>
                Edit
              </Button>
            )}
            {task.can.delete && (
              <Button variant="danger" onClick={() => setDeleteTarget(task)}>
                Delete
              </Button>
            )}
          </div>
        </div>
      </div>

      <div className="grid gap-8 lg:grid-cols-3">
        <div className="space-y-8 lg:col-span-2">
          <Section title="Description">
            <p className="text-sm whitespace-pre-line text-slate-700">{task.description || "No description."}</p>
          </Section>

          <Section title={`Attachments (${attachments.length})`}>
            {task.can.update && (
              <AttachmentUploader taskId={task.id} onUploaded={(a) => setTask((t) => t && { ...t, attachments: [a, ...(t.attachments ?? [])] })} />
            )}
            <AttachmentList
              attachments={attachments}
              canDelete={task.can.update}
              onDeleted={(aid) => setTask((t) => t && { ...t, attachments: t.attachments?.filter((a) => a.id !== aid) })}
            />
          </Section>

          <Section title="Comments">
            <CommentSection taskId={task.id} />
          </Section>
        </div>

        <aside className="h-fit rounded-lg border border-slate-200 bg-white p-4 text-sm">
          <dl className="space-y-3">
            {[
              ["Assignee", task.assignee?.name ?? "Unassigned"],
              ["Created by", task.creator?.name ?? "—"],
              ["Due date", formatDate(task.due_date)],
              ["Created", formatDate(task.created_at, true)],
              ["Updated", formatDate(task.updated_at, true)],
            ].map(([label, value]) => (
              <div key={label} className="flex justify-between gap-4">
                <dt className="text-slate-500">{label}</dt>
                <dd className="text-right font-medium text-slate-800">{value}</dd>
              </div>
            ))}
          </dl>
        </aside>
      </div>

      <TaskFormModal open={editOpen} task={task} onClose={() => setEditOpen(false)} onSaved={load} />
      <DeleteTaskDialog task={deleteTarget} onClose={() => setDeleteTarget(null)} onDeleted={() => router.replace("/tasks")} />
    </div>
  );
}
