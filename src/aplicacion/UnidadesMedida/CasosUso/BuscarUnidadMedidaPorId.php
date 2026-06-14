<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\UnidadesMedida\CasosUso;

use Ventas\Dominio\UnidadesMedida\Entidades\UnidadMedida;
use Ventas\Dominio\UnidadesMedida\Repositorios\UnidadMedidaRepository;

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
