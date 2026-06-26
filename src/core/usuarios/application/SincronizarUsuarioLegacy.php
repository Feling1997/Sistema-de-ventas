<?php

declare(strict_types=1);

namespace Ventas\Core\Usuarios\Application;

use Ventas\Core\Roles\Infrastructure\Models\RolModel;
use Ventas\Core\Usuarios\Domain\Repositorios\UsuarioRepository;

final class SincronizarUsuarioLegacy
{
    public function __construct(private readonly UsuarioRepository $usuarioRepository)
    {
    }

    /**
     * @param array<string, mixed> $usuarioSesion
     */
    public function ejecutar(array $usuarioSesion): void
    {
        $rolLegacy = strtoupper((string) ($usuarioSesion['rol'] ?? ''));
        $nombreRol = $rolLegacy === 'ADMIN' ? 'Administrador' : 'Vendedor';
        $rol = RolModel::query()->where('nombre', $nombreRol)->first();

        if ($rol instanceof RolModel) {
            $this->usuarioRepository->sincronizarLegacy($usuarioSesion, (int) $rol->id);
        }
    }
}
