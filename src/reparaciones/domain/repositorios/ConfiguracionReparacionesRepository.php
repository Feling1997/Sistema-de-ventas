<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Domain\Repositorios;

interface ConfiguracionReparacionesRepository
{
    /**
     * @return array<string, string>
     */
    public function obtenerTodo(): array;

    /**
     * @param array<string, mixed> $datos
     * @return array<string, string>
     */
    public function guardar(array $datos): array;
}
