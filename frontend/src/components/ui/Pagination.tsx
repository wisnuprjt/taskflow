import type { Paginated } from "@/lib/types";
import { Button } from "./Button";

export function Pagination({ meta, onPage }: { meta: Paginated<unknown>["meta"]; onPage: (page: number) => void }) {
  if (meta.total === 0) return null;

  return (
    <div className="flex flex-col items-center justify-between gap-3 text-sm text-slate-600 sm:flex-row">
      <p>
        Showing {meta.from}–{meta.to} of {meta.total}
      </p>
      <div className="flex items-center gap-2">
        <Button variant="secondary" disabled={meta.current_page <= 1} onClick={() => onPage(meta.current_page - 1)}>
          Previous
        </Button>
        <span className="px-2">
          {meta.current_page} / {meta.last_page}
        </span>
        <Button variant="secondary" disabled={meta.current_page >= meta.last_page} onClick={() => onPage(meta.current_page + 1)}>
          Next
        </Button>
      </div>
    </div>
  );
}
