<?php

declare(strict_types=1);

namespace Ventas\Core\Contactos\Application;

use Ventas\Core\Contactos\Domain\Repositorios\ContactoRepository;

/**
 * Preparado para sugerir contactos al escribir nombre, telefono o documento.
 *
 * Ejemplo futuro:
 * buscar "Juan" debera sugerir "Juan Perez", telefono y documento. Al elegir
 * una opcion, la UI podra completar nombre, apellido, telefono, correo,
 * documento y direccion para evitar volver a tipear datos.
 */
final class AutocompletarContactos
{
    public function __construct(private readonly ContactoRepository $contactoRepository)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function ejecutar(string $texto): array
    {
        $resultados = [];

        foreach ($this->contactoRepository->autocompletar($texto) as $contacto) {
            $resultados[] = [
                'id' => $contacto->id(),
                'nombre' => $contacto->nombre(),
                'apellido' => $contacto->apellido(),
                'telefono' => $contacto->telefono(),
                'correo' => $contacto->correo(),
                'documento' => $contacto->documento(),
                'direccion' => $contacto->direccion(),
            ];
        }

        return $resultados;
    }
}
