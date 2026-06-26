<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Domain\Repositorios;

interface ReparacionRepository
{
    /**
     * Contrato reservado para persistir reparaciones completas en una fase futura.
     *
     * Campos esperados:
     * codigo, contacto_id, equipo_id, estado_id, problema, diagnostico,
     * garantia, precio, observaciones, fecha_ingreso, fecha_entrega, activo.
     *
     * @param array<string, mixed> $datos
     */
    public function crear(array $datos): mixed;
}
