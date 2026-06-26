<?php

declare(strict_types=1);

namespace Ventas\Productos\Application;

use Ventas\Productos\Domain\Repositorios\ProductoRepository;

final class ListarProductosParaExportar
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(bool $soloAlta = true): array
    {
        return $this->productoRepository->listarParaExportar($soloAlta);
    }
}
