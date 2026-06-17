<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Presupuestos\Application\BuscarPresupuestoPorId;
use Ventas\Presupuestos\Application\GenerarPdfPresupuesto;
use Ventas\Presupuestos\Application\ObtenerArchivoPdfPresupuesto;
use Ventas\Presupuestos\Application\ObtenerDetallePresupuesto;
use Ventas\Presupuestos\Application\RenderizarTicketPresupuesto;
use Ventas\Presupuestos\Domain\Repositorios\ComprobantePresupuestoRepository;
use Ventas\Presupuestos\Domain\Repositorios\PresupuestoRepository;
use Ventas\Presupuestos\Infrastructure\RegistroPresupuestos;

$ok = false;
$idBusqueda = 0;
$resultadoBusqueda = 'null';
$cantidadDetalle = 0;
$claseBuscar = '';
$claseDetalle = '';
$claseRenderTicket = '';
$claseGenerarPdf = '';
$claseObtenerPdf = '';
$claseRepositorio = '';
$claseComprobante = '';
$claseRepositorioBuscar = '';
$claseRepositorioDetalle = '';
$claseEntidad = '';
$namespaceViejoUtilizado = false;
$namespaceModularUtilizado = false;
$mensaje = '';

try {
    $container = new Container();
    $pdo = new \PDO(
        'mysql:host=127.0.0.1;dbname=sistema_ventas;charset=utf8mb4',
        'root',
        '',
        [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]
    );

    $container->instance(\PDO::class, $pdo);
    RegistroPresupuestos::registrar($container);

    $statement = $pdo->prepare('SELECT id FROM presupuestos ORDER BY id ASC LIMIT 1');
    $statement->execute();
    $filaId = $statement->fetch();

    if (is_array($filaId)) {
        $idBusqueda = (int) ($filaId['id'] ?? 0);
    }

    $buscarPresupuestoPorId = $container->get(BuscarPresupuestoPorId::class);
    $obtenerDetallePresupuesto = $container->get(ObtenerDetallePresupuesto::class);
    $renderizarTicketPresupuesto = $container->get(RenderizarTicketPresupuesto::class);
    $generarPdfPresupuesto = $container->get(GenerarPdfPresupuesto::class);
    $obtenerArchivoPdfPresupuesto = $container->get(ObtenerArchivoPdfPresupuesto::class);
    $repositorio = $container->get(PresupuestoRepository::class);
    $comprobante = $container->get(ComprobantePresupuestoRepository::class);

    $presupuesto = $idBusqueda > 0 ? $buscarPresupuestoPorId->ejecutar($idBusqueda) : null;
    $detalle = $idBusqueda > 0 ? $obtenerDetallePresupuesto->ejecutar($idBusqueda) : [];

    $refBuscar = new \ReflectionClass($buscarPresupuestoPorId);
    $propBuscar = $refBuscar->getProperty('presupuestoRepository');
    $propBuscar->setAccessible(true);
    $repoBuscar = $propBuscar->getValue($buscarPresupuestoPorId);

    $refDetalle = new \ReflectionClass($obtenerDetallePresupuesto);
    $propDetalle = $refDetalle->getProperty('presupuestoRepository');
    $propDetalle->setAccessible(true);
    $repoDetalle = $propDetalle->getValue($obtenerDetallePresupuesto);

    $resultadoBusqueda = $presupuesto === null ? 'null' : 'entidad';
    $cantidadDetalle = count($detalle);
    $claseBuscar = get_class($buscarPresupuestoPorId);
    $claseDetalle = get_class($obtenerDetallePresupuesto);
    $claseRenderTicket = get_class($renderizarTicketPresupuesto);
    $claseGenerarPdf = get_class($generarPdfPresupuesto);
    $claseObtenerPdf = get_class($obtenerArchivoPdfPresupuesto);
    $claseRepositorio = get_class($repositorio);
    $claseComprobante = get_class($comprobante);
    $claseRepositorioBuscar = get_class($repoBuscar);
    $claseRepositorioDetalle = get_class($repoDetalle);
    $claseEntidad = $presupuesto === null ? 'null' : get_class($presupuesto);
    $namespaceViejoUtilizado = str_starts_with($claseRepositorio, 'Ventas\\Infraestructura\\Persistencia\\MySQL\\Presupuestos')
        || str_starts_with($claseComprobante, 'Ventas\\Infraestructura\\Presupuestos')
        || str_starts_with($claseBuscar, 'Ventas\\Aplicacion\\Presupuestos')
        || str_starts_with($claseEntidad, 'Ventas\\Dominio\\Presupuestos');
    $namespaceModularUtilizado = str_starts_with($claseRepositorio, 'Ventas\\Presupuestos\\Infrastructure')
        && str_starts_with($claseComprobante, 'Ventas\\Presupuestos\\Infrastructure')
        && str_starts_with($claseBuscar, 'Ventas\\Presupuestos\\Application')
        && ($claseEntidad === 'null' || str_starts_with($claseEntidad, 'Ventas\\Presupuestos\\Domain'));
    $ok = true;
} catch (\Throwable $e) {
    $mensaje = $e->getMessage();
}

echo 'Validacion modulo Presupuestos modular' . PHP_EOL;
echo 'OK: ' . ($ok ? 'SI' : 'NO') . PHP_EOL;
echo 'buscarPorId(' . $idBusqueda . '): ' . $resultadoBusqueda . PHP_EOL;
echo 'Cantidad detalle: ' . $cantidadDetalle . PHP_EOL;
echo 'Clase BuscarPresupuestoPorId: ' . $claseBuscar . PHP_EOL;
echo 'Clase ObtenerDetallePresupuesto: ' . $claseDetalle . PHP_EOL;
echo 'Clase RenderizarTicketPresupuesto: ' . $claseRenderTicket . PHP_EOL;
echo 'Clase GenerarPdfPresupuesto: ' . $claseGenerarPdf . PHP_EOL;
echo 'Clase ObtenerArchivoPdfPresupuesto: ' . $claseObtenerPdf . PHP_EOL;
echo 'Repositorio resuelto: ' . $claseRepositorio . PHP_EOL;
echo 'Comprobante resuelto: ' . $claseComprobante . PHP_EOL;
echo 'Repositorio en Buscar: ' . $claseRepositorioBuscar . PHP_EOL;
echo 'Repositorio en Detalle: ' . $claseRepositorioDetalle . PHP_EOL;
echo 'Entidad devuelta: ' . $claseEntidad . PHP_EOL;
echo 'Namespace viejo utilizado: ' . ($namespaceViejoUtilizado ? 'SI' : 'NO') . PHP_EOL;
echo 'Namespace modular utilizado: ' . ($namespaceModularUtilizado ? 'SI' : 'NO') . PHP_EOL;
echo 'PDF generado fisicamente: NO' . PHP_EOL;
echo 'Impresion ejecutada: NO' . PHP_EOL;

if ($mensaje !== '') {
    echo 'Error: ' . $mensaje . PHP_EOL;
}
