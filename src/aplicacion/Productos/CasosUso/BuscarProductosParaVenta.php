<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Productos\CasosUso;

use Ventas\Dominio\Productos\Repositorios\ProductoRepository;

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
