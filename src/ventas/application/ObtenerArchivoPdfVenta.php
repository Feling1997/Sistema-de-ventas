<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application;

use Ventas\Ventas\Domain\Repositorios\ComprobanteVentaRepository;

final class ObtenerArchivoPdfVenta
{
    public function __construct(private readonly ComprobanteVentaRepository $comprobanteVentaRepository)
    {
    }

    public function ejecutar(int $idVenta): array
    {
        $archivo = $this->comprobanteVentaRepository->obtenerArchivoPdf($idVenta);

        return $archivo;
    }
}
