<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Domain\Repositorios\ConfiguracionReparacionesRepository;

final class GuardarConfiguracionReparaciones
{
    public function __construct(private readonly ConfiguracionReparacionesRepository $configuracion)
    {
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function ejecutar(array $datos): array
    {
        $configuracion = $this->configuracion->guardar($datos);
        $resultado = [
            'ok' => true,
            'mensaje' => 'Configuracion de reparaciones guardada.',
            'configuracion' => $configuracion,
        ];

        return $resultado;
    }
}
