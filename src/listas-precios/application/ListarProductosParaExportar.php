<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Application;

use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;

final class ListarProductosParaExportar
{
    public function __construct(private readonly ListaPrecioRepository $listaPrecioRepository)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function ejecutar(int $idLista = 0): array
    {
        $resultado = $this->listaPrecioRepository->productosParaExportar($idLista);

        return $resultado;
    }
}
