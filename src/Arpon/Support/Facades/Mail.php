<?php

namespace Arpon\Support\Facades;

use Arpon\Contracts\Mail\Mailable;
use Arpon\Mail\Mailer;


/**
 * @method static Mailer to(string $address, string|null $name = null)
 * @method static Mailer cc(string $address, string|null $name = null)
 * @method static Mailer bcc(string $address, string|null $name = null)
 * @method static Mailer subject(string $subject)
 * @method static Mailer from(string $address, string|null $name = null)
 * @method static Mailer replyTo(string $address, string|null $name = null)
 * @method static Mailer attach(string $file, array $options = [])
 * @method static Mailer attachData(string $data, string $name, array $options = [])
 * @method static int send(Mailable|string $mailable, array $data = [], callable|null $callback = null)
 * @method static int raw(string $text, callable $callback)
 * @method static int html(string $html, callable $callback)
 * 
 * @see \Arpon\Mail\Mailer
 */
class Mail extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mailer';
    }
}
