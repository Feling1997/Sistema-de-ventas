<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Application;

use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;

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
