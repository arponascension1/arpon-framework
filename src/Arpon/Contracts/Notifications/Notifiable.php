<?php

namespace Arpon\Contracts\Notifications;

interface Notifiable
{
    /**
     * Get the notification routing information for the given driver.
     *
     * @param  string  $driver
     * @return mixed
     */
    public function routeNotificationFor($driver);

    /**
     * Get the entity's notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function notifications();

    /**
     * Get the entity's read notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function readNotifications();

    /**
     * Get the entity's unread notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function unreadNotifications();
}
