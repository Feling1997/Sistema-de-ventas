<?php

declare(strict_types=1);

namespace Ventas\Auth\Infrastructure;

use PDO;
use Ventas\Auth\Application\AutenticarUsuario;
use Ventas\Auth\Application\CerrarSesionAuth;
use Ventas\Auth\Application\CrearSesionSinLogin;
use Ventas\Auth\Application\IniciarSesionAuth;
use Ventas\Auth\Application\ObtenerSesionActual;
use Ventas\Auth\Application\VerificarModoSinLogin;
use Ventas\Auth\Domain\Repositorios\ConfiguracionAuthRepository;
use Ventas\Auth\Domain\Repositorios\SesionAuthRepository;
use Ventas\Infraestructura\Configuracion\DatabaseConfig;
use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Infraestructura\Persistencia\MySQL\PdoConnectionFactory;
use Ventas\Usuarios\Domain\Repositorios\UsuarioRepository;
use Ventas\Usuarios\Infrastructure\MySQLUsuarioRepository;

final class RegistroAuth
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

        if (!$container->has(UsuarioRepository::class)) {
            $container->singleton(UsuarioRepository::class, fn (Container $container): UsuarioRepository => new MySQLUsuarioRepository($container->get(PDO::class)));
        }

        $container->singleton(SesionAuthRepository::class, fn (): SesionAuthRepository => new SesionPhpAuthRepository());

        $container->singleton(ConfiguracionAuthRepository::class, fn (Container $container): ConfiguracionAuthRepository => new MySQLConfiguracionAuthRepository($container->get(PDO::class), dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'almacenamiento' . DIRECTORY_SEPARATOR . 'configuracion_sistema.json'));

        $container->bind(AutenticarUsuario::class, fn (Container $container): AutenticarUsuario => new AutenticarUsuario($container->get(UsuarioRepository::class), $container->get(SesionAuthRepository::class)));

        $container->bind(ObtenerSesionActual::class, fn (Container $container): ObtenerSesionActual => new ObtenerSesionActual($container->get(SesionAuthRepository::class)));

        $container->bind(IniciarSesionAuth::class, fn (Container $container): IniciarSesionAuth => new IniciarSesionAuth($container->get(SesionAuthRepository::class)));

        $container->bind(CerrarSesionAuth::class, fn (Container $container): CerrarSesionAuth => new CerrarSesionAuth($container->get(SesionAuthRepository::class)));

        $container->bind(VerificarModoSinLogin::class, fn (Container $container): VerificarModoSinLogin => new VerificarModoSinLogin($container->get(ConfiguracionAuthRepository::class)));

        $container->bind(CrearSesionSinLogin::class, fn (Container $container): CrearSesionSinLogin => new CrearSesionSinLogin($container->get(SesionAuthRepository::class)));
    }
}
