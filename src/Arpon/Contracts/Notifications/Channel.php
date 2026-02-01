<?php

namespace Arpon\Contracts\Notifications;

use Arpon\Notifications\Notification;

interface Channel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  Notification  $notification
     * @return mixed
     */
    public function send($notifiable, $notification);
}