<?php

declare(strict_types=1);

namespace Ventas\Importacion\Domain\Repositorios;

interface ImportacionLogRepository
{
    public function guardarLog(int $idUsuario, string $archivo, array $resumen): void;
}
