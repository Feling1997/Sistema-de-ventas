<?php

declare(strict_types=1);

namespace Ventas\Backups\Domain\Repositorios;

interface BackblazeStorageRepository
{
    public function configurado(array $config): bool;

    public function probar(array $config): array;

    public function subir(string $ruta, array $config): array;
}
