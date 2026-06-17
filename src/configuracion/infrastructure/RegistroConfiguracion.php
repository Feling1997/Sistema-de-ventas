<?php

declare(strict_types=1);

namespace Ventas\Configuracion\Infrastructure;

use PDO;
use Ventas\Configuracion\Application\ObtenerConfiguracionAuth;
use Ventas\Configuracion\Application\ObtenerConfiguracionBalanza;
use Ventas\Configuracion\Application\ObtenerConfiguracionFiscal;
use Ventas\Configuracion\Application\ObtenerConfiguracionGeneral;
use Ventas\Configuracion\Application\ObtenerConfiguracionVenta;
use Ventas\Configuracion\Domain\Repositorios\ConfiguracionRepository;
use Ventas\Infraestructura\Configuracion\DatabaseConfig;
use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Infraestructura\Persistencia\MySQL\PdoConnectionFactory;

final class RegistroConfiguracion
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

        $container->singleton(ConfiguracionRepository::class, fn (Container $container): ConfiguracionRepository => new MySQLConfiguracionRepository(
            $container->get(PDO::class),
            dirname(__DIR__, 3) . '/almacenamiento/configuracion_sistema.json',
            dirname(__DIR__, 3) . '/configuraciones/arca.php'
        ));

        $container->bind(ObtenerConfiguracionGeneral::class, fn (Container $container): ObtenerConfiguracionGeneral => new ObtenerConfiguracionGeneral($container->get(ConfiguracionRepository::class)));

        $container->bind(ObtenerConfiguracionFiscal::class, fn (Container $container): ObtenerConfiguracionFiscal => new ObtenerConfiguracionFiscal($container->get(ConfiguracionRepository::class)));

        $container->bind(ObtenerConfiguracionVenta::class, fn (Container $container): ObtenerConfiguracionVenta => new ObtenerConfiguracionVenta($container->get(ConfiguracionRepository::class)));

        $container->bind(ObtenerConfiguracionBalanza::class, fn (Container $container): ObtenerConfiguracionBalanza => new ObtenerConfiguracionBalanza($container->get(ConfiguracionRepository::class)));

        $container->bind(ObtenerConfiguracionAuth::class, fn (Container $container): ObtenerConfiguracionAuth => new ObtenerConfiguracionAuth($container->get(ConfiguracionRepository::class)));
    }
}
