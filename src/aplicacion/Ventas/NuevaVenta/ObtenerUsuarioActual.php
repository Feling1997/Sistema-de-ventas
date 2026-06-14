<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\NuevaVenta;

use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\UsuarioActualRepository;

final class ObtenerUsuarioActual
{
    public function __construct(private readonly UsuarioActualRepository $usuarioActualRepository)
    {
    }

    public function ejecutar(): array
    {
        $usuario = $this->usuarioActualRepository->obtener();

        return $usuario;
    }
}
