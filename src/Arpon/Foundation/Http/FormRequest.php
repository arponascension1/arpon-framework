<?php

namespace Arpon\Foundation\Http;

use Arpon\Http\Request;
use Arpon\Validation\Validator;
use Arpon\Validation\ValidationException;

abstract class FormRequest extends Request
{
    /**
     * The validator instance.
     *
     * @var Validator
     */
    protected $validator;

    /**
     * The validated data from the request.
     *
     * @var array
     */
    protected $validatedData = [];

    /**
     * The redirect destination URL.
     *
     * @var string|null
     */
    protected $redirect;

    /**
     * The route to redirect to.
     *
     * @var string|null
     */
    protected $redirectRoute;

    /**
     * The controller action to redirect to.
     *
     * @var string|null
     */
    protected $redirectAction;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Get the validator instance for the request.
     *
     * @return Validator
     */
    protected function getValidatorInstance(): Validator
    {
        if ($this->validator) {
            return $this->validator;
        }

        $validator = $this->createDefaultValidator();

        if (method_exists($this, 'withValidator')) {
            $this->withValidator($validator);
        }

        $this->setValidator($validator);

        return $this->validator;
    }

    /**
     * Create the default validator instance.
     *
     * @return Validator
     */
    protected function createDefaultValidator(): Validator
    {
        return validator(
            $this->validationData(),
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );
    }

    /**
     * Get data to be validated from the request.
     *
     * @return array
     */
    public function validationData(): array
    {
        return $this->all();
    }

    /**
     * Set the validator instance.
     *
     * @param Validator $validator
     * @return $this
     */
    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Validate the class instance.
     *
     * @return void
     * @throws \Arpon\Validation\ValidationException
     */
    public function validateResolved(): void
    {
        $this->prepareForValidation();

        if (!$this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        $validator = $this->getValidatorInstance();

        if ($validator->fails()) {
            $this->failedValidation($validator);
        }

        $this->passedValidation();
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Override in subclass if needed
    }

    /**
     * Determine if the request passes the authorization check.
     *
     * @return bool
     */
    protected function passesAuthorization(): bool
    {
        if (method_exists($this, 'authorize')) {
            return $this->authorize();
        }

        return true;
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     */
    protected function failedAuthorization(): void
    {
        abort(403, 'This action is unauthorized.');
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @return void
     * @throws \Arpon\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException($validator);
    }

    /**
     * Handle a passed validation attempt.
     *
     * @return void
     */
    protected function passedValidation(): void
    {
        // Override in subclass if needed
    }

    /**
     * Get the validated data from the request.
     *
     * @return array
     */
    public function validated(): array
    {
        if (empty($this->validatedData)) {
            $this->validatedData = $this->validator->validated();
        }

        return $this->validatedData;
    }

    /**
     * Get a subset of the validated data.
     *
     * @param  array|mixed  $keys
     * @return array
     */
    public function safe(array $keys = []): array
    {
        $validated = $this->validated();

        if (empty($keys)) {
            return $validated;
        }

        $results = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $validated)) {
                $results[$key] = $validated[$key];
            }
        }

        return $results;
    }

    /**
     * Create a new form request instance from a base request.
     *
     * @param  \Arpon\Http\Request  $from
     * @return static
     */
    public static function createFrom(Request $from): static
    {
        // Access the properties using reflection since they're protected
        $reflector = new \ReflectionClass($from);
        
        $getProperty = function($name) use ($reflector, $from) {
            try {
                $property = $reflector->getProperty($name);
                return $property->getValue($from);
            } catch (\ReflectionException $e) {
                return null;
            }
        };

        $files = $getProperty('files') ?? [];
        
        $instance = new static(
            $getProperty('query') ?? [],
            $getProperty('request') ?? [],
            [],  // Pass empty array to avoid re-processing $_FILES
            $getProperty('cookies') ?? [],
            $getProperty('headers') ?? [],
            $getProperty('server') ?? [],
            $getProperty('content')
        );

        // Manually set the files property using reflection
        $filesProperty = $reflector->getProperty('files');
        $filesProperty->setValue($instance, $files);

        // Copy route resolver
        $routeResolver = $getProperty('routeResolver');
        if ($routeResolver) {
            $instance->setRouteResolver($routeResolver);
        }

        // Copy session
        $session = $getProperty('session');
        if ($session) {
            $instance->setSession($session);
        }

        return $instance;
    }

    /**
     * Get the route resolver callback.
     *
     * @return \Closure|null
     */
    public function getRouteResolver()
    {
        return $this->routeResolver;
    }

    /**
     * Check if the request has a session.
     *
     * @return bool
     */
    public function hasSession(): bool
    {
        return $this->session !== null;
    }
}
