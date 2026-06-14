<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Usuarios\CasosUso;

use Ventas\Dominio\Usuarios\Repositorios\UsuarioRepository;

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
