<?php

declare(strict_types=1);

namespace Ventas\Core\Contactos\Application;

use Ventas\Core\Contactos\Domain\Entidades\Contacto;
use Ventas\Core\Contactos\Domain\Repositorios\ContactoRepository;

final class ActualizarContacto
{
    public function __construct(private readonly ContactoRepository $contactoRepository)
    {
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function ejecutar(int $id, array $datos): ?Contacto
    {
        $contacto = $this->contactoRepository->actualizar($id, $datos);

        return $contacto;
    }
}
