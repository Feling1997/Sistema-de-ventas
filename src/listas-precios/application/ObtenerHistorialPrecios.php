<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Application;

use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;

final class ObtenerHistorialPrecios
{
    public function __construct(private readonly ListaPrecioRepository $listaPrecioRepository)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function ejecutar(string $desde = '', string $hasta = '', int $idLista = 0): array
    {
        $resultado = $this->listaPrecioRepository->historialPrecios($desde, $hasta, $idLista);

        return $resultado;
    }
}
