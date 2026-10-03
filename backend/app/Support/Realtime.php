<?php

namespace App\Support;

/**
 * Realtime is a nice-to-have on top of the REST API: if Reverb is down, the change the user made
 * must still be saved. So broadcast failures are reported to the log instead of failing the request.
 */
class Realtime
{
    public static function broadcast(object $event): void
    {
        rescue(function () use ($event) {
            // Statement form on purpose: PendingBroadcast sends on destruct, which must happen inside rescue().
            broadcast($event);
        });
    }
}
