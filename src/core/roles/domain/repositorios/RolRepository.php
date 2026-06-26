<?php

declare(strict_types=1);

namespace Ventas\Core\Roles\Domain\Repositorios;

use Ventas\Core\Roles\Domain\Entidades\Rol;

interface RolRepository
{
    /**
     * @return Rol[]
     */
    public function listar(): array;

    public function buscarPorId(int $id): ?Rol;

    public function guardar(array $datos): Rol;

    public function desactivar(int $id): bool;

    public function asegurarIniciales(): void;
}
