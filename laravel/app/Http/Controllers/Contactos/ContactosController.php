<?php

declare(strict_types=1);

namespace App\Http\Controllers\Contactos;

use Ventas\Core\Contactos\Application\ActualizarContacto;
use Ventas\Core\Contactos\Application\AutocompletarContactos;
use Ventas\Core\Contactos\Application\BuscarContacto;
use Ventas\Core\Contactos\Application\CrearContacto;
use Ventas\Core\Contactos\Application\DesactivarContacto;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ContactosController extends Controller
{
    public function index(): View
    {
        $vista = view('contactos.index');

        return $vista;
    }

    public function buscar(Request $request, BuscarContacto $buscarContacto): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        $contacto = $id > 0 ? $buscarContacto->ejecutar($id) : null;
        $datos = $contacto !== null ? $contacto->comoArray() : null;
        $respuesta = response()->json(['contacto' => $datos]);

        return $respuesta;
    }

    public function autocompletar(Request $request, AutocompletarContactos $autocompletarContactos): JsonResponse
    {
        $resultados = $autocompletarContactos->ejecutar((string) $request->query('q', ''));
        $respuesta = response()->json($resultados);

        return $respuesta;
    }

    public function store(Request $request, CrearContacto $crearContacto): JsonResponse
    {
        $contacto = $crearContacto->ejecutar($this->datosContacto($request));
        $respuesta = response()->json($contacto->comoArray(), 201);

        return $respuesta;
    }

    public function update(int $id, Request $request, ActualizarContacto $actualizarContacto): JsonResponse
    {
        $contacto = $actualizarContacto->ejecutar($id, $this->datosContacto($request));
        $codigo = $contacto !== null ? 200 : 404;
        $respuesta = response()->json(['contacto' => $contacto?->comoArray()], $codigo);

        return $respuesta;
    }

    public function destroy(int $id, DesactivarContacto $desactivarContacto): JsonResponse
    {
        $desactivado = $desactivarContacto->ejecutar($id);
        $codigo = $desactivado ? 200 : 404;
        $respuesta = response()->json(['desactivado' => $desactivado], $codigo);

        return $respuesta;
    }

    /**
     * @return array<string, mixed>
     */
    private function datosContacto(Request $request): array
    {
        $datos = $request->only([
            'nombre',
            'apellido',
            'telefono',
            'correo',
            'documento',
            'direccion',
            'observaciones',
            'activo',
        ]);

        return $datos;
    }
}
