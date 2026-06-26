<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backups;

use App\Http\Controllers\Controller;
use App\Jobs\CrearBackupJob;
use App\Jobs\SubirBackblazeJob;
use App\Support\Jobs\EstadoJobStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Ventas\Backups\Application\GenerarEstructuraRespaldo;
use Ventas\Backups\Application\GenerarResumenRespaldo;
use Ventas\Backups\Application\GenerarTextoResumenRespaldo;
use Ventas\Core\Infrastructure\Container\Container;

final class BackupsController extends Controller
{
    private const DIRECTORIO = 'backups';

    public function __construct(
        private readonly Container $container,
        private readonly EstadoJobStore $estadoJobStore
    ) {
    }

    public function index(): View
    {
        Storage::disk('local')->makeDirectory(self::DIRECTORIO);

        return view('backups.index', [
            'resumen' => $this->resumen(),
            'estructura' => $this->estructura(),
        ]);
    }

    public function crearBackup(): JsonResponse
    {
        $estado = $this->estadoJobStore->crear('crear_backup');
        CrearBackupJob::dispatch($estado['id']);

        return response()->json($this->estadoJobStore->obtener($estado['id']));
    }

    public function subirBackblaze(): JsonResponse
    {
        $estado = $this->estadoJobStore->crear('subir_backblaze');
        SubirBackblazeJob::dispatch($estado['id']);

        return response()->json($this->estadoJobStore->obtener($estado['id']));
    }

    public function listarBackups(): JsonResponse
    {
        return response()->json([
            'backups' => $this->archivos(),
        ]);
    }

    public function descargar(string $id): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'mensaje' => 'Descarga real no habilitada en esta fase.',
            'id' => $id,
        ], 409);
    }

    public function eliminar(string $id): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'mensaje' => 'Eliminacion real no habilitada en esta fase.',
            'id' => $id,
        ], 409);
    }

    public function estadoJob(string $id): JsonResponse
    {
        return response()->json($this->estadoJobStore->obtener($id));
    }

    private function resumen(): array
    {
        /** @var GenerarResumenRespaldo $generarResumen */
        $generarResumen = $this->container->get(GenerarResumenRespaldo::class);
        /** @var GenerarTextoResumenRespaldo $generarTexto */
        $generarTexto = $this->container->get(GenerarTextoResumenRespaldo::class);
        $resumen = $generarResumen->ejecutar();
        $resumen['texto'] = $generarTexto->ejecutar($resumen);

        return $resumen;
    }

    private function estructura(): string
    {
        /** @var GenerarEstructuraRespaldo $generarEstructura */
        $generarEstructura = $this->container->get(GenerarEstructuraRespaldo::class);

        return $generarEstructura->ejecutar();
    }

    private function archivos(): array
    {
        Storage::disk('local')->makeDirectory(self::DIRECTORIO);
        $archivos = array_map(
            static fn (string $ruta): array => [
                'id' => basename($ruta),
                'nombre' => basename($ruta),
                'ruta' => $ruta,
                'tamano' => Storage::disk('local')->size($ruta),
                'modificado' => Storage::disk('local')->lastModified($ruta),
            ],
            Storage::disk('local')->files(self::DIRECTORIO)
        );

        return array_slice($archivos, 0, 20);
    }
}
