<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Usuarios\CasosUso;

use Ventas\Dominio\Usuarios\Entidades\Usuario;
use Ventas\Dominio\Usuarios\Repositorios\UsuarioRepository;

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
