<?php

declare(strict_types=1);

namespace Ventas\Usuarios\Application;

use Ventas\Usuarios\Domain\Entidades\Usuario;

final class VerificarPermisoModulo
{
    public function ejecutar(Usuario $usuario, string $modulo): bool
    {
        return $usuario->puedeAccederModulo($modulo);
    }
}
