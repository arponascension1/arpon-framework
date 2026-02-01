<?php

namespace Arpon\Notifications\Channels;

use Arpon\Contracts\Notifications\Channels\Channel;

class DatabaseChannel implements Channel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Arpon\Notifications\Notification  $notification
     * @return mixed
     */
    public function send($notifiable, $notification)
    {
        return $notifiable->routeNotificationFor('database')->notifications()->create([
            'id' => $notification->id(),
            'type' => get_class($notification),
            'data' => $notification->toDatabase($notifiable),
            'read_at' => null,
        ]);
    }
}
