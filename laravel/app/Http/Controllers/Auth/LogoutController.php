<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Ventas\Auth\Application\CerrarSesionAuth;
use Ventas\Core\Infrastructure\Container\Container;

final class LogoutController extends Controller
{
    public function __invoke(Container $container): RedirectResponse
    {
        /** @var CerrarSesionAuth $cerrarSesionAuth */
        $cerrarSesionAuth = $container->get(CerrarSesionAuth::class);
        $cerrarSesionAuth->ejecutar();
        $respuesta = redirect()->route('login');

        return $respuesta;
    }
}
