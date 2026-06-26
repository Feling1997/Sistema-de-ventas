<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Application;

use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;

final class ObtenerPrecioProductoCompleto
{
    public function __construct(private readonly ListaPrecioRepository $listaPrecioRepository)
    {
    }

    /**
     * @return array{porcentaje: float, precio: float}|null
     */
    public function ejecutar(int $idProducto, int $idLista): ?array
    {
        $resultado = $this->listaPrecioRepository->precioProductoCompleto($idProducto, $idLista);

        return $resultado;
    }
}
