<?php

declare(strict_types=1);

namespace Ventas\Ventas\Domain\NuevaVenta\Repositorios;

interface UsuarioActualRepository
{
    public function obtener(): array;
}
