<?php

declare(strict_types=1);

namespace Ventas\Sistema\Application;

use Ventas\Instalacion\Application\VerificarInstalacion;
use Ventas\Reparaciones\Application\ObtenerSaludReparaciones;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

final class GenerarDiagnosticoSistema
{
    public function __construct(
        private readonly VerificarInstalacion $instalacion,
        private readonly ObtenerSaludReparaciones $saludReparaciones,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function ejecutar(): array
    {
        $instalacion = $this->instalacion->ejecutar(false);
        $migraciones = $this->migraciones();
        $instalacion['migraciones'] = $migraciones;
        $backups = $this->backups();
        $salud = $this->salud();
        $resultado = [
            'nombre' => (string) config('sistema.nombre'),
            'version' => (string) config('sistema.version'),
            'estado_general' => $this->estadoGeneral($instalacion, $migraciones, $backups, $salud),
            'modo' => (string) config('reparaciones.modo', config('sistema.modo', 'laravel')),
            'php' => $instalacion['php'],
            'extensiones' => $instalacion['extensiones'],
            'mariadb' => $instalacion['mariadb'],
            'storage' => $instalacion['storage'],
            'bases' => $instalacion['bases'],
            'migraciones' => $migraciones,
            'backups' => $backups,
            'salud' => $salud,
        ];

        return $resultado;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function migraciones(): array
    {
        $datos = [
            'sistema_core' => $this->migracionConexion('sistema_core', [
                '2026_06_20_000001_create_core_contactos_table',
            ]),
            'sistema_reparaciones' => $this->migracionConexion('sistema_reparaciones', [
                '2026_06_21_000001_create_reparaciones_estados_table',
                '2026_06_21_000002_create_reparaciones_equipos_table',
                '2026_06_21_000003_create_reparaciones_table',
                '2026_06_21_000004_create_reparaciones_adjuntos_table',
                '2026_06_21_000005_create_reparaciones_tickets_table',
                '2026_06_22_000001_create_configuracion_reparaciones_table',
                '2026_06_22_000002_create_reparaciones_auditoria_table',
                '2026_06_22_000003_add_production_indexes_reparaciones',
                '2026_06_22_000004_add_miniatura_to_reparaciones_adjuntos_table',
            ]),
            'sistema_ventas' => [
                'estado' => 'OK',
                'mensaje' => 'sin migraciones Laravel especificas aun',
                'pendientes' => 0,
            ],
        ];

        return $datos;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param array<int, string> $esperadas
     * @return array<string, mixed>
     */
    private function migracionConexion(string $conexion, array $esperadas): array
    {
        $estado = 'OK';
        $mensaje = 'sin migraciones pendientes';
        $pendientes = 0;

        try {
            $ejecutadas = DB::connection($conexion)->table('migrations')->pluck('migration')->all();
            $faltantes = array_values(array_diff($esperadas, array_map('strval', $ejecutadas)));
            $pendientes = count($faltantes);
            $estado = $pendientes === 0 ? 'OK' : 'WARNING';
            $mensaje = $pendientes === 0 ? 'sin migraciones pendientes' : 'migraciones pendientes: ' . $pendientes;
        } catch (Throwable $throwable) {
            $estado = 'WARNING';
            $mensaje = $throwable->getMessage();
        }

        return ['estado' => $estado, 'mensaje' => $mensaje, 'pendientes' => $pendientes];
    }

    /**
     * @return array<string, mixed>
     */
    private function backups(): array
    {
        $ruta = base_path('../backups');
        $archivos = File::exists($ruta) ? File::files($ruta) : [];
        $directorios = File::exists($ruta) ? File::directories($ruta) : [];
        $datos = [
            'estado' => File::exists($ruta) ? 'OK' : 'WARNING',
            'ruta' => $ruta,
            'archivos_json' => collect($archivos)->filter(static fn ($archivo): bool => $archivo->getExtension() === 'json')->count(),
            'directorios' => count($directorios),
        ];

        return $datos;
    }

    /**
     * @return array<string, mixed>
     */
    private function salud(): array
    {
        $datos = ['estado' => 'WARNING', 'mensaje' => 'salud no disponible'];

        try {
            $datos = $this->saludReparaciones->ejecutar();
        } catch (Throwable $throwable) {
            $datos = ['estado' => 'WARNING', 'mensaje' => $throwable->getMessage()];
        }

        return $datos;
    }

    /**
     * @param array<string, mixed> $instalacion
     * @param array<string, mixed> $migraciones
     * @param array<string, mixed> $backups
     * @param array<string, mixed> $salud
     */
    private function estadoGeneral(array $instalacion, array $migraciones, array $backups, array $salud): string
    {
        $estados = collect([$instalacion, $migraciones, $backups, $salud])->flatMap(fn (array $item): array => $this->extraerEstados($item));
        $estado = 'OK';

        if ($estados->contains('ERROR')) {
            $estado = 'ERROR';
        } elseif ($estados->contains('WARNING') || $estados->contains('PENDIENTE')) {
            $estado = 'WARNING';
        }

        return $estado;
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<int, string>
     */
    private function extraerEstados(array $datos): array
    {
        $estados = [];

        foreach ($datos as $clave => $valor) {
            if ($clave === 'estado' && is_string($valor)) {
                $estados[] = $valor;
            } elseif (is_array($valor)) {
                $estados = array_merge($estados, $this->extraerEstados($valor));
            }
        }

        return $estados;
    }
}
