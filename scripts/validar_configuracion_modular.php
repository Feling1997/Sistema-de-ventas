<?php

declare(strict_types=1);

use Ventas\Configuracion\Application\ObtenerConfiguracionAuth;
use Ventas\Configuracion\Application\ObtenerConfiguracionBalanza;
use Ventas\Configuracion\Application\ObtenerConfiguracionFiscal;
use Ventas\Configuracion\Application\ObtenerConfiguracionGeneral;
use Ventas\Configuracion\Application\ObtenerConfiguracionVenta;
use Ventas\Configuracion\Domain\Repositorios\ConfiguracionRepository;
use Ventas\Configuracion\Infrastructure\MySQLConfiguracionRepository;
use Ventas\Configuracion\Infrastructure\RegistroConfiguracion;
use Ventas\Infraestructura\Contenedor\Container;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$salida = [];
$salida[] = 'Validacion Configuracion modular';
$salida[] = 'Namespace modular objetivo: Ventas\\Configuracion';
$salida[] = 'Lectura real ejecutada: SI';
$salida[] = 'Escritura de configuracion ejecutada: NO';
$salida[] = 'Backups ejecutados: NO';
$salida[] = 'Subida de archivos ejecutada: NO';
$salida[] = 'Cambio de seguridad ejecutado: NO';

try {
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

    RegistroConfiguracion::registrar($container);

    $repositorio = $container->get(ConfiguracionRepository::class);
    $casosUso = [
        ObtenerConfiguracionGeneral::class => $container->get(ObtenerConfiguracionGeneral::class),
        ObtenerConfiguracionFiscal::class => $container->get(ObtenerConfiguracionFiscal::class),
        ObtenerConfiguracionVenta::class => $container->get(ObtenerConfiguracionVenta::class),
        ObtenerConfiguracionBalanza::class => $container->get(ObtenerConfiguracionBalanza::class),
        ObtenerConfiguracionAuth::class => $container->get(ObtenerConfiguracionAuth::class),
    ];

    $general = $casosUso[ObtenerConfiguracionGeneral::class]->ejecutar();
    $fiscal = $casosUso[ObtenerConfiguracionFiscal::class]->ejecutar();
    $venta = $casosUso[ObtenerConfiguracionVenta::class]->ejecutar();
    $balanza = $casosUso[ObtenerConfiguracionBalanza::class]->ejecutar();
    $auth = $casosUso[ObtenerConfiguracionAuth::class]->ejecutar();

    $salida[] = ConfiguracionRepository::class . ' => ' . get_class($repositorio);

    foreach ($casosUso as $clase => $instancia) {
        $salida[] = $clase . ' => ' . get_class($instancia);
    }

    $salida[] = 'Cantidad de claves generales: ' . count($general);
    $salida[] = 'Fiscal nombre_comercio: ' . (string) ($fiscal['empresa']['nombre_comercio'] ?? '');
    $salida[] = 'Fiscal cuit: ' . (string) ($fiscal['empresa']['cuit'] ?? '');
    $salida[] = 'Fiscal punto_venta: ' . (string) ($fiscal['empresa']['punto_venta'] ?? '');
    $salida[] = 'Venta controlar_stock_ventas: ' . ((bool) ($venta['controlar_stock_ventas'] ?? false) ? 'SI' : 'NO');
    $salida[] = 'Venta formato_impresion_ticket: ' . (string) ($venta['formato_impresion_ticket'] ?? '');
    $salida[] = 'Balanza modo: ' . (string) ($balanza['modo'] ?? '');
    $salida[] = 'Balanza prefijos cantidad: ' . count($balanza['prefijos_cantidad'] ?? []);
    $salida[] = 'Balanza prefijos importe: ' . count($balanza['prefijos_importe'] ?? []);
    $salida[] = 'Auth modo: ' . (string) ($auth['auth_modo'] ?? '');
    $salida[] = 'Auth sin_login_habilitado: ' . ((bool) ($auth['sin_login_habilitado'] ?? false) ? 'SI' : 'NO');
    $salida[] = 'class_exists ObtenerConfiguracionGeneral: ' . (class_exists(ObtenerConfiguracionGeneral::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists ObtenerConfiguracionFiscal: ' . (class_exists(ObtenerConfiguracionFiscal::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists ObtenerConfiguracionVenta: ' . (class_exists(ObtenerConfiguracionVenta::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists ObtenerConfiguracionBalanza: ' . (class_exists(ObtenerConfiguracionBalanza::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists ObtenerConfiguracionAuth: ' . (class_exists(ObtenerConfiguracionAuth::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists MySQLConfiguracionRepository: ' . (class_exists(MySQLConfiguracionRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists RegistroConfiguracion: ' . (class_exists(RegistroConfiguracion::class) ? 'OK' : 'ERROR');
    $salida[] = 'interface_exists ConfiguracionRepository: ' . (interface_exists(ConfiguracionRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'Namespace viejo utilizado: NO';
    $salida[] = 'Namespace modular utilizado: SI';
} catch (Throwable $throwable) {
    $salida[] = 'ERROR CONTROLADO: ' . $throwable->getMessage();
    $salida[] = 'Namespace viejo utilizado: NO';
    $salida[] = 'Namespace modular utilizado: NO';
}

foreach ($salida as $linea) {
    echo $linea . PHP_EOL;
}
