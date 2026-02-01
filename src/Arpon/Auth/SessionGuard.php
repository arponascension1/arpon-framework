<?php

namespace Arpon\Auth;

use Arpon\Contracts\Auth\Authenticatable;
use Arpon\Contracts\Auth\StatefulGuard;
use Arpon\Contracts\Auth\UserProvider;
use Arpon\Contracts\Session\SessionStore;

class SessionGuard implements StatefulGuard
{
    /**
     * The currently authenticated user.
     *
     * @var \Arpon\Contracts\Auth\Authenticatable|null
     */
    protected $user;

    /**
     * The user provider implementation.
     *
     * @var \Arpon\Contracts\Auth\UserProvider
     */
    protected $provider;

    /**
     * The session store used by the guard.
     *
     * @var \Arpon\Session\Store
     */
    protected $session;

    /**
     * The request instance.
     *
     * @var \Arpon\Http\Request
     */
    protected $request;

    /**
     * Indicates if the logout method has been called.
     *
     * @var bool
     */
    protected $loggedOut = false;

    /**
     * Indicates if a token user retrieval has been attempted.
     *
     * @var bool
     */
    protected $recallAttempted = false;

    /**
     * The name of the session variable.
     *
     * @var string
     */
    protected $name;

    /**
     * The cookie jar instance.
     *
     * @var \Arpon\Cookie\CookieJar
     */
    protected $cookie;

    /**
     * The event dispatcher instance.
     *
     * @var \Arpon\Events\Dispatcher
     */
    protected $events;

    /**
     * Indicates if the user was authenticated via a remember me cookie.
     *
     * @var bool
     */
    protected $viaRemember = false;

    /**
     * The user we last attempted to retrieve.
     *
     * @var \Arpon\Contracts\Auth\Authenticatable
     */
    protected $lastAttempted;

    /**
     * Create a new authentication guard.
     *
     * @param  string  $name
     * @param  \Arpon\Contracts\Auth\UserProvider  $provider
     * @param  \Arpon\Contracts\Session\SessionStore  $session
     * @return void
     */
    public function __construct($name, UserProvider $provider, SessionStore $session)
    {
        $this->name = $name;
        $this->session = $session;
        $this->provider = $provider;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Arpon\Contracts\Auth\Authenticatable|null
     */
    /**
     * Get the currently authenticated user.
     *
     * @return \App\Models\User|\Arpon\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if ($this->loggedOut) {
            return null;
        }

        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        if (! is_null($this->user)) {
            return $this->user;
        }

        $id = $this->session->get($this->getName());

        // First we will try to load the user using the identifier in the session if
        // one exists. Otherwise we will check for a "remember me" cookie in this
        // request, and if one exists, attempt to retrieve the user using that.
        if (! is_null($id) && ! $this->loggedOut) {
            $this->user = $this->provider->retrieveById($id);
        }

        // If the user is null, but we have a remember me cookie, we'll try to
        // retrieve the user using that cookie token. This allows us to keep the
        // user logged in even when their session has expired.
        if (is_null($this->user) && ! is_null($recaller = $this->recaller())) {
            $this->user = $this->userFromRecaller($recaller);

            if ($this->user) {
                $this->updateSession($this->user->getAuthIdentifier());

                $this->fireLoginEvent($this->user, true);
            }
        }

        return $this->user;
    }

    /**
     * Log a user into the application.
     *
     * @param  \Arpon\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function login(Authenticatable $user, $remember = false)
    {
        $this->updateSession($user->getAuthIdentifier());

        // If the user should be permanently "remembered" by the application we will
        // create a permanent "remember me" cookie for this user, and we will also
        // store a new remember token for the user in the database.
        if ($remember) {
            $this->ensureRememberTokenIsSet($user);

            $this->queueRecallerCookie($user);
        }

        // If we have an event dispatcher instance set we will fire an event so that
        // any listeners will be aware that a user has signed in. This allows the
        // developer to do any additional work after a user has authenticated.
        $this->fireLoginEvent($user, $remember);

        $this->setUser($user);
    }

    /**
     * Log the given user ID into the application.
     *
     * @param  mixed  $id
     * @param  bool  $remember
     * @return \Arpon\Contracts\Auth\Authenticatable|null
     */
    public function loginUsingId($id, $remember = false)
    {
        if (! is_null($user = $this->provider->retrieveById($id))) {
            $this->login($user, $remember);

            return $user;
        }

        return null;
    }

    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function once(array $credentials = [])
    {
        $this->fireAttemptEvent($credentials);

        if ($this->validate($credentials)) {
            $this->setUser($this->lastAttempted);

            return true;
        }

        return false;
    }

    /**
     * Log the given user ID into the application without sessions or cookies.
     *
     * @param  mixed  $id
     * @return \Arpon\Contracts\Auth\Authenticatable|null
     */
    public function onceUsingId($id)
    {
        if (! is_null($user = $this->provider->retrieveById($id))) {
            $this->setUser($user);

            return $user;
        }

        return null;
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie.
     *
     * @return bool
     */
    public function viaRemember()
    {
        return $this->viaRemember;
    }

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout()
    {
        $user = $this->user();

        $this->clearUserDataFromStorage();

        // If we have an event dispatcher instance, we can fire off the logout event
        // so any further work can be done by a listener that needs to be notified
        // when a logged out user is logged out from this application.
        if (! is_null($this->user)) {
            $this->fireLogoutEvent($user);
        }

        // Once we have fired the logout event we will clear the user out of memory
        // so they are no longer available as the authenticated user. They will be
        // refreshed the next time a request is processed with their session.
        $this->user = null;

        $this->loggedOut = true;
    }

    /**
     * Invalidate the current user's session.
     *
     * @return void
     */
    public function invalidate()
    {
        $this->session->invalidate();

        $this->loggedOut = false;
    }

    /**
     * Register an authentication listener event.
     *
     * @param  string  $event
     * @param  \Closure  $callback
     * @return void
     */
    public function listen($event, $callback)
    {
        if ($this->events) {
            $this->events->listen($event, $callback);
        }
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return ! is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return ! $this->check();
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return mixed|null
     */
    public function id()
    {
        if ($this->user()) {
            return $this->user()->getAuthIdentifier();
        }

        return null;
    }

    /**
     * Set the current user.
     *
     * @param  \Arpon\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;

        $this->loggedOut = false;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            return true;
        }

        return false;
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        $this->fireAttemptEvent($credentials, $remember);

        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            $this->login($user, $remember);

            return true;
        }

        $this->fireFailedEvent($user, $credentials);

        return false;
    }

    /**
     * Determine if the user matches the credentials.
     *
     * @param  mixed  $user
     * @param  array  $credentials
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials)
    {
        return ! is_null($user) && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Update the session with the given ID.
     *
     * @param  string  $id
     * @return void
     */
    protected function updateSession($id)
    {
        $this->session->put($this->getName(), $id);

        $this->session->migrate(true);
    }

    /**
     * Get the session store used by the guard.
     *
     * @return \Arpon\Session\Store
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Get the cookie jar instance.
     *
     * @return \Arpon\Cookie\CookieJar
     */
    public function getCookieJar()
    {
        return $this->cookie;
    }

    /**
     * Set the cookie jar instance.
     *
     * @param  \Arpon\Cookie\CookieJar  $cookie
     * @return void
     */
    public function setCookieJar($cookie)
    {
        $this->cookie = $cookie;
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return \Arpon\Events\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Arpon\Events\Dispatcher  $events
     * @return void
     */
    public function setDispatcher($events)
    {
        $this->events = $events;
    }

    /**
     * Get the request instance.
     *
     * @return \Arpon\Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the request instance.
     *
     * @param  \Arpon\Http\Request  $request
     * @return void
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * Get the name of the session variable.
     *
     * @return string
     */
    protected function getName()
    {
        return 'login_'.$this->name.'_'.sha1(static::class);
    }

    /**
     * Get the name of the cookie used to store the "recaller".
     *
     * @return string
     */
    protected function getRecallerName()
    {
        return 'remember_'.$this->name.'_'.sha1(static::class);
    }

    /**
     * Get the recaller cookie value.
     *
     * @return string|null
     */
    protected function recaller()
    {
        if (is_null($this->request)) {
            return null;
        }

        return $this->request->cookie($this->getRecallerName());
    }

    /**
     * Pull a user from the repository based on the "remember me" cookie.
     *
     * @param  string  $recaller
     * @return mixed
     */
    protected function userFromRecaller($recaller)
    {
        if (! $this->validRecaller($recaller) || $this->recallAttempted) {
            return null;
        }

        $this->recallAttempted = true;

        $segments = explode('|', $recaller);

        $user = $this->provider->retrieveByToken($segments[0], $segments[1]);

        if ($user) {
            $this->viaRemember = true;
        }

        return $user;
    }

    /**
     * Determine if the recaller cookie is valid.
     *
     * @param  string  $recaller
     * @return bool
     */
    protected function validRecaller($recaller)
    {
        if (! is_string($recaller) || ! str_contains($recaller, '|')) {
            return false;
        }

        $segments = explode('|', $recaller);

        if (count($segments) !== 3) {
            return false;
        }

        return $segments[0] !== '' && $segments[1] !== '' && $segments[2] !== '';
    }

    /**
     * Ensure the remember token is set for the user.
     *
     * @param  \Arpon\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function ensureRememberTokenIsSet(Authenticatable $user)
    {
        if (empty($user->getRememberToken())) {
            $this->provider->updateRememberToken($user, $this->generateRememberToken($user));
        }
    }

    /**
     * Create a new "remember me" token for the user.
     *
     * @param  \Arpon\Contracts\Auth\Authenticatable  $user
     * @return string
     */
    protected function generateRememberToken($user)
    {
        return \str_random(60);
    }

    /**
     * Queue the recaller cookie into the cookie jar.
     *
     * @param  \Arpon\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function queueRecallerCookie(Authenticatable $user)
    {
        $value = $user->getAuthIdentifier().'|'.$user->getRememberToken().'|'.\str_random(16);
        $cookie = $this->cookie->make($this->getRecallerName(), $value, 2628000); // 5 years
        $this->cookie->queue($cookie);
    }

    /**
     * Remove the user data from the session and cookies.
     *
     * @return void
     */
    protected function clearUserDataFromStorage()
    {
        $this->session->remove($this->getName());

        if (! is_null($this->recaller())) {
            $this->cookie->queue($this->cookie->forget($this->getRecallerName()));
        }
    }

    /**
     * Fire the attempt event.
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @return void
     */
    protected function fireAttemptEvent(array $credentials, $remember = false)
    {
        if ($this->events) {
            $this->events->dispatch('auth.attempt', [$credentials, $remember]);
        }
    }

    /**
     * Fire the login event.
     *
     * @param  \Arpon\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    protected function fireLoginEvent($user, $remember = false)
    {
        if ($this->events) {
            $this->events->dispatch('auth.login', [$user, $remember]);
        }
    }

    /**
     * Fire the logout event.
     *
     * @param  \Arpon\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function fireLogoutEvent($user)
    {
        if ($this->events) {
            $this->events->dispatch('auth.logout', [$user]);
        }
    }

    /**
     * Fire the failed authentication event.
     *
     * @param  \Arpon\Contracts\Auth\Authenticatable|null  $user
     * @param  array  $credentials
     * @return void
     */
    protected function fireFailedEvent($user, array $credentials)
    {
        if ($this->events) {
            $this->events->dispatch('auth.failed', [$user, $credentials]);
        }
    }
}
