<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\ListasPrecios\CasosUso;

use Ventas\Dominio\ListasPrecios\Repositorios\ListaPrecioRepository;

final class ListarListasPrecios
{
    public function __construct(private readonly ListaPrecioRepository $listaPrecioRepository)
    {
    }

    public function ejecutar(): array
    {
        return $this->listaPrecioRepository->listar();
    }
}
