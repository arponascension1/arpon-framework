<?php

namespace Arpon\Validation;

class ValidationException extends \Exception
{
    public $validator;
    protected $response;
    protected $errorBag = 'default';
    protected $redirectTo;

    public function __construct(Validator $validator, $response = null, $errorBag = 'default')
    {
        parent::__construct('The given data was invalid.');
        
        $this->validator = $validator;
        $this->response = $response;
        $this->errorBag = $errorBag;
    }

    public static function withMessages(array $messages)
    {
        // Create a simple validator with the given messages
        $validator = new Validator([], [], $messages);
        $validator->fails(); // Mark as failed
        
        // Set the errors manually
        foreach ($messages as $key => $message) {
            $messages[$key] = is_array($message) ? $message : [$message];
        }
        
        $reflection = new \ReflectionClass($validator);
        $property = $reflection->getProperty('errors');
        $property->setAccessible(true);
        $property->setValue($validator, $messages);
        
        return new static($validator);
    }

    public function errors()
    {
        return $this->validator->errors();
    }

    public function getValidator()
    {
        return $this->validator;
    }

    public function status()
    {
        return 422;
    }

    public function errorBag()
    {
        return $this->errorBag;
    }

    public function redirectTo()
    {
        return $this->redirectTo;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
