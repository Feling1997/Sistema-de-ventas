<?php

declare(strict_types=1);

namespace Ventas\Usuarios\Application;

use Ventas\Usuarios\Domain\Excepciones\UsuarioActualNoEliminableException;
use Ventas\Usuarios\Domain\Excepciones\UsuarioConVentasException;
use Ventas\Usuarios\Domain\Excepciones\UsuarioNoEncontradoException;
use Ventas\Usuarios\Domain\Repositorios\UsuarioRepository;

final class EliminarUsuario
{
    public function __construct(private readonly UsuarioRepository $usuarioRepository)
    {
    }

    public function ejecutar(int $id, int $idUsuarioActual): void
    {
        $usuario = $this->usuarioRepository->buscarPorId($id);

        if ($usuario === null) {
            throw new UsuarioNoEncontradoException('Usuario no encontrado.');
        }

        if ($id === $idUsuarioActual) {
            throw new UsuarioActualNoEliminableException('No se puede eliminar el usuario actual.');
        }

        if ($this->usuarioRepository->tieneVentasAsociadas($id)) {
            throw new UsuarioConVentasException('No se puede eliminar un usuario con ventas asociadas.');
        }

        $this->usuarioRepository->eliminar($id);
    }
}
