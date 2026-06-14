<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Ventas\NuevaVenta;

use PDO;
use Ventas\Aplicacion\Productos\CasosUso\BuscarProductoPorCodigoOPLU;
use Ventas\Aplicacion\Productos\CasosUso\ObtenerProductoParaVenta;
use Ventas\Aplicacion\Stock\CasosUso\BuscarStockPorId;
use Ventas\Aplicacion\Ventas\NuevaVenta\ActualizarItemCarritoVenta;
use Ventas\Aplicacion\Ventas\NuevaVenta\AgregarItemCarritoVenta;
use Ventas\Aplicacion\Ventas\NuevaVenta\AplicarListaPrecioCarritoVenta;
use Ventas\Aplicacion\Ventas\NuevaVenta\CalcularTotalCarritoVenta;
use Ventas\Aplicacion\Ventas\NuevaVenta\GuardarFormularioVenta;
use Ventas\Aplicacion\Ventas\NuevaVenta\GuardarMenuVentas;
use Ventas\Aplicacion\Ventas\NuevaVenta\InterpretarCodigoBalanzaVenta;
use Ventas\Aplicacion\Ventas\NuevaVenta\ListarClientesVenta;
use Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerCarritoVenta;
use Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerFormularioVenta;
use Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerInicioVentas;
use Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerPanelVentas;
use Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerSaldosFavorClientes;
use Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerUsuarioActual;
use Ventas\Aplicacion\Ventas\NuevaVenta\QuitarItemCarritoVenta;
use Ventas\Aplicacion\Ventas\NuevaVenta\RenderizarCarritoVenta;
use Ventas\Aplicacion\Ventas\NuevaVenta\VaciarCarritoVenta;
use Ventas\Aplicacion\Ventas\CasosUso\ConfirmarVenta;
use Ventas\Dominio\ListasPrecios\Repositorios\ListaPrecioRepository;
use Ventas\Dominio\Productos\Repositorios\ProductoRepository;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\CarritoVentaRepository;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\ClienteVentaRepository;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\ConfiguracionVentaRepository;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\FormularioVentaRepository;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\MenuVentasRepository;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\SaldoFavorClienteRepository;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\UsuarioActualRepository;
use Ventas\Dominio\Ventas\Repositorios\VentaRepository;
use Ventas\Infraestructura\Configuracion\DatabaseConfig;
use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Infraestructura\Persistencia\MySQL\ListasPrecios\MySQLListaPrecioRepository;
use Ventas\Infraestructura\Persistencia\MySQL\PdoConnectionFactory;
use Ventas\Infraestructura\Persistencia\MySQL\Productos\MySQLProductoRepository;
use Ventas\Infraestructura\Persistencia\MySQL\Ventas\MySQLVentaRepository;

final class RegistroNuevaVenta
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

        if (!$container->has(ListaPrecioRepository::class)) {
            $container->singleton(ListaPrecioRepository::class, fn (Container $container): ListaPrecioRepository => new MySQLListaPrecioRepository($container->get(PDO::class)));
        }

        if (!$container->has(ProductoRepository::class)) {
            $container->singleton(ProductoRepository::class, fn (Container $container): ProductoRepository => new MySQLProductoRepository($container->get(PDO::class)));
        }

        if (!$container->has(VentaRepository::class)) {
            $container->singleton(VentaRepository::class, fn (Container $container): VentaRepository => new MySQLVentaRepository($container->get(PDO::class)));
        }

        if (!$container->has(ObtenerProductoParaVenta::class)) {
            $container->bind(ObtenerProductoParaVenta::class, fn (Container $container): ObtenerProductoParaVenta => new ObtenerProductoParaVenta($container->get(ProductoRepository::class)));
        }

        if (!$container->has(BuscarProductoPorCodigoOPLU::class)) {
            $container->bind(BuscarProductoPorCodigoOPLU::class, fn (Container $container): BuscarProductoPorCodigoOPLU => new BuscarProductoPorCodigoOPLU($container->get(ProductoRepository::class)));
        }

        $container->singleton(CarritoVentaRepository::class, fn (): CarritoVentaRepository => new SesionCarritoVentaRepository());

        $container->singleton(FormularioVentaRepository::class, fn (): FormularioVentaRepository => new SesionFormularioVentaRepository());

        $container->singleton(UsuarioActualRepository::class, fn (): UsuarioActualRepository => new SesionUsuarioActualRepository());

        $container->singleton(MenuVentasRepository::class, fn (): MenuVentasRepository => new ArchivoMenuVentasRepository());

        $container->singleton(ClienteVentaRepository::class, fn (Container $container): ClienteVentaRepository => new MySQLClienteVentaRepository($container->get(PDO::class)));

        $container->singleton(ConfiguracionVentaRepository::class, fn (Container $container): ConfiguracionVentaRepository => new MySQLConfiguracionVentaRepository($container->get(PDO::class)));

        $container->singleton(SaldoFavorClienteRepository::class, fn (Container $container): SaldoFavorClienteRepository => new MySQLSaldoFavorClienteRepository($container->get(PDO::class)));

        $container->bind(ObtenerCarritoVenta::class, fn (Container $container): ObtenerCarritoVenta => new ObtenerCarritoVenta($container->get(CarritoVentaRepository::class)));

        $container->bind(ObtenerUsuarioActual::class, fn (Container $container): ObtenerUsuarioActual => new ObtenerUsuarioActual($container->get(UsuarioActualRepository::class)));

        $container->bind(ObtenerInicioVentas::class, fn (Container $container): ObtenerInicioVentas => new ObtenerInicioVentas($container->get(UsuarioActualRepository::class), $container->get(ConfiguracionVentaRepository::class)));

        $container->bind(ObtenerPanelVentas::class, fn (Container $container): ObtenerPanelVentas => new ObtenerPanelVentas($container->get(UsuarioActualRepository::class)));

        $container->bind(GuardarMenuVentas::class, fn (Container $container): GuardarMenuVentas => new GuardarMenuVentas($container->get(UsuarioActualRepository::class), $container->get(MenuVentasRepository::class)));

        $container->bind(RenderizarCarritoVenta::class, fn (): RenderizarCarritoVenta => new RenderizarCarritoVenta());

        $container->bind(InterpretarCodigoBalanzaVenta::class, fn (Container $container): InterpretarCodigoBalanzaVenta => new InterpretarCodigoBalanzaVenta($container->get(ProductoRepository::class), $container->get(ConfiguracionVentaRepository::class)));

        $container->bind(CalcularTotalCarritoVenta::class, fn (): CalcularTotalCarritoVenta => new CalcularTotalCarritoVenta());

        $container->bind(ObtenerFormularioVenta::class, fn (Container $container): ObtenerFormularioVenta => new ObtenerFormularioVenta(
                $container->get(FormularioVentaRepository::class),
                $container->get(ListaPrecioRepository::class)
            ));

        $container->bind(GuardarFormularioVenta::class, fn (Container $container): GuardarFormularioVenta => new GuardarFormularioVenta($container->get(FormularioVentaRepository::class)));

        $container->bind(ListarClientesVenta::class, fn (Container $container): ListarClientesVenta => new ListarClientesVenta($container->get(ClienteVentaRepository::class)));

        $container->bind(ObtenerSaldosFavorClientes::class, fn (Container $container): ObtenerSaldosFavorClientes => new ObtenerSaldosFavorClientes($container->get(SaldoFavorClienteRepository::class)));

        $container->bind(AplicarListaPrecioCarritoVenta::class, fn (Container $container): AplicarListaPrecioCarritoVenta => new AplicarListaPrecioCarritoVenta(
                $container->get(CarritoVentaRepository::class),
                $container->get(ProductoRepository::class)
            ));

        $container->bind(AgregarItemCarritoVenta::class, fn (Container $container): AgregarItemCarritoVenta => new AgregarItemCarritoVenta(
                $container->get(CarritoVentaRepository::class),
                $container->get(ProductoRepository::class),
                $container->get(ObtenerProductoParaVenta::class),
                $container->get(BuscarStockPorId::class),
                $container->get(ConfiguracionVentaRepository::class)
            ));

        $container->bind(ActualizarItemCarritoVenta::class, fn (Container $container): ActualizarItemCarritoVenta => new ActualizarItemCarritoVenta(
                $container->get(CarritoVentaRepository::class),
                $container->get(ObtenerProductoParaVenta::class),
                $container->get(BuscarStockPorId::class),
                $container->get(ConfiguracionVentaRepository::class)
            ));

        $container->bind(QuitarItemCarritoVenta::class, fn (Container $container): QuitarItemCarritoVenta => new QuitarItemCarritoVenta($container->get(CarritoVentaRepository::class)));

        $container->bind(VaciarCarritoVenta::class, fn (Container $container): VaciarCarritoVenta => new VaciarCarritoVenta($container->get(CarritoVentaRepository::class)));

        $container->bind(ConfirmarVenta::class, fn (Container $container): ConfirmarVenta => new ConfirmarVenta(
                $container->get(VentaRepository::class),
                $container->get(CarritoVentaRepository::class),
                $container->get(FormularioVentaRepository::class),
                $container->get(ConfiguracionVentaRepository::class)
            ));
    }
}
