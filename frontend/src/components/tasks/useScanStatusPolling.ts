"use client";

import { useEffect, useRef } from "react";
import { useToast } from "@/components/providers/ToastProvider";
import { api } from "@/lib/api";
import type { Attachment, Task } from "@/lib/types";

const INTERVAL_MS = 2000;

/**
 * While any attachment is still being virus-scanned, re-fetch the task every 2 s and report
 * each finished scan with a toast. Stops by itself once nothing is pending.
 */
export function useScanStatusPolling(taskId: number | undefined, attachments: Attachment[], onUpdate: (latest: Attachment[]) => void) {
  const toast = useToast();
  const onUpdateRef = useRef(onUpdate);
  useEffect(() => {
    onUpdateRef.current = onUpdate;
  });

  // A string key, so the effect only restarts when the set of pending files actually changes.
  const pendingKey = attachments
    .filter((a) => a.scan_status === "pending")
    .map((a) => a.id)
    .join(",");

  useEffect(() => {
    if (!taskId || !pendingKey) return;
    const pending = new Set(pendingKey.split(",").map(Number));

    const timer = setInterval(async () => {
      try {
        const res = await api<{ data: Task }>(`/tasks/${taskId}`);
        const latest = res.data.attachments ?? [];
        for (const a of latest) {
          if (!pending.has(a.id) || a.scan_status === "pending") continue;
          pending.delete(a.id); // toast once even if two requests overlap
          if (a.scan_status === "clean") toast(`${a.file_name} passed the virus scan.`);
          else toast(`${a.file_name} was quarantined by the virus scan.`, "error");
        }
        onUpdateRef.current(latest);
      } catch {
        // Transient failure: the next tick tries again.
      }
    }, INTERVAL_MS);

    return () => clearInterval(timer);
  }, [taskId, pendingKey, toast]);
}
