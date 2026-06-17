<?php

declare(strict_types=1);

namespace Ventas\Ventas\Infrastructure\NuevaVenta;

use Ventas\Ventas\Domain\NuevaVenta\Repositorios\MenuVentasRepository;

final class ArchivoMenuVentasRepository implements MenuVentasRepository
{
    public function guardarPreferencias(int $idUsuario, string $rol, array $seleccion): bool
    {
        $ok = menu_guardar_preferencias_usuario($idUsuario, $rol, $seleccion);

        return $ok;
    }
}
