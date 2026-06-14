<?php

declare(strict_types=1);

namespace Ventas\Dominio\UnidadesMedida\Repositorios;

use Ventas\Dominio\UnidadesMedida\Entidades\UnidadMedida;

interface UnidadMedidaRepository
{
    /**
     * @return UnidadMedida[]
     */
    public function listar(): array;

    public function buscarPorId(int $id): ?UnidadMedida;
}
