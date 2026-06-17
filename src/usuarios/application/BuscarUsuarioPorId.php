<?php

declare(strict_types=1);

namespace Ventas\Usuarios\Application;

use Ventas\Usuarios\Domain\Entidades\Usuario;
use Ventas\Usuarios\Domain\Repositorios\UsuarioRepository;

final class BuscarUsuarioPorId
{
    public function __construct(private readonly UsuarioRepository $usuarioRepository)
    {
    }

    public function ejecutar(int $id): ?Usuario
    {
        return $this->usuarioRepository->buscarPorId($id);
    }
}
