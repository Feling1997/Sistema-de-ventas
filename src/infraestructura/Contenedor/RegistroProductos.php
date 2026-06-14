<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Contenedor;

use PDO;
use Ventas\Aplicacion\Productos\CasosUso\BuscarProductoFormularioPorId;
use Ventas\Aplicacion\Productos\CasosUso\BuscarProductoPorId;
use Ventas\Aplicacion\Productos\CasosUso\BuscarProductoPorCodigoOPLU;
use Ventas\Aplicacion\Productos\CasosUso\BuscarProductosParaVenta;
use Ventas\Aplicacion\Productos\CasosUso\EliminarProductosNoVendidos;
use Ventas\Aplicacion\Productos\CasosUso\ListarProductos;
use Ventas\Aplicacion\Productos\CasosUso\ListarProductosVista;
use Ventas\Aplicacion\Productos\CasosUso\ListarProductosPorStock;
use Ventas\Aplicacion\Productos\CasosUso\ObtenerProductoParaVenta;
use Ventas\Aplicacion\Productos\CasosUso\ObtenerPreciosProducto;
use Ventas\Dominio\Productos\Repositorios\ProductoRepository;
use Ventas\Infraestructura\Configuracion\DatabaseConfig;
use Ventas\Infraestructura\Persistencia\MySQL\PdoConnectionFactory;
use Ventas\Infraestructura\Persistencia\MySQL\Productos\MySQLProductoRepository;

final class RegistroProductos
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

        $container->singleton(ProductoRepository::class, fn (Container $container): ProductoRepository => new MySQLProductoRepository($container->get(PDO::class)));

        $container->bind(ListarProductos::class, fn (Container $container): ListarProductos => new ListarProductos($container->get(ProductoRepository::class)));

        $container->bind(BuscarProductoPorId::class, fn (Container $container): BuscarProductoPorId => new BuscarProductoPorId($container->get(ProductoRepository::class)));

        $container->bind(BuscarProductoFormularioPorId::class, fn (Container $container): BuscarProductoFormularioPorId => new BuscarProductoFormularioPorId($container->get(ProductoRepository::class)));

        $container->bind(ListarProductosVista::class, fn (Container $container): ListarProductosVista => new ListarProductosVista($container->get(ProductoRepository::class)));

        $container->bind(ListarProductosPorStock::class, fn (Container $container): ListarProductosPorStock => new ListarProductosPorStock($container->get(ProductoRepository::class)));

        $container->bind(BuscarProductosParaVenta::class, fn (Container $container): BuscarProductosParaVenta => new BuscarProductosParaVenta($container->get(ProductoRepository::class)));

        $container->bind(ObtenerProductoParaVenta::class, fn (Container $container): ObtenerProductoParaVenta => new ObtenerProductoParaVenta($container->get(ProductoRepository::class)));

        $container->bind(ObtenerPreciosProducto::class, fn (Container $container): ObtenerPreciosProducto => new ObtenerPreciosProducto($container->get(ProductoRepository::class)));

        $container->bind(BuscarProductoPorCodigoOPLU::class, fn (Container $container): BuscarProductoPorCodigoOPLU => new BuscarProductoPorCodigoOPLU($container->get(ProductoRepository::class)));

        $container->bind(EliminarProductosNoVendidos::class, fn (Container $container): EliminarProductosNoVendidos => new EliminarProductosNoVendidos($container->get(ProductoRepository::class)));
    }
}
