"use client";

import { usePresence } from "./usePresence";

/** Header badge: how many users are signed in right now; hover shows who. */
export function OnlineUsers() {
  const members = usePresence("online");
  if (members.length === 0) return null;

  return (
    <span
      className="hidden rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 sm:inline"
      title={members.map((m) => `${m.name} (${m.role})`).join("\n")}
    >
      {members.length} online
    </span>
  );
}
