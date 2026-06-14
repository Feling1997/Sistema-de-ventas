<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Contenedor;

use PDO;
use Ventas\Aplicacion\Stock\CasosUso\BuscarStockPorId;
use Ventas\Aplicacion\Stock\CasosUso\ListarStockGeneral;
use Ventas\Aplicacion\Stock\CasosUso\ListarStock;
use Ventas\Dominio\Stock\Repositorios\StockRepository;
use Ventas\Infraestructura\Configuracion\DatabaseConfig;
use Ventas\Infraestructura\Persistencia\MySQL\PdoConnectionFactory;
use Ventas\Infraestructura\Persistencia\MySQL\Stock\MySQLStockRepository;

final class RegistroStock
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

        $container->singleton(StockRepository::class, fn (Container $container): StockRepository => new MySQLStockRepository($container->get(PDO::class)));

        $container->bind(ListarStock::class, fn (Container $container): ListarStock => new ListarStock($container->get(StockRepository::class)));

        $container->bind(ListarStockGeneral::class, fn (Container $container): ListarStockGeneral => new ListarStockGeneral($container->get(StockRepository::class)));

        $container->bind(BuscarStockPorId::class, fn (Container $container): BuscarStockPorId => new BuscarStockPorId($container->get(StockRepository::class)));
    }
}
