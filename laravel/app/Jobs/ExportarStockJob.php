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
use Ventas\Stock\Application\ListarStock;

final class ExportarStockJob
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
            $estado->actualizar($this->jobId, 'procesando', 30, 'Exportando stock.');
            /** @var ListarStock $listar */
            $listar = $container->get(ListarStock::class);
            $filas = array_map(
                static fn (object $stock): array => [
                    'id' => method_exists($stock, 'id') ? $stock->id() : null,
                    'nombre' => method_exists($stock, 'nombre') ? $stock->nombre() : '',
                    'unidad' => method_exists($stock, 'unidad') ? $stock->unidad() : '',
                    'tipo_stock' => method_exists($stock, 'tipoStock') ? $stock->tipoStock() : '',
                    'cantidad' => method_exists($stock, 'cantidad') ? $stock->cantidad() : 0,
                    'stock_minimo' => method_exists($stock, 'stockMinimo') ? $stock->stockMinimo() : 0,
                    'precio_costo' => method_exists($stock, 'precioCosto') ? $stock->precioCosto() : 0,
                    'activo' => method_exists($stock, 'activo') ? $stock->activo() : false,
                ],
                array_slice($listar->ejecutar(), 0, 20)
            );
            $archivo = $writer->escribir('exportaciones', 'stock', $filas);
            $estado->actualizar($this->jobId, 'completado', 100, 'Stock exportado.', $archivo);
        } catch (Throwable $exception) {
            $estado->actualizar($this->jobId, 'error', 100, $exception->getMessage());
        }
    }
}
