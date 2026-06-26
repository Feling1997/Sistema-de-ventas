<?php

declare(strict_types=1);

namespace Ventas\Core\Contactos\Application;

use Ventas\Core\Contactos\Domain\Entidades\Contacto;
use Ventas\Core\Contactos\Domain\Repositorios\ContactoRepository;

final class CrearContacto
{
    public function __construct(private readonly ContactoRepository $contactoRepository)
    {
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function ejecutar(array $datos): Contacto
    {
        $contacto = $this->contactoRepository->crear($datos);

        return $contacto;
    }
}
