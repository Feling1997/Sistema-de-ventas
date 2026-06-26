<?php

declare(strict_types=1);

namespace Ventas\Configuracion\Domain\Repositorios;

interface ConfiguracionRepository
{
    public function obtenerGeneral(): array;

    public function obtenerFiscal(): array;

    public function obtenerVenta(): array;

    public function obtenerBalanza(): array;

    public function obtenerAuth(): array;

    /**
     * @param array<string, mixed> $datos
     */
    public function guardar(array $datos): bool;

    public function restablecerGrupo(string $grupo): bool;

    /**
     * @return array<string, string>
     */
    public function obtenerGrupo(string $grupo): array;

    /**
     * @return array<string, array<string, string>>
     */
    public function obtenerMetadatos(): array;

    public function inicializarEsquema(): bool;
}
