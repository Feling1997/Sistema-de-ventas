<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Ventas\Auth\Application\AutenticarUsuario;
use Ventas\Auth\Domain\Repositorios\SesionAuthRepository;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Core\Usuarios\Application\InicializarRbacSistema;
use Ventas\Core\Usuarios\Application\SincronizarUsuarioLegacy;
use Ventas\Core\Usuarios\Infrastructure\Models\UsuarioCoreModel;

final class LoginController extends Controller
{
    public function create(): View
    {
        $vista = view('auth.login');

        return $vista;
    }

    public function store(
        LoginRequest $request,
        Container $container,
        InicializarRbacSistema $inicializarRbac,
        SincronizarUsuarioLegacy $sincronizarUsuarioLegacy
    ): RedirectResponse {
        /** @var AutenticarUsuario $autenticarUsuario */
        $autenticarUsuario = $container->get(AutenticarUsuario::class);
        $resultado = $autenticarUsuario->ejecutar(
            (string) $request->validated('usuario'),
            (string) $request->validated('clave')
        );
        $resultado = ($resultado['ok'] ?? false) === true ? $resultado : $this->autenticarUsuarioCore($request, $container);

        if (($resultado['ok'] ?? false) === true) {
            $inicializarRbac->ejecutar();

            if (is_array($resultado['usuario'] ?? null) && ($resultado['core'] ?? false) !== true) {
                $sincronizarUsuarioLegacy->ejecutar($resultado['usuario']);
            }

            if (is_array($resultado['usuario'] ?? null)) {
                $request->session()->put('usuario_logueado', $resultado['usuario']);
            }

            $respuesta = redirect()->intended('/usuarios');
        } else {
            $respuesta = back()
                ->withErrors(['usuario' => (string) ($resultado['error'] ?? 'No se pudo iniciar sesion.')])
                ->withInput($request->only('usuario', 'recordarme'));
        }

        return $respuesta;
    }

    /**
     * @return array<string, mixed>
     */
    private function autenticarUsuarioCore(LoginRequest $request, Container $container): array
    {
        $usuario = UsuarioCoreModel::query()
            ->with('roles.permisos')
            ->where('usuario', (string) $request->validated('usuario'))
            ->where('activo', true)
            ->first();
        $ok = $usuario instanceof UsuarioCoreModel
            && $usuario->clave !== null
            && Hash::check((string) $request->validated('clave'), (string) $usuario->clave);
        $resultado = ['ok' => false, 'error' => 'Credenciales invalidas.'];

        if ($ok) {
            $rol = $usuario->roles->first();
            $permisos = $usuario->roles
                ->flatMap(static fn ($rolItem) => $rolItem->permisos->pluck('codigo'))
                ->unique()
                ->values()
                ->all();
            $datosUsuario = [
                'id' => (int) $usuario->id,
                'usuario' => (string) $usuario->usuario,
                'rol' => (string) ($rol?->nombre ?? 'Usuario'),
                'permisos' => $permisos,
            ];
            /** @var SesionAuthRepository $sesion */
            $sesion = $container->get(SesionAuthRepository::class);
            $sesion->guardarUsuario($datosUsuario);
            $request->session()->put('usuario_logueado', $datosUsuario);
            $usuario->ultimo_acceso = now();
            $usuario->save();
            $resultado = ['ok' => true, 'core' => true, 'usuario' => $datosUsuario];
        }

        return $resultado;
    }
}
