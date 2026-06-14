<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Usuarios\CasosUso;

use Ventas\Dominio\Usuarios\Entidades\Usuario;
use Ventas\Dominio\Usuarios\Excepciones\UsuarioDuplicadoException;
use Ventas\Dominio\Usuarios\Excepciones\UsuarioNoEncontradoException;
use Ventas\Dominio\Usuarios\Repositorios\UsuarioRepository;

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
