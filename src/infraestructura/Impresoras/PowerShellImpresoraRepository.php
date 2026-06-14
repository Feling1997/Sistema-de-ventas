<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Impresoras;

use Ventas\Dominio\Impresoras\Repositorios\ImpresoraRepository;

final class PowerShellImpresoraRepository implements ImpresoraRepository
{
    public function listar(): array
    {
        $impresoras = [];
        $salida = @shell_exec('powershell -NoProfile -Command "Get-Printer | Select-Object -ExpandProperty Name"');

        if (is_string($salida) && trim($salida) !== '') {
            $lineas = preg_split('/\r?\n/', $salida);
            $lineas = is_array($lineas) ? $lineas : [];

            foreach ($lineas as $linea) {
                $nombre = trim((string) $linea);

                if ($nombre !== '') {
                    $impresoras[] = $nombre;
                }
            }
        }

        $impresoras = array_values(array_unique($impresoras));

        return $impresoras;
    }
}
