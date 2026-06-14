<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Contenedor;

use PDO;
use Ventas\Aplicacion\Presupuestos\CasosUso\BuscarPresupuestoPorId;
use Ventas\Aplicacion\Presupuestos\CasosUso\GenerarPdfPresupuesto;
use Ventas\Aplicacion\Presupuestos\CasosUso\ObtenerArchivoPdfPresupuesto;
use Ventas\Aplicacion\Presupuestos\CasosUso\ObtenerDetallePresupuesto;
use Ventas\Aplicacion\Presupuestos\CasosUso\RenderizarTicketPresupuesto;
use Ventas\Dominio\Presupuestos\Repositorios\ComprobantePresupuestoRepository;
use Ventas\Dominio\Presupuestos\Repositorios\PresupuestoRepository;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\ConfiguracionVentaRepository;
use Ventas\Infraestructura\Configuracion\DatabaseConfig;
use Ventas\Infraestructura\Persistencia\MySQL\PdoConnectionFactory;
use Ventas\Infraestructura\Persistencia\MySQL\Presupuestos\MySQLPresupuestoRepository;
use Ventas\Infraestructura\Presupuestos\Comprobantes\HtmlPdfPresupuestoRepository;
use Ventas\Infraestructura\Ventas\NuevaVenta\MySQLConfiguracionVentaRepository;

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

        if (!$container->has(ConfiguracionVentaRepository::class)) {
            $container->singleton(ConfiguracionVentaRepository::class, fn (Container $container): ConfiguracionVentaRepository => new MySQLConfiguracionVentaRepository($container->get(PDO::class)));
        }

        $container->singleton(ComprobantePresupuestoRepository::class, fn (Container $container): ComprobantePresupuestoRepository => new HtmlPdfPresupuestoRepository($container->get(ConfiguracionVentaRepository::class)));

        $container->bind(BuscarPresupuestoPorId::class, fn (Container $container): BuscarPresupuestoPorId => new BuscarPresupuestoPorId($container->get(PresupuestoRepository::class)));

        $container->bind(ObtenerDetallePresupuesto::class, fn (Container $container): ObtenerDetallePresupuesto => new ObtenerDetallePresupuesto($container->get(PresupuestoRepository::class)));

        $container->bind(RenderizarTicketPresupuesto::class, fn (Container $container): RenderizarTicketPresupuesto => new RenderizarTicketPresupuesto($container->get(PresupuestoRepository::class), $container->get(ComprobantePresupuestoRepository::class)));

        $container->bind(GenerarPdfPresupuesto::class, fn (Container $container): GenerarPdfPresupuesto => new GenerarPdfPresupuesto($container->get(PresupuestoRepository::class), $container->get(ComprobantePresupuestoRepository::class)));

        $container->bind(ObtenerArchivoPdfPresupuesto::class, fn (Container $container): ObtenerArchivoPdfPresupuesto => new ObtenerArchivoPdfPresupuesto($container->get(ComprobantePresupuestoRepository::class)));
    }
}
