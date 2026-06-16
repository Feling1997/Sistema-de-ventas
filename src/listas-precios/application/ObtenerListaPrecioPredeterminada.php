<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Application;

use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;

final class ObtenerListaPrecioPredeterminada
{
    public function __construct(private readonly ListaPrecioRepository $listaPrecioRepository)
    {
    }

    public function ejecutar(): int
    {
        $id = $this->listaPrecioRepository->idPredeterminada();

        return $id;
    }
}
