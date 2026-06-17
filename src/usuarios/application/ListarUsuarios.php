<?php

declare(strict_types=1);

namespace Ventas\Usuarios\Application;

use Ventas\Usuarios\Domain\Repositorios\UsuarioRepository;

final class ListarUsuarios
{
    public function __construct(private readonly UsuarioRepository $usuarioRepository)
    {
    }

    public function ejecutar(): array
    {
        return $this->usuarioRepository->listar();
    }
}
