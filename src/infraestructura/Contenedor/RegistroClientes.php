<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Contenedor;

use PDO;
use Ventas\Aplicacion\Clientes\CasosUso\ActualizarCliente;
use Ventas\Aplicacion\Clientes\CasosUso\BuscarClientePorId;
use Ventas\Aplicacion\Clientes\CasosUso\CrearCliente;
use Ventas\Aplicacion\Clientes\CasosUso\EliminarCliente;
use Ventas\Aplicacion\Clientes\CasosUso\ListarClientes;
use Ventas\Dominio\Clientes\Repositorios\ClienteRepository;
use Ventas\Infraestructura\Configuracion\DatabaseConfig;
use Ventas\Infraestructura\Persistencia\MySQL\Clientes\MySQLClienteRepository;
use Ventas\Infraestructura\Persistencia\MySQL\PdoConnectionFactory;

final class RegistroClientes
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

        $container->singleton(ClienteRepository::class, fn (Container $container): ClienteRepository => new MySQLClienteRepository($container->get(PDO::class)));

        $container->bind(ListarClientes::class, fn (Container $container): ListarClientes => new ListarClientes($container->get(ClienteRepository::class)));

        $container->bind(BuscarClientePorId::class, fn (Container $container): BuscarClientePorId => new BuscarClientePorId($container->get(ClienteRepository::class)));

        $container->bind(CrearCliente::class, fn (Container $container): CrearCliente => new CrearCliente($container->get(ClienteRepository::class)));

        $container->bind(ActualizarCliente::class, fn (Container $container): ActualizarCliente => new ActualizarCliente($container->get(ClienteRepository::class)));

        $container->bind(EliminarCliente::class, fn (Container $container): EliminarCliente => new EliminarCliente($container->get(ClienteRepository::class)));
    }
}
