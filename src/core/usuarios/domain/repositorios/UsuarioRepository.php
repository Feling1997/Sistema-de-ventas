<?php

declare(strict_types=1);

namespace Ventas\Core\Usuarios\Domain\Repositorios;

use Ventas\Core\Usuarios\Domain\Entidades\UsuarioCore;

interface UsuarioRepository
{
    /**
     * @return UsuarioCore[]
     */
    public function listar(): array;

    public function buscarPorId(int $id): ?UsuarioCore;

    public function buscarPorLegacyId(int $legacyId): ?UsuarioCore;

    public function guardar(array $datos): UsuarioCore;

    public function desactivar(int $id): bool;

    public function asignarRol(int $usuarioId, int $rolId): bool;

    public function sincronizarLegacy(array $usuarioSesion, int $rolId): UsuarioCore;
}
