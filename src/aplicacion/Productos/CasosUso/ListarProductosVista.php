<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Productos\CasosUso;

use Ventas\Dominio\Productos\Repositorios\ProductoRepository;

final class ListarProductosVista
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(string $ordenCampo, string $ordenDireccion, int $idListaPrecio): array
    {
        return $this->productoRepository->listarParaVista($ordenCampo, $ordenDireccion, $idListaPrecio);
    }
}
