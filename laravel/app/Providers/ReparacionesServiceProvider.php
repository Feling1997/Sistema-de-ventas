<?php

declare(strict_types=1);

namespace App\Providers;

use Ventas\Reparaciones\Application\ActualizarReparacion;
use Ventas\Reparaciones\Application\ActualizarEquipo;
use Ventas\Reparaciones\Application\AgregarAdjunto;
use Ventas\Reparaciones\Application\AgregarEquipo;
use Ventas\Reparaciones\Application\AutocompletarContactosReparacion;
use Ventas\Reparaciones\Application\BuscarEquipo;
use Ventas\Reparaciones\Application\BuscarReparacion;
use Ventas\Reparaciones\Application\CambiarEstadoReparacion;
use Ventas\Reparaciones\Application\CrearReparacion;
use Ventas\Reparaciones\Application\GenerarTicketReparacion;
use Ventas\Reparaciones\Application\GuardarConfiguracionReparaciones;
use Ventas\Reparaciones\Application\EliminarAdjunto;
use Ventas\Reparaciones\Application\ListarEquipos;
use Ventas\Reparaciones\Application\ListarAdjuntos;
use Ventas\Reparaciones\Application\ListarEstadosReparacion;
use Ventas\Reparaciones\Application\ListarReparaciones;
use Ventas\Reparaciones\Application\ObtenerConfiguracionReparaciones;
use Ventas\Reparaciones\Application\ObtenerResumenReparaciones;
use Ventas\Reparaciones\Application\ObtenerMetricasReparaciones;
use Ventas\Reparaciones\Application\ObtenerSaludReparaciones;
use Ventas\Reparaciones\Application\RegistrarAuditoriaReparacion;
use Ventas\Reparaciones\Domain\Repositorios\ConfiguracionReparacionesRepository;
use Ventas\Reparaciones\Infrastructure\Repositories\EloquentConfiguracionReparacionesRepository;
use Illuminate\Support\ServiceProvider;

final class ReparacionesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->configurarConexionSistemaReparaciones();
        $this->app->bind(ListarReparaciones::class);
        $this->app->bind(BuscarReparacion::class);
        $this->app->bind(CrearReparacion::class);
        $this->app->bind(ActualizarReparacion::class);
        $this->app->bind(CambiarEstadoReparacion::class);
        $this->app->bind(BuscarEquipo::class);
        $this->app->bind(AgregarEquipo::class);
        $this->app->bind(ActualizarEquipo::class);
        $this->app->bind(AgregarAdjunto::class);
        $this->app->bind(ListarAdjuntos::class);
        $this->app->bind(EliminarAdjunto::class);
        $this->app->bind(GenerarTicketReparacion::class);
        $this->app->bind(AutocompletarContactosReparacion::class);
        $this->app->bind(ListarEquipos::class);
        $this->app->bind(ListarEstadosReparacion::class);
        $this->app->bind(ConfiguracionReparacionesRepository::class, EloquentConfiguracionReparacionesRepository::class);
        $this->app->bind(ObtenerConfiguracionReparaciones::class);
        $this->app->bind(GuardarConfiguracionReparaciones::class);
        $this->app->bind(ObtenerResumenReparaciones::class);
        $this->app->bind(RegistrarAuditoriaReparacion::class);
        $this->app->bind(ObtenerMetricasReparaciones::class);
        $this->app->bind(ObtenerSaludReparaciones::class);
    }

    private function configurarConexionSistemaReparaciones(): void
    {
        $conexion = config('database.connections.mysql', []);
        $conexion['database'] = env('REPARACIONES_DB_DATABASE', 'sistema_reparaciones');
        $conexion['username'] = env('REPARACIONES_DB_USERNAME', env('DB_USERNAME', 'root'));
        $conexion['password'] = env('REPARACIONES_DB_PASSWORD', env('DB_PASSWORD', ''));
        $conexion['host'] = env('REPARACIONES_DB_HOST', env('DB_HOST', '127.0.0.1'));
        $conexion['port'] = env('REPARACIONES_DB_PORT', env('DB_PORT', '3306'));

        config(['database.connections.sistema_reparaciones' => $conexion]);
    }
}
