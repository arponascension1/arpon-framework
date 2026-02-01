<?php

namespace Arpon\Database\Connectors;

use Arpon\Contracts\Database\Connector as ConnectorContract;
use PDO;

class MySqlConnector extends Connector implements ConnectorContract
{
    /**
     * Establish a database connection.
     *
     * @param  array  $config
     * @return \PDO
     */
    public function connect(array $config)
    {
        $dsn = $this->getDsn($config);

        $options = $this->getOptions($config);

        // We need to grab the PDO options that should be used by default and then
        // create a new PDO connection instance. The PDO options will be used
        // to setup some important PDO attributes such as the error mode.
        $connection = $this->createConnection($dsn, $config, $options);

        if (! empty($config['database'])) {
            $connection->exec("use `{$config['database']}`");
        }

        $this->configureEncoding($connection, $config);

        // Next, we will check to see if a timezone has been specified in the config
        // and if it has we will run a query to set it as the default timezone
        // for the connection so all of the dates will be in that timezone.
        $this->configureTimezone($connection, $config);

        $this->setModes($connection, $config);

        return $connection;
    }

    /**
     * Create a DSN string from a configuration.
     *
     * @param  array   $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        return $this->hasSocket($config)
                            ? $this->getSocketDsn($config)
                            : $this->getHostDsn($config);
    }

    /**
     * Determine if the given configuration array has a UNIX socket.
     *
     * @param  array  $config
     * @return bool
     */
    protected function hasSocket(array $config)
    {
        return isset($config['unix_socket']) && ! empty($config['unix_socket']);
    }

    /**
     * Get the DSN string for a socket configuration.
     *
     * @param  array  $config
     * @return string
     */
    protected function getSocketDsn(array $config)
    {
        return "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
    }

    /**
     * Get the DSN string for a host / port configuration.
     *
     * @param  array  $config
     * @return string
     */
    protected function getHostDsn(array $config)
    {
        extract($config, EXTR_SKIP);

        return isset($port)
                    ? "mysql:host={$host};port={$port};dbname={$database}"
                    : "mysql:host={$host};dbname={$database}";
    }

    /**
     * Set the connection character set and collation.
     *
     * @param  \PDO    $connection
     * @param  array   $config
     * @return void
     */
    protected function configureEncoding($connection, array $config)
    {
        if (! isset($config['charset'])) {
            return;
        }

        $connection->prepare("set names '{$config['charset']}'" . $this->getCollation($config))->execute();
    }

    /**
     * Get the collation for the configuration.
     *
     * @param  array  $config
     * @return string
     */
    protected function getCollation(array $config)
    {
        return isset($config['collation']) ? " collate '{$config['collation']}'" : '';
    }

    /**
     * Set the timezone on the connection.
     *
     * @param  \PDO    $connection
     * @param  array   $config
     * @return void
     */
    protected function configureTimezone($connection, array $config)
    {
        if (isset($config['timezone'])) {
            $connection->prepare('set time_zone="' . $config['timezone'] . '"')->execute();
        }
    }

    /**
     * Set the modes on the connection.
     *
     * @param  \PDO    $connection
     * @param  array   $config
     * @return void
     */
    protected function setModes($connection, array $config)
    {
        if (isset($config['modes'])) {
            $this->setCustomModes($connection, $config);
        } elseif (isset($config['strict'])) {
            if ($config['strict']) {
                $connection->prepare($this->strictMode())->execute();
            } else {
                $connection->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
            }
        }
    }

    /**
     * Set the custom modes on the connection.
     *
     * @param  \PDO    $connection
     * @param  array   $config
     * @return void
     */
    protected function setCustomModes($connection, array $config)
    {
        $modes = implode(',', $config['modes']);

        $connection->prepare("set session sql_mode='{$modes}'")->execute();
    }

    /**
     * Get the query for strict mode.
     *
     * @return string
     */
    protected function strictMode()
    {
        return "set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
    }
}
