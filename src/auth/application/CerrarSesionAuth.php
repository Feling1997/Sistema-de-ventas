<?php

declare(strict_types=1);

namespace Ventas\Auth\Application;

use Ventas\Auth\Domain\Repositorios\SesionAuthRepository;

final class CerrarSesionAuth
{
    public function __construct(private readonly SesionAuthRepository $sesionAuthRepository)
    {
    }

    public function ejecutar(): bool
    {
        $this->sesionAuthRepository->limpiar();
        $ok = true;

        return $ok;
    }
}
