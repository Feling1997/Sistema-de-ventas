<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\ListasPrecios\CasosUso;

use Ventas\Dominio\ListasPrecios\Entidades\ListaPrecio;
use Ventas\Dominio\ListasPrecios\Repositorios\ListaPrecioRepository;

final class BuscarListaPrecioPorId
{
    public function __construct(private readonly ListaPrecioRepository $listaPrecioRepository)
    {
    }

    public function ejecutar(int $id): ?ListaPrecio
    {
        return $this->listaPrecioRepository->buscarPorId($id);
    }
}
