<?php

declare(strict_types=1);

namespace App\Http\Controllers\Exportaciones;

use App\Http\Controllers\Controller;
use App\Jobs\ExportarClientesJob;
use App\Jobs\ExportarProductosJob;
use App\Jobs\ExportarStockJob;
use App\Jobs\ExportarVentasJob;
use App\Jobs\ImportarClientesJob;
use App\Jobs\ImportarProductosJob;
use App\Jobs\ImportarStockJob;
use App\Support\Jobs\EstadoJobStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class ExportacionesController extends Controller
{
    public function __construct(private readonly EstadoJobStore $estadoJobStore)
    {
    }

    public function index(): View
    {
        $this->prepararDirectorios();

        return view('exportaciones.index');
    }

    public function exportarProductos(): JsonResponse
    {
        $estado = $this->estadoJobStore->crear('exportar_productos');
        ExportarProductosJob::dispatch($estado['id']);

        return response()->json($this->estadoJobStore->obtener($estado['id']));
    }

    public function exportarStock(): JsonResponse
    {
        $estado = $this->estadoJobStore->crear('exportar_stock');
        ExportarStockJob::dispatch($estado['id']);

        return response()->json($this->estadoJobStore->obtener($estado['id']));
    }

    public function exportarClientes(): JsonResponse
    {
        $estado = $this->estadoJobStore->crear('exportar_clientes');
        ExportarClientesJob::dispatch($estado['id']);

        return response()->json($this->estadoJobStore->obtener($estado['id']));
    }

    public function exportarVentas(): JsonResponse
    {
        $estado = $this->estadoJobStore->crear('exportar_ventas');
        ExportarVentasJob::dispatch($estado['id']);

        return response()->json($this->estadoJobStore->obtener($estado['id']));
    }

    public function importarProductos(): JsonResponse
    {
        $estado = $this->estadoJobStore->crear('importar_productos');
        ImportarProductosJob::dispatch($estado['id']);

        return response()->json($this->estadoJobStore->obtener($estado['id']));
    }

    public function importarStock(): JsonResponse
    {
        $estado = $this->estadoJobStore->crear('importar_stock');
        ImportarStockJob::dispatch($estado['id']);

        return response()->json($this->estadoJobStore->obtener($estado['id']));
    }

    public function importarClientes(): JsonResponse
    {
        $estado = $this->estadoJobStore->crear('importar_clientes');
        ImportarClientesJob::dispatch($estado['id']);

        return response()->json($this->estadoJobStore->obtener($estado['id']));
    }

    public function estadoJob(string $id): JsonResponse
    {
        return response()->json($this->estadoJobStore->obtener($id));
    }

    private function prepararDirectorios(): void
    {
        Storage::disk('local')->makeDirectory('exportaciones');
        Storage::disk('local')->makeDirectory('importaciones');
    }
}
