<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Contenedor;

use PDO;
use Ventas\Aplicacion\Usuarios\CasosUso\BuscarUsuarioPorId;
use Ventas\Aplicacion\Usuarios\CasosUso\ListarUsuarios;
use Ventas\Dominio\Usuarios\Repositorios\UsuarioRepository;
use Ventas\Infraestructura\Configuracion\DatabaseConfig;
use Ventas\Infraestructura\Persistencia\MySQL\PdoConnectionFactory;
use Ventas\Infraestructura\Persistencia\MySQL\Usuarios\MySQLUsuarioRepository;

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
    }
}
