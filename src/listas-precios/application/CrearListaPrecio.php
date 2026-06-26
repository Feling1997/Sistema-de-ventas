<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Application;

use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;

final class CrearListaPrecio
{
    public function __construct(private readonly ListaPrecioRepository $listaPrecioRepository)
    {
    }

    public function ejecutar(string $nombre, int $activo): bool
    {
        $resultado = $this->listaPrecioRepository->crear($nombre, $activo);

        return $resultado;
    }
}
