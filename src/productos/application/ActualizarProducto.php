<?php

declare(strict_types=1);

namespace Ventas\Productos\Application;

use Ventas\Productos\Domain\Repositorios\ProductoRepository;

final class ActualizarProducto
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(
        int $id,
        string $nombre,
        string $codBarras,
        ?int $idStock,
        float $factorConversion,
        float $ganancia,
        float $precioFinal,
        int $activo
    ): bool {
        return $this->productoRepository->actualizar($id, $nombre, $codBarras, $idStock, $factorConversion, $ganancia, $precioFinal, $activo);
    }
}
