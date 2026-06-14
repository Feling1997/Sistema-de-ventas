<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Contenedor;

use Ventas\Aplicacion\Impresoras\CasosUso\ListarImpresoras;
use Ventas\Dominio\Impresoras\Repositorios\ImpresoraRepository;
use Ventas\Infraestructura\Impresoras\PowerShellImpresoraRepository;

final class RegistroImpresoras
{
    public static function registrar(Container $container): void
    {
        $container->singleton(ImpresoraRepository::class, fn (): ImpresoraRepository => new PowerShellImpresoraRepository());

        $container->bind(ListarImpresoras::class, fn (Container $container): ListarImpresoras => new ListarImpresoras($container->get(ImpresoraRepository::class)));
    }
}
