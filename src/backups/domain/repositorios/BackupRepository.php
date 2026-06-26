<?php

declare(strict_types=1);

namespace Ventas\Backups\Domain\Repositorios;

interface BackupRepository
{
    public function generarResumen(): array;

    public function generarTextoResumen(array $resumen): string;

    public function generarEstructura(): string;
}
