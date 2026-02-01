<?php

namespace Arpon\Support\Facades;

use Arpon\Support\Facades\Facade;

class Notification extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'notification.channel_manager';
    }
}
