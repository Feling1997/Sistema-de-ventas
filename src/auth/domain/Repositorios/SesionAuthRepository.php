<?php

declare(strict_types=1);

namespace Ventas\Auth\Domain\Repositorios;

interface SesionAuthRepository
{
    public function obtenerUsuario(): ?array;

    public function guardarUsuario(array $usuario): void;

    public function limpiar(): void;
}
