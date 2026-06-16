<?php

declare(strict_types=1);

namespace Ventas\UnidadesMedida\Application;

use Ventas\UnidadesMedida\Domain\Repositorios\UnidadMedidaRepository;

final class ListarUnidadesMedida
{
    public function __construct(private readonly UnidadMedidaRepository $unidadMedidaRepository)
    {
    }

    public function ejecutar(): array
    {
        return $this->unidadMedidaRepository->listar();
    }
}
