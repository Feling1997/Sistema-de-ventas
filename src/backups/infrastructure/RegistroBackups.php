<?php

declare(strict_types=1);

namespace Ventas\Backups\Infrastructure;

use PDO;
use Ventas\Backups\Application\AgregarArchivoRespaldo;
use Ventas\Backups\Application\AgregarCarpetaRespaldo;
use Ventas\Backups\Application\CopiarRespaldoLocal;
use Ventas\Backups\Application\GenerarDumpMysqlRespaldo;
use Ventas\Backups\Application\GenerarEstructuraRespaldo;
use Ventas\Backups\Application\GenerarResumenRespaldo;
use Ventas\Backups\Application\GenerarRespaldoSistema;
use Ventas\Backups\Application\GenerarTextoResumenRespaldo;
use Ventas\Backups\Application\ProbarConexionBackblaze;
use Ventas\Backups\Application\SubirRespaldoBackblaze;
use Ventas\Backups\Application\VerificarBackblazeConfigurado;
use Ventas\Backups\Domain\Repositorios\BackblazeStorageRepository;
use Ventas\Backups\Domain\Repositorios\BackupRepository;
use Ventas\Backups\Domain\Repositorios\DatabaseDumpRepository;
use Ventas\Backups\Domain\Repositorios\FilesystemRespaldoRepository as FilesystemRespaldoRepositoryContract;
use Ventas\Core\Infrastructure\Config\DatabaseConfig;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Core\Infrastructure\Persistence\Mysql\PdoConnectionFactory;

final class RegistroBackups
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

        $container->singleton(BackupRepository::class, fn (): BackupRepository => new FilesystemBackupRepository());

        $container->singleton(DatabaseDumpRepository::class, fn (Container $container): DatabaseDumpRepository => new MySQLDatabaseDumpRepository($container->get(PDO::class)));

        $container->singleton(FilesystemRespaldoRepositoryContract::class, fn (Container $container): FilesystemRespaldoRepositoryContract => new FilesystemRespaldoRepository($container->get(BackupRepository::class), $container->get(DatabaseDumpRepository::class)));

        $container->singleton(BackblazeB2HttpRepository::class, fn (): BackblazeB2HttpRepository => new BackblazeB2HttpRepository());

        $container->singleton(BackblazeStorageRepository::class, fn (Container $container): BackblazeStorageRepository => $container->get(BackblazeB2HttpRepository::class));

        $container->bind(GenerarResumenRespaldo::class, fn (Container $container): GenerarResumenRespaldo => new GenerarResumenRespaldo($container->get(BackupRepository::class)));

        $container->bind(GenerarTextoResumenRespaldo::class, fn (Container $container): GenerarTextoResumenRespaldo => new GenerarTextoResumenRespaldo($container->get(BackupRepository::class)));

        $container->bind(GenerarEstructuraRespaldo::class, fn (Container $container): GenerarEstructuraRespaldo => new GenerarEstructuraRespaldo($container->get(BackupRepository::class)));

        $container->bind(GenerarDumpMysqlRespaldo::class, fn (Container $container): GenerarDumpMysqlRespaldo => new GenerarDumpMysqlRespaldo($container->get(DatabaseDumpRepository::class)));

        $container->bind(GenerarRespaldoSistema::class, fn (Container $container): GenerarRespaldoSistema => new GenerarRespaldoSistema($container->get(FilesystemRespaldoRepositoryContract::class)));

        $container->bind(CopiarRespaldoLocal::class, fn (Container $container): CopiarRespaldoLocal => new CopiarRespaldoLocal($container->get(FilesystemRespaldoRepositoryContract::class)));

        $container->bind(AgregarArchivoRespaldo::class, fn (Container $container): AgregarArchivoRespaldo => new AgregarArchivoRespaldo($container->get(FilesystemRespaldoRepositoryContract::class)));

        $container->bind(AgregarCarpetaRespaldo::class, fn (Container $container): AgregarCarpetaRespaldo => new AgregarCarpetaRespaldo($container->get(FilesystemRespaldoRepositoryContract::class)));

        $container->bind(VerificarBackblazeConfigurado::class, fn (Container $container): VerificarBackblazeConfigurado => new VerificarBackblazeConfigurado($container->get(BackblazeStorageRepository::class)));

        $container->bind(ProbarConexionBackblaze::class, fn (Container $container): ProbarConexionBackblaze => new ProbarConexionBackblaze($container->get(BackblazeStorageRepository::class)));

        $container->bind(SubirRespaldoBackblaze::class, fn (Container $container): SubirRespaldoBackblaze => new SubirRespaldoBackblaze($container->get(BackblazeStorageRepository::class)));
    }
}
