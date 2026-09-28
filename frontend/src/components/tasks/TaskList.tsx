import Link from "next/link";
import { PriorityBadge, StatusBadge } from "@/components/ui/Badges";
import { formatDate } from "@/lib/format";
import type { Task } from "@/lib/types";

interface Props {
  tasks: Task[];
  onEdit: (task: Task) => void;
  onDelete: (task: Task) => void;
}

function Actions({ task, onEdit, onDelete }: { task: Task } & Omit<Props, "tasks">) {
  return (
    <div className="flex justify-end gap-3 text-sm">
      {task.can.update && (
        <button onClick={() => onEdit(task)} className="font-medium text-indigo-600 hover:text-indigo-800">
          Edit
        </button>
      )}
      {task.can.delete && (
        <button onClick={() => onDelete(task)} className="font-medium text-red-600 hover:text-red-800">
          Delete
        </button>
      )}
    </div>
  );
}

/** Table on desktop, stacked cards on mobile. */
export function TaskList({ tasks, onEdit, onDelete }: Props) {
  return (
    <>
      <div className="hidden overflow-hidden rounded-lg border border-slate-200 bg-white md:block">
        <table className="w-full text-left text-sm">
          <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th className="px-4 py-3">Title</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Priority</th>
              <th className="px-4 py-3">Assignee</th>
              <th className="px-4 py-3">Due</th>
              <th className="px-4 py-3" />
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {tasks.map((task) => (
              <tr key={task.id} className="hover:bg-slate-50">
                <td className="px-4 py-3">
                  <Link href={`/tasks/${task.id}`} className="font-medium text-slate-900 hover:text-indigo-600">
                    {task.title}
                  </Link>
                  <p className="text-xs text-slate-400">
                    {task.comments_count ?? 0} comments · {task.attachments_count ?? 0} files
                  </p>
                </td>
                <td className="px-4 py-3">
                  <StatusBadge status={task.status} />
                </td>
                <td className="px-4 py-3">
                  <PriorityBadge priority={task.priority} />
                </td>
                <td className="px-4 py-3 text-slate-600">{task.assignee?.name ?? "—"}</td>
                <td className="px-4 py-3 whitespace-nowrap text-slate-600">{formatDate(task.due_date)}</td>
                <td className="px-4 py-3">
                  <Actions task={task} onEdit={onEdit} onDelete={onDelete} />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <ul className="space-y-3 md:hidden">
        {tasks.map((task) => (
          <li key={task.id} className="rounded-lg border border-slate-200 bg-white p-4">
            <Link href={`/tasks/${task.id}`} className="font-medium text-slate-900">
              {task.title}
            </Link>
            <div className="mt-2 flex flex-wrap gap-2">
              <StatusBadge status={task.status} />
              <PriorityBadge priority={task.priority} />
            </div>
            <p className="mt-2 text-xs text-slate-500">
              {task.assignee?.name ?? "Unassigned"} · Due {formatDate(task.due_date)}
            </p>
            <div className="mt-3 border-t border-slate-100 pt-3">
              <Actions task={task} onEdit={onEdit} onDelete={onDelete} />
            </div>
          </li>
        ))}
      </ul>
    </>
  );
}
