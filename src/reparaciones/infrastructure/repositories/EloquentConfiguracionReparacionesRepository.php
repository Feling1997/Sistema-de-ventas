<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Infrastructure\Repositories;

use Ventas\Reparaciones\Domain\Repositorios\ConfiguracionReparacionesRepository;
use Ventas\Reparaciones\Infrastructure\Models\ConfiguracionReparacionesModel;
use Illuminate\Support\Facades\Cache;

final class EloquentConfiguracionReparacionesRepository implements ConfiguracionReparacionesRepository
{
    /**
     * @return array<string, string>
     */
    public function obtenerTodo(): array
    {
        $configuracion = Cache::remember('reparaciones.configuracion', 300, function (): array {
            $datos = $this->defaults();
            $filas = ConfiguracionReparacionesModel::query()->get(['clave', 'valor']);

            foreach ($filas as $fila) {
                $clave = (string) $fila->clave;
                if (array_key_exists($clave, $datos)) {
                    $datos[$clave] = (string) $fila->valor;
                }
            }

            return $datos;
        });

        return $configuracion;
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, string>
     */
    public function guardar(array $datos): array
    {
        $configuracion = $this->defaults();

        foreach ($configuracion as $clave => $valorInicial) {
            $valor = array_key_exists($clave, $datos) ? (string) $datos[$clave] : $valorInicial;
            ConfiguracionReparacionesModel::query()->updateOrCreate(
                ['clave' => $clave],
                [
                    'valor' => $valor,
                    'tipo' => $this->tipo($clave),
                    'grupo' => 'tickets',
                ]
            );
            $configuracion[$clave] = $valor;
        }
        Cache::forget('reparaciones.configuracion');

        return $configuracion;
    }

    /**
     * @return array<string, string>
     */
    private function defaults(): array
    {
        $defaults = [
            'nombre_comercio' => 'Reparaciones',
            'telefono_comercio' => '',
            'direccion_comercio' => '',
            'impresora_predeterminada' => '',
            'mostrar_logo' => '0',
            'texto_ticket' => 'Gracias por su visita',
            'observaciones_ticket' => '',
        ];

        return $defaults;
    }

    private function tipo(string $clave): string
    {
        $tipo = $clave === 'mostrar_logo' ? 'boolean' : 'string';

        return $tipo;
    }
}
