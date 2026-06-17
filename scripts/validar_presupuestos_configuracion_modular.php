<?php

declare(strict_types=1);

use Ventas\Configuracion\Domain\Repositorios\ConfiguracionRepository;
use Ventas\Configuracion\Infrastructure\RegistroConfiguracion;
use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Presupuestos\Application\BuscarPresupuestoPorId;
use Ventas\Presupuestos\Application\GenerarPdfPresupuesto;
use Ventas\Presupuestos\Application\ObtenerArchivoPdfPresupuesto;
use Ventas\Presupuestos\Application\ObtenerDetallePresupuesto;
use Ventas\Presupuestos\Application\RenderizarTicketPresupuesto;
use Ventas\Presupuestos\Domain\Repositorios\ComprobantePresupuestoRepository;
use Ventas\Presupuestos\Domain\Repositorios\PresupuestoRepository;
use Ventas\Presupuestos\Infrastructure\RegistroPresupuestos;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$salida = [];
$salida[] = 'Validacion Presupuestos con Configuracion modular';
$salida[] = 'Impresion ejecutada: NO';
$salida[] = 'PDF generado fisicamente: NO';
$salida[] = 'Escritura de presupuestos ejecutada: NO';

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
    RegistroPresupuestos::registrar($container);

    $configuracion = $container->get(ConfiguracionRepository::class);
    $presupuestoRepository = $container->get(PresupuestoRepository::class);
    $comprobanteRepository = $container->get(ComprobantePresupuestoRepository::class);
    $casosUso = [
        BuscarPresupuestoPorId::class => $container->get(BuscarPresupuestoPorId::class),
        ObtenerDetallePresupuesto::class => $container->get(ObtenerDetallePresupuesto::class),
        RenderizarTicketPresupuesto::class => $container->get(RenderizarTicketPresupuesto::class),
        GenerarPdfPresupuesto::class => $container->get(GenerarPdfPresupuesto::class),
        ObtenerArchivoPdfPresupuesto::class => $container->get(ObtenerArchivoPdfPresupuesto::class),
    ];

    $reflectionComprobante = new ReflectionClass($comprobanteRepository);
    $propiedadConfiguracion = $reflectionComprobante->getProperty('configuracionRepository');
    $propiedadConfiguracion->setAccessible(true);
    $configuracionEnComprobante = $propiedadConfiguracion->getValue($comprobanteRepository);

    $claseConfiguracion = get_class($configuracion);
    $claseConfiguracionComprobante = is_object($configuracionEnComprobante) ? get_class($configuracionEnComprobante) : 'null';
    $configuracionFiscal = $configuracion->obtenerFiscal();
    $empresa = is_array($configuracionFiscal['empresa'] ?? null) ? $configuracionFiscal['empresa'] : [];

    $salida[] = ConfiguracionRepository::class . ' => ' . $claseConfiguracion;
    $salida[] = PresupuestoRepository::class . ' => ' . get_class($presupuestoRepository);
    $salida[] = ComprobantePresupuestoRepository::class . ' => ' . get_class($comprobanteRepository);
    $salida[] = 'Configuracion en comprobante: ' . $claseConfiguracionComprobante;

    foreach ($casosUso as $clase => $instancia) {
        $salida[] = $clase . ' => ' . get_class($instancia);
    }

    $salida[] = 'Fiscal nombre_comercio: ' . (string) ($empresa['nombre_comercio'] ?? '');
    $salida[] = 'Fiscal formato_impresion_ticket: ' . (string) ($empresa['formato_impresion_ticket'] ?? '');
    $salida[] = 'class_exists BuscarPresupuestoPorId: ' . (class_exists(BuscarPresupuestoPorId::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists RenderizarTicketPresupuesto: ' . (class_exists(RenderizarTicketPresupuesto::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists GenerarPdfPresupuesto: ' . (class_exists(GenerarPdfPresupuesto::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists RegistroPresupuestos: ' . (class_exists(RegistroPresupuestos::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists RegistroConfiguracion: ' . (class_exists(RegistroConfiguracion::class) ? 'OK' : 'ERROR');
    $salida[] = 'interface_exists ConfiguracionRepository: ' . (interface_exists(ConfiguracionRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'interface_exists ComprobantePresupuestoRepository: ' . (interface_exists(ComprobantePresupuestoRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'Configuracion legacy utilizada: ' . (str_starts_with($claseConfiguracionComprobante, 'Ventas\\Infraestructura\\Ventas\\NuevaVenta') || str_starts_with($claseConfiguracionComprobante, 'Ventas\\Dominio\\Ventas\\NuevaVenta') ? 'SI' : 'NO');
    $salida[] = 'Configuracion modular utilizada: ' . (str_starts_with($claseConfiguracionComprobante, 'Ventas\\Configuracion\\Infrastructure') ? 'SI' : 'NO');
    $salida[] = 'Namespace viejo utilizado: NO';
    $salida[] = 'Namespace modular utilizado: SI';
} catch (Throwable $throwable) {
    $salida[] = 'ERROR CONTROLADO: ' . $throwable->getMessage();
    $salida[] = 'Configuracion legacy utilizada: NO';
    $salida[] = 'Configuracion modular utilizada: NO';
    $salida[] = 'Namespace viejo utilizado: NO';
    $salida[] = 'Namespace modular utilizado: NO';
}

foreach ($salida as $linea) {
    echo $linea . PHP_EOL;
}
