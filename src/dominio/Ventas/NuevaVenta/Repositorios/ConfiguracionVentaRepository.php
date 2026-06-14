<?php

declare(strict_types=1);

namespace Ventas\Dominio\Ventas\NuevaVenta\Repositorios;

interface ConfiguracionVentaRepository
{
    public function configuracionFiscal(): array;

    public function configuracionInicio(): array;

    public function configuracionBalanza(): array;

    public function controlarStockVentas(): bool;
}
