"use client";

import { useEffect, useState } from "react";
import { getEcho } from "@/lib/echo";

/** What the backend returns for a member in routes/channels.php. */
export interface PresenceMember {
  id: number;
  name: string;
  role: string;
}

/**
 * Joins a presence channel and keeps the list of members currently in it.
 * The same user in several tabs counts once (members are keyed by user id).
 * Use one usePresence per channel name; other hooks may share the channel (e.g. useTyping).
 */
export function usePresence(name: string | null): PresenceMember[] {
  const [members, setMembers] = useState<PresenceMember[]>([]);

  useEffect(() => {
    const echo = getEcho();
    if (!name || !echo) return;

    echo
      .join(name)
      .here((list: PresenceMember[]) => setMembers(list))
      .joining((m: PresenceMember) => setMembers((all) => (all.some((x) => x.id === m.id) ? all : [...all, m])))
      .leaving((m: PresenceMember) => setMembers((all) => all.filter((x) => x.id !== m.id)));

    return () => {
      echo.leave(name);
      setMembers([]);
    };
  }, [name]);

  return members;
}
