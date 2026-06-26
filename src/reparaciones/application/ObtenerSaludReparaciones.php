<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\AuditoriaReparacionModel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ObtenerSaludReparaciones
{
    /**
     * @return array<string, mixed>
     */
    public function ejecutar(): array
    {
        $criticos = AuditoriaReparacionModel::query()->where('severidad', 'critico')->count();
        $medios = AuditoriaReparacionModel::query()->where('severidad', 'medio')->count();
        $bajos = AuditoriaReparacionModel::query()->where('severidad', 'bajo')->where('resultado', '!=', 'ok')->count();
        $fallidas = AuditoriaReparacionModel::query()->where('resultado', '!=', 'ok')->latest()->limit(20)->get();
        $checks = [
            'base_sistema_core' => $this->verificarBase('sistema_core'),
            'base_sistema_reparaciones' => $this->verificarBase('sistema_reparaciones'),
            'storage' => $this->verificarStorage(),
            'cache' => $this->verificarCache(),
            'configuracion' => $this->verificarConfiguracion(),
            'migraciones' => $this->verificarMigraciones(),
        ];
        $resultado = [
            'estado' => $this->estadoGeneral($checks, $criticos),
            'errores_criticos' => $criticos,
            'errores_medios' => $medios,
            'errores_bajos' => $bajos,
            'checks' => $checks,
            'eventos_fallidos' => $fallidas->map(static fn (AuditoriaReparacionModel $fila): array => [
                'fecha' => $fila->created_at,
                'accion' => $fila->accion,
                'reparacion_id' => $fila->reparacion_id,
                'resultado' => $fila->resultado,
                'severidad' => $fila->severidad,
                'mensaje' => $fila->mensaje,
            ])->all(),
        ];

        return $resultado;
    }

    /**
     * @return array<string, string>
     */
    private function verificarBase(string $conexion): array
    {
        $estado = 'OK';
        $mensaje = 'conexion disponible';

        try {
            DB::connection($conexion)->getPdo();
        } catch (Throwable $exception) {
            $estado = 'ERROR';
            $mensaje = $exception->getMessage();
        }

        return ['estado' => $estado, 'mensaje' => $mensaje];
    }

    /**
     * @return array<string, string>
     */
    private function verificarStorage(): array
    {
        $estado = 'OK';
        $mensaje = 'storage disponible';

        try {
            Storage::disk('local')->exists('reparaciones');
        } catch (Throwable $exception) {
            $estado = 'ERROR';
            $mensaje = $exception->getMessage();
        }

        return ['estado' => $estado, 'mensaje' => $mensaje];
    }

    /**
     * @return array<string, string>
     */
    private function verificarCache(): array
    {
        $estado = 'OK';
        $mensaje = 'cache disponible';

        try {
            Cache::put('reparaciones.salud.cache', 'ok', 60);
            $mensaje = Cache::get('reparaciones.salud.cache') === 'ok' ? 'cache disponible' : 'cache sin lectura';
            $estado = $mensaje === 'cache disponible' ? 'OK' : 'WARNING';
        } catch (Throwable $exception) {
            $estado = 'ERROR';
            $mensaje = $exception->getMessage();
        }

        return ['estado' => $estado, 'mensaje' => $mensaje];
    }

    /**
     * @return array<string, string>
     */
    private function verificarConfiguracion(): array
    {
        $modo = (string) config('reparaciones.modo');
        $estado = $modo === 'laravel' ? 'OK' : 'WARNING';
        $mensaje = 'modo=' . $modo;

        return ['estado' => $estado, 'mensaje' => $mensaje];
    }

    /**
     * @return array<string, string>
     */
    private function verificarMigraciones(): array
    {
        $estado = 'OK';
        $mensaje = 'migrate:status disponible';

        try {
            $codigo = Artisan::call('migrate:status', ['--database' => 'sistema_reparaciones']);
            $estado = $codigo === 0 ? 'OK' : 'WARNING';
        } catch (Throwable $exception) {
            $estado = 'WARNING';
            $mensaje = $exception->getMessage();
        }

        return ['estado' => $estado, 'mensaje' => $mensaje];
    }

    /**
     * @param array<string, array<string, string>> $checks
     */
    private function estadoGeneral(array $checks, int $criticos): string
    {
        $estado = 'OK';

        if ($criticos > 0 || collect($checks)->contains(static fn (array $check): bool => $check['estado'] === 'ERROR')) {
            $estado = 'ERROR';
        } elseif (collect($checks)->contains(static fn (array $check): bool => $check['estado'] === 'WARNING')) {
            $estado = 'WARNING';
        }

        return $estado;
    }
}
