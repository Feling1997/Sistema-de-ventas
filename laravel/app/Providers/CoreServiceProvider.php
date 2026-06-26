<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Ventas\Core\Contactos\Application\ActualizarContacto;
use Ventas\Core\Contactos\Application\AutocompletarContactos;
use Ventas\Core\Contactos\Application\BuscarContacto;
use Ventas\Core\Contactos\Application\BuscarContactoPorDocumento;
use Ventas\Core\Contactos\Application\BuscarContactoPorTelefono;
use Ventas\Core\Contactos\Application\CrearContacto;
use Ventas\Core\Contactos\Application\DesactivarContacto;
use Ventas\Core\Contactos\Domain\Repositorios\ContactoRepository;
use Ventas\Core\Contactos\Infrastructure\Repositories\EloquentContactoRepository;
use Ventas\Core\Permisos\Application\ActivarPermisoRol;
use Ventas\Core\Permisos\Application\QuitarPermisoRol;
use Ventas\Core\Permisos\Application\VerificarPermisoSistema;
use Ventas\Core\Permisos\Domain\Repositorios\PermisoRepository;
use Ventas\Core\Permisos\Infrastructure\Repositories\EloquentPermisoRepository;
use Ventas\Core\Roles\Application\DesactivarRol;
use Ventas\Core\Roles\Application\GuardarRol;
use Ventas\Core\Roles\Domain\Repositorios\RolRepository;
use Ventas\Core\Roles\Infrastructure\Repositories\EloquentRolRepository;
use Ventas\Core\Usuarios\Application\DesactivarUsuarioCore;
use Ventas\Core\Usuarios\Application\GuardarUsuarioCore;
use Ventas\Core\Usuarios\Application\InicializarRbacSistema;
use Ventas\Core\Usuarios\Application\ListarPanelUsuarios;
use Ventas\Core\Usuarios\Application\SincronizarUsuarioLegacy;
use Ventas\Core\Usuarios\Domain\Repositorios\UsuarioRepository;
use Ventas\Core\Usuarios\Infrastructure\Repositories\EloquentUsuarioCoreRepository;

final class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         * Core define los modulos compartidos:
         * Usuarios, Roles, Permisos, Sesiones, Configuraciones, Contactos,
         * Preferencias y Auditoria.
         *
         * Las conexiones futuras permitiran sistema_core como base obligatoria
         * y sistema_ventas / sistema_reparaciones como bases opcionales.
         */
        $this->configurarConexionSistemaCore();
        $this->app->bind(ContactoRepository::class, EloquentContactoRepository::class);
        $this->app->bind(BuscarContacto::class);
        $this->app->bind(BuscarContactoPorDocumento::class);
        $this->app->bind(BuscarContactoPorTelefono::class);
        $this->app->bind(AutocompletarContactos::class);
        $this->app->bind(CrearContacto::class);
        $this->app->bind(ActualizarContacto::class);
        $this->app->bind(DesactivarContacto::class);
        $this->app->bind(RolRepository::class, EloquentRolRepository::class);
        $this->app->bind(PermisoRepository::class, EloquentPermisoRepository::class);
        $this->app->bind(UsuarioRepository::class, EloquentUsuarioCoreRepository::class);
        $this->app->bind(InicializarRbacSistema::class);
        $this->app->bind(ListarPanelUsuarios::class);
        $this->app->bind(GuardarUsuarioCore::class);
        $this->app->bind(DesactivarUsuarioCore::class);
        $this->app->bind(GuardarRol::class);
        $this->app->bind(DesactivarRol::class);
        $this->app->bind(ActivarPermisoRol::class);
        $this->app->bind(QuitarPermisoRol::class);
        $this->app->bind(VerificarPermisoSistema::class);
        $this->app->bind(SincronizarUsuarioLegacy::class);
    }

    private function configurarConexionSistemaCore(): void
    {
        $conexion = config('database.connections.mysql', []);
        $conexion['database'] = env('CORE_DB_DATABASE', 'sistema_core');
        $conexion['username'] = env('CORE_DB_USERNAME', env('DB_USERNAME', 'root'));
        $conexion['password'] = env('CORE_DB_PASSWORD', env('DB_PASSWORD', ''));
        $conexion['host'] = env('CORE_DB_HOST', env('DB_HOST', '127.0.0.1'));
        $conexion['port'] = env('CORE_DB_PORT', env('DB_PORT', '3306'));

        config(['database.connections.sistema_core' => $conexion]);
    }
}
