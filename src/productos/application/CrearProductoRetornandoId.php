<?php

declare(strict_types=1);

namespace Ventas\Productos\Application;

use Ventas\Productos\Domain\Repositorios\ProductoRepository;

final class CrearProductoRetornandoId
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(
        string $nombre,
        string $codBarras,
        ?int $idStock,
        float $factorConversion,
        float $ganancia,
        float $precioFinal,
        int $activo
    ): int {
        return $this->productoRepository->crearRetornandoId($nombre, $codBarras, $idStock, $factorConversion, $ganancia, $precioFinal, $activo);
    }
}
