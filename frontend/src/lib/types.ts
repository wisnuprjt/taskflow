export type Role = "admin" | "member";
export type TaskStatus = "todo" | "in_progress" | "done";
export type TaskPriority = "low" | "medium" | "high";

export interface User {
  id: number;
  name: string;
  email: string;
  role: Role;
}

export interface Attachment {
  id: number;
  task_id: number;
  file_name: string;
  file_size: number;
  mime_type: string;
  scan_status: "pending" | "clean" | "infected";
  download_url: string;
  /** Server-generated preview; null for non-images or while the job is still running. */
  thumbnail_url: string | null;
  uploaded_at: string;
}

/** Broadcast on `tasks` and `task.{id}`; carries no task data, clients re-fetch with their own token. */
export interface TaskChangedEvent {
  task_id: number;
  title: string;
  actor: { id: number; name: string };
}

/** Broadcast on `task.{id}` whenever one of its attachments changes. */
export interface AttachmentChangedEvent {
  action: "uploaded" | "deleted" | "scanned" | "thumbnail_ready";
  attachment_id: number;
  file_name: string;
  scan_status: Attachment["scan_status"];
}

export interface Task {
  id: number;
  title: string;
  description: string | null;
  status: TaskStatus;
  priority: TaskPriority;
  due_date: string | null;
  assigned_user_id: number | null;
  created_by: number;
  assignee?: User | null;
  creator?: User;
  attachments_count?: number;
  comments_count?: number;
  attachments?: Attachment[];
  can: { update: boolean; delete: boolean };
  created_at: string;
  updated_at: string;
}

export interface Comment {
  id: number;
  task_id: number;
  comment: string;
  user?: User;
  created_at: string;
}

export interface Paginated<T> {
  data: T[];
  meta: { current_page: number; last_page: number; per_page: number; total: number; from: number | null; to: number | null };
}

export type TaskInput = Partial<Pick<Task, "title" | "description" | "status" | "priority" | "due_date" | "assigned_user_id">>;

export const STATUS_LABELS: Record<TaskStatus, string> = { todo: "To do", in_progress: "In progress", done: "Done" };
export const PRIORITY_LABELS: Record<TaskPriority, string> = { low: "Low", medium: "Medium", high: "High" };
