<?php

function connectDatabase(array $defaults, string $localConfigPath): PDO
{
    $environmentKeys = [
        'host' => 'DB_HOST',
        'port' => 'DB_PORT',
        'dbname' => 'DB_NAME',
        'username' => 'DB_USER',
        'password' => 'DB_PASSWORD',
    ];
    $config = $defaults;
    if (is_file($localConfigPath)) {
        $localConfig = require $localConfigPath;
        if (!is_array($localConfig)) {
            throw new RuntimeException('Database local configuration must return an array.');
        }
        $config = array_replace($config, array_intersect_key($localConfig, $defaults));
    }

    foreach ($environmentKeys as $key => $environmentKey) {
        $value = getenv($environmentKey);
        if ($value !== false) {
            $config[$key] = $value;
        }
    }

    foreach (['host', 'port', 'dbname', 'username', 'password'] as $requiredKey) {
        if (!array_key_exists($requiredKey, $config) || ($requiredKey !== 'password' && trim((string)$config[$requiredKey]) === '')) {
            throw new RuntimeException('Database configuration is incomplete. Set DB_* environment variables or create the local config file.');
        }
    }

    try {
        return new PDO(
            'mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['dbname'] . ';charset=utf8mb4',
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $exception) {
        error_log('Database connection failed: ' . $exception->getMessage());
        throw new RuntimeException('Database connection failed. Check the DB_* configuration and database server.');
    }
}
