<?php

declare(strict_types=1);

namespace Ventas\Auth\Application;

use Ventas\Auth\Domain\Repositorios\ConfiguracionAuthRepository;

final class VerificarModoSinLogin
{
    public function __construct(private readonly ConfiguracionAuthRepository $configuracionAuthRepository)
    {
    }

    public function ejecutar(): array
    {
        $sinLogin = $this->configuracionAuthRepository->sinLoginHabilitado();
        $noHayUsuarios = $this->configuracionAuthRepository->noHayUsuariosCreados();
        $resultado = [
            'modo' => $this->configuracionAuthRepository->modoAuth(),
            'sin_login_habilitado' => $sinLogin,
            'no_hay_usuarios_creados' => $noHayUsuarios,
            'admin_local_habilitado' => $sinLogin || $noHayUsuarios,
            'usuario_especial_sin_login' => $this->configuracionAuthRepository->existeUsuarioEspecialSinLogin(),
        ];

        return $resultado;
    }
}
