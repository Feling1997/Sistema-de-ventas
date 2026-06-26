<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Application;

use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;

final class ObtenerPrecioProducto
{
    public function __construct(private readonly ListaPrecioRepository $listaPrecioRepository)
    {
    }

    public function ejecutar(int $idProducto, int $idLista): ?float
    {
        $resultado = $this->listaPrecioRepository->precioProducto($idProducto, $idLista);

        return $resultado;
    }
}
