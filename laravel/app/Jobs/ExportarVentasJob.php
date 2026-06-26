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
use Ventas\Ventas\Application\ListarVentas;

final class ExportarVentasJob
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
            $estado->actualizar($this->jobId, 'procesando', 30, 'Exportando ventas.');
            /** @var ListarVentas $listar */
            $listar = $container->get(ListarVentas::class);
            $filas = array_map(
                static fn (object $venta): array => [
                    'id' => method_exists($venta, 'id') ? $venta->id() : null,
                    'fecha' => method_exists($venta, 'fecha') ? $venta->fecha() : '',
                    'id_cliente' => method_exists($venta, 'idCliente') ? $venta->idCliente() : 0,
                    'id_usuario' => method_exists($venta, 'idUsuario') ? $venta->idUsuario() : 0,
                    'total' => method_exists($venta, 'total') ? $venta->total() : 0,
                ],
                array_slice($listar->ejecutar(), 0, 20)
            );
            $archivo = $writer->escribir('exportaciones', 'ventas', $filas);
            $estado->actualizar($this->jobId, 'completado', 100, 'Ventas exportadas.', $archivo);
        } catch (Throwable $exception) {
            $estado->actualizar($this->jobId, 'error', 100, $exception->getMessage());
        }
    }
}
