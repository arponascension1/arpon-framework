<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;
use Arpon\Contracts\Validation\DataAwareRule;
use Arpon\Support\Facades\DB;

class Exists implements Rule, DataAwareRule
{
    protected $table;
    protected $column;
    protected $wheres = [];
    protected $data = [];
    protected $connectionName;

    public function __construct($table, $column = null)
    {
        $this->table = $table;
        $this->column = $column;
    }

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Add a where clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $value
     * @return $this
     */
    public function where($column, $value = null)
    {
        $this->wheres[$column] = $value;
        return $this;
    }

    /**
     * Set database connection name.
     *
     * @param  string  $connection
     * @return $this
     */
    public function on($connection)
    {
        $this->connectionName = $connection;
        return $this;
    }

    public function passes($attribute, $value)
    {
        // Get column name (use attribute if not specified)
        $column = $this->column ?: $attribute;

        try {
            // Build query using Arpon Query Builder
            $query = DB::table($this->table)->where($column, $value);

            // Add where conditions
            foreach ($this->wheres as $whereColumn => $whereValue) {
                $query->where($whereColumn, $whereValue);
            }

            // Get count
            $count = $query->count();

            // Value exists if count is greater than 0
            return $count > 0;

        } catch (\Exception $e) {
            // If no database connection or error, fail validation
            return false;
        }
    }

    public function message()
    {
        return 'The selected :attribute is invalid.';
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getColumn()
    {
        return $this->column;
    }
}
