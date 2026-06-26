<?php

declare(strict_types=1);

namespace Ventas\Core\Infrastructure\Persistence\Mysql;

use PDO;
use RuntimeException;
use Throwable;
use Ventas\Core\Infrastructure\Config\DatabaseConfig;

final class PdoConnectionFactory
{
    public function __construct(private readonly ?DatabaseConfig $config = null)
    {
    }

    public function create(): PDO
    {
        $pdo = null;
        $config = $this->config ?? new DatabaseConfig();

        try {
            $pdo = new PDO($config->dsn(), $config->username(), $config->password(), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => $config->timeout(),
            ]);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "No se pudo conectar a la base de datos MySQL {$config->database()}.",
                0,
                $exception
            );
        }

        return $pdo;
    }
}
