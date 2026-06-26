<?php

declare(strict_types=1);

namespace Ventas\Core\Contactos\Application;

use Ventas\Core\Contactos\Domain\Entidades\Contacto;
use Ventas\Core\Contactos\Domain\Repositorios\ContactoRepository;

final class BuscarContactoPorDocumento
{
    public function __construct(private readonly ContactoRepository $contactoRepository)
    {
    }

    public function ejecutar(string $documento): ?Contacto
    {
        $contacto = $this->contactoRepository->buscarPorDocumento($documento);

        return $contacto;
    }
}
