<?php

namespace Arpon\Contracts\Hashing;

interface Hasher
{
    /**
     * Get information about the given hashed value.
     *
     * @param  string  
     * @return array
     */
    public function info($hashedValue);

    /**
     * Hash the given value.
     *
     * @param  string  
     * @param  array  
     * @return string
     */
    public function make($value, array $options = []);

    /**
     * Check the given plain value against a hash.
     *
     * @param  string  
     * @param  string  
     * @param  array  
     * @return bool
     */
    public function check($value, $hashedValue, array $options = []);

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param  string  
     * @param  array  
     * @return bool
     */
    public function needsRehash($hashedValue, array $options = []);
}
