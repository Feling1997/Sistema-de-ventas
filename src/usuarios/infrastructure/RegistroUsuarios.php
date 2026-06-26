<?php

declare(strict_types=1);

namespace Ventas\Usuarios\Infrastructure;

use PDO;
use Ventas\Core\Infrastructure\Config\DatabaseConfig;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Core\Infrastructure\Persistence\Mysql\PdoConnectionFactory;
use Ventas\Usuarios\Application\ActualizarUsuario;
use Ventas\Usuarios\Application\BuscarUsuarioPorId;
use Ventas\Usuarios\Application\CrearUsuario;
use Ventas\Usuarios\Application\EliminarUsuario;
use Ventas\Usuarios\Application\ListarUsuarios;
use Ventas\Usuarios\Application\VerificarPermisoModulo;
use Ventas\Usuarios\Domain\Repositorios\UsuarioRepository;

final class RegistroUsuarios
{
    public static function registrar(Container $container): void
    {
        if (!$container->has(DatabaseConfig::class)) {
            $container->singleton(DatabaseConfig::class, fn (): DatabaseConfig => new DatabaseConfig());
        }

        if (!$container->has(PdoConnectionFactory::class)) {
            $container->singleton(PdoConnectionFactory::class, fn (Container $container): PdoConnectionFactory => new PdoConnectionFactory($container->get(DatabaseConfig::class)));
        }

        if (!$container->has(PDO::class)) {
            $container->singleton(PDO::class, fn (Container $container): PDO => $container->get(PdoConnectionFactory::class)->create());
        }

        $container->singleton(UsuarioRepository::class, fn (Container $container): UsuarioRepository => new MySQLUsuarioRepository($container->get(PDO::class)));

        $container->bind(ListarUsuarios::class, fn (Container $container): ListarUsuarios => new ListarUsuarios($container->get(UsuarioRepository::class)));

        $container->bind(BuscarUsuarioPorId::class, fn (Container $container): BuscarUsuarioPorId => new BuscarUsuarioPorId($container->get(UsuarioRepository::class)));

        $container->bind(CrearUsuario::class, fn (Container $container): CrearUsuario => new CrearUsuario($container->get(UsuarioRepository::class)));

        $container->bind(ActualizarUsuario::class, fn (Container $container): ActualizarUsuario => new ActualizarUsuario($container->get(UsuarioRepository::class)));

        $container->bind(EliminarUsuario::class, fn (Container $container): EliminarUsuario => new EliminarUsuario($container->get(UsuarioRepository::class)));

        $container->bind(VerificarPermisoModulo::class, fn (): VerificarPermisoModulo => new VerificarPermisoModulo());
    }
}
