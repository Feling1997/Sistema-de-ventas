<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Infrastructure;

use PDO;
use Ventas\Core\Infrastructure\Config\DatabaseConfig;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Core\Infrastructure\Persistence\Mysql\PdoConnectionFactory;
use Ventas\ListasPrecios\Application\ActualizarListaPrecio;
use Ventas\ListasPrecios\Application\BuscarListaPrecioPorId;
use Ventas\ListasPrecios\Application\CrearListaPrecio;
use Ventas\ListasPrecios\Application\EliminarListaPrecio;
use Ventas\ListasPrecios\Application\EsListaBase;
use Ventas\ListasPrecios\Application\EsListaCosto;
use Ventas\ListasPrecios\Application\EsListaPublico;
use Ventas\ListasPrecios\Application\GuardarPrecioProducto;
use Ventas\ListasPrecios\Application\GuardarPrecioProductoOrigen;
use Ventas\ListasPrecios\Application\InicializarEsquemaListasPrecios;
use Ventas\ListasPrecios\Application\ListarListasPrecios;
use Ventas\ListasPrecios\Application\ListarProductosParaExportar;
use Ventas\ListasPrecios\Application\ObtenerListaPrecioPredeterminada;
use Ventas\ListasPrecios\Application\ObtenerHistorialPrecios;
use Ventas\ListasPrecios\Application\ObtenerPrecioProducto;
use Ventas\ListasPrecios\Application\ObtenerPrecioProductoCargado;
use Ventas\ListasPrecios\Application\ObtenerPrecioProductoCompleto;
use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;

final class RegistroListasPrecios
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

        $container->singleton(ListaPrecioRepository::class, fn (Container $container): ListaPrecioRepository => new MySQLListaPrecioRepository($container->get(PDO::class)));

        $container->bind(ListarListasPrecios::class, fn (Container $container): ListarListasPrecios => new ListarListasPrecios($container->get(ListaPrecioRepository::class)));

        $container->bind(BuscarListaPrecioPorId::class, fn (Container $container): BuscarListaPrecioPorId => new BuscarListaPrecioPorId($container->get(ListaPrecioRepository::class)));

        $container->bind(ObtenerListaPrecioPredeterminada::class, fn (Container $container): ObtenerListaPrecioPredeterminada => new ObtenerListaPrecioPredeterminada($container->get(ListaPrecioRepository::class)));

        $container->bind(EsListaCosto::class, fn (): EsListaCosto => new EsListaCosto());

        $container->bind(EsListaPublico::class, fn (): EsListaPublico => new EsListaPublico());

        $container->bind(EsListaBase::class, fn (Container $container): EsListaBase => new EsListaBase($container->get(ListaPrecioRepository::class)));

        $container->bind(CrearListaPrecio::class, fn (Container $container): CrearListaPrecio => new CrearListaPrecio($container->get(ListaPrecioRepository::class)));

        $container->bind(ActualizarListaPrecio::class, fn (Container $container): ActualizarListaPrecio => new ActualizarListaPrecio($container->get(ListaPrecioRepository::class)));

        $container->bind(EliminarListaPrecio::class, fn (Container $container): EliminarListaPrecio => new EliminarListaPrecio($container->get(ListaPrecioRepository::class)));

        $container->bind(ObtenerPrecioProducto::class, fn (Container $container): ObtenerPrecioProducto => new ObtenerPrecioProducto($container->get(ListaPrecioRepository::class)));

        $container->bind(ObtenerPrecioProductoCargado::class, fn (Container $container): ObtenerPrecioProductoCargado => new ObtenerPrecioProductoCargado($container->get(ListaPrecioRepository::class)));

        $container->bind(ObtenerPrecioProductoCompleto::class, fn (Container $container): ObtenerPrecioProductoCompleto => new ObtenerPrecioProductoCompleto($container->get(ListaPrecioRepository::class)));

        $container->bind(GuardarPrecioProducto::class, fn (Container $container): GuardarPrecioProducto => new GuardarPrecioProducto($container->get(ListaPrecioRepository::class)));

        $container->bind(GuardarPrecioProductoOrigen::class, fn (Container $container): GuardarPrecioProductoOrigen => new GuardarPrecioProductoOrigen($container->get(ListaPrecioRepository::class)));

        $container->bind(ListarProductosParaExportar::class, fn (Container $container): ListarProductosParaExportar => new ListarProductosParaExportar($container->get(ListaPrecioRepository::class)));

        $container->bind(ObtenerHistorialPrecios::class, fn (Container $container): ObtenerHistorialPrecios => new ObtenerHistorialPrecios($container->get(ListaPrecioRepository::class)));

        $container->bind(InicializarEsquemaListasPrecios::class, fn (Container $container): InicializarEsquemaListasPrecios => new InicializarEsquemaListasPrecios($container->get(ListaPrecioRepository::class)));
    }
}
