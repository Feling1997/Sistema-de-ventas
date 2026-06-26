<?php

declare(strict_types=1);

namespace Ventas\Core\Contactos\Application;

use Ventas\Core\Contactos\Domain\Repositorios\ContactoRepository;

final class DesactivarContacto
{
    public function __construct(private readonly ContactoRepository $contactoRepository)
    {
    }

    public function ejecutar(int $id): bool
    {
        $desactivado = $this->contactoRepository->desactivar($id);

        return $desactivado;
    }
}
