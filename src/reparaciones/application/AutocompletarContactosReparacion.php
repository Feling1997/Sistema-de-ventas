<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Core\Contactos\Application\AutocompletarContactos;

final class AutocompletarContactosReparacion
{
    public function __construct(private readonly AutocompletarContactos $autocompletarContactos)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function ejecutar(string $texto): array
    {
        $resultados = array_slice($this->autocompletarContactos->ejecutar($texto), 0, 20);

        return $resultados;
    }
}
