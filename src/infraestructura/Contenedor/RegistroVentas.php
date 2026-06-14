<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Contenedor;

use PDO;
use Ventas\Aplicacion\Ventas\CasosUso\BuscarVentaPorId;
use Ventas\Aplicacion\Ventas\CasosUso\GenerarPdfComprobanteVenta;
use Ventas\Aplicacion\Ventas\CasosUso\ListarVentas;
use Ventas\Aplicacion\Ventas\CasosUso\ListarVentasPeriodo;
use Ventas\Aplicacion\Ventas\CasosUso\ObtenerArchivoPdfVenta;
use Ventas\Aplicacion\Ventas\CasosUso\ObtenerComprobanteVenta;
use Ventas\Aplicacion\Ventas\CasosUso\ObtenerDetalleVenta;
use Ventas\Aplicacion\Ventas\CasosUso\ObtenerDetallesVentas;
use Ventas\Aplicacion\Ventas\CasosUso\ObtenerEstadosFiscalesVentas;
use Ventas\Aplicacion\Ventas\CasosUso\ObtenerResumenVentasPeriodo;
use Ventas\Aplicacion\Ventas\CasosUso\RenderizarTicketVenta;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\ConfiguracionVentaRepository;
use Ventas\Dominio\Ventas\Repositorios\ComprobanteVentaRepository;
use Ventas\Dominio\Ventas\Repositorios\VentaRepository;
use Ventas\Infraestructura\Configuracion\DatabaseConfig;
use Ventas\Infraestructura\Persistencia\MySQL\PdoConnectionFactory;
use Ventas\Infraestructura\Persistencia\MySQL\Ventas\MySQLVentaRepository;
use Ventas\Infraestructura\Ventas\Comprobantes\HtmlPdfComprobanteVentaRepository;
use Ventas\Infraestructura\Ventas\NuevaVenta\MySQLConfiguracionVentaRepository;

final class RegistroVentas
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

        $container->singleton(VentaRepository::class, fn (Container $container): VentaRepository => new MySQLVentaRepository($container->get(PDO::class)));

        if (!$container->has(ConfiguracionVentaRepository::class)) {
            $container->singleton(ConfiguracionVentaRepository::class, fn (Container $container): ConfiguracionVentaRepository => new MySQLConfiguracionVentaRepository($container->get(PDO::class)));
        }

        $container->singleton(ComprobanteVentaRepository::class, fn (Container $container): ComprobanteVentaRepository => new HtmlPdfComprobanteVentaRepository($container->get(ConfiguracionVentaRepository::class)));

        $container->bind(ListarVentas::class, fn (Container $container): ListarVentas => new ListarVentas($container->get(VentaRepository::class)));

        $container->bind(ListarVentasPeriodo::class, fn (Container $container): ListarVentasPeriodo => new ListarVentasPeriodo($container->get(VentaRepository::class)));

        $container->bind(BuscarVentaPorId::class, fn (Container $container): BuscarVentaPorId => new BuscarVentaPorId($container->get(VentaRepository::class)));

        $container->bind(ObtenerDetalleVenta::class, fn (Container $container): ObtenerDetalleVenta => new ObtenerDetalleVenta($container->get(VentaRepository::class)));

        $container->bind(ObtenerComprobanteVenta::class, fn (Container $container): ObtenerComprobanteVenta => new ObtenerComprobanteVenta($container->get(VentaRepository::class)));

        $container->bind(ObtenerResumenVentasPeriodo::class, fn (Container $container): ObtenerResumenVentasPeriodo => new ObtenerResumenVentasPeriodo($container->get(VentaRepository::class)));

        $container->bind(ObtenerEstadosFiscalesVentas::class, fn (Container $container): ObtenerEstadosFiscalesVentas => new ObtenerEstadosFiscalesVentas($container->get(VentaRepository::class)));

        $container->bind(ObtenerDetallesVentas::class, fn (Container $container): ObtenerDetallesVentas => new ObtenerDetallesVentas($container->get(VentaRepository::class)));

        $container->bind(RenderizarTicketVenta::class, fn (Container $container): RenderizarTicketVenta => new RenderizarTicketVenta($container->get(VentaRepository::class), $container->get(ComprobanteVentaRepository::class)));

        $container->bind(GenerarPdfComprobanteVenta::class, fn (Container $container): GenerarPdfComprobanteVenta => new GenerarPdfComprobanteVenta($container->get(VentaRepository::class), $container->get(ComprobanteVentaRepository::class)));

        $container->bind(ObtenerArchivoPdfVenta::class, fn (Container $container): ObtenerArchivoPdfVenta => new ObtenerArchivoPdfVenta($container->get(ComprobanteVentaRepository::class)));
    }
}
