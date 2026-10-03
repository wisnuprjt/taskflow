"use client";

import Echo from "laravel-echo";
import Pusher from "pusher-js";
import { api } from "./api";

let instance: Echo<"reverb"> | null = null;

/**
 * One shared WebSocket connection to Laravel Reverb, opened on first use (browser only).
 * Returns null when Reverb is not configured, so the app keeps working without realtime.
 */
export function getEcho(): Echo<"reverb"> | null {
  const key = process.env.NEXT_PUBLIC_REVERB_APP_KEY;
  if (typeof window === "undefined" || !key) return null;

  if (!instance) {
    const port = Number(process.env.NEXT_PUBLIC_REVERB_PORT ?? 8080);
    instance = new Echo({
      broadcaster: "reverb",
      key,
      wsHost: process.env.NEXT_PUBLIC_REVERB_HOST ?? "localhost",
      wsPort: port,
      wssPort: port,
      forceTLS: process.env.NEXT_PUBLIC_REVERB_SCHEME === "https",
      enabledTransports: ["ws", "wss"],
      Pusher,
      // Private/presence channels are authorised by the API with the JWT, read on every request
      // so it always follows the current login.
      channelAuthorization: {
        customHandler: ({ socketId, channelName }: { socketId: string; channelName: string }, callback: (error: Error | null, data: never) => void) => {
          api<never>("/broadcasting/auth", { method: "POST", body: { socket_id: socketId, channel_name: channelName } })
            .then((data) => callback(null, data))
            .catch((err: Error) => callback(err, null as never));
        },
      },
    });
  }
  return instance;
}

/** How many components currently use each private channel (several can share one, e.g. comments + attachments). */
const channelRefs = new Map<string, number>();

/** Joins a private channel; pair every call with leavePrivate() so the channel is left once nobody uses it. */
export function joinPrivate(name: string) {
  const echo = getEcho();
  if (!echo) return null;
  channelRefs.set(name, (channelRefs.get(name) ?? 0) + 1);
  return echo.private(name);
}

export function leavePrivate(name: string) {
  const remaining = (channelRefs.get(name) ?? 1) - 1;
  if (remaining > 0) {
    channelRefs.set(name, remaining);
    return;
  }
  channelRefs.delete(name);
  instance?.leave(name);
}

/** Closes the socket on logout so the next user starts with a fresh, correctly authorised connection. */
export function disconnectEcho() {
  instance?.disconnect();
  instance = null;
  channelRefs.clear();
}
