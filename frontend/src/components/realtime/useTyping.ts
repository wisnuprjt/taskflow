"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { getEcho } from "@/lib/echo";

interface Typist {
  id: number;
  name: string;
}

/** Hide someone's "typing…" if no new keystroke signal arrives within this time. */
const TYPING_TIMEOUT_MS = 3000;
/** Throttle outgoing signals: one whisper per this interval is enough, not one per keystroke. */
const SEND_EVERY_MS = 1500;

/**
 * "X is typing…" over client whispers on a presence channel. Whispers go browser -> Reverb -> browsers,
 * never through Laravel or the database, so they are cheap enough to send while typing.
 */
export function useTyping(channelName: string | null, me: Typist | null) {
  const [typing, setTyping] = useState<Typist[]>([]);
  const timers = useRef(new Map<number, ReturnType<typeof setTimeout>>());
  const lastSent = useRef(0);

  useEffect(() => {
    const echo = getEcho();
    if (!channelName || !echo) return;
    const channel = echo.join(channelName);
    const pending = timers.current;

    const remove = (id: number) => {
      clearTimeout(pending.get(id));
      pending.delete(id);
      setTyping((all) => all.filter((t) => t.id !== id));
    };
    const onTyping = (t: Typist) => {
      clearTimeout(pending.get(t.id));
      pending.set(t.id, setTimeout(() => remove(t.id), TYPING_TIMEOUT_MS));
      setTyping((all) => (all.some((x) => x.id === t.id) ? all : [...all, t]));
    };
    const onStopped = (t: Typist) => remove(t.id);

    channel.listenForWhisper("typing", onTyping).listenForWhisper("stopped-typing", onStopped);
    return () => {
      channel.stopListeningForWhisper("typing", onTyping).stopListeningForWhisper("stopped-typing", onStopped);
      pending.forEach(clearTimeout);
      pending.clear();
      setTyping([]);
    };
  }, [channelName]);

  const meId = me?.id;
  const meName = me?.name;

  /** Call on every keystroke; it is throttled internally. */
  const notifyTyping = useCallback(() => {
    if (!channelName || !meId || !meName || Date.now() - lastSent.current < SEND_EVERY_MS) return;
    lastSent.current = Date.now();
    getEcho()?.join(channelName).whisper("typing", { id: meId, name: meName });
  }, [channelName, meId, meName]);

  /** Call after sending (or clearing) the message so others stop seeing "typing…" immediately. */
  const notifyStopped = useCallback(() => {
    if (!channelName || !meId || !meName) return;
    lastSent.current = 0;
    getEcho()?.join(channelName).whisper("stopped-typing", { id: meId, name: meName });
  }, [channelName, meId, meName]);

  return { typing, notifyTyping, notifyStopped };
}
