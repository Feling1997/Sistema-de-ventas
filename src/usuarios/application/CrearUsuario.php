<?php

declare(strict_types=1);

namespace Ventas\Usuarios\Application;

use Ventas\Usuarios\Domain\Entidades\Usuario;
use Ventas\Usuarios\Domain\Excepciones\UsuarioDuplicadoException;
use Ventas\Usuarios\Domain\Repositorios\UsuarioRepository;

final class CrearUsuario
{
    public function __construct(private readonly UsuarioRepository $usuarioRepository)
    {
    }

    public function ejecutar(Usuario $usuario): Usuario
    {
        if ($this->usuarioRepository->existeUsuario($usuario->usuario())) {
            throw new UsuarioDuplicadoException('El usuario ya existe.');
        }

        return $this->usuarioRepository->guardar($usuario);
    }
}
