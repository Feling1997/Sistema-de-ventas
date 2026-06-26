<?php

declare(strict_types=1);

namespace Ventas\Presupuestos\Infrastructure;

use PDO;
use Ventas\Configuracion\Domain\Repositorios\ConfiguracionRepository;
use Ventas\Configuracion\Infrastructure\RegistroConfiguracion;
use Ventas\Core\Infrastructure\Config\DatabaseConfig;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Core\Infrastructure\Persistence\Mysql\PdoConnectionFactory;
use Ventas\Presupuestos\Application\BuscarPresupuestoPorId;
use Ventas\Presupuestos\Application\GenerarPdfPresupuesto;
use Ventas\Presupuestos\Application\ObtenerArchivoPdfPresupuesto;
use Ventas\Presupuestos\Application\ObtenerDetallePresupuesto;
use Ventas\Presupuestos\Application\RenderizarTicketPresupuesto;
use Ventas\Presupuestos\Domain\Repositorios\ComprobantePresupuestoRepository;
use Ventas\Presupuestos\Domain\Repositorios\PresupuestoRepository;

final class RegistroPresupuestos
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

        $container->singleton(PresupuestoRepository::class, fn (Container $container): PresupuestoRepository => new MySQLPresupuestoRepository($container->get(PDO::class)));

        if (!$container->has(ConfiguracionRepository::class)) {
            RegistroConfiguracion::registrar($container);
        }

        $container->singleton(ComprobantePresupuestoRepository::class, fn (Container $container): ComprobantePresupuestoRepository => new HtmlPdfPresupuestoRepository($container->get(ConfiguracionRepository::class)));

        $container->bind(BuscarPresupuestoPorId::class, fn (Container $container): BuscarPresupuestoPorId => new BuscarPresupuestoPorId($container->get(PresupuestoRepository::class)));

        $container->bind(ObtenerDetallePresupuesto::class, fn (Container $container): ObtenerDetallePresupuesto => new ObtenerDetallePresupuesto($container->get(PresupuestoRepository::class)));

        $container->bind(RenderizarTicketPresupuesto::class, fn (Container $container): RenderizarTicketPresupuesto => new RenderizarTicketPresupuesto($container->get(PresupuestoRepository::class), $container->get(ComprobantePresupuestoRepository::class)));

        $container->bind(GenerarPdfPresupuesto::class, fn (Container $container): GenerarPdfPresupuesto => new GenerarPdfPresupuesto($container->get(PresupuestoRepository::class), $container->get(ComprobantePresupuestoRepository::class)));

        $container->bind(ObtenerArchivoPdfPresupuesto::class, fn (Container $container): ObtenerArchivoPdfPresupuesto => new ObtenerArchivoPdfPresupuesto($container->get(ComprobantePresupuestoRepository::class)));
    }
}
