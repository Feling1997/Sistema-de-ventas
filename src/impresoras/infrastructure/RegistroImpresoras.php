<?php

declare(strict_types=1);

namespace Ventas\Impresoras\Infrastructure;

use Ventas\Impresoras\Application\ListarImpresoras;
use Ventas\Impresoras\Domain\Repositorios\ImpresoraRepository;
use Ventas\Core\Infrastructure\Container\Container;

final class RegistroImpresoras
{
    public static function registrar(Container $container): void
    {
        $container->singleton(ImpresoraRepository::class, fn (): ImpresoraRepository => new PowerShellImpresoraRepository());

        $container->bind(ListarImpresoras::class, fn (Container $container): ListarImpresoras => new ListarImpresoras($container->get(ImpresoraRepository::class)));
    }
}
