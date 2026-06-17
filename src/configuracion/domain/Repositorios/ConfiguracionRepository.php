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
}
