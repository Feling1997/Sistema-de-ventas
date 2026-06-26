<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Infrastructure;

use PDO;
use Ventas\CuentasCorrientes\Application\BuscarCuentaCorrientePorId;
use Ventas\CuentasCorrientes\Application\BuscarReciboCuentaCorriente;
use Ventas\CuentasCorrientes\Application\CancelarCuentaCorriente;
use Ventas\CuentasCorrientes\Application\ListarCuotasPendientes;
use Ventas\CuentasCorrientes\Application\ListarCuotasPendientesDetalle;
use Ventas\CuentasCorrientes\Application\ListarRecibosCuentaCorriente;
use Ventas\CuentasCorrientes\Application\ListarSaldosFavorClientes;
use Ventas\CuentasCorrientes\Application\MarcarAlertasLeidas;
use Ventas\CuentasCorrientes\Application\MarcarCuotaPagada;
use Ventas\CuentasCorrientes\Application\ObtenerCantidadVencidasNoLeidas;
use Ventas\CuentasCorrientes\Application\ObtenerResumenGeneralCuentaCorriente;
use Ventas\CuentasCorrientes\Application\RegistrarAnticipoCuentaCorriente;
use Ventas\CuentasCorrientes\Application\RegistrarPagoCuentaCorriente;
use Ventas\CuentasCorrientes\Domain\Repositorios\AplicarSaldoFavorRepository;
use Ventas\CuentasCorrientes\Domain\Repositorios\CuentaCorrienteRepository;
use Ventas\CuentasCorrientes\Domain\Repositorios\CrearCuentaCorrienteVentaRepository;
use Ventas\CuentasCorrientes\Domain\Repositorios\SaldoFavorClienteRepository;
use Ventas\Core\Infrastructure\Config\DatabaseConfig;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Core\Infrastructure\Persistence\Mysql\PdoConnectionFactory;

final class RegistroCuentasCorrientes
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

        $container->singleton(CuentaCorrienteRepository::class, fn (Container $container): CuentaCorrienteRepository => new MySQLCuentaCorrienteRepository($container->get(PDO::class)));

        $container->singleton(SaldoFavorClienteRepository::class, fn (Container $container): SaldoFavorClienteRepository => new MySQLSaldoFavorClienteRepository($container->get(PDO::class)));

        $container->singleton(CrearCuentaCorrienteVentaRepository::class, fn (Container $container): CrearCuentaCorrienteVentaRepository => new MySQLCuentaCorrienteVentaRepository($container->get(PDO::class)));

        $container->singleton(AplicarSaldoFavorRepository::class, fn (Container $container): AplicarSaldoFavorRepository => new MySQLAplicarSaldoFavorRepository($container->get(PDO::class), $container->get(SaldoFavorClienteRepository::class)));

        $container->bind(ListarCuotasPendientesDetalle::class, fn (Container $container): ListarCuotasPendientesDetalle => new ListarCuotasPendientesDetalle($container->get(CuentaCorrienteRepository::class)));

        $container->bind(ObtenerResumenGeneralCuentaCorriente::class, fn (Container $container): ObtenerResumenGeneralCuentaCorriente => new ObtenerResumenGeneralCuentaCorriente($container->get(CuentaCorrienteRepository::class)));

        $container->bind(ListarRecibosCuentaCorriente::class, fn (Container $container): ListarRecibosCuentaCorriente => new ListarRecibosCuentaCorriente($container->get(CuentaCorrienteRepository::class)));

        $container->bind(ListarSaldosFavorClientes::class, fn (Container $container): ListarSaldosFavorClientes => new ListarSaldosFavorClientes($container->get(CuentaCorrienteRepository::class)));

        $container->bind(BuscarCuentaCorrientePorId::class, fn (Container $container): BuscarCuentaCorrientePorId => new BuscarCuentaCorrientePorId($container->get(CuentaCorrienteRepository::class)));

        $container->bind(ListarCuotasPendientes::class, fn (Container $container): ListarCuotasPendientes => new ListarCuotasPendientes($container->get(CuentaCorrienteRepository::class)));

        $container->bind(BuscarReciboCuentaCorriente::class, fn (Container $container): BuscarReciboCuentaCorriente => new BuscarReciboCuentaCorriente($container->get(CuentaCorrienteRepository::class)));

        $container->bind(ObtenerCantidadVencidasNoLeidas::class, fn (Container $container): ObtenerCantidadVencidasNoLeidas => new ObtenerCantidadVencidasNoLeidas($container->get(CuentaCorrienteRepository::class)));

        $container->bind(MarcarCuotaPagada::class, fn (Container $container): MarcarCuotaPagada => new MarcarCuotaPagada($container->get(CuentaCorrienteRepository::class)));

        $container->bind(CancelarCuentaCorriente::class, fn (Container $container): CancelarCuentaCorriente => new CancelarCuentaCorriente($container->get(CuentaCorrienteRepository::class)));

        $container->bind(MarcarAlertasLeidas::class, fn (Container $container): MarcarAlertasLeidas => new MarcarAlertasLeidas($container->get(CuentaCorrienteRepository::class)));

        $container->bind(RegistrarAnticipoCuentaCorriente::class, fn (Container $container): RegistrarAnticipoCuentaCorriente => new RegistrarAnticipoCuentaCorriente($container->get(CuentaCorrienteRepository::class)));

        $container->bind(RegistrarPagoCuentaCorriente::class, fn (Container $container): RegistrarPagoCuentaCorriente => new RegistrarPagoCuentaCorriente($container->get(CuentaCorrienteRepository::class)));
    }
}
