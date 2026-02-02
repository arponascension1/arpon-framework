<?php

namespace Arpon\Database\Connectors;

use Arpon\Contracts\Database\Connector as ConnectorContract;
use InvalidArgumentException;
use PDO;

class SQLiteConnector extends Connector implements ConnectorContract
{
    /**
     * Establish a database connection.
     *
     * @param  array  $config
     * @return \PDO
     */
    public function connect(array $config)
    {
        $options = $this->getOptions($config);

        // SQLite supports "in-memory" databases that only last as long as the owning
        // connection does. These are useful for tests or for short lifetime store
        // querying. Path may be a file or ":memory" for memory-only databases.
        if ($config['database'] === ':memory:') {
            return $this->createConnection('sqlite::memory:', $config, $options);
        }

        $path = $config['database'];

        // Create database file and directory if they don't exist
        if (!file_exists($path)) {
            $directory = dirname($path);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            touch($path);
        }

        $path = realpath($path);

        return $this->createConnection("sqlite:{$path}", $config, $options);
    }
}
