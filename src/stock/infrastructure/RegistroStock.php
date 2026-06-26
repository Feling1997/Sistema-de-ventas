<?php

declare(strict_types=1);

namespace Ventas\Stock\Infrastructure;

use PDO;
use Ventas\Core\Infrastructure\Config\DatabaseConfig;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Core\Infrastructure\Persistence\Mysql\PdoConnectionFactory;
use Ventas\Stock\Application\ActualizarStock;
use Ventas\Stock\Application\AlertasStockBajo;
use Ventas\Stock\Application\BuscarStockPorId;
use Ventas\Stock\Application\ContarProductosAsociadosStock;
use Ventas\Stock\Application\CrearStock;
use Ventas\Stock\Application\CrearStockRetornandoId;
use Ventas\Stock\Application\EliminarStock;
use Ventas\Stock\Application\EstaAsociadoAProductosStock;
use Ventas\Stock\Application\InicializarAlertasStock;
use Ventas\Stock\Application\InicializarEsquemaStock;
use Ventas\Stock\Application\ListarFaltantes;
use Ventas\Stock\Application\ListarStock;
use Ventas\Stock\Application\ListarStockGeneral;
use Ventas\Stock\Application\MarcarAlertaLeida;
use Ventas\Stock\Application\RecalcularCostosPorCotizacion;
use Ventas\Stock\Application\RecalcularPreciosProductosPorStock;
use Ventas\Stock\Application\ResumenAlertasStockBajo;
use Ventas\Stock\Application\SumarCantidadStock;
use Ventas\Stock\Application\ObtenerCostoStock;
use Ventas\Stock\Application\ObtenerCostoEnPesosStock;
use Ventas\Stock\Application\ObtenerCotizacionDolarStock;
use Ventas\Stock\Application\ObtenerStockActivo;
use Ventas\Stock\Application\ObtenerStockPorProducto;
use Ventas\Stock\Domain\Repositorios\StockRepository;

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

        $container->bind(CrearStock::class, fn (Container $container): CrearStock => new CrearStock($container->get(StockRepository::class)));

        $container->bind(CrearStockRetornandoId::class, fn (Container $container): CrearStockRetornandoId => new CrearStockRetornandoId($container->get(StockRepository::class)));

        $container->bind(ActualizarStock::class, fn (Container $container): ActualizarStock => new ActualizarStock($container->get(StockRepository::class)));

        $container->bind(SumarCantidadStock::class, fn (Container $container): SumarCantidadStock => new SumarCantidadStock($container->get(StockRepository::class)));

        $container->bind(ContarProductosAsociadosStock::class, fn (Container $container): ContarProductosAsociadosStock => new ContarProductosAsociadosStock($container->get(StockRepository::class)));

        $container->bind(EstaAsociadoAProductosStock::class, fn (Container $container): EstaAsociadoAProductosStock => new EstaAsociadoAProductosStock($container->get(StockRepository::class)));

        $container->bind(EliminarStock::class, fn (Container $container): EliminarStock => new EliminarStock($container->get(StockRepository::class)));

        $container->bind(RecalcularPreciosProductosPorStock::class, fn (Container $container): RecalcularPreciosProductosPorStock => new RecalcularPreciosProductosPorStock($container->get(StockRepository::class)));

        $container->bind(RecalcularCostosPorCotizacion::class, fn (Container $container): RecalcularCostosPorCotizacion => new RecalcularCostosPorCotizacion($container->get(StockRepository::class)));

        $container->bind(AlertasStockBajo::class, fn (Container $container): AlertasStockBajo => new AlertasStockBajo($container->get(StockRepository::class)));

        $container->bind(ResumenAlertasStockBajo::class, fn (Container $container): ResumenAlertasStockBajo => new ResumenAlertasStockBajo($container->get(StockRepository::class)));

        $container->bind(MarcarAlertaLeida::class, fn (Container $container): MarcarAlertaLeida => new MarcarAlertaLeida($container->get(StockRepository::class)));

        $container->bind(ListarFaltantes::class, fn (Container $container): ListarFaltantes => new ListarFaltantes($container->get(StockRepository::class)));

        $container->bind(ObtenerCotizacionDolarStock::class, fn (Container $container): ObtenerCotizacionDolarStock => new ObtenerCotizacionDolarStock($container->get(StockRepository::class)));

        $container->bind(ObtenerCostoStock::class, fn (Container $container): ObtenerCostoStock => new ObtenerCostoStock($container->get(StockRepository::class)));

        $container->bind(ObtenerCostoEnPesosStock::class, fn (Container $container): ObtenerCostoEnPesosStock => new ObtenerCostoEnPesosStock($container->get(StockRepository::class)));

        $container->bind(ObtenerStockActivo::class, fn (Container $container): ObtenerStockActivo => new ObtenerStockActivo($container->get(StockRepository::class)));

        $container->bind(ObtenerStockPorProducto::class, fn (Container $container): ObtenerStockPorProducto => new ObtenerStockPorProducto($container->get(StockRepository::class)));

        $container->bind(InicializarEsquemaStock::class, fn (Container $container): InicializarEsquemaStock => new InicializarEsquemaStock($container->get(StockRepository::class)));

        $container->bind(InicializarAlertasStock::class, fn (Container $container): InicializarAlertasStock => new InicializarAlertasStock($container->get(StockRepository::class)));
    }
}
