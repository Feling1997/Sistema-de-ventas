<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Usuarios\CasosUso;

use Ventas\Dominio\Usuarios\Entidades\Usuario;
use Ventas\Dominio\Usuarios\Excepciones\UsuarioDuplicadoException;
use Ventas\Dominio\Usuarios\Repositorios\UsuarioRepository;

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
