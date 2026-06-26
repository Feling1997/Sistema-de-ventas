<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Ventas\Auth\Infrastructure\RegistroAuth;
use Ventas\Backups\Infrastructure\RegistroBackups;
use Ventas\Clientes\Infrastructure\RegistroClientes;
use Ventas\Configuracion\Infrastructure\RegistroConfiguracion;
use Ventas\CuentasCorrientes\Infrastructure\RegistroCuentasCorrientes;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\ListasPrecios\Infrastructure\RegistroListasPrecios;
use Ventas\Presupuestos\Infrastructure\RegistroPresupuestos;
use Ventas\Precios\Infrastructure\RegistroPrecios;
use Ventas\Productos\Infrastructure\RegistroProductos;
use Ventas\Stock\Infrastructure\RegistroStock;
use Ventas\Usuarios\Infrastructure\RegistroUsuarios;
use Ventas\Ventas\Infrastructure\RegistroVentas;

final class SistemaVentasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Container::class, function (): Container {
            $container = new Container();

            RegistroAuth::registrar($container);
            RegistroListasPrecios::registrar($container);
            RegistroPrecios::registrar($container);
            RegistroUsuarios::registrar($container);
            RegistroClientes::registrar($container);
            RegistroProductos::registrar($container);
            RegistroStock::registrar($container);
            RegistroVentas::registrar($container);
            RegistroConfiguracion::registrar($container);
            RegistroBackups::registrar($container);
            RegistroPresupuestos::registrar($container);
            RegistroCuentasCorrientes::registrar($container);

            return $container;
        });
    }
}
