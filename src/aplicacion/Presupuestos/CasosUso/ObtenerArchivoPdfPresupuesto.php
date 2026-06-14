<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Presupuestos\CasosUso;

use Ventas\Dominio\Presupuestos\Repositorios\ComprobantePresupuestoRepository;

final class ObtenerArchivoPdfPresupuesto
{
    public function __construct(private readonly ComprobantePresupuestoRepository $comprobantePresupuestoRepository)
    {
    }

    public function ejecutar(int $idPresupuesto): array
    {
        $archivo = $this->comprobantePresupuestoRepository->obtenerArchivoPdf($idPresupuesto);

        return $archivo;
    }
}
