<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Ventas\Auth\Application\ObtenerSesionActual;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Core\Permisos\Application\VerificarPermisoSistema as VerificarPermisoSistemaUseCase;

final class VerificarPermisoSistema
{
    public function __construct(
        private readonly Container $container,
        private readonly VerificarPermisoSistemaUseCase $verificarPermisoSistema
    ) {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        /** @var ObtenerSesionActual $obtenerSesionActual */
        $obtenerSesionActual = $this->container->get(ObtenerSesionActual::class);
        $usuario = $obtenerSesionActual->ejecutar();
        $permitido = false;

        if (is_array($usuario)) {
            $permitido = strtoupper((string) ($usuario['rol'] ?? '')) === 'ADMIN';
            $permitido = $permitido || $this->verificarPermisoSistema->ejecutar((int) ($usuario['id'] ?? 0), $permiso);
        }

        if ($usuario === null) {
            $respuesta = redirect()->route('login');
        } elseif ($permitido) {
            $respuesta = $next($request);
        } else {
            $respuesta = response('No autorizado para el permiso ' . $permiso, 403);
        }

        return $respuesta;
    }
}
