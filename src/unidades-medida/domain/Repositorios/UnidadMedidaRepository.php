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

    public function buscarPorAbreviatura(string $abreviatura): ?UnidadMedida;

    public function crear(string $nombre, string $abreviatura, string $tipo, int $decimales, int $activo = 1): bool;

    public function crearSinDuplicar(string $nombre, string $abreviatura, string $tipo, int $decimales, int $activo = 1): ?UnidadMedida;

    public function asegurarDesdeFormulario(string $unidad, array $datos): string;
}
