<?php

declare(strict_types=1);

namespace Ventas\Precios\Infrastructure;

use PDO;
use Ventas\Core\Infrastructure\Config\DatabaseConfig;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Core\Infrastructure\Persistence\Mysql\PdoConnectionFactory;
use Ventas\Precios\Application\CalcularCostoEnPesos;
use Ventas\Precios\Application\NormalizarMonedaCosto;
use Ventas\Precios\Application\ObtenerCotizacionDolar;
use Ventas\Precios\Application\RecalcularCostosPorCotizacion;
use Ventas\Precios\Application\RecalcularPreciosProductosPorStock;
use Ventas\Precios\Domain\Repositorios\PrecioRepository;

final class RegistroPrecios
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

        $container->singleton(PrecioRepository::class, fn (Container $container): PrecioRepository => new MySQLPrecioRepository($container->get(PDO::class)));

        $container->bind(NormalizarMonedaCosto::class, fn (): NormalizarMonedaCosto => new NormalizarMonedaCosto());

        $container->bind(ObtenerCotizacionDolar::class, fn (): ObtenerCotizacionDolar => new ObtenerCotizacionDolar());

        $container->bind(CalcularCostoEnPesos::class, fn (Container $container): CalcularCostoEnPesos => new CalcularCostoEnPesos(
            $container->get(NormalizarMonedaCosto::class),
            $container->get(ObtenerCotizacionDolar::class)
        ));

        $container->bind(RecalcularPreciosProductosPorStock::class, fn (Container $container): RecalcularPreciosProductosPorStock => new RecalcularPreciosProductosPorStock($container->get(PrecioRepository::class)));

        $container->bind(RecalcularCostosPorCotizacion::class, fn (Container $container): RecalcularCostosPorCotizacion => new RecalcularCostosPorCotizacion(
            $container->get(PrecioRepository::class),
            $container->get(ObtenerCotizacionDolar::class),
            $container->get(RecalcularPreciosProductosPorStock::class)
        ));
    }
}
