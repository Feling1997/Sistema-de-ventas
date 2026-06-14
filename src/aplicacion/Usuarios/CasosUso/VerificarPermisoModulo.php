<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Usuarios\CasosUso;

use Ventas\Dominio\Usuarios\Entidades\Usuario;

final class VerificarPermisoModulo
{
    public function ejecutar(Usuario $usuario, string $modulo): bool
    {
        return $usuario->puedeAccederModulo($modulo);
    }
}
