<?php

namespace Arpon\Validation;

use Closure;
use Arpon\Support\MessageBag;
use Arpon\Contracts\Validation as Contracts;

class Validator
{
    protected $data;
    protected $rules = [];
    protected $messages = [];
    protected $customAttributes = [];
    protected $fallbackMessages = [];
    protected $failedRules = [];
    protected $customValues = [];
    protected $extensions = [];
    protected $implicitExtensions = [];
    protected $replacers = [];
    protected static $globalExtensions = [];
    protected static $globalImplicitExtensions = [];
    protected static $globalReplacers = [];

    protected $implicitRules = [
        'Required', 'RequiredWith', 'RequiredWithAll', 'RequiredWithout',
        'RequiredWithoutAll', 'RequiredIf', 'RequiredUnless', 'Accepted',
        'Present',
    ];

    protected $numericRules = ['Numeric', 'Integer', 'Decimal'];

    protected $sizeRules = ['Size', 'Between', 'Min', 'Max', 'Gt', 'Lt', 'Gte', 'Lte'];

    protected $after = [];

    protected $messageBag;

    public function __construct(array $data, array $rules, array $messages = [], array $attributes = [])
    {
        $this->data = $this->parseData($data);
        $this->rules = $this->explodeRules($rules);
        $this->messages = array_merge($this->messages, $messages);
        $this->customAttributes = $attributes;

        // $this->fallbackMessages = require __DIR__ . '/messages.php';
    }

    protected function parseData(array $data)
    {
        $newData = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $newData[$key] = $value;
            } else {
                $newData[$key] = $value;
            }
        }

        return $newData;
    }

    protected function explodeRules($rules)
    {
        foreach ($rules as $key => &$rule) {
            if (is_string($rule)) {
                $rule = explode('|', $rule);
            }

            $rule = $this->explodeExplicitRule($rule);
        }

        return $rules;
    }

    protected function explodeExplicitRule($rule)
    {
        if (is_string($rule)) {
            return explode('|', $rule);
        }

        return $rule;
    }

    public function validate()
    {
        if ($this->fails()) {
            throw new ValidationException($this);
        }

        return $this->validated();
    }

    public function validated()
    {
        $results = [];

        $rules = $this->getRules();

        foreach ($rules as $key => $rule) {
            $results[$key] = $this->getValue($key);
        }

        return $results;
    }

    public function fails()
    {
        return !$this->passes();
    }

    public function passes()
    {
        $this->failedRules = [];

        foreach ($this->rules as $attribute => $rules) {
            foreach ($rules as $rule) {
                $this->validateAttribute($attribute, $rule);
            }
        }

        // Run after validation callbacks
        foreach ($this->after as $callback) {
            call_user_func($callback, $this);
        }

        // Check if any errors were added by after callbacks
        if ($this->messageBag !== null && $this->messageBag->any()) {
            // Mark as failed if errors were added
            return false;
        }

        return count($this->failedRules) === 0;
    }

    protected function validateAttribute($attribute, $rule)
    {
        // Check if rule is an object (new rule class style)
        if (is_object($rule)) {
            $this->validateUsingRuleObject($attribute, $rule);
            return;
        }

        // Convert string rule to Rule object
        $ruleObject = $this->convertStringToRuleObject($rule);
        
        if ($ruleObject) {
            $this->validateUsingRuleObject($attribute, $ruleObject);
            return;
        }

        // Fallback for custom extensions
        [$ruleName, $parameters] = $this->parseRule($rule);

        if ($ruleName === '') {
            return;
        }

        $ruleName = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $ruleName)));
        
        $value = $this->getValue($attribute);
        $isImplicit = isset($this->implicitExtensions[$ruleName]) || isset(static::$globalImplicitExtensions[$ruleName]);

        // Check if field is nullable and value is null/empty
        if ($this->isNullable($attribute) && $this->isEmptyValue($value)) {
            if (!$isImplicit) {
                return;
            }
        }
        
        if (!$isImplicit && !$this->validatePresent($attribute, $value)) {
            return;
        }
        
        if (isset($this->extensions[$ruleName])) {
            $this->callExtension($ruleName, $attribute, $value, $parameters);
        } elseif (isset(static::$globalExtensions[$ruleName])) {
            $this->callGlobalExtension($ruleName, $attribute, $value, $parameters);
        }
    }

    /**
     * Convert string rule to Rule object.
     *
     * @param  string  $rule
     * @return object|null
     */
    protected function convertStringToRuleObject($rule)
    {
        [$ruleName, $parameters] = $this->parseRule($rule);
        
        if ($ruleName === '') {
            return null;
        }

        // Convert to StudlyCase for class name
        $ruleClass = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $ruleName)));
        
        // Map of rule names to Rule classes
        $ruleMap = [
            'Required' => \Arpon\Validation\Rules\Required::class,
            'RequiredIf' => \Arpon\Validation\Rules\RequiredIf::class,
            'RequiredWith' => \Arpon\Validation\Rules\RequiredWith::class,
            'RequiredWithout' => \Arpon\Validation\Rules\RequiredWithout::class,
            'Email' => \Arpon\Validation\Rules\Email::class,
            'Url' => \Arpon\Validation\Rules\Url::class,
            'Alpha' => \Arpon\Validation\Rules\Alpha::class,
            'AlphaNum' => \Arpon\Validation\Rules\AlphaNum::class,
            'AlphaDash' => \Arpon\Validation\Rules\AlphaDash::class,
            'Numeric' => \Arpon\Validation\Rules\Numeric::class,
            'Integer' => \Arpon\Validation\Rules\Integer::class,
            'String' => \Arpon\Validation\Rules\StringRule::class,
            'Boolean' => \Arpon\Validation\Rules\Boolean::class,
            'Array' => \Arpon\Validation\Rules\ArrayRule::class,
            'Min' => \Arpon\Validation\Rules\Min::class,
            'Max' => \Arpon\Validation\Rules\Max::class,
            'Between' => \Arpon\Validation\Rules\Between::class,
            'Size' => \Arpon\Validation\Rules\Size::class,
            'Gt' => \Arpon\Validation\Rules\Gt::class,
            'Gte' => \Arpon\Validation\Rules\Gte::class,
            'Lt' => \Arpon\Validation\Rules\Lt::class,
            'Lte' => \Arpon\Validation\Rules\Lte::class,
            'In' => \Arpon\Validation\Rules\In::class,
            'NotIn' => \Arpon\Validation\Rules\NotIn::class,
            'Confirmed' => \Arpon\Validation\Rules\Confirmed::class,
            'Same' => \Arpon\Validation\Rules\Same::class,
            'Different' => \Arpon\Validation\Rules\Different::class,
            'Date' => \Arpon\Validation\Rules\Date::class,
            'DateFormat' => \Arpon\Validation\Rules\DateFormat::class,
            'Before' => \Arpon\Validation\Rules\Before::class,
            'BeforeOrEqual' => \Arpon\Validation\Rules\BeforeOrEqual::class,
            'After' => \Arpon\Validation\Rules\After::class,
            'AfterOrEqual' => \Arpon\Validation\Rules\AfterOrEqual::class,
            'Accepted' => \Arpon\Validation\Rules\Accepted::class,
            'Declined' => \Arpon\Validation\Rules\Declined::class,
            'Present' => \Arpon\Validation\Rules\Present::class,
            'Nullable' => \Arpon\Validation\Rules\Nullable::class,
            'File' => \Arpon\Validation\Rules\File::class,
            'Image' => \Arpon\Validation\Rules\Image::class,
            'Mimes' => \Arpon\Validation\Rules\Mimes::class,
            'Distinct' => \Arpon\Validation\Rules\Distinct::class,
            'Regex' => \Arpon\Validation\Rules\Regex::class,
            'NotRegex' => \Arpon\Validation\Rules\NotRegex::class,
            'StartsWith' => \Arpon\Validation\Rules\StartsWith::class,
            'EndsWith' => \Arpon\Validation\Rules\EndsWith::class,
            'Lowercase' => \Arpon\Validation\Rules\Lowercase::class,
            'Uppercase' => \Arpon\Validation\Rules\Uppercase::class,
            'Ip' => \Arpon\Validation\Rules\Ip::class,
            'Ipv4' => \Arpon\Validation\Rules\Ipv4::class,
            'Ipv6' => \Arpon\Validation\Rules\Ipv6::class,
            'Json' => \Arpon\Validation\Rules\Json::class,
            'MacAddress' => \Arpon\Validation\Rules\MacAddress::class,
            'Uuid' => \Arpon\Validation\Rules\Uuid::class,
            'Decimal' => \Arpon\Validation\Rules\Decimal::class,
            'Multiple' => \Arpon\Validation\Rules\Multiple::class,
            'Unique' => \Arpon\Validation\Rules\Unique::class,
            'Exists' => \Arpon\Validation\Rules\Exists::class,
        ];

        if (!isset($ruleMap[$ruleClass])) {
            return null;
        }

        $class = $ruleMap[$ruleClass];

        // Instantiate rule with parameters
        try {
            if (empty($parameters)) {
                return new $class();
            }

            // Rules with single parameter
            if (in_array($ruleClass, ['Min', 'Max', 'Size', 'Gt', 'Gte', 'Lt', 'Lte', 'Before', 'After', 
                                       'BeforeOrEqual', 'AfterOrEqual', 'DateFormat', 'Regex', 'NotRegex',
                                       'Same', 'Different', 'Multiple'])) {
                return new $class($parameters[0]);
            }

            // Rules with two parameters
            if (in_array($ruleClass, ['Between', 'RequiredIf', 'Decimal'])) {
                return new $class($parameters[0], $parameters[1] ?? null);
            }

            // Rules with three parameters (Unique: table, column, ignoreId)
            if ($ruleClass === 'Unique') {
                $table = $parameters[0] ?? null;
                $column = $parameters[1] ?? null;
                $ignoreId = $parameters[2] ?? null;
                return new $class($table, $column, $ignoreId);
            }

            // Rules with two parameters (Exists: table, column)
            if ($ruleClass === 'Exists') {
                $table = $parameters[0] ?? null;
                $column = $parameters[1] ?? null;
                return new $class($table, $column);
            }

            // Rules with array parameter
            if (in_array($ruleClass, ['In', 'NotIn', 'Mimes', 'StartsWith', 'EndsWith', 'RequiredWith', 'RequiredWithout'])) {
                return new $class($parameters);
            }

            return new $class();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate an attribute using a rule object.
     *
     * @param  string  $attribute
     * @param  object  $rule
     * @return void
     */
    protected function validateUsingRuleObject($attribute, $rule)
    {
        $value = $this->getValue($attribute);

        // Check if field is nullable and value is null/empty
        if ($this->isNullable($attribute) && $this->isEmptyValue($value)) {
            // Skip validation for nullable fields with null/empty values
            // unless it's the Nullable rule itself or an implicit rule
            if (!($rule instanceof \Arpon\Validation\Rules\Nullable) && 
                !($rule instanceof Contracts\ImplicitRule)) {
                return;
            }
        }

        // Check if it's an implicit rule (validates even if attribute missing)
        if ($rule instanceof Contracts\ImplicitRule) {
            // Always validate implicit rules
        } elseif (!$this->validatePresent($attribute, $value)) {
            // Skip validation if attribute not present and rule is not implicit
            return;
        }

        // Set data for DataAwareRule instances
        if ($rule instanceof Contracts\DataAwareRule) {
            $rule->setData($this->data);
        }

        // Execute validation
        $passes = $rule->passes($attribute, $value);

        if (!$passes) {
            // Get short rule name for message lookup
            $ruleClass = get_class($rule);
            $ruleName = substr(strrchr($ruleClass, '\\'), 1) ?: $ruleClass;
            $lowerRule = strtolower($ruleName);

            // Check for inline custom message first
            $message = $this->getInlineMessage($attribute, $lowerRule);

            if (is_null($message)) {
                // Fallback to default message from rule object
                $message = $rule->message();
            }
            
            // Replace placeholders in message
            $message = $this->makeReplacementsForRuleObject($message, $attribute, $rule);
            
            // Add to failed rules
            $this->failedRules[$attribute][$ruleClass] = [];
            
            // Add error message
            if (!isset($this->messages[$attribute])) {
                $this->messages[$attribute] = [];
            }
            
            // Avoid duplicate messages
            if (!in_array($message, $this->messages[$attribute])) {
                $this->messages[$attribute][] = $message;
            }
        }
    }

    /**
     * Make replacements for rule object messages.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  object  $rule
     * @return string
     */
    protected function makeReplacementsForRuleObject($message, $attribute, $rule)
    {
        $message = str_replace(':attribute', $this->getAttribute($attribute), $message);
        
        // Try common getter methods
        if (is_callable([$rule, 'getMin'])) {
            $message = str_replace(':min', $rule->getMin(), $message);
        }
        if (is_callable([$rule, 'getMax'])) {
            $message = str_replace(':max', $rule->getMax(), $message);
        }
        if (is_callable([$rule, 'getSize'])) {
            $message = str_replace(':size', $rule->getSize(), $message);
        }
        if (is_callable([$rule, 'getValue'])) {
            $message = str_replace(':value', $rule->getValue(), $message);
        }
        if (is_callable([$rule, 'getDate'])) {
            $message = str_replace(':date', $rule->getDate(), $message);
        }
        if (is_callable([$rule, 'getField'])) {
            $message = str_replace(':other', $this->getAttribute($rule->getField()), $message);
        }
        if (is_callable([$rule, 'getValues'])) {
            $values = $rule->getValues();
            if (is_array($values)) {
                $message = str_replace(':values', implode(', ', $values), $message);
            }
        }
        if (is_callable([$rule, 'getMimes'])) {
            $mimes = $rule->getMimes();
            if (is_array($mimes)) {
                $message = str_replace(':values', implode(', ', $mimes), $message);
            }
        }
        if (is_callable([$rule, 'getFormat'])) {
            $message = str_replace(':format', $rule->getFormat(), $message);
        }
        
        return $message;
    }

    protected function parseRule($rule)
    {
        // Handle rule objects
        if (is_object($rule)) {
            return [$rule, []];
        }
        
        if (is_array($rule)) {
            return [$rule[0], array_slice($rule, 1)];
        }

        if (strpos($rule, ':') !== false) {
            [$rule, $parameter] = explode(':', $rule, 2);
            return [$rule, $this->parseParameters($rule, $parameter)];
        }

        return [$rule, []];
    }

    protected function parseParameters($rule, $parameter)
    {
        return str_getcsv($parameter);
    }

    protected function getValue($attribute)
    {
        return $this->data[$attribute] ?? null;
    }

    protected function isNullable($attribute)
    {
        if (!array_key_exists($attribute, $this->rules)) {
            return false;
        }

        foreach ($this->rules[$attribute] as $rule) {
            if (is_object($rule) && $rule instanceof \Arpon\Validation\Rules\Nullable) {
                return true;
            }
            
            if (is_string($rule)) {
                [$ruleName, ] = $this->parseRule($rule);
                $ruleName = strtolower($ruleName);
                if ($ruleName === 'nullable') {
                    return true;
                }
            }
        }

        return false;
    }

    protected function isEmptyValue($value)
    {
        if ($value instanceof \Arpon\Http\UploadedFile) {
            return $value->getError() === UPLOAD_ERR_NO_FILE;
        }

        return is_null($value) || $value === '' || $value === [];
    }

    protected function hasRule($attribute, $rules)
    {
        return !is_null($this->getRule($attribute, $rules));
    }

    protected function getRule($attribute, $rules)
    {
        if (!array_key_exists($attribute, $this->rules)) {
            return null;
        }

        foreach ($this->rules[$attribute] as $rule) {
            [$rule, $parameters] = $this->parseRule($rule);

            if (in_array($rule, $rules)) {
                return [$rule, $parameters];
            }
        }

        return null;
    }

    protected function presentOrRuleIsImplicit($rule, $attribute, $value)
    {
        if (is_string($value) && trim($value) === '') {
            return $this->isImplicit($rule);
        }

        return $this->validatePresent($attribute, $value) || $this->isImplicit($rule);
    }

    protected function isImplicit($rule)
    {
        return in_array($rule, $this->implicitRules) ||
               isset($this->implicitExtensions[$rule]) ||
               isset(static::$globalImplicitExtensions[$rule]);
    }

    protected function callExtension($rule, $attribute, $value, $parameters)
    {
        $callback = $this->extensions[$rule];

        if ($callback instanceof Closure) {
            $result = call_user_func_array($callback, [$attribute, $value, $parameters, $this]);
        } else {
            $result = call_user_func_array($callback, [$attribute, $value, $parameters, $this]);
        }

        if (!$result) {
            $this->addFailure($attribute, $rule, $parameters);
        }
    }

    protected function callGlobalExtension($rule, $attribute, $value, $parameters)
    {
        $callback = static::$globalExtensions[$rule];

        if ($callback instanceof Closure) {
            $result = call_user_func_array($callback, [$attribute, $value, $parameters, $this]);
        } else {
            $result = call_user_func_array($callback, [$attribute, $value, $parameters, $this]);
        }

        if (!$result) {
            $this->addFailure($attribute, $rule, $parameters);
        }
    }

    protected function addFailure($attribute, $rule, $parameters = [])
    {
        $this->addError($attribute, $rule, $parameters);

        $this->failedRules[$attribute][$rule] = $parameters;
    }

    protected function addError($attribute, $rule, $parameters)
    {
        $message = $this->getMessage($attribute, $rule);

        $message = $this->makeReplacements($message, $attribute, $rule, $parameters);

        $this->messages[$attribute][] = $message;
    }

    protected function getMessage($attribute, $rule)
    {
        $lowerRule = strtolower($rule);

        // Check for attribute-specific rule message first (e.g. 'email.required')
        $inlineMessage = $this->getInlineMessage($attribute, $lowerRule);

        if (!is_null($inlineMessage)) {
            return $inlineMessage;
        }

        // Check for generic rule message (e.g. 'required')
        if (isset($this->messages[$lowerRule])) {
            return $this->messages[$lowerRule];
        }

        $customMessage = $this->getCustomMessageFromTranslator($attribute, $lowerRule);

        if ($customMessage !== $lowerRule) {
            return $customMessage;
        }

        return $this->fallbackMessages[$lowerRule] ?? "The {$attribute} field is invalid.";
    }

    protected function getInlineMessage($attribute, $rule)
    {
        return $this->messages["{$attribute}.{$rule}"] ?? $this->messages[$rule] ?? null;
    }

    protected function getCustomMessageFromTranslator($attribute, $rule)
    {
        return $this->fallbackMessages[$rule] ?? $rule;
    }

    protected function makeReplacements($message, $attribute, $rule, $parameters)
    {
        $message = str_replace(':attribute', $this->getAttribute($attribute), $message);

        // Handle rule object parameters (if any getter methods exist)
        if (is_object($rule)) {
            $ruleClass = get_class($rule);
            $ruleShortName = substr(strrchr($ruleClass, '\\'), 1) ?: $ruleClass;
            
            // Try common getter methods
            if (is_callable([$rule, 'getMin'])) {
                $message = str_replace(':min', $rule->getMin(), $message);
            }
            if (is_callable([$rule, 'getMax'])) {
                $message = str_replace(':max', $rule->getMax(), $message);
            }
            if (is_callable([$rule, 'getSize'])) {
                $message = str_replace(':size', $rule->getSize(), $message);
            }
            if (is_callable([$rule, 'getValue'])) {
                $message = str_replace(':value', $rule->getValue(), $message);
            }
            if (is_callable([$rule, 'getDate'])) {
                $message = str_replace(':date', $rule->getDate(), $message);
            }
            if (is_callable([$rule, 'getField'])) {
                $message = str_replace(':other', $rule->getField(), $message);
            }
            if (is_callable([$rule, 'getValues'])) {
                $values = $rule->getValues();
                if (is_array($values)) {
                    $message = str_replace(':values', implode(', ', $values), $message);
                }
            }
            if (is_callable([$rule, 'getMimes'])) {
                $mimes = $rule->getMimes();
                if (is_array($mimes)) {
                    $message = str_replace(':values', implode(', ', $mimes), $message);
                }
            }
            if (is_callable([$rule, 'getFormat'])) {
                $message = str_replace(':format', $rule->getFormat(), $message);
            }
            
            $rule = $ruleShortName;
        }

        $method = "replace{$rule}";

        if (method_exists($this, $method)) {
            $message = $this->{$method}($message, $attribute, $rule, $parameters);
        }

        if (isset($this->replacers[$rule])) {
            $message = $this->callReplacer($message, $attribute, $rule, $parameters, $this->replacers[$rule]);
        }

        if (isset(static::$globalReplacers[$rule])) {
            $message = $this->callReplacer($message, $attribute, $rule, $parameters, static::$globalReplacers[$rule]);
        }

        return $message;
    }

    protected function callReplacer($message, $attribute, $rule, $parameters, $replacer)
    {
        return call_user_func($replacer, $message, $attribute, $rule, $parameters, $this);
    }

    protected function getAttribute($attribute)
    {
        return $this->customAttributes[$attribute] ?? str_replace('_', ' ', $attribute);
    }

    public function errors()
    {
        // Return cached MessageBag for mutation in after callbacks
        if ($this->messageBag !== null) {
            return $this->messageBag;
        }

        if (!isset($this->messages['messages'])) {
            $messages = [];
            foreach ($this->messages as $key => $value) {
                if (is_array($value)) {
                    $messages[$key] = $value;
                }
            }
            $this->messageBag = new MessageBag($messages);
        } else {
            $this->messageBag = new MessageBag($this->messages);
        }

        return $this->messageBag;
    }

    public function failed()
    {
        return $this->failedRules;
    }

    /**
     * Add an after validation callback.
     *
     * @param callable|string $callback
     * @return $this
     */
    public function after($callback)
    {
        $this->after[] = $callback;

        return $this;
    }

    public function getRules()
    {
        return $this->rules;
    }

    // Validation Rules

    protected function validatePresent($attribute, $value)
    {
        return array_key_exists($attribute, $this->data);
    }
    // Replacers

    protected function replaceMin($message, $attribute, $rule, $parameters)
    {
        return str_replace(':min', $parameters[0], $message);
    }

    protected function replaceMax($message, $attribute, $rule, $parameters)
    {
        return str_replace(':max', $parameters[0], $message);
    }

    protected function replaceSize($message, $attribute, $rule, $parameters)
    {
        return str_replace(':size', $parameters[0], $message);
    }

    protected function replaceBetween($message, $attribute, $rule, $parameters)
    {
        return str_replace([':min', ':max'], $parameters, $message);
    }

    protected function replaceSame($message, $attribute, $rule, $parameters)
    {
        return str_replace(':other', $parameters[0], $message);
    }

    protected function replaceDifferent($message, $attribute, $rule, $parameters)
    {
        return str_replace(':other', $parameters[0], $message);
    }

    // Static methods for extensions

    public static function extend($rule, $extension, $message = null)
    {
        $rule = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $rule)));
        static::$globalExtensions[$rule] = $extension;

        if ($message) {
            static::$globalReplacers[$rule] = function ($msg, $attribute, $rule, $parameters, $validator) use ($message) {
                return str_replace(':attribute', $validator->getAttribute($attribute), $message);
            };
        }
    }

    public static function extendImplicit($rule, $extension, $message = null)
    {
        $rule = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $rule)));
        static::$globalImplicitExtensions[$rule] = $extension;
        static::$globalExtensions[$rule] = $extension;

        if ($message) {
            static::$globalReplacers[$rule] = function ($msg, $attribute, $rule, $parameters, $validator) use ($message) {
                return str_replace(':attribute', $validator->getAttribute($attribute), $message);
            };
        }
    }

    public static function replacer($rule, $replacer)
    {
        $rule = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $rule)));
        static::$globalReplacers[$rule] = $replacer;
    }
}
