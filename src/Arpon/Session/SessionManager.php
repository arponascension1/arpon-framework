<?php

namespace Arpon\Session;

use Arpon\Contracts\Session\SessionManager as SessionManagerContract;
use Arpon\Contracts\Session\SessionStore;
use Arpon\Session\Handlers\CookieSessionHandler;
use Arpon\Session\Handlers\DatabaseSessionHandler;
use Arpon\Session\Handlers\FileSessionHandler;
use Arpon\Session\Handlers\ArraySessionHandler;
use Arpon\Session\Handlers\EncryptedSessionHandler;
use Arpon\Session\Store\Store;

class SessionManager implements SessionManagerContract
{
    protected $app;
    protected SessionStore $store;
    protected array $config;
    protected bool $started = false;
    protected bool $destroyed = false;

    public function __construct(\Arpon\Foundation\Application $app, array $config = [])
    {
        $this->app = $app;
        
        // Merge with defaults
        $this->config = array_merge([
            'driver' => 'file',
            'lifetime' => 120,
            'files' => $app->storagePath('framework/sessions'),
            'connection' => null,
            'table' => 'sessions',
            'cookie' => 'arpon_session',
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'http_only' => true,
            'same_site' => 'lax',
            'encrypt' => false,
            'lottery' => [2, 100],
        ], $config);
        
        // Normalize cookie configuration
        if (isset($this->config['cookie']) && is_string($this->config['cookie'])) {
            $this->config['cookie'] = [
                'name' => $this->config['cookie'],
                'path' => $this->config['path'] ?? '/',
                'domain' => $this->config['domain'] ?? null,
                'secure' => $this->config['secure'] ?? false,
                'http_only' => $this->config['http_only'] ?? true,
                'same_site' => $this->config['same_site'] ?? 'lax',
            ];
        }

        $this->store = $this->createStore();
    }

    public function start()
    {
        if ($this->started) {
            return true;
        }

        // Try to get session ID from request
        $sessionId = $this->getSessionIdFromRequest();
        
        // If no session ID from cookie, generate a new one
        if (!$sessionId) {
            $sessionId = $this->generateSessionId();
        }
        
        // Set the session ID before starting
        $this->store->setId($sessionId);

        // Initialize the session store (this loads the session data)
        $this->store->start();

        $this->started = true;

        return true;
    }

    protected function getSessionIdFromRequest()
    {
        // Try to get session ID from cookie
        $cookieName = $this->config['cookie']['name'] ?? 'arpon_session';
        
        if (isset($_COOKIE[$cookieName])) {
            return $_COOKIE[$cookieName];
        }
        
        return null;
    }

    public function getId()
    {
        $id = $this->store->getId();
        
        // Fallback to cookie if store doesn't have it
        if (!$id) {
            $id = $this->getSessionIdFromRequest();
        }
        
        return $id ?: '';
    }

    public function setId($id)
    {
        if ($this->started) {
            throw new \RuntimeException('Cannot set session ID after session has started.');
        }

        $this->store->setId($id);
        return $this;
    }

    protected function generateSessionId()
    {
        return bin2hex(random_bytes(16));
    }

    public function getName()
    {
        return session_name();
    }

    public function setName($name)
    {
        if ($this->started) {
            throw new \RuntimeException('Cannot set session name after session has started.');
        }

        session_name($name);
        return $this;
    }

    public function invalidate($lifetime = null)
    {
        $this->flush();
        $this->regenerate($destroy = true, $lifetime);

        return true;
    }

    public function regenerate($destroy = false, $lifetime = null)
    {
        if (!$this->started) {
            $this->start();
        }

        $currentId = $this->store->getId();
        $newId = $this->generateSessionId();

        if ($destroy) {
            $this->store->getHandler()->destroy($currentId);
        }

        $this->store->setId($newId);

        if ($lifetime !== null) {
            ini_set('session.cookie_lifetime', $lifetime);
        }

        return true;
    }

    public function save()
    {
        if (!$this->started) {
            return;
        }

        // Age flash data
        $this->ageFlashData();

        // Save using custom session store
        $this->store->save();

        // Set session cookie
        $this->setSessionCookie();

        // Don't set started to false - the session remains active for the rest of the request
    }

    protected function setSessionCookie()
    {
        $cookieConfig = $this->config['cookie'];
        $sessionId = $this->store->getId();

        $cookieName = $cookieConfig['name'] ?? 'arpon_session';
        $cookieOptions = [
            'expires' => 0, // Session cookie
            'path' => $cookieConfig['path'] ?? '/',
            'domain' => $cookieConfig['domain'] ?? null,
            'secure' => $cookieConfig['secure'] ?? false,
            'httponly' => $cookieConfig['http_only'] ?? true,
            'samesite' => $cookieConfig['same_site'] ?? 'lax',
        ];

        setcookie($cookieName, $sessionId, $cookieOptions);
    }

    public function forget($key)
    {
        $this->remove($key);
        return $this;
    }

    public function flush()
    {
        if (!$this->started) {
            $this->start();
        }

        $this->store->clear();
        return $this;
    }

    public function get($key, $default = null)
    {
        if (!$this->started) {
            $this->start();
        }

        return $this->store->get($key, $default);
    }

    public function put($key, $value = null)
    {
        if (!$this->started) {
            $this->start();
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->store->set($k, $v);
            }
        } else {
            $this->store->set($key, $value);
        }

        return $this;
    }

    public function has($key)
    {
        if (!$this->started) {
            $this->start();
        }

        return $this->store->has($key);
    }

    public function exists($key)
    {
        if (!$this->started) {
            $this->start();
        }

        return $this->store->exists($key);
    }

    public function pull($key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->forget($key);

        return $value;
    }

    public function push($key, $value)
    {
        if (!$this->started) {
            $this->start();
        }

        $array = $this->get($key, []);
        $array[] = $value;
        $this->put($key, $array);

        return $this;
    }

    public function increment($key, $amount = 1)
    {
        if (!$this->started) {
            $this->start();
        }

        $value = $this->get($key, 0) + $amount;
        $this->put($key, $value);

        return $value;
    }

    public function decrement($key, $amount = 1)
    {
        return $this->increment($key, -$amount);
    }

    public function flash($key, $value)
    {
        $this->put('_flash.new', array_merge($this->get('_flash.new', []), [$key]));
        $this->put($key, $value);

        return $this;
    }

    public function now($key, $value)
    {
        $this->put($key, $value);
        $this->push('_flash.old', $key);

        return $this;
    }

    public function keep($keys = null)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $new = $this->get('_flash.new', []);
        $old = $this->get('_flash.old', []);

        foreach ($keys as $key) {
            if (in_array($key, $old)) {
                $old = array_diff($old, [$key]);
            }
        }

        $this->put('_flash.old', $old);
        $this->put('_flash.new', array_unique(array_merge($new, $keys)));

        return $this;
    }

    public function reflash()
    {
        $new = $this->get('_flash.new', []);
        $old = $this->get('_flash.old', []);

        $this->put('_flash.new', array_unique(array_merge($new, $old)));
        $this->put('_flash.old', []);

        return $this;
    }

    public function flashInput(array $input)
    {
        $this->flash('_old_input', $input);
        return $this;
    }

    public function getOldInput($key = null, $default = null)
    {
        $input = $this->get('_old_input', []);

        if (is_null($key)) {
            return $input;
        }

        return $input[$key] ?? $default;
    }

    public function token()
    {
        // Check if token exists in cookie first (more reliable than session for now)
        $cookieToken = $_COOKIE['XSRF-TOKEN'] ?? null;
        
        if ($cookieToken && strlen($cookieToken) === 64) {
            return $cookieToken;
        }
        
        // Fall back to session
        if (!$this->has('_token')) {
            $this->regenerateToken();
        }

        $token = $this->get('_token');
        
        // Also store in cookie for reliability
        if (!$cookieToken || $cookieToken !== $token) {
            $cookieConfig = $this->config['cookie'];
            setcookie('XSRF-TOKEN', $token, [
                'expires' => time() + 7200, // 2 hours
                'path' => $cookieConfig['path'] ?? '/',
                'domain' => $cookieConfig['domain'] ?? null,
                'secure' => $cookieConfig['secure'] ?? false,
                'httponly' => false, // Allow JavaScript to read for AJAX
                'samesite' => $cookieConfig['same_site'] ?? 'Lax'
            ]);
        }
        
        return $token;
    }

    public function regenerateToken()
    {
        $newToken = bin2hex(random_bytes(32));
        $this->put('_token', $newToken);
        return $this;
    }

    public function isStarted()
    {
        return $this->started;
    }

    public function getHandler()
    {
        return $this->store->getHandler();
    }

    public function getStore()
    {
        return $this->store;
    }

    public function all()
    {
        if (!$this->started) {
            $this->start();
        }
        return $this->store->all();
    }

    /**
     * Get a subset of the session attributes.
     *
     * @param  array  $keys
     * @return array
     */
    public function only(array $keys)
    {
        $all = $this->all();
        $results = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $results[$key] = $all[$key];
            }
        }

        return $results;
    }

    /**
     * Get all session attributes except a specified array of keys.
     *
     * @param  array  $keys
     * @return array
     */
    public function except(array $keys)
    {
        $all = $this->all();

        foreach ($keys as $key) {
            unset($all[$key]);
        }

        return $all;
    }

    public function setStore(SessionStore $store)
    {
        $this->store = $store;
        return $this;
    }

    /**
     * Destroy the session.
     *
     * @return bool
     */
    public function destroy()
    {
        if ($this->started) {
            $this->store->destroy();
            $this->started = false;
            $this->destroyed = true;
        }
        
        return true;
    }

    /**
     * Check if the session is destroyed.
     *
     * @return bool
     */
    public function isDestroyed()
    {
        return $this->destroyed;
    }

    protected function createStore()
    {
        $handler = $this->createHandler();

        if ($this->config['encrypt'] ?? false) {
            $handler = new \Arpon\Session\Handlers\EncryptedSessionHandler($handler, $this->app->make('encrypter'));
        }

        return new Store($this->config['cookie']['name'] ?? $this->config['cookie'] ?? 'arpon_session', $handler);
    }

    protected function createHandler()
    {
        $driver = $this->config['driver'];

        switch ($driver) {
            case 'file':
                return new FileSessionHandler($this->config['files'], $this->config['lifetime']);
            case 'database':
                return new DatabaseSessionHandler(
                    $this->app->make('db'),
                    $this->config['table'] ?? 'sessions',
                    $this->config['lifetime']
                );
            case 'cookie':
                return new CookieSessionHandler($this->config['cookie']);
            case 'array':
                return new ArraySessionHandler();
            default:
                throw new \InvalidArgumentException("Unsupported session driver: {$driver}");
        }
    }

    protected function configureSession()
    {
        $cookieConfig = $this->config['cookie'] ?? [];

        session_set_cookie_params([
            'lifetime' => ($this->config['lifetime'] ?? 120) * 60,
            'path' => $cookieConfig['path'] ?? '/',
            'domain' => $cookieConfig['domain'] ?? null,
            'secure' => $cookieConfig['secure'] ?? false,
            'httponly' => $cookieConfig['http_only'] ?? true,
            'samesite' => $cookieConfig['same_site'] ?? 'lax',
        ]);

        ini_set('session.gc_maxlifetime', ($this->config['lifetime'] ?? 120) * 60);
        $lottery = $this->config['lottery'] ?? [2, 100];
        ini_set('session.gc_probability', $lottery[0]);
        ini_set('session.gc_divisor', $lottery[1]);
    }

    protected function ageFlashData()
    {
        $old = $this->get('_flash.old', []);
        $new = $this->get('_flash.new', []);
        
        foreach ($old as $key) {
            $this->forget($key);
        }

        $this->put('_flash.old', $new);
        $this->put('_flash.new', []);
    }

    protected function remove($key)
    {
        if (!$this->started) {
            $this->start();
        }

        $this->store->remove($key);
    }
}
