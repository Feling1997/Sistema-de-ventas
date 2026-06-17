<?php

declare(strict_types=1);

namespace Ventas\Usuarios\Application;

use Ventas\Usuarios\Domain\Entidades\Usuario;
use Ventas\Usuarios\Domain\Excepciones\UsuarioDuplicadoException;
use Ventas\Usuarios\Domain\Excepciones\UsuarioNoEncontradoException;
use Ventas\Usuarios\Domain\Repositorios\UsuarioRepository;

final class ActualizarUsuario
{
    public function __construct(private readonly UsuarioRepository $usuarioRepository)
    {
    }

    public function ejecutar(Usuario $usuario): void
    {
        if ($usuario->id() === null || $this->usuarioRepository->buscarPorId($usuario->id()) === null) {
            throw new UsuarioNoEncontradoException('Usuario no encontrado.');
        }

        if ($this->usuarioRepository->existeUsuario($usuario->usuario(), $usuario->id())) {
            throw new UsuarioDuplicadoException('El usuario ya existe.');
        }

        $this->usuarioRepository->actualizar($usuario);
    }
}
