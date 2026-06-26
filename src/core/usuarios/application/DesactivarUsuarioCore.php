<?php

declare(strict_types=1);

namespace Ventas\Core\Usuarios\Application;

use Ventas\Core\Usuarios\Domain\Repositorios\UsuarioRepository;

final class DesactivarUsuarioCore
{
    public function __construct(private readonly UsuarioRepository $usuarioRepository)
    {
    }

    public function ejecutar(int $id): bool
    {
        $desactivado = $this->usuarioRepository->desactivar($id);

        return $desactivado;
    }
}
