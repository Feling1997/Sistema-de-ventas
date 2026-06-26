<?php

declare(strict_types=1);

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Ventas\Core\Permisos\Application\ActivarPermisoRol;
use Ventas\Core\Permisos\Application\QuitarPermisoRol;
use Ventas\Core\Roles\Application\DesactivarRol;
use Ventas\Core\Roles\Application\GuardarRol;
use Ventas\Core\Usuarios\Application\DesactivarUsuarioCore;
use Ventas\Core\Usuarios\Application\GuardarUsuarioCore;
use Ventas\Core\Usuarios\Application\InicializarRbacSistema;
use Ventas\Core\Usuarios\Application\ListarPanelUsuarios;

final class UsuariosController extends Controller
{
    public function index(InicializarRbacSistema $inicializarRbac, ListarPanelUsuarios $listarPanelUsuarios): View
    {
        $inicializarRbac->ejecutar();
        $panel = $listarPanelUsuarios->ejecutar();
        $vista = view('usuarios.index', $panel);

        return $vista;
    }

    public function store(Request $request, GuardarUsuarioCore $guardarUsuario): JsonResponse
    {
        $respuesta = response()->json($guardarUsuario->ejecutar($this->datosUsuario($request)), 201);

        return $respuesta;
    }

    public function update(int $id, Request $request, GuardarUsuarioCore $guardarUsuario): JsonResponse
    {
        $datos = $this->datosUsuario($request);
        $datos['id'] = $id;
        $respuesta = response()->json($guardarUsuario->ejecutar($datos));

        return $respuesta;
    }

    public function destroy(int $id, DesactivarUsuarioCore $desactivarUsuario): JsonResponse
    {
        $ok = $desactivarUsuario->ejecutar($id);
        $respuesta = response()->json(['ok' => $ok], $ok ? 200 : 404);

        return $respuesta;
    }

    public function storeRol(Request $request, GuardarRol $guardarRol): JsonResponse
    {
        $respuesta = response()->json($guardarRol->ejecutar($this->datosRol($request)), 201);

        return $respuesta;
    }

    public function updateRol(int $id, Request $request, GuardarRol $guardarRol): JsonResponse
    {
        $datos = $this->datosRol($request);
        $datos['id'] = $id;
        $respuesta = response()->json($guardarRol->ejecutar($datos));

        return $respuesta;
    }

    public function destroyRol(int $id, DesactivarRol $desactivarRol): JsonResponse
    {
        $ok = $desactivarRol->ejecutar($id);
        $respuesta = response()->json(['ok' => $ok], $ok ? 200 : 404);

        return $respuesta;
    }

    public function activarPermiso(int $rol, int $permiso, ActivarPermisoRol $activarPermisoRol): JsonResponse
    {
        $ok = $activarPermisoRol->ejecutar($rol, $permiso);
        $respuesta = response()->json(['ok' => $ok], $ok ? 200 : 404);

        return $respuesta;
    }

    public function quitarPermiso(int $rol, int $permiso, QuitarPermisoRol $quitarPermisoRol): JsonResponse
    {
        $ok = $quitarPermisoRol->ejecutar($rol, $permiso);
        $respuesta = response()->json(['ok' => $ok], $ok ? 200 : 404);

        return $respuesta;
    }

    /**
     * @return array<string, mixed>
     */
    private function datosUsuario(Request $request): array
    {
        $datos = [
            'nombre' => (string) $request->input('nombre', ''),
            'apellido' => (string) $request->input('apellido', ''),
            'usuario' => (string) $request->input('usuario', ''),
            'email' => (string) $request->input('email', ''),
            'clave' => (string) $request->input('clave', ''),
            'clave_confirmation' => (string) $request->input('clave_confirmation', ''),
            'activo' => (bool) $request->boolean('activo', true),
            'rol_id' => (int) $request->input('rol_id', 0),
        ];

        return $datos;
    }

    /**
     * @return array<string, mixed>
     */
    private function datosRol(Request $request): array
    {
        $datos = [
            'nombre' => (string) $request->input('nombre', ''),
            'descripcion' => (string) $request->input('descripcion', ''),
            'activo' => (bool) $request->boolean('activo', true),
        ];

        return $datos;
    }
}
