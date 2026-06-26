<?php

declare(strict_types=1);

namespace Ventas\Importacion\Infrastructure;

use PDO;
use Ventas\Importacion\Application\AnalizarImportacionProductos;
use Ventas\Importacion\Application\ImportarProductosDesdeExcel;
use Ventas\Importacion\Application\ListarHojasImportacionExcel;
use Ventas\Importacion\Domain\Repositorios\ImportacionExcelRepository;
use Ventas\Importacion\Domain\Repositorios\ImportacionHistorialRepository;
use Ventas\Importacion\Domain\Repositorios\ImportacionLogRepository;
use Ventas\Importacion\Domain\Repositorios\ImportacionPreciosRepository;
use Ventas\Importacion\Domain\Repositorios\ImportacionProductosRepository;
use Ventas\Core\Infrastructure\Config\DatabaseConfig;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Core\Infrastructure\Persistence\Mysql\PdoConnectionFactory;

final class RegistroImportacion
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

        $container->singleton(ImportacionExcelRepository::class, fn (Container $container): ImportacionExcelRepository => new MySQLImportacionExcelRepository($container->get(PDO::class)));

        $container->singleton(ImportacionProductosRepository::class, fn (Container $container): ImportacionProductosRepository => new MySQLImportacionProductosRepository($container->get(PDO::class)));

        $container->singleton(ImportacionPreciosRepository::class, fn (Container $container): ImportacionPreciosRepository => new MySQLImportacionPreciosRepository($container->get(PDO::class)));

        $container->singleton(ImportacionHistorialRepository::class, fn (Container $container): ImportacionHistorialRepository => new MySQLImportacionHistorialRepository($container->get(PDO::class)));

        $container->singleton(ImportacionLogRepository::class, fn (Container $container): ImportacionLogRepository => new MySQLImportacionLogRepository($container->get(PDO::class)));

        $container->bind(ListarHojasImportacionExcel::class, fn (Container $container): ListarHojasImportacionExcel => new ListarHojasImportacionExcel($container->get(ImportacionExcelRepository::class)));

        $container->bind(AnalizarImportacionProductos::class, fn (Container $container): AnalizarImportacionProductos => new AnalizarImportacionProductos($container->get(ImportacionExcelRepository::class)));

        $container->bind(ImportarProductosDesdeExcel::class, fn (Container $container): ImportarProductosDesdeExcel => new ImportarProductosDesdeExcel(
            $container->get(ImportacionExcelRepository::class),
            $container->get(ImportacionProductosRepository::class),
            $container->get(ImportacionPreciosRepository::class),
            $container->get(ImportacionHistorialRepository::class),
            $container->get(ImportacionLogRepository::class)
        ));
    }
}
