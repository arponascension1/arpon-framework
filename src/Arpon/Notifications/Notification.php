<?php

namespace Arpon\Notifications;

use Arpon\Notifications\Channels\MailChannel;
use Arpon\Notifications\Channels\DatabaseChannel;
use Arpon\Mail\Mailable;

abstract class Notification
{
    /**
     * The notification's channels.
     *
     * @var array
     */
    public $via = [];

    /**
     * The notification's ID.
     *
     * @var string|null
     */
    public $id;

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    abstract public function via($notifiable);

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Arpon\Mail\Mailable
     */
    public function toMail($notifiable)
    {
        throw new \Exception('Mail channel not supported for this notification.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [];
    }

    /**
     * Get the database representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return $this->toArray($notifiable);
    }

    /**
     * Determine if the notification should be sent.
     *
     * @param  mixed  $notifiable
     * @param  string  $channel
     * @return bool
     */
    public function shouldSend($notifiable, $channel)
    {
        return true;
    }

    /**
     * Get the unique ID for the notification.
     *
     * @return string
     */
    public function id()
    {
        if ($this->id) {
            return $this->id;
        }

        // Generate a unique ID using uniqid with more entropy
        return uniqid('notif_', true);
    }

    /**
     * Send the given notification to the given notifiable entities.
     *
     * @param  array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public static function send($notifiables, $notification)
    {
        return app('notification.channel_manager')->send($notifiables, $notification);
    }

    /**
     * Send the given notification immediately.
     *
     * @param  |array|mixed  $notifiable
     * @param  mixed  $notification
     * @return void
     */
    public static function sendNow($notifiables, $notification)
    {
        return app('notification.channel_manager')->sendNow($notifiables, $notification);
    }
}
