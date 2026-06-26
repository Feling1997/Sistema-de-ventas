<?php

declare(strict_types=1);

namespace Ventas\Instalacion\Application;

use Illuminate\Support\Facades\File;

final class GuardarModoReparaciones
{
    /**
     * @return array<string, mixed>
     */
    public function ejecutar(string $modo): array
    {
        $modoFinal = 'laravel';
        $directorio = storage_path('app/instalacion');
        $ruta = $directorio . '/reparaciones_modo.json';
        File::ensureDirectoryExists($directorio);
        File::put($ruta, json_encode(['modo' => $modoFinal, 'fecha' => date('c')], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $resultado = ['ok' => true, 'modo' => $modoFinal, 'ruta' => $ruta];

        return $resultado;
    }
}
