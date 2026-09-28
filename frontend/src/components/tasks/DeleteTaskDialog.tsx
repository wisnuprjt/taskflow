"use client";

import { useState } from "react";
import { useToast } from "@/components/providers/ToastProvider";
import { ConfirmDialog } from "@/components/ui/Modal";
import { api, ApiError } from "@/lib/api";
import type { Task } from "@/lib/types";

export function DeleteTaskDialog({ task, onClose, onDeleted }: { task: Task | null; onClose: () => void; onDeleted: (task: Task) => void }) {
  const toast = useToast();
  const [deleting, setDeleting] = useState(false);

  async function onConfirm() {
    if (!task) return;
    setDeleting(true);
    try {
      await api(`/tasks/${task.id}`, { method: "DELETE" });
      toast("Task deleted.");
      onDeleted(task);
      onClose();
    } catch (err) {
      toast((err as ApiError).message, "error");
    } finally {
      setDeleting(false);
    }
  }

  return (
    <ConfirmDialog
      open={task !== null}
      title="Delete task"
      message={
        <>
          Delete <strong>{task?.title}</strong>? Its attachments and comments will be removed too. This cannot be undone.
        </>
      }
      loading={deleting}
      onConfirm={onConfirm}
      onClose={onClose}
    />
  );
}
