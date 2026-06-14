<?php

declare(strict_types=1);

namespace Ventas\Dominio\Ventas\NuevaVenta\Repositorios;

interface UsuarioActualRepository
{
    public function obtener(): array;
}
