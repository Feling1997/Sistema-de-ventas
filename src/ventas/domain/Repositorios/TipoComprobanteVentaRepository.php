<?php

declare(strict_types=1);

namespace Ventas\Ventas\Domain\Repositorios;

interface TipoComprobanteVentaRepository
{
    public function listar(): array;
}
