<?php

namespace Arpon\Auth\Access;

use Closure;
use Arpon\Contracts\Auth\Authenticatable;
use Arpon\Foundation\Application;
use Exception;

class Gate
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The user resolver.
     */
    protected Closure $userResolver;

    /**
     * All of the defined abilities.
     */
    protected array $abilities = [];

    /**
     * All of the defined policies.
     */
    protected array $policies = [];

    /**
     * All of the registered before callbacks.
     */
    protected array $beforeCallbacks = [];

    /**
     * Create a new gate instance.
     */
    public function __construct(Application $app, Closure $userResolver)
    {
        $this->app = $app;
        $this->userResolver = $userResolver;
    }

    /**
     * Determine if a given ability has been defined.
     */
    public function has(string $ability): bool
    {
        return isset($this->abilities[$ability]);
    }

    /**
     * Define a new ability.
     */
    public function define(string $ability, callable|string $callback): static
    {
        if (is_string($callback) && str_contains($callback, '@')) {
            $callback = $this->createAbilityCallback($callback);
        }

        $this->abilities[$ability] = $callback;

        return $this;
    }

    /**
     * Register a callback to run before all gate checks.
     */
    public function before(callable $callback): static
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     */
    public function allows(string $ability, mixed $arguments = []): bool
    {
        return $this->check($ability, $arguments);
    }

    /**
     * Determine if the given ability should be denied for the current user.
     */
    public function denies(string $ability, mixed $arguments = []): bool
    {
        return ! $this->allows($ability, $arguments);
    }

    /**
     * Define a policy class for a given class type.
     */
    public function policy(string $class, string $policy): static
    {
        $this->policies[$class] = $policy;

        return $this;
    }

    /**
     * Determine if all of the given abilities should be granted for the current user.
     */
    public function check(string $ability, mixed $arguments = []): bool
    {
        $user = $this->resolveUser();

        $arguments = is_array($arguments) ? $arguments : [$arguments];

        // First, we'll run the "before" callbacks to see if we can short-circuit
        $result = $this->callBeforeCallbacks($user, $ability, $arguments);

        if (! is_null($result)) {
            return $result;
        }

        // If a policy exists for the first argument, we will use it
        if (! empty($arguments) && $policy = $this->getPolicyFor($arguments[0])) {
            return $this->callPolicyMethod($policy, $ability, $user, $arguments);
        }

        if (! isset($this->abilities[$ability])) {
            return false;
        }

        $callback = $this->abilities[$ability];

        return $callback($user, ...$arguments);
    }

    /**
     * Get the policy for a given class or object.
     */
    protected function getPolicyFor(mixed $class): ?string
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (! is_string($class)) {
            return null;
        }

        return $this->policies[$class] ?? null;
    }

    /**
     * Call the appropriate method on the given policy.
     */
    protected function callPolicyMethod(string $policy, string $ability, ?Authenticatable $user, array $arguments): bool
    {
        $method = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $ability)));
        $method = lcfirst($method);

        $instance = $this->app->make($policy);

        if (! method_exists($instance, $method)) {
            return false;
        }

        return $this->app->call([$instance, $method], array_merge([$user], $arguments));
    }

    /**
     * Determine if the given ability should be granted for the current user, or throw exception.
     *
     * @throws \Exception
     */
    public function authorize(string $ability, mixed $arguments = []): bool
    {
        if ($this->allows($ability, $arguments)) {
            return true;
        }

        throw new Exception('This action is unauthorized.', 403);
    }

    /**
     * Resolve the user from the user resolver.
     */
    protected function resolveUser(): ?Authenticatable
    {
        return call_user_func($this->userResolver);
    }

    /**
     * Call all of the before callbacks and return the first non-null result.
     */
    protected function callBeforeCallbacks(?Authenticatable $user, string $ability, array $arguments): ?bool
    {
        foreach ($this->beforeCallbacks as $callback) {
            $result = $callback($user, $ability, $arguments);

            if (! is_null($result)) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Create a callback for a string-based ability.
     */
    protected function createAbilityCallback(string $callback): Closure
    {
        return function () use ($callback) {
            return $this->app->call($callback, func_get_args());
        };
    }
}
