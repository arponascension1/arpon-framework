<?php

namespace Arpon\Mail;

use Arpon\Foundation\ServiceProvider;


class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerMailer();
        $this->registerTransportManager();
    }

    protected function registerMailer()
    {
        $this->app->singleton('mailer', function ($app) {
            $config = $app['config']['mail'];
            
            $mailer = new Mailer(
                $app['view'],
                $app['transport.manager'],
                $app['events']
            );
            
            $mailer->setFrom($config['from']['address'], $config['from']['name']);
            
            return $mailer;
        });
    }

    protected function registerTransportManager()
    {
        $this->app->singleton('transport.manager', function ($app) {
            return new TransportManager($app);
        });
    }
}
