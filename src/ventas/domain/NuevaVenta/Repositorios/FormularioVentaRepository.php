<?php

declare(strict_types=1);

namespace Ventas\Ventas\Domain\NuevaVenta\Repositorios;

interface FormularioVentaRepository
{
    public function obtener(): array;

    public function guardar(array $datos): void;
}
