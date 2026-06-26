<?php

declare(strict_types=1);

namespace Ventas\Backups\Domain\Repositorios;

use PharData;

interface FilesystemRespaldoRepository
{
    public function generar(): array;

    public function copiarA(string $origen, string $destino): array;

    public function agregarArchivo(PharData $archivo, string $base, string $relativo): void;

    public function agregarCarpeta(PharData $archivo, string $base, string $relativo): void;
}
