<?php

declare(strict_types=1);

namespace Ventas\UnidadesMedida\Application;

use Ventas\UnidadesMedida\Domain\Entidades\UnidadMedida;
use Ventas\UnidadesMedida\Domain\Repositorios\UnidadMedidaRepository;

final class BuscarUnidadMedidaPorId
{
    public function __construct(private readonly UnidadMedidaRepository $unidadMedidaRepository)
    {
    }

    public function ejecutar(int $id): ?UnidadMedida
    {
        return $this->unidadMedidaRepository->buscarPorId($id);
    }
}
