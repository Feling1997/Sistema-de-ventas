<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Ventas\Auth\Application\ObtenerSesionActual;
use Ventas\Core\Infrastructure\Container\Container;

final class VerificarAutenticacionSistema
{
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var ObtenerSesionActual $obtenerSesionActual */
        $obtenerSesionActual = $this->container->get(ObtenerSesionActual::class);
        $usuario = $obtenerSesionActual->ejecutar();

        if ($usuario !== null) {
            $respuesta = $next($request);
        } else {
            $respuesta = redirect()->route('login');
        }

        return $respuesta;
    }
}
