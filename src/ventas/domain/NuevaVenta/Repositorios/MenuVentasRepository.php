<?php

declare(strict_types=1);

namespace Ventas\Ventas\Domain\NuevaVenta\Repositorios;

interface MenuVentasRepository
{
    public function guardarPreferencias(int $idUsuario, string $rol, array $seleccion): bool;
}
