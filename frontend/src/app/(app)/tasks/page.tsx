"use client";

import { useCallback, useEffect, useState } from "react";
import { useAuth } from "@/components/providers/AuthProvider";
import { useToast } from "@/components/providers/ToastProvider";
import { useChannelEvent } from "@/components/realtime/useChannelEvent";
import { DeleteTaskDialog } from "@/components/tasks/DeleteTaskDialog";
import { TaskFilters, type Filters } from "@/components/tasks/TaskFilters";
import { TaskFormModal } from "@/components/tasks/TaskFormModal";
import { TaskList } from "@/components/tasks/TaskList";
import { Button } from "@/components/ui/Button";
import { Pagination } from "@/components/ui/Pagination";
import { PageLoader } from "@/components/ui/Spinner";
import { EmptyState, ErrorState } from "@/components/ui/States";
import { api, ApiError } from "@/lib/api";
import type { Paginated, Task, TaskChangedEvent } from "@/lib/types";

const PER_PAGE = 10;
const DEFAULT_FILTERS: Filters = { search: "", status: "", priority: "", sort: "-created_at" };

export default function TasksPage() {
  const [filters, setFilters] = useState<Filters>(DEFAULT_FILTERS);
  const [search, setSearch] = useState(""); // debounced copy of filters.search
  const [page, setPage] = useState(1);
  const [result, setResult] = useState<Paginated<Task> | null>(null);
  const [error, setError] = useState("");
  const [reloads, setReloads] = useState(0);
  const [settledKey, setSettledKey] = useState(""); // key of the last request that finished
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<Task | null>(null);
  const [deleting, setDeleting] = useState<Task | null>(null);

  useEffect(() => {
    const id = setTimeout(() => setSearch(filters.search.trim()), 350);
    return () => clearTimeout(id);
  }, [filters.search]);

  const { status, priority, sort } = filters;
  const { user } = useAuth();

  // The role is part of the key: permissions (`can`) in the response change with it.
  const requestKey = JSON.stringify([page, search, status, priority, sort, reloads, user?.role]);
  const loading = settledKey !== requestKey;
  const reload = useCallback(() => setReloads((n) => n + 1), []);

  // Someone (maybe in another tab) changed a task: re-run the current query so filters, sorting,
  // pagination and per-user permissions all stay correct. Only other people's changes get a toast.
  const toast = useToast();
  const onTaskChanged = (verb: string) => (e: TaskChangedEvent) => {
    reload();
    if (e.actor.id !== user?.id) toast(`${e.actor.name} ${verb} "${e.title}"`);
  };
  useChannelEvent("tasks", ".task.created", onTaskChanged("created"));
  useChannelEvent("tasks", ".task.updated", onTaskChanged("updated"));
  useChannelEvent("tasks", ".task.deleted", onTaskChanged("deleted"));

  useEffect(() => {
    let stale = false; // ignore responses that arrive after the query changed
    api<Paginated<Task>>("/tasks", { query: { page, per_page: PER_PAGE, search, status, priority, sort } })
      .then((res) => {
        if (stale) return;
        // Deleting the last item of the last page would leave us on an empty page.
        if (res.data.length === 0 && page > 1) return setPage(page - 1);
        setResult(res);
        setError("");
      })
      .catch((err: ApiError) => !stale && setError(err.message))
      .finally(() => !stale && setSettledKey(requestKey));
    return () => {
      stale = true;
    };
  }, [requestKey, page, search, status, priority, sort]);

  function onFiltersChange(next: Filters) {
    setFilters(next);
    setPage(1);
  }

  function openForm(task: Task | null) {
    setEditing(task);
    setFormOpen(true);
  }

  const hasFilters = search || status || priority;

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">Tasks</h1>
          {result && <p className="text-sm text-slate-500">{result.meta.total} total</p>}
        </div>
        <Button onClick={() => openForm(null)}>+ New task</Button>
      </div>

      <TaskFilters value={filters} onChange={onFiltersChange} />

      {loading && !result ? (
        <PageLoader />
      ) : error ? (
        <ErrorState message={error} onRetry={reload} />
      ) : result && result.data.length === 0 ? (
        <EmptyState title={hasFilters ? "No tasks match your filters" : "No tasks yet"}>
          {hasFilters ? (
            <button className="text-indigo-600 hover:underline" onClick={() => onFiltersChange(DEFAULT_FILTERS)}>
              Clear filters
            </button>
          ) : (
            "Create your first task to get started."
          )}
        </EmptyState>
      ) : (
        result && (
          <div className={`space-y-4 transition-opacity ${loading ? "opacity-60" : ""}`}>
            <TaskList tasks={result.data} onEdit={openForm} onDelete={setDeleting} />
            <Pagination meta={result.meta} onPage={setPage} />
          </div>
        )
      )}

      <TaskFormModal open={formOpen} task={editing} onClose={() => setFormOpen(false)} onSaved={reload} />
      <DeleteTaskDialog task={deleting} onClose={() => setDeleting(null)} onDeleted={reload} />
    </div>
  );
}
