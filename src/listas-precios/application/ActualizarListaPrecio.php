<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Application;

use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;

final class ActualizarListaPrecio
{
    public function __construct(private readonly ListaPrecioRepository $listaPrecioRepository)
    {
    }

    public function ejecutar(int $id, string $nombre, int $activo): bool
    {
        $resultado = $this->listaPrecioRepository->actualizar($id, $nombre, $activo);

        return $resultado;
    }
}
