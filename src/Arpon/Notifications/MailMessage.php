<?php

namespace Arpon\Notifications;

use Arpon\Mail\Mailable;

class MailMessage extends Mailable
{
    /**
     * The view for the message.
     *
     * @var string
     */
    public $view;

    /**
     * The view data for the message.
     *
     * @var array
     */
    public $viewData = [];

    /**
     * The "level" of the notification (info, success, error).
     *
     * @var string
     */
    public $level = 'info';

    /**
     * The main "action" for the message.
     *
     * @var array
     */
    public $action;

    /**
     * The optional explanation for the message.
     *
     * @var string
     */
    public $introLines = [];

    /**
     * The optional "outro" lines for the message.
     *
     * @var array
     */
    public $outroLines = [];

    /**
     * The action URL for the message.
     *
     * @var string|null
     */
    public $actionUrl;

    /**
     * Create a new mail message instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->view = 'notifications::email';
        $this->viewData = [
            'level' => $this->level,
            'actionText' => null,
            'actionUrl' => null,
            'introLines' => $this->introLines,
            'outroLines' => $this->outroLines,
            'greeting' => null,
            'salutation' => null,
        ];
    }

    /**
     * Set the subject for the message.
     *
     * @param  string  $subject
     * @return $this
     */
    public function subject($subject)
    {
        $this->subject = $subject;
        $this->viewData['subject'] = $subject;

        return $this;
    }

    /**
     * Set the "level" of the notification (info, success, error).
     *
     * @param  string  $level
     * @return $this
     */
    public function level($level)
    {
        $this->level = $level;
        $this->viewData['level'] = $level;

        return $this;
    }

    /**
     * Set the greeting for the message.
     *
     * @param  string  $greeting
     * @return $this
     */
    public function greeting($greeting)
    {
        $this->viewData['greeting'] = $greeting;

        return $this;
    }

    /**
     * Add a line of text to the notification.
     *
     * @param  string|array  $line
     * @return $this
     */
    public function line($line)
    {
        $this->introLines[] = $line;
        $this->viewData['introLines'] = $this->introLines;

        return $this;
    }

    /**
     * Add multiple lines of text to the notification.
     *
     * @param  array  $lines
     * @return $this
     */
    public function lines(array $lines)
    {
        foreach ($lines as $line) {
            $this->line($line);
        }

        return $this;
    }

    /**
     * Add a line of text to the notification.
     *
     * @param  string  $line
     * @return $this
     */
    public function introLine($line)
    {
        return $this->line($line);
    }

    /**
     * Add a line of text to the notification.
     *
     * @param  string  $line
     * @return $this
     */
    public function outroLine($line)
    {
        $this->outroLines[] = $line;
        $this->viewData['outroLines'] = $this->outroLines;

        return $this;
    }

    /**
     * Configure the "call to action" button.
     *
     * @param  string  $text
     * @param  string  $url
     * @return $this
     */
    public function action($text, $url)
    {
        $this->action = ['text' => $text, 'url' => $url];
        $this->actionUrl = $url;
        $this->viewData['actionText'] = $text;
        $this->viewData['actionUrl'] = $url;

        return $this;
    }

    /**
     * Add a salutation to the message.
     *
     * @param  string  $salutation
     * @return $this
     */
    public function salutation($salutation)
    {
        $this->viewData['salutation'] = $salutation;

        return $this;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this;
    }

    /**
     * Render the message content.
     *
     * @return string
     */
    public function render()
    {
        return app('view')->make($this->view, $this->viewData)->render();
    }
}
