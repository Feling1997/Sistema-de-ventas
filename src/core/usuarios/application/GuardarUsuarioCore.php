<?php

declare(strict_types=1);

namespace Ventas\Core\Usuarios\Application;

use Ventas\Core\Usuarios\Domain\Repositorios\UsuarioRepository;

final class GuardarUsuarioCore
{
    public function __construct(private readonly UsuarioRepository $usuarioRepository)
    {
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function ejecutar(array $datos): array
    {
        $usuario = $this->usuarioRepository->guardar($datos);
        $respuesta = ['ok' => true, 'usuario' => $usuario->comoArray()];

        return $respuesta;
    }
}
