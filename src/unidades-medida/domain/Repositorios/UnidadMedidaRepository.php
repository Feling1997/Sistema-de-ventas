<?php

declare(strict_types=1);

namespace Ventas\UnidadesMedida\Domain\Repositorios;

use Ventas\UnidadesMedida\Domain\Entidades\UnidadMedida;

interface UnidadMedidaRepository
{
    /**
     * @return UnidadMedida[]
     */
    public function listar(): array;

    public function buscarPorId(int $id): ?UnidadMedida;
}
