<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application\NuevaVenta;

use Ventas\Ventas\Domain\NuevaVenta\Repositorios\MenuVentasRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\UsuarioActualRepository;

final class GuardarMenuVentas
{
    public function __construct(
        private readonly UsuarioActualRepository $usuarioActualRepository,
        private readonly MenuVentasRepository $menuVentasRepository
    ) {
    }

    public function ejecutar(array $seleccion): bool
    {
        $usuario = $this->usuarioActualRepository->obtener();
        $ok = $this->menuVentasRepository->guardarPreferencias((int) ($usuario['id'] ?? 0), (string) ($usuario['rol'] ?? ''), $seleccion);

        return $ok;
    }
}
