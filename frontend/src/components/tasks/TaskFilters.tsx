"use client";

import { Input, Select } from "@/components/ui/Field";
import { PRIORITY_LABELS, STATUS_LABELS } from "@/lib/types";

export interface Filters {
  search: string;
  status: string;
  priority: string;
  sort: string;
}

const SORTS: Record<string, string> = {
  "-created_at": "Newest",
  created_at: "Oldest",
  due_date: "Due date (soonest)",
  "-due_date": "Due date (latest)",
  "-priority": "Priority (high first)",
  priority: "Priority (low first)",
  title: "Title (A–Z)",
  "-title": "Title (Z–A)",
};

export function TaskFilters({ value, onChange }: { value: Filters; onChange: (f: Filters) => void }) {
  const set = (key: keyof Filters, v: string) => onChange({ ...value, [key]: v });

  return (
    <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
      <Input type="search" placeholder="Search title…" aria-label="Search" value={value.search} onChange={(e) => set("search", e.target.value)} className="col-span-2 md:col-span-1" />
      <Select aria-label="Status" value={value.status} onChange={(e) => set("status", e.target.value)}>
        <option value="">All statuses</option>
        {Object.entries(STATUS_LABELS).map(([v, l]) => (
          <option key={v} value={v}>
            {l}
          </option>
        ))}
      </Select>
      <Select aria-label="Priority" value={value.priority} onChange={(e) => set("priority", e.target.value)}>
        <option value="">All priorities</option>
        {Object.entries(PRIORITY_LABELS).map(([v, l]) => (
          <option key={v} value={v}>
            {l}
          </option>
        ))}
      </Select>
      <Select aria-label="Sort" value={value.sort} onChange={(e) => set("sort", e.target.value)} className="col-span-2 md:col-span-1">
        {Object.entries(SORTS).map(([v, l]) => (
          <option key={v} value={v}>
            {l}
          </option>
        ))}
      </Select>
    </div>
  );
}
