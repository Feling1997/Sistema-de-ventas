<?php

declare(strict_types=1);

namespace Ventas\Core\Permisos\Domain\Repositorios;

use Ventas\Core\Permisos\Domain\Entidades\Permiso;

interface PermisoRepository
{
    /**
     * @return Permiso[]
     */
    public function listar(): array;

    public function buscarPorId(int $id): ?Permiso;

    public function asegurarIniciales(): void;

    public function permisosDeRol(int $rolId): array;

    public function activarParaRol(int $rolId, int $permisoId): bool;

    public function quitarDeRol(int $rolId, int $permisoId): bool;

    public function usuarioTienePermiso(int $usuarioLegacyId, string $codigo): bool;
}
