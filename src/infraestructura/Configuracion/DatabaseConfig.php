<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Configuracion;

final class DatabaseConfig
{
    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly string $database = 'sistema_ventas',
        private readonly string $username = 'root',
        private readonly string $password = '',
        private readonly string $charset = 'utf8mb4',
        private readonly int $timeout = 2
    ) {
    }

    public function host(): string
    {
        return $this->host;
    }

    public function database(): string
    {
        return $this->database;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function charset(): string
    {
        return $this->charset;
    }

    public function timeout(): int
    {
        return $this->timeout;
    }

    public function dsn(): string
    {
        return "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
    }
}
