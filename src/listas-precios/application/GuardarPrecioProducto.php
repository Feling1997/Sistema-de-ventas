<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Application;

use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;

final class GuardarPrecioProducto
{
    public function __construct(private readonly ListaPrecioRepository $listaPrecioRepository)
    {
    }

    public function ejecutar(int $idProducto, int $idLista, float $porcentaje, float $precio): bool
    {
        $resultado = $this->listaPrecioRepository->guardarPrecioProducto($idProducto, $idLista, $porcentaje, $precio);

        return $resultado;
    }
}
