<?php

namespace Arpon\Notifications;

use Arpon\Foundation\ServiceProvider;
use Arpon\Notifications\ChannelManager;
use Arpon\Support\Facades\Notification;

class NotificationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerChannelManager();
        $this->registerChannels();
        $this->registerViews();
    }

    protected function registerChannelManager()
    {
        $this->app->singleton('notification.channel_manager', function ($app) {
            return new ChannelManager($app);
        });

        $this->app->alias('notification.channel_manager', ChannelManager::class);
    }

    protected function registerChannels()
    {
        $this->app->singleton('notification.driver.database', function ($app) {
            return new Channels\DatabaseChannel;
        });

        $this->app->singleton('notification.driver.mail', function ($app) {
            return new Channels\MailChannel($app['mailer']);
        });
    }

    protected function registerViews()
    {
        // Register notification views path
        $this->app['view']->addNamespace(
            'notifications',
            __DIR__.'/resources/views'
        );

        $this->app['view']->addNamespace(
            'mail',
            __DIR__.'/resources/views/components'
        );
    }
}
