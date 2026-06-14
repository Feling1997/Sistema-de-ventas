<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Usuarios\CasosUso;

use Ventas\Dominio\Usuarios\Excepciones\UsuarioActualNoEliminableException;
use Ventas\Dominio\Usuarios\Excepciones\UsuarioConVentasException;
use Ventas\Dominio\Usuarios\Excepciones\UsuarioNoEncontradoException;
use Ventas\Dominio\Usuarios\Repositorios\UsuarioRepository;

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
