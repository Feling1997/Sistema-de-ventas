<?php

declare(strict_types=1);

namespace Ventas\Auth\Domain\Repositorios;

interface ConfiguracionAuthRepository
{
    public function modoAuth(): ?string;

    public function sinLoginHabilitado(): bool;

    public function noHayUsuariosCreados(): bool;

    public function existeUsuarioEspecialSinLogin(): bool;
}
