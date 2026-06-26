<?php

declare(strict_types=1);

namespace Ventas\Importacion\Infrastructure;

use PDO;
use Ventas\Importacion\Domain\Repositorios\ImportacionLogRepository;

final class MySQLImportacionLogRepository implements ImportacionLogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function guardarLog(int $idUsuario, string $archivo, array $resumen): void
    {
        $statement = $this->pdo->prepare("INSERT INTO importaciones_productos_log (id_usuario, archivo, nuevos, actualizados, omitidos) VALUES (?, ?, ?, ?, ?)");
        $statement->execute([
            $idUsuario > 0 ? $idUsuario : null,
            substr($archivo, 0, 255),
            (int) ($resumen["nuevos"] ?? 0),
            (int) ($resumen["actualizados"] ?? 0),
            (int) ($resumen["omitidos"] ?? 0),
        ]);
    }
}
