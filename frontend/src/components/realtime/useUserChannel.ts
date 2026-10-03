"use client";

import { useAuth } from "@/components/providers/AuthProvider";
import { useToast } from "@/components/providers/ToastProvider";
import type { User } from "@/lib/types";
import { useChannelEvent } from "./useChannelEvent";

/**
 * Events aimed at the signed-in user only, on their private channel (authorised by the API with the JWT).
 */
export function useUserChannel(userId: number | undefined) {
  const toast = useToast();
  const { updateUser } = useAuth();
  const channel = userId ? `App.Models.User.${userId}` : null;

  // Connectivity check: `php artisan realtime:ping {email}` sends this.
  useChannelEvent<{ message: string }>(channel, ".ping", (e) => toast(e.message));

  // `php artisan user:role {email} {role}`: the header updates at once, and pages that depend on
  // the role re-fetch so Edit/Delete buttons match the new permissions.
  useChannelEvent<{ user: User }>(channel, ".role.changed", (e) => {
    updateUser(e.user);
    toast(`Your role was changed to ${e.user.role}.`);
  });
}
