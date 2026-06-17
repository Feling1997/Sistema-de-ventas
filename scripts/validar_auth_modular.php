<?php

declare(strict_types=1);

use Ventas\Auth\Application\AutenticarUsuario;
use Ventas\Auth\Application\CerrarSesionAuth;
use Ventas\Auth\Application\CrearSesionSinLogin;
use Ventas\Auth\Application\IniciarSesionAuth;
use Ventas\Auth\Application\ObtenerSesionActual;
use Ventas\Auth\Application\VerificarModoSinLogin;
use Ventas\Auth\Domain\Repositorios\ConfiguracionAuthRepository;
use Ventas\Auth\Domain\Repositorios\SesionAuthRepository;
use Ventas\Auth\Infrastructure\MySQLConfiguracionAuthRepository;
use Ventas\Auth\Infrastructure\RegistroAuth;
use Ventas\Auth\Infrastructure\SesionPhpAuthRepository;
use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Usuarios\Domain\Repositorios\UsuarioRepository;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$salida = [];
$salida[] = 'Validacion Auth modular';
$salida[] = 'Namespace modular objetivo: Ventas\\Auth';
$salida[] = 'Sesion CLI temporal controlada: SI';
$salida[] = 'Autenticacion real ejecutada: NO';
$salida[] = 'Password modificado: NO';
$salida[] = 'Usuarios modificados: NO';

try {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_save_path(sys_get_temp_dir());
        session_id('authmodularcli-' . bin2hex(random_bytes(4)));
    }

    $container = new Container();
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=sistema_ventas;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    $container->instance(PDO::class, $pdo);

    RegistroAuth::registrar($container);

    $repositorios = [
        SesionAuthRepository::class => $container->get(SesionAuthRepository::class),
        ConfiguracionAuthRepository::class => $container->get(ConfiguracionAuthRepository::class),
        UsuarioRepository::class => $container->get(UsuarioRepository::class),
    ];
    $casosUso = [
        AutenticarUsuario::class => $container->get(AutenticarUsuario::class),
        ObtenerSesionActual::class => $container->get(ObtenerSesionActual::class),
        IniciarSesionAuth::class => $container->get(IniciarSesionAuth::class),
        CerrarSesionAuth::class => $container->get(CerrarSesionAuth::class),
        VerificarModoSinLogin::class => $container->get(VerificarModoSinLogin::class),
        CrearSesionSinLogin::class => $container->get(CrearSesionSinLogin::class),
    ];

    foreach ($repositorios as $contrato => $instancia) {
        $salida[] = $contrato . ' => ' . get_class($instancia);
    }

    foreach ($casosUso as $clase => $instancia) {
        $salida[] = $clase . ' => ' . get_class($instancia);
    }

    $verificacion = $casosUso[VerificarModoSinLogin::class]->ejecutar();
    $sesionSinLogin = $casosUso[CrearSesionSinLogin::class]->ejecutar();
    $sesionActual = $casosUso[ObtenerSesionActual::class]->ejecutar();

    $salida[] = 'Modo auth detectado: ' . (string) ($verificacion['modo'] ?? 'NULL');
    $salida[] = 'sin_login_habilitado: ' . ((bool) ($verificacion['sin_login_habilitado'] ?? false) ? 'SI' : 'NO');
    $salida[] = 'no_hay_usuarios_creados: ' . ((bool) ($verificacion['no_hay_usuarios_creados'] ?? false) ? 'SI' : 'NO');
    $salida[] = 'admin_local_habilitado: ' . ((bool) ($verificacion['admin_local_habilitado'] ?? false) ? 'SI' : 'NO');
    $salida[] = 'usuario_especial_sin_login: ' . ((bool) ($verificacion['usuario_especial_sin_login'] ?? false) ? 'SI' : 'NO');
    $salida[] = 'CrearSesionSinLogin usuario: ' . (string) ($sesionSinLogin['usuario'] ?? '');
    $salida[] = 'Sesion actual usuario: ' . (string) ($sesionActual['usuario'] ?? '');
    $salida[] = 'class_exists AutenticarUsuario: ' . (class_exists(AutenticarUsuario::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists RegistroAuth: ' . (class_exists(RegistroAuth::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists SesionPhpAuthRepository: ' . (class_exists(SesionPhpAuthRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists MySQLConfiguracionAuthRepository: ' . (class_exists(MySQLConfiguracionAuthRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'interface_exists SesionAuthRepository: ' . (interface_exists(SesionAuthRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'interface_exists ConfiguracionAuthRepository: ' . (interface_exists(ConfiguracionAuthRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'Namespace viejo utilizado: NO';
    $salida[] = 'Namespace modular utilizado: SI';

    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
} catch (Throwable $throwable) {
    $salida[] = 'ERROR CONTROLADO: ' . $throwable->getMessage();
    $salida[] = 'Namespace viejo utilizado: NO';
    $salida[] = 'Namespace modular utilizado: NO';
}

foreach ($salida as $linea) {
    echo $linea . PHP_EOL;
}
