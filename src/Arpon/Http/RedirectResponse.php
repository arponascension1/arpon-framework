<?php

namespace Arpon\Http;

use Arpon\Contracts\Session\SessionManager;

class RedirectResponse extends Response
{
    protected $session;
    protected $targetUrl;
    protected $request;

    public function __construct($targetUrl, $status = 302, $headers = [])
    {
        parent::__construct('', $status);
        $this->targetUrl = $targetUrl;
        $this->setHeader('Location', $targetUrl);
        
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }
    }

    public function with($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->flash($k, $v);
            }
        } else {
            $this->flash($key, $value);
        }

        return $this;
    }

    public function withInput(array $input = null)
    {
        $input = $input ?: request()->all();
        $this->session()->flashInput($input);

        return $this;
    }

    public function onlyInput($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $input = request()->only($keys);
        $this->session()->flashInput($input);

        return $this;
    }

    public function exceptInput($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $input = request()->except($keys);
        $this->session()->flashInput($input);

        return $this;
    }

    public function withErrors($errors, $key = 'default')
    {
        if ($errors instanceof \Arpon\Support\MessageBag) {
            $this->session()->flash('errors', $errors);
        } elseif (is_array($errors)) {
            $messageBag = new \Arpon\Support\MessageBag();
            foreach ($errors as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $messageBag->add($field, $message);
                }
            }
            $this->session()->flash('errors', $messageBag);
        } else {
            $messageBag = new \Arpon\Support\MessageBag();
            $messageBag->add($key, $errors);
            $this->session()->flash('errors', $messageBag);
        }

        return $this;
    }

    public function withSuccess($message)
    {
        return $this->with('success', $message);
    }

    public function withWarning($message)
    {
        return $this->with('warning', $message);
    }

    public function withInfo($message)
    {
        return $this->with('info', $message);
    }

    public function withError($message)
    {
        return $this->with('error', $message);
    }

    public function withCookie($cookie)
    {
        if ($cookie instanceof \Arpon\Cookie\Cookie) {
            app('cookie')->queue($cookie);
        }

        return $this;
    }

    public function getTargetUrl()
    {
        return $this->targetUrl;
    }

    public function setTargetUrl($url)
    {
        $this->targetUrl = $url;
        $this->setHeader('Location', $url);

        return $this;
    }

    protected function flash($key, $value)
    {
        $this->session()->flash($key, $value);
        return $this;
    }

    protected function session()
    {
        if (!$this->session) {
            $this->session = app('session');
        }

        return $this->session;
    }

    public function setSession(SessionManager $session)
    {
        $this->session = $session;
        return $this;
    }
}
