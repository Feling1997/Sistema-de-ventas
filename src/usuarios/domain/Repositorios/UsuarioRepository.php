<?php

declare(strict_types=1);

namespace Ventas\Usuarios\Domain\Repositorios;

use Ventas\Usuarios\Domain\Entidades\Usuario;

interface UsuarioRepository
{
    /**
     * @return Usuario[]
     */
    public function listar(): array;

    public function buscarPorId(int $id): ?Usuario;

    public function buscarPorUsuario(string $usuario): ?Usuario;

    public function existeUsuario(string $usuario, ?int $exceptoId = null): bool;

    public function guardar(Usuario $usuario): Usuario;

    public function actualizar(Usuario $usuario): void;

    public function actualizarClave(int $id, string $claveHash): void;

    public function eliminar(int $id): void;

    public function tieneVentasAsociadas(int $id): bool;
}
