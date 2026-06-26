<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reparaciones;

use App\Http\Controllers\Controller;
use Ventas\Reparaciones\Application\ActualizarEquipo;
use Ventas\Reparaciones\Application\ActualizarReparacion;
use Ventas\Reparaciones\Application\AgregarAdjunto;
use Ventas\Reparaciones\Application\AgregarEquipo;
use Ventas\Reparaciones\Application\AutocompletarContactosReparacion;
use Ventas\Reparaciones\Application\BuscarEquipo;
use Ventas\Reparaciones\Application\BuscarReparacion;
use Ventas\Reparaciones\Application\CrearReparacion;
use Ventas\Reparaciones\Application\EliminarAdjunto;
use Ventas\Reparaciones\Application\GenerarTicketReparacion;
use Ventas\Reparaciones\Application\GuardarConfiguracionReparaciones;
use Ventas\Reparaciones\Application\ListarAdjuntos;
use Ventas\Reparaciones\Application\ListarEquipos;
use Ventas\Reparaciones\Application\ListarEstadosReparacion;
use Ventas\Reparaciones\Application\ListarReparaciones;
use Ventas\Reparaciones\Application\ObtenerConfiguracionReparaciones;
use Ventas\Reparaciones\Application\ObtenerMetricasReparaciones;
use Ventas\Reparaciones\Application\ObtenerResumenReparaciones;
use Ventas\Reparaciones\Application\ObtenerSaludReparaciones;
use Ventas\Reparaciones\Application\RegistrarAuditoriaReparacion;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Illuminate\View\View;

final class ReparacionesController extends Controller
{
    public function index(): View
    {
        return view('reparaciones.index');
    }

    public function buscar(Request $request, ListarReparaciones $listarReparaciones): JsonResponse
    {
        /** @var JsonResponse $respuesta */
        $respuesta = $this->auditar('buscar', function () use ($request, $listarReparaciones): JsonResponse {
            $resultados = $listarReparaciones->ejecutar($request->only([
                'q',
                'estado',
                'activo',
                'fecha_desde',
                'fecha_hasta',
                'contacto_id',
            ]), max(1, (int) $request->query('page', 1)), 20);

            return response()->json($resultados);
        });

        return $respuesta;
    }

    public function resumen(ObtenerResumenReparaciones $obtenerResumen): JsonResponse
    {
        $resumen = $obtenerResumen->ejecutar();

        return response()->json($resumen);
    }

    public function configuracion(ObtenerConfiguracionReparaciones $obtenerConfiguracion): View
    {
        $configuracion = $obtenerConfiguracion->ejecutar();

        return view('reparaciones.configuracion', ['configuracion' => $configuracion]);
    }

    public function guardarConfiguracion(Request $request, GuardarConfiguracionReparaciones $guardarConfiguracion): JsonResponse
    {
        /** @var JsonResponse $respuesta */
        $respuesta = $this->auditar('configuracion', function () use ($request, $guardarConfiguracion): JsonResponse {
            $resultado = $guardarConfiguracion->ejecutar($request->only([
                'nombre_comercio',
                'telefono_comercio',
                'direccion_comercio',
                'impresora_predeterminada',
                'mostrar_logo',
                'texto_ticket',
                'observaciones_ticket',
            ]));

            return response()->json($resultado);
        });

        return $respuesta;
    }

    public function contactos(Request $request, AutocompletarContactosReparacion $autocompletar): JsonResponse
    {
        /** @var JsonResponse $respuesta */
        $respuesta = $this->auditar('buscar_contacto', function () use ($request, $autocompletar): JsonResponse {
            $contactos = $autocompletar->ejecutar(trim((string) $request->query('q', '')));

            return response()->json($contactos);
        });

        return $respuesta;
    }

    public function estados(ListarEstadosReparacion $listarEstados): JsonResponse
    {
        $estados = $listarEstados->ejecutar();

        return response()->json($estados);
    }

    public function equipos(Request $request, BuscarEquipo $buscarEquipo, ListarEquipos $listarEquipos): JsonResponse
    {
        /** @var JsonResponse $respuesta */
        $respuesta = $this->auditar('buscar_equipo', function () use ($request, $buscarEquipo, $listarEquipos): JsonResponse {
            $id = (int) $request->query('id', 0);
            $equipo = $id > 0 ? $buscarEquipo->ejecutar($id) : null;
            $equipos = $id > 0 ? [] : $listarEquipos->ejecutar(20, trim((string) $request->query('q', '')));

            return response()->json([
                'equipo' => $equipo,
                'data' => $equipos,
            ]);
        });

        return $respuesta;
    }

    public function storeEquipo(Request $request, AgregarEquipo $agregarEquipo): JsonResponse
    {
        /** @var JsonResponse $respuesta */
        $respuesta = $this->auditar('buscar_equipo', function () use ($request, $agregarEquipo): JsonResponse {
            $resultado = $agregarEquipo->ejecutar($request->only([
                'contacto_id',
                'tipo',
                'marca',
                'modelo',
                'serie',
                'observaciones',
            ]));

            return response()->json($resultado, $resultado['ok'] === true ? 201 : 422);
        });

        return $respuesta;
    }

    public function updateEquipo(int $id, Request $request, ActualizarEquipo $actualizarEquipo): JsonResponse
    {
        $resultado = $actualizarEquipo->ejecutar($id, $request->only([
            'contacto_id',
            'tipo',
            'marca',
            'modelo',
            'serie',
            'observaciones',
        ]));

        return response()->json($resultado, $resultado['ok'] === true ? 200 : 404);
    }

    public function mostrar(int $id, BuscarReparacion $buscarReparacion): JsonResponse
    {
        $reparacion = $buscarReparacion->ejecutar($id);
        $estado = $reparacion !== null ? 200 : 404;

        return response()->json([
            'reparacion' => $reparacion,
        ], $estado);
    }

    public function store(Request $request, CrearReparacion $crearReparacion): JsonResponse
    {
        /** @var JsonResponse $respuesta */
        $respuesta = $this->auditar('crear_reparacion', function () use ($request, $crearReparacion): JsonResponse {
            $resultado = $crearReparacion->ejecutar($request->only([
                'contacto_id',
                'equipo_id',
                'problema',
                'diagnostico',
                'garantia',
                'precio',
                'observaciones',
                'estado_id',
                'fecha_ingreso',
                'fecha_entrega',
            ]));

            return response()->json($resultado, $resultado['ok'] === true ? 201 : 422);
        });

        return $respuesta;
    }

    public function update(int $id, Request $request, ActualizarReparacion $actualizarReparacion): JsonResponse
    {
        $accion = $request->has('estado_id') ? 'cambiar_estado' : 'editar_reparacion';
        /** @var JsonResponse $respuesta */
        $respuesta = $this->auditar($accion, function () use ($id, $request, $actualizarReparacion): JsonResponse {
            $resultado = $actualizarReparacion->ejecutar($id, $request->only([
                'contacto_id',
                'equipo_id',
                'problema',
                'diagnostico',
                'garantia',
                'precio',
                'observaciones',
                'estado_id',
                'fecha_ingreso',
                'fecha_entrega',
            ]));

            return response()->json($resultado, $resultado['ok'] === true ? 200 : 404);
        }, $id);

        return $respuesta;
    }

    public function destroy(int $id, ActualizarReparacion $actualizarReparacion): JsonResponse
    {
        /** @var JsonResponse $respuesta */
        $respuesta = $this->auditar('editar_reparacion', function () use ($id, $actualizarReparacion): JsonResponse {
            $resultado = $actualizarReparacion->ejecutar($id, ['activo' => false]);

            return response()->json($resultado, $resultado['ok'] === true ? 200 : 404);
        }, $id);

        return $respuesta;
    }

    public function adjuntos(int $id, ListarAdjuntos $listarAdjuntos): JsonResponse
    {
        $adjuntos = $listarAdjuntos->ejecutar($id);

        return response()->json(['data' => $adjuntos]);
    }

    public function storeAdjunto(int $id, Request $request, AgregarAdjunto $agregarAdjunto): JsonResponse
    {
        /** @var JsonResponse $respuesta */
        $respuesta = $this->auditar('agregar_adjunto', function () use ($id, $request, $agregarAdjunto): JsonResponse {
            $resultado = $agregarAdjunto->ejecutar([
                'reparacion_id' => $id,
                'archivo' => $request->file('archivo'),
            ]);

            return response()->json($resultado, $resultado['ok'] === true ? 201 : 422);
        }, $id);

        return $respuesta;
    }

    public function destroyAdjunto(int $id, EliminarAdjunto $eliminarAdjunto): JsonResponse
    {
        /** @var JsonResponse $respuesta */
        $respuesta = $this->auditar('eliminar_adjunto', function () use ($id, $eliminarAdjunto): JsonResponse {
            $resultado = $eliminarAdjunto->ejecutar($id);

            return response()->json($resultado, $resultado['ok'] === true ? 200 : 404);
        });

        return $respuesta;
    }

    public function ticket(int $id, GenerarTicketReparacion $generarTicket): \Illuminate\Http\Response
    {
        /** @var \Illuminate\Http\Response $respuesta */
        $respuesta = $this->auditar('ticket', function () use ($id, $generarTicket): \Illuminate\Http\Response {
            $resultado = $generarTicket->ejecutar($id);
            $estado = $resultado['ok'] === true ? 200 : 404;
            $html = (string) ($resultado['html'] ?? 'Ticket no encontrado');

            return response($html, $estado);
        }, $id);

        return $respuesta;
    }

    public function metricas(ObtenerMetricasReparaciones $obtenerMetricas): JsonResponse
    {
        $metricas = $obtenerMetricas->ejecutar();

        return response()->json($metricas);
    }

    public function salud(ObtenerSaludReparaciones $obtenerSalud): JsonResponse
    {
        $salud = $obtenerSalud->ejecutar();

        return response()->json($salud);
    }

    private function auditar(string $accion, Closure $operacion, ?int $reparacionId = null): mixed
    {
        $inicio = microtime(true);
        $respuesta = null;
        $resultado = 'ok';
        $severidad = 'bajo';
        $mensaje = null;

        try {
            $respuesta = $operacion();
            if ($respuesta instanceof Response && $respuesta->getStatusCode() >= 400) {
                $resultado = 'error';
                $severidad = $respuesta->getStatusCode() >= 500 ? 'critico' : 'medio';
                $mensaje = 'HTTP ' . $respuesta->getStatusCode();
            }
        } catch (Throwable $exception) {
            $resultado = 'exception';
            $severidad = 'critico';
            $mensaje = $exception->getMessage();
            $this->registrarAuditoria($accion, $inicio, $resultado, $severidad, $mensaje, $reparacionId);
            throw $exception;
        }

        $this->registrarAuditoria($accion, $inicio, $resultado, $severidad, $mensaje, $this->reparacionIdRespuesta($respuesta, $reparacionId));

        return $respuesta;
    }

    private function registrarAuditoria(string $accion, float $inicio, string $resultado, string $severidad, ?string $mensaje, ?int $reparacionId): void
    {
        app(RegistrarAuditoriaReparacion::class)->ejecutar([
            'accion' => $accion,
            'usuario' => auth()->id(),
            'reparacion_id' => $reparacionId,
            'tiempo_ms' => (int) round((microtime(true) - $inicio) * 1000),
            'resultado' => $resultado,
            'severidad' => $severidad,
            'mensaje' => $mensaje,
        ]);
    }

    private function reparacionIdRespuesta(mixed $respuesta, ?int $predeterminado): ?int
    {
        $id = $predeterminado;

        if ($id === null && $respuesta instanceof JsonResponse) {
            $datos = json_decode((string) $respuesta->getContent(), true);
            if (is_array($datos) && isset($datos['id'])) {
                $id = (int) $datos['id'];
            }
        }

        return $id;
    }
}
