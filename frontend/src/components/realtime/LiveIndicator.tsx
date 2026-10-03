"use client";

import { useEffect, useState } from "react";
import { getEcho } from "@/lib/echo";

type State = "connecting" | "connected" | "unavailable" | "disconnected" | "failed" | "initialized";

const STYLE: Record<"live" | "pending" | "offline", { dot: string; label: string }> = {
  live: { dot: "bg-emerald-500", label: "Live" },
  pending: { dot: "animate-pulse bg-amber-400", label: "Connecting" },
  offline: { dot: "bg-slate-300", label: "Offline" },
};

/** Small header badge showing whether the realtime (WebSocket) connection is up. */
export function LiveIndicator() {
  const [state, setState] = useState<State>("initialized");

  useEffect(() => {
    const connection = getEcho()?.connector.pusher.connection;
    if (!connection) return;
    const onChange = ({ current }: { current: State }) => setState(current);
    connection.bind("state_change", onChange);
    // Sync with a connection that was already open before this component mounted.
    queueMicrotask(() => setState(connection.state as State));
    return () => {
      connection.unbind("state_change", onChange);
    };
  }, []);

  const style = state === "connected" ? STYLE.live : state === "connecting" || state === "initialized" ? STYLE.pending : STYLE.offline;

  return (
    <span className="flex items-center gap-1.5 text-xs text-slate-500" title={`Realtime: ${state}`}>
      <span className={`h-2 w-2 rounded-full ${style.dot}`} />
      <span className="hidden sm:inline">{style.label}</span>
    </span>
  );
}
