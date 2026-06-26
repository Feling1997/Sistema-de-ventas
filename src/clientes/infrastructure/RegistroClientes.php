<?php

declare(strict_types=1);

namespace Ventas\Clientes\Infrastructure;

use PDO;
use Ventas\Clientes\Application\ActualizarCliente;
use Ventas\Clientes\Application\BuscarClientePorId;
use Ventas\Clientes\Application\CrearCliente;
use Ventas\Clientes\Application\EliminarCliente;
use Ventas\Clientes\Application\InicializarEsquemaClientesFiscales;
use Ventas\Clientes\Application\ListarClientes;
use Ventas\Clientes\Application\ValidarClienteFacturaA;
use Ventas\Clientes\Domain\Repositorios\ClienteRepository;
use Ventas\Core\Infrastructure\Config\DatabaseConfig;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Core\Infrastructure\Persistence\Mysql\PdoConnectionFactory;

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

        $container->bind(ValidarClienteFacturaA::class, fn (): ValidarClienteFacturaA => new ValidarClienteFacturaA());

        $container->bind(InicializarEsquemaClientesFiscales::class, fn (Container $container): InicializarEsquemaClientesFiscales => new InicializarEsquemaClientesFiscales($container->get(ClienteRepository::class)));
    }
}
