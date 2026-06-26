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
use Ventas\Clientes\Application\ListarClientes;
use Ventas\Core\Infrastructure\Container\Container;

final class ExportarClientesJob
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
            $estado->actualizar($this->jobId, 'procesando', 30, 'Exportando clientes.');
            /** @var ListarClientes $listar */
            $listar = $container->get(ListarClientes::class);
            $filas = array_map(
                static fn (object $cliente): array => [
                    'id' => method_exists($cliente, 'id') ? $cliente->id() : null,
                    'nombre' => method_exists($cliente, 'nombre') ? $cliente->nombre() : '',
                    'documento' => method_exists($cliente, 'documento') ? $cliente->documento() : '',
                    'telefono' => method_exists($cliente, 'telefono') ? $cliente->telefono() : '',
                    'email' => method_exists($cliente, 'email') ? $cliente->email() : '',
                    'direccion' => method_exists($cliente, 'direccion') ? $cliente->direccion() : '',
                ],
                array_slice($listar->ejecutar(), 0, 20)
            );
            $archivo = $writer->escribir('exportaciones', 'clientes', $filas);
            $estado->actualizar($this->jobId, 'completado', 100, 'Clientes exportados.', $archivo);
        } catch (Throwable $exception) {
            $estado->actualizar($this->jobId, 'error', 100, $exception->getMessage());
        }
    }
}
