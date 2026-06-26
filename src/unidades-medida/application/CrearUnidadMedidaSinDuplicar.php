<?php

declare(strict_types=1);

namespace Ventas\UnidadesMedida\Application;

use Ventas\UnidadesMedida\Domain\Entidades\UnidadMedida;
use Ventas\UnidadesMedida\Domain\Repositorios\UnidadMedidaRepository;

final class CrearUnidadMedidaSinDuplicar
{
    public function __construct(private readonly UnidadMedidaRepository $unidadMedidaRepository)
    {
    }

    public function ejecutar(string $nombre, string $abreviatura, string $tipo, int $decimales, int $activo = 1): ?UnidadMedida
    {
        return $this->unidadMedidaRepository->crearSinDuplicar($nombre, $abreviatura, $tipo, $decimales, $activo);
    }
}
