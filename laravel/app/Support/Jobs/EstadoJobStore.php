<?php

declare(strict_types=1);

namespace App\Support\Jobs;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class EstadoJobStore
{
    private const DIRECTORIO = 'jobs';

    public function crear(string $tipo): array
    {
        $id = (string) Str::uuid();
        $estado = [
            'id' => $id,
            'tipo' => $tipo,
            'estado' => 'pendiente',
            'porcentaje' => 0,
            'mensaje' => 'Job en cola.',
            'archivo' => null,
        ];

        Storage::disk('local')->put($this->ruta($id), json_encode($estado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $estado;
    }

    public function actualizar(string $id, string $estado, int $porcentaje, string $mensaje, ?string $archivo = null): array
    {
        $actual = $this->obtener($id);
        $actual['estado'] = $estado;
        $actual['porcentaje'] = max(0, min(100, $porcentaje));
        $actual['mensaje'] = $mensaje;
        $actual['archivo'] = $archivo;

        Storage::disk('local')->put($this->ruta($id), json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $actual;
    }

    public function obtener(string $id): array
    {
        $ruta = $this->ruta($id);
        $estado = [
            'id' => $id,
            'tipo' => 'desconocido',
            'estado' => 'error',
            'porcentaje' => 0,
            'mensaje' => 'Job no encontrado.',
            'archivo' => null,
        ];

        if (Storage::disk('local')->exists($ruta)) {
            $contenido = Storage::disk('local')->get($ruta);
            $decodificado = json_decode((string) $contenido, true);

            if (is_array($decodificado)) {
                $estado = $decodificado;
            }
        }

        return $estado;
    }

    private function ruta(string $id): string
    {
        return self::DIRECTORIO . '/' . $id . '.json';
    }
}
