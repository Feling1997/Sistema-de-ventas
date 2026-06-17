<?php

declare(strict_types=1);

namespace Ventas\Productos\Application;

use Ventas\Productos\Domain\Repositorios\ProductoRepository;

final class BuscarProductosParaVenta
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(
        string $texto,
        string $modo,
        int $idListaPrecio,
        int $limite = 30
    ): array {
        return $this->productoRepository->buscarParaVenta($texto, $modo, $idListaPrecio, $limite);
    }
}
