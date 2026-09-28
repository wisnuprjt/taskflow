"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useToast } from "@/components/providers/ToastProvider";
import { Button } from "@/components/ui/Button";
import { Field, Input, Select, Textarea } from "@/components/ui/Field";
import { Modal } from "@/components/ui/Modal";
import { api, ApiError } from "@/lib/api";
import { firstErrors } from "@/lib/format";
import { PRIORITY_LABELS, STATUS_LABELS, type Task, type TaskInput, type User } from "@/lib/types";

interface Props {
  open: boolean;
  task?: Task | null; // edit mode when provided
  onClose: () => void;
  onSaved: (task: Task) => void;
}

export function TaskFormModal({ open, task, onClose, onSaved }: Props) {
  // Modal unmounts its children when closed, so the form starts fresh on every open.
  return (
    <Modal open={open} title={task ? "Edit task" : "New task"} onClose={onClose}>
      <TaskForm task={task} onClose={onClose} onSaved={onSaved} />
    </Modal>
  );
}

function initialForm(task?: Task | null): TaskInput {
  return {
    title: task?.title ?? "",
    description: task?.description ?? "",
    status: task?.status ?? "todo",
    priority: task?.priority ?? "medium",
    due_date: task?.due_date ?? "",
    assigned_user_id: task?.assigned_user_id ?? null,
  };
}

function TaskForm({ task, onClose, onSaved }: Omit<Props, "open">) {
  const toast = useToast();
  const [form, setForm] = useState<TaskInput>(() => initialForm(task));
  const [users, setUsers] = useState<User[]>([]);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    api<{ data: User[] }>("/users")
      .then((res) => setUsers(res.data))
      .catch(() => undefined);
  }, []);

  const set = <K extends keyof TaskInput>(key: K, value: TaskInput[K]) => setForm((f) => ({ ...f, [key]: value }));

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    const body = { ...form, due_date: form.due_date || null, description: form.description || null };
    try {
      const res = task
        ? await api<{ data: Task }>(`/tasks/${task.id}`, { method: "PUT", body })
        : await api<{ data: Task }>("/tasks", { method: "POST", body });
      toast(task ? "Task updated." : "Task created.");
      onSaved(res.data);
      onClose();
    } catch (err) {
      const apiErr = err as ApiError;
      setErrors(firstErrors(apiErr.errors));
      if (!apiErr.errors) toast(apiErr.message, "error");
    } finally {
      setSaving(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="space-y-4">
      <Field label="Title" error={errors.title}>
        <Input required maxLength={255} value={form.title ?? ""} onChange={(e) => set("title", e.target.value)} autoFocus />
      </Field>
      <Field label="Description" error={errors.description}>
        <Textarea rows={3} value={form.description ?? ""} onChange={(e) => set("description", e.target.value)} />
      </Field>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field label="Status" error={errors.status}>
          <Select value={form.status} onChange={(e) => set("status", e.target.value as TaskInput["status"])}>
            {Object.entries(STATUS_LABELS).map(([v, l]) => (
              <option key={v} value={v}>
                {l}
              </option>
            ))}
          </Select>
        </Field>
        <Field label="Priority" error={errors.priority}>
          <Select value={form.priority} onChange={(e) => set("priority", e.target.value as TaskInput["priority"])}>
            {Object.entries(PRIORITY_LABELS).map(([v, l]) => (
              <option key={v} value={v}>
                {l}
              </option>
            ))}
          </Select>
        </Field>
        <Field label="Assignee" error={errors.assigned_user_id}>
          <Select value={form.assigned_user_id ?? ""} onChange={(e) => set("assigned_user_id", e.target.value ? Number(e.target.value) : null)}>
            <option value="">Unassigned</option>
            {users.map((u) => (
              <option key={u.id} value={u.id}>
                {u.name}
              </option>
            ))}
          </Select>
        </Field>
        <Field label="Due date" error={errors.due_date}>
          <Input type="date" value={form.due_date ?? ""} onChange={(e) => set("due_date", e.target.value)} />
        </Field>
      </div>
      <div className="flex justify-end gap-2 pt-2">
        <Button type="button" variant="secondary" onClick={onClose} disabled={saving}>
          Cancel
        </Button>
        <Button type="submit" loading={saving}>
          {task ? "Save changes" : "Create task"}
        </Button>
      </div>
    </form>
  );
}
