<?php

declare(strict_types=1);

namespace Ventas\UnidadesMedida\Infrastructure;

use PDO;
use Ventas\Core\Infrastructure\Config\DatabaseConfig;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Core\Infrastructure\Persistence\Mysql\PdoConnectionFactory;
use Ventas\UnidadesMedida\Application\AsegurarUnidadMedidaDesdeFormulario;
use Ventas\UnidadesMedida\Application\BuscarUnidadMedidaPorAbreviatura;
use Ventas\UnidadesMedida\Application\BuscarUnidadMedidaPorId;
use Ventas\UnidadesMedida\Application\CrearUnidadMedidaSinDuplicar;
use Ventas\UnidadesMedida\Application\ListarUnidadesMedida;
use Ventas\UnidadesMedida\Domain\Repositorios\UnidadMedidaRepository;

final class RegistroUnidadesMedida
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

        $container->singleton(UnidadMedidaRepository::class, fn (Container $container): UnidadMedidaRepository => new MySQLUnidadMedidaRepository($container->get(PDO::class)));

        $container->bind(ListarUnidadesMedida::class, fn (Container $container): ListarUnidadesMedida => new ListarUnidadesMedida($container->get(UnidadMedidaRepository::class)));

        $container->bind(BuscarUnidadMedidaPorId::class, fn (Container $container): BuscarUnidadMedidaPorId => new BuscarUnidadMedidaPorId($container->get(UnidadMedidaRepository::class)));

        $container->bind(BuscarUnidadMedidaPorAbreviatura::class, fn (Container $container): BuscarUnidadMedidaPorAbreviatura => new BuscarUnidadMedidaPorAbreviatura($container->get(UnidadMedidaRepository::class)));

        $container->bind(CrearUnidadMedidaSinDuplicar::class, fn (Container $container): CrearUnidadMedidaSinDuplicar => new CrearUnidadMedidaSinDuplicar($container->get(UnidadMedidaRepository::class)));

        $container->bind(AsegurarUnidadMedidaDesdeFormulario::class, fn (Container $container): AsegurarUnidadMedidaDesdeFormulario => new AsegurarUnidadMedidaDesdeFormulario($container->get(UnidadMedidaRepository::class)));
    }
}
