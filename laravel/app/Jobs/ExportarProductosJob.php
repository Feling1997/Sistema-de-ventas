<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\Exportaciones\CsvExportacionWriter;
use App\Support\Jobs\EstadoJobStore;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Productos\Application\ListarProductos;

final class ExportarProductosJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $jobId)
    {
    }

    public function handle(Container $container, EstadoJobStore $estado, CsvExportacionWriter $writer): void
    {
        try {
            $estado->actualizar($this->jobId, 'procesando', 30, 'Exportando productos.');
            /** @var ListarProductos $listar */
            $listar = $container->get(ListarProductos::class);
            $filas = array_map(
                static fn (object $producto): array => [
                    'id' => method_exists($producto, 'id') ? $producto->id() : null,
                    'codigo_barras' => method_exists($producto, 'codBarras') ? $producto->codBarras() : '',
                    'nombre' => method_exists($producto, 'nombre') ? $producto->nombre() : '',
                    'id_stock' => method_exists($producto, 'idStock') ? $producto->idStock() : null,
                    'precio_final' => method_exists($producto, 'precioFinal') ? $producto->precioFinal() : 0,
                    'activo' => method_exists($producto, 'activo') ? $producto->activo() : false,
                ],
                array_slice($listar->ejecutar(), 0, 20)
            );
            $archivo = $writer->escribir('exportaciones', 'productos', $filas);
            $estado->actualizar($this->jobId, 'completado', 100, 'Productos exportados.', $archivo);
        } catch (Throwable $exception) {
            $estado->actualizar($this->jobId, 'error', 100, $exception->getMessage());
        }
    }
}
