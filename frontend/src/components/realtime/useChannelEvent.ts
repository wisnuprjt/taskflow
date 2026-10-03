"use client";

import { useEffect, useRef } from "react";
import { joinPrivate, leavePrivate } from "@/lib/echo";

/**
 * Listens for one broadcast event on a private channel while the component is mounted.
 * `event` is the backend's broadcastAs() name with a leading dot, e.g. ".comment.created".
 * Pass `channel = null` to skip (e.g. while the id is not known yet).
 */
export function useChannelEvent<T>(channel: string | null, event: string, handler: (payload: T) => void) {
  // Latest handler without re-subscribing on every render.
  const handlerRef = useRef(handler);
  useEffect(() => {
    handlerRef.current = handler;
  });

  useEffect(() => {
    if (!channel) return;
    const subscription = joinPrivate(channel);
    if (!subscription) return; // realtime not configured: the page still works, just not live

    const listener = (payload: T) => handlerRef.current(payload);
    subscription.listen(event, listener);
    return () => {
      subscription.stopListening(event, listener);
      leavePrivate(channel);
    };
  }, [channel, event]);
}
