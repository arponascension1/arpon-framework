<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;
use Arpon\Contracts\Validation\DataAwareRule;
use Arpon\Support\Facades\DB;

class Unique implements Rule, DataAwareRule
{
    protected $table;
    protected $column;
    protected $ignoreId;
    protected $idColumn = 'id';
    protected $wheres = [];
    protected $data = [];
    protected $connectionName;

    public function __construct($table, $column = null, $ignoreId = null)
    {
        $this->table = $table;
        $this->column = $column;
        $this->ignoreId = $ignoreId;
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
     * Ignore a specific ID when checking uniqueness.
     *
     * @param  mixed  $id
     * @param  string  $idColumn
     * @return $this
     */
    public function ignore($id, $idColumn = 'id')
    {
        $this->ignoreId = $id;
        $this->idColumn = $idColumn;
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

            // Add ignore condition
            if ($this->ignoreId !== null) {
                $query->where($this->idColumn, '!=', $this->ignoreId);
            }

            // Add where conditions
            foreach ($this->wheres as $whereColumn => $whereValue) {
                $query->where($whereColumn, $whereValue);
            }

            // Get count
            $count = $query->count();

            // Value is unique if count is 0
            return $count == 0;

        } catch (\Exception $e) {
            // If no database connection or error, fail validation
            return false;
        }
    }

    public function message()
    {
        return 'The :attribute has already been taken.';
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getColumn()
    {
        return $this->column;
    }

    public function getIgnoreId()
    {
        return $this->ignoreId;
    }
}
