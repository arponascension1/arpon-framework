<?php

namespace Arpon\Notifications;

trait Notifiable
{
    /**
     * Get the notification routing information for the given driver.
     *
     * @param  string  $driver
     * @return mixed
     */
    public function routeNotificationFor($driver)
    {
        if (method_exists($this, $method = 'routeNotificationFor'.ucfirst($driver))) {
            return $this->$method();
        }

        switch ($driver) {
            case 'database':
                return $this;
            case 'mail':
                return $this->email;
            default:
                return null;
        }
    }

    /**
     * Get the entity's notifications.
     *
     * @return \Arpon\Database\Eloquent\Relations\MorphMany
     */
    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Get the entity's read notifications.
     *
     * @return \Arpon\Database\Eloquent\Relations\MorphMany
     */
    public function readNotifications()
    {
        return $this->notifications()
                    ->whereNotNull('read_at');
    }

    /**
     * Get the entity's unread notifications.
     *
     * @return \Arpon\Database\Eloquent\Relations\MorphMany
     */
    public function unreadNotifications()
    {
        return $this->notifications()
                    ->whereNull('read_at');
    }

    /**
     * Send the given notification.
     *
     * @param  \Arpon\Notifications\Notification  $notification
     * @return void
     */
    public function notify($notification)
    {
        app('notification.channel_manager')->send($this, $notification);
    }

    /**
     * Send the given notification immediately.
     *
     * @param  \Arpon\Notifications\Notification  $notification
     * @return void
     */
    public function notifyNow($notification)
    {
        app('notification.channel_manager')->sendNow($this, $notification);
    }

    /**
     * Send the given notifications.
     *
     * @param  \Arpon\Notifications\Notification[]  $notifications
     * @return void
     */
    public function notifyMany($notifications)
    {
        foreach ($notifications as $notification) {
            $this->notify($notification);
        }
    }

    /**
     * Mark the notification as read.
     *
     * @param  string|null  $notificationId
     * @return void
     */
    public function markAsRead($notificationId = null)
    {
        if ($notificationId) {
            $this->unreadNotifications()
                ->where('id', $notificationId)
                ->update(['read_at' => now()]);
        } else {
            $this->unreadNotifications()->update(['read_at' => now()]);
        }
    }

    /**
     * Mark the notification as unread.
     *
     * @param  string|null  $notificationId
     * @return void
     */
    public function markAsUnread($notificationId = null)
    {
        if ($notificationId) {
            $this->readNotifications()
                ->where('id', $notificationId)
                ->update(['read_at' => null]);
        } else {
            $this->readNotifications()->update(['read_at' => null]);
        }
    }
}
