<?php

declare(strict_types=1);

namespace Ventas\Dominio\Ventas\NuevaVenta\Repositorios;

interface ClienteVentaRepository
{
    public function listarParaVenta(): array;
}
