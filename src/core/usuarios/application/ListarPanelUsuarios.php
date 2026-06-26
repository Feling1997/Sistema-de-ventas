<?php

declare(strict_types=1);

namespace Ventas\Core\Usuarios\Application;

use Ventas\Core\Permisos\Domain\Repositorios\PermisoRepository;
use Ventas\Core\Roles\Domain\Repositorios\RolRepository;
use Ventas\Core\Usuarios\Domain\Repositorios\UsuarioRepository;

final class ListarPanelUsuarios
{
    public function __construct(
        private readonly UsuarioRepository $usuarioRepository,
        private readonly RolRepository $rolRepository,
        private readonly PermisoRepository $permisoRepository
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function ejecutar(): array
    {
        $roles = [];

        foreach ($this->rolRepository->listar() as $rol) {
            $datosRol = $rol->comoArray();
            $datosRol['permisos'] = $rol->id() !== null ? $this->permisoRepository->permisosDeRol($rol->id()) : [];
            $roles[] = $datosRol;
        }

        $panel = [
            'usuarios' => array_map(static fn ($usuario): array => $usuario->comoArray(), $this->usuarioRepository->listar()),
            'roles' => $roles,
            'permisos' => array_map(static fn ($permiso): array => $permiso->comoArray(), $this->permisoRepository->listar()),
        ];

        return $panel;
    }
}
