<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application;

use Ventas\Ventas\Domain\Repositorios\TipoComprobanteVentaRepository;

final class ObtenerTiposComprobanteVenta
{
    public function __construct(private readonly TipoComprobanteVentaRepository $tipoComprobanteVentaRepository)
    {
    }

    public function ejecutar(): array
    {
        $tiposComprobante = $this->tipoComprobanteVentaRepository->listar();

        return $tiposComprobante;
    }
}
