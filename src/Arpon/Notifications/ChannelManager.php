<?php

namespace Arpon\Notifications;

use Arpon\Contracts\Notifications\Channel;

class ChannelManager
{
    protected $app;
    protected $channels = [];
    protected $defaultChannel = 'database';

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Send the given notification to the given notifiable entities.
     *
     * @param  mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiables, $notification)
    {
        $notifiables = $this->formatNotifiables($notifiables);

        foreach ($notifiables as $notifiable) {
            $this->sendToNotifiable($notifiable, $notification);
        }
    }

    /**
     * Send the given notification immediately.
     *
     * @param  mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function sendNow($notifiables, $notification)
    {
        $notifiables = $this->formatNotifiables($notifiables);

        foreach ($notifiables as $notifiable) {
            $this->sendToNotifiable($notifiable, $notification, true);
        }
    }

    /**
     * Send notification to the given notifiable.
     *
     * @param  mixed  $notifiable
     * @param  mixed  $notification
     * @param  bool  $shouldQueue
     * @return void
     */
    protected function sendToNotifiable($notifiable, $notification, $shouldQueue = false)
    {
        $channels = $notification->via($notifiable);

        if (!is_array($channels)) {
            $channels = [$channels];
        }

        foreach ($channels as $channel) {
            if (!$notification->shouldSend($notifiable, $channel)) {
                continue;
            }

            $driver = $this->driver($channel);

            if ($driver instanceof Channel) {
                $driver->send($notifiable, $notification);
            }
        }
    }

    /**
     * Get a channel instance.
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function driver($name = null)
    {
        $name = $name ?: $this->getDefaultChannel();

        if (!isset($this->channels[$name])) {
            $this->channels[$name] = $this->createDriver($name);
        }

        return $this->channels[$name];
    }

    /**
     * Create a new driver instance.
     *
     * @param  string  $name
     * @return mixed
     */
    protected function createDriver($name)
    {
        $method = 'create'.ucfirst($name).'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        $key = "notification.driver.{$name}";

        if ($this->app->bound($key)) {
            return $this->app[$key];
        }

        throw new \InvalidArgumentException("Channel [{$name}] not found.");
    }

    /**
     * Create the database driver.
     *
     * @return \Arpon\Notifications\Channels\DatabaseChannel
     */
    protected function createDatabaseDriver()
    {
        return $this->app['notification.driver.database'];
    }

    /**
     * Create the mail driver.
     *
     * @return \Arpon\Notifications\Channels\MailChannel
     */
    protected function createMailDriver()
    {
        return $this->app['notification.driver.mail'];
    }

    /**
     * Get the default channel name.
     *
     * @return string
     */
    public function getDefaultChannel()
    {
        return $this->defaultChannel;
    }

    /**
     * Set the default channel name.
     *
     * @param  string  $channel
     * @return void
     */
    public function setDefaultChannel($channel)
    {
        $this->defaultChannel = $channel;
    }

    /**
     * Format the notifiables into a Collection / array.
     *
     * @param  mixed  $notifiables
     * @return array
     */
    protected function formatNotifiables($notifiables)
    {
        if (!is_array($notifiables) && !$notifiables instanceof \Traversable) {
            return [$notifiables];
        }

        return $notifiables;
    }
}
