<?php

namespace Arpon\Notifications\Channels;

use Arpon\Contracts\Notifications\Channel;
use Arpon\Mail\Mailer;
use Arpon\Notifications\MailMessage;

class MailChannel implements Channel
{
    protected $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Arpon\Notifications\Notification  $notification
     * @return mixed
     */
    public function send($notifiable, $notification)
    {
        $message = $notification->toMail($notifiable);

        if (!$message) {
            return;
        }

        // If it's a MailMessage, render it first
        if ($message instanceof MailMessage) {
            $html = $message->render();
            $this->mailer->to($notifiable->routeNotificationFor('mail'))->html($html, function ($mailable) use ($message) {
                $mailable->subject($message->getSubject());
            });
        } else {
            // Handle regular Mailable
            $this->mailer->to($notifiable->routeNotificationFor('mail'))->send($message);
        }
    }
}
