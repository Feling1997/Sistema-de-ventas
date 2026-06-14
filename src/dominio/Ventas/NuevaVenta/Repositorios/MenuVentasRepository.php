<?php

declare(strict_types=1);

namespace Ventas\Dominio\Ventas\NuevaVenta\Repositorios;

interface MenuVentasRepository
{
    public function guardarPreferencias(int $idUsuario, string $rol, array $seleccion): bool;
}
