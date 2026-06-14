<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Contenedor;

use PDO;
use Ventas\Aplicacion\ListasPrecios\CasosUso\BuscarListaPrecioPorId;
use Ventas\Aplicacion\ListasPrecios\CasosUso\ListarListasPrecios;
use Ventas\Aplicacion\ListasPrecios\CasosUso\ObtenerListaPrecioPredeterminada;
use Ventas\Dominio\ListasPrecios\Repositorios\ListaPrecioRepository;
use Ventas\Infraestructura\Configuracion\DatabaseConfig;
use Ventas\Infraestructura\Persistencia\MySQL\ListasPrecios\MySQLListaPrecioRepository;
use Ventas\Infraestructura\Persistencia\MySQL\PdoConnectionFactory;

final class RegistroListasPrecios
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

        $container->singleton(ListaPrecioRepository::class, fn (Container $container): ListaPrecioRepository => new MySQLListaPrecioRepository($container->get(PDO::class)));

        $container->bind(ListarListasPrecios::class, fn (Container $container): ListarListasPrecios => new ListarListasPrecios($container->get(ListaPrecioRepository::class)));

        $container->bind(BuscarListaPrecioPorId::class, fn (Container $container): BuscarListaPrecioPorId => new BuscarListaPrecioPorId($container->get(ListaPrecioRepository::class)));

        $container->bind(ObtenerListaPrecioPredeterminada::class, fn (Container $container): ObtenerListaPrecioPredeterminada => new ObtenerListaPrecioPredeterminada($container->get(ListaPrecioRepository::class)));
    }
}
