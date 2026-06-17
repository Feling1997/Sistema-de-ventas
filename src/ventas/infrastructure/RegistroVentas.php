<?php

declare(strict_types=1);

namespace Ventas\Ventas\Infrastructure;

use PDO;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\ConfiguracionVentaRepository;
use Ventas\Infraestructura\Configuracion\DatabaseConfig;
use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Infraestructura\Persistencia\MySQL\PdoConnectionFactory;
use Ventas\Ventas\Application\BuscarVentaPorId;
use Ventas\Ventas\Application\ConfirmarVenta;
use Ventas\Ventas\Application\GenerarPdfComprobanteVenta;
use Ventas\Ventas\Application\ListarVentas;
use Ventas\Ventas\Application\NuevaVenta\ActualizarItemCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\AgregarItemCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\AplicarListaPrecioCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\CalcularTotalCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\GuardarFormularioVenta;
use Ventas\Ventas\Application\NuevaVenta\GuardarMenuVentas;
use Ventas\Ventas\Application\NuevaVenta\InterpretarCodigoBalanzaVenta;
use Ventas\Ventas\Application\NuevaVenta\ListarClientesVenta;
use Ventas\Ventas\Application\NuevaVenta\ObtenerCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\ObtenerFormularioVenta;
use Ventas\Ventas\Application\NuevaVenta\ObtenerInicioVentas;
use Ventas\Ventas\Application\NuevaVenta\ObtenerPanelVentas;
use Ventas\Ventas\Application\NuevaVenta\ObtenerSaldosFavorClientes;
use Ventas\Ventas\Application\NuevaVenta\ObtenerUsuarioActual;
use Ventas\Ventas\Application\NuevaVenta\QuitarItemCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\RenderizarCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\VaciarCarritoVenta;
use Ventas\Ventas\Application\ListarVentasPeriodo;
use Ventas\Ventas\Application\ObtenerArchivoPdfVenta;
use Ventas\Ventas\Application\ObtenerComprobanteVenta;
use Ventas\Ventas\Application\ObtenerDetallesVentas;
use Ventas\Ventas\Application\ObtenerDetalleVenta;
use Ventas\Ventas\Application\ObtenerEstadosFiscalesVentas;
use Ventas\Ventas\Application\ObtenerResumenVentasPeriodo;
use Ventas\Ventas\Application\RenderizarTicketVenta;
use Ventas\Ventas\Domain\Repositorios\ComprobanteVentaRepository;
use Ventas\Ventas\Domain\Repositorios\VentaRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\CarritoVentaRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\ClienteVentaRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\FormularioVentaRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\MenuVentasRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\SaldoFavorClienteRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\UsuarioActualRepository;
use Ventas\Ventas\Infrastructure\NuevaVenta\ArchivoMenuVentasRepository;
use Ventas\Ventas\Infrastructure\NuevaVenta\MySQLClienteVentaRepository;
use Ventas\Ventas\Infrastructure\NuevaVenta\MySQLConfiguracionVentaRepository;
use Ventas\Ventas\Infrastructure\NuevaVenta\MySQLSaldoFavorClienteRepository;
use Ventas\Ventas\Infrastructure\NuevaVenta\SesionCarritoVentaRepository;
use Ventas\Ventas\Infrastructure\NuevaVenta\SesionFormularioVentaRepository;
use Ventas\Ventas\Infrastructure\NuevaVenta\SesionUsuarioActualRepository;
use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;
use Ventas\ListasPrecios\Infrastructure\MySQLListaPrecioRepository;
use Ventas\Productos\Application\BuscarProductoPorCodigoOPLU;
use Ventas\Productos\Application\ObtenerProductoParaVenta;
use Ventas\Productos\Domain\Repositorios\ProductoRepository;
use Ventas\Productos\Infrastructure\MySQLProductoRepository;
use Ventas\Stock\Application\BuscarStockPorId;
use Ventas\Stock\Domain\Repositorios\StockRepository;
use Ventas\Stock\Infrastructure\MySQLStockRepository;

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

        if (!$container->has(ListaPrecioRepository::class)) {
            $container->singleton(ListaPrecioRepository::class, fn (Container $container): ListaPrecioRepository => new MySQLListaPrecioRepository($container->get(PDO::class)));
        }

        if (!$container->has(ProductoRepository::class)) {
            $container->singleton(ProductoRepository::class, fn (Container $container): ProductoRepository => new MySQLProductoRepository($container->get(PDO::class)));
        }

        if (!$container->has(StockRepository::class)) {
            $container->singleton(StockRepository::class, fn (Container $container): StockRepository => new MySQLStockRepository($container->get(PDO::class)));
        }

        if (!$container->has(ObtenerProductoParaVenta::class)) {
            $container->bind(ObtenerProductoParaVenta::class, fn (Container $container): ObtenerProductoParaVenta => new ObtenerProductoParaVenta($container->get(ProductoRepository::class)));
        }

        if (!$container->has(BuscarProductoPorCodigoOPLU::class)) {
            $container->bind(BuscarProductoPorCodigoOPLU::class, fn (Container $container): BuscarProductoPorCodigoOPLU => new BuscarProductoPorCodigoOPLU($container->get(ProductoRepository::class)));
        }

        if (!$container->has(BuscarStockPorId::class)) {
            $container->bind(BuscarStockPorId::class, fn (Container $container): BuscarStockPorId => new BuscarStockPorId($container->get(StockRepository::class)));
        }

        if (!$container->has(ConfiguracionVentaRepository::class)) {
            $container->singleton(ConfiguracionVentaRepository::class, fn (Container $container): ConfiguracionVentaRepository => new MySQLConfiguracionVentaRepository($container->get(PDO::class)));
        }

        $container->singleton(CarritoVentaRepository::class, fn (): CarritoVentaRepository => new SesionCarritoVentaRepository());

        $container->singleton(FormularioVentaRepository::class, fn (): FormularioVentaRepository => new SesionFormularioVentaRepository());

        $container->singleton(UsuarioActualRepository::class, fn (): UsuarioActualRepository => new SesionUsuarioActualRepository());

        $container->singleton(MenuVentasRepository::class, fn (): MenuVentasRepository => new ArchivoMenuVentasRepository());

        $container->singleton(ClienteVentaRepository::class, fn (Container $container): ClienteVentaRepository => new MySQLClienteVentaRepository($container->get(PDO::class)));

        $container->singleton(SaldoFavorClienteRepository::class, fn (Container $container): SaldoFavorClienteRepository => new MySQLSaldoFavorClienteRepository($container->get(PDO::class)));

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

        $container->bind(ObtenerCarritoVenta::class, fn (Container $container): ObtenerCarritoVenta => new ObtenerCarritoVenta($container->get(CarritoVentaRepository::class)));

        $container->bind(ObtenerUsuarioActual::class, fn (Container $container): ObtenerUsuarioActual => new ObtenerUsuarioActual($container->get(UsuarioActualRepository::class)));

        $container->bind(ObtenerInicioVentas::class, fn (Container $container): ObtenerInicioVentas => new ObtenerInicioVentas($container->get(UsuarioActualRepository::class), $container->get(ConfiguracionVentaRepository::class)));

        $container->bind(ObtenerPanelVentas::class, fn (Container $container): ObtenerPanelVentas => new ObtenerPanelVentas($container->get(UsuarioActualRepository::class)));

        $container->bind(GuardarMenuVentas::class, fn (Container $container): GuardarMenuVentas => new GuardarMenuVentas($container->get(UsuarioActualRepository::class), $container->get(MenuVentasRepository::class)));

        $container->bind(RenderizarCarritoVenta::class, fn (): RenderizarCarritoVenta => new RenderizarCarritoVenta());

        $container->bind(InterpretarCodigoBalanzaVenta::class, fn (Container $container): InterpretarCodigoBalanzaVenta => new InterpretarCodigoBalanzaVenta($container->get(ProductoRepository::class), $container->get(ConfiguracionVentaRepository::class)));

        $container->bind(CalcularTotalCarritoVenta::class, fn (): CalcularTotalCarritoVenta => new CalcularTotalCarritoVenta());

        $container->bind(ObtenerFormularioVenta::class, fn (Container $container): ObtenerFormularioVenta => new ObtenerFormularioVenta($container->get(FormularioVentaRepository::class), $container->get(ListaPrecioRepository::class)));

        $container->bind(GuardarFormularioVenta::class, fn (Container $container): GuardarFormularioVenta => new GuardarFormularioVenta($container->get(FormularioVentaRepository::class)));

        $container->bind(ListarClientesVenta::class, fn (Container $container): ListarClientesVenta => new ListarClientesVenta($container->get(ClienteVentaRepository::class)));

        $container->bind(ObtenerSaldosFavorClientes::class, fn (Container $container): ObtenerSaldosFavorClientes => new ObtenerSaldosFavorClientes($container->get(SaldoFavorClienteRepository::class)));

        $container->bind(AplicarListaPrecioCarritoVenta::class, fn (Container $container): AplicarListaPrecioCarritoVenta => new AplicarListaPrecioCarritoVenta($container->get(CarritoVentaRepository::class), $container->get(ProductoRepository::class)));

        $container->bind(AgregarItemCarritoVenta::class, fn (Container $container): AgregarItemCarritoVenta => new AgregarItemCarritoVenta($container->get(CarritoVentaRepository::class), $container->get(ProductoRepository::class), $container->get(ObtenerProductoParaVenta::class), $container->get(BuscarStockPorId::class), $container->get(ConfiguracionVentaRepository::class)));

        $container->bind(ActualizarItemCarritoVenta::class, fn (Container $container): ActualizarItemCarritoVenta => new ActualizarItemCarritoVenta($container->get(CarritoVentaRepository::class), $container->get(ObtenerProductoParaVenta::class), $container->get(BuscarStockPorId::class), $container->get(ConfiguracionVentaRepository::class)));

        $container->bind(QuitarItemCarritoVenta::class, fn (Container $container): QuitarItemCarritoVenta => new QuitarItemCarritoVenta($container->get(CarritoVentaRepository::class)));

        $container->bind(VaciarCarritoVenta::class, fn (Container $container): VaciarCarritoVenta => new VaciarCarritoVenta($container->get(CarritoVentaRepository::class)));

        $container->bind(ConfirmarVenta::class, fn (Container $container): ConfirmarVenta => new ConfirmarVenta($container->get(VentaRepository::class), $container->get(CarritoVentaRepository::class), $container->get(FormularioVentaRepository::class), $container->get(ConfiguracionVentaRepository::class)));
    }
}
