<?php

declare(strict_types=1);

use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Ventas\Application\BuscarVentaPorId;
use Ventas\Ventas\Application\GenerarPdfComprobanteVenta;
use Ventas\Ventas\Application\ListarVentas;
use Ventas\Ventas\Application\ListarVentasPeriodo;
use Ventas\Ventas\Application\ObtenerArchivoPdfVenta;
use Ventas\Ventas\Application\ObtenerComprobanteVenta;
use Ventas\Ventas\Application\ObtenerDetallesVentas;
use Ventas\Ventas\Application\ObtenerDetalleVenta;
use Ventas\Ventas\Application\ObtenerEstadosFiscalesVentas;
use Ventas\Ventas\Application\ObtenerResumenVentasPeriodo;
use Ventas\Ventas\Application\RenderizarTicketVenta;
use Ventas\Ventas\Domain\Entidades\Venta;
use Ventas\Ventas\Domain\Repositorios\ComprobanteVentaRepository;
use Ventas\Ventas\Domain\Repositorios\VentaRepository;
use Ventas\Ventas\Infrastructure\HtmlPdfComprobanteVentaRepository;
use Ventas\Ventas\Infrastructure\MySQLVentaRepository;
use Ventas\Ventas\Infrastructure\RegistroVentas;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$salida = [];
$salida[] = 'Validacion Ventas lectura/comprobantes modular';
$salida[] = 'Namespace modular objetivo: Ventas\\Ventas';
$salida[] = 'Operacion de escritura ejecutada: NO';
$salida[] = 'PDF generado fisicamente: NO';
$salida[] = 'Impresion ejecutada: NO';

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

    RegistroVentas::registrar($container);

    $ventaRepository = $container->get(VentaRepository::class);
    $comprobanteVentaRepository = $container->get(ComprobanteVentaRepository::class);
    $listarVentas = $container->get(ListarVentas::class);
    $buscarVentaPorId = $container->get(BuscarVentaPorId::class);
    $obtenerDetalleVenta = $container->get(ObtenerDetalleVenta::class);
    $obtenerComprobanteVenta = $container->get(ObtenerComprobanteVenta::class);
    $listarVentasPeriodo = $container->get(ListarVentasPeriodo::class);
    $obtenerResumenVentasPeriodo = $container->get(ObtenerResumenVentasPeriodo::class);
    $obtenerEstadosFiscalesVentas = $container->get(ObtenerEstadosFiscalesVentas::class);
    $obtenerDetallesVentas = $container->get(ObtenerDetallesVentas::class);
    $renderizarTicketVenta = $container->get(RenderizarTicketVenta::class);
    $generarPdfComprobanteVenta = $container->get(GenerarPdfComprobanteVenta::class);
    $obtenerArchivoPdfVenta = $container->get(ObtenerArchivoPdfVenta::class);

    $ventas = $listarVentas->ejecutar();
    $ventasPeriodo = $listarVentasPeriodo->ejecutar('', '', 'fecha', 'DESC');
    $resumenPeriodo = $obtenerResumenVentasPeriodo->ejecutar($ventasPeriodo);
    $idVenta = 0;
    $idsVentas = [];

    foreach ($ventasPeriodo as $ventaPeriodo) {
        $idNormalizado = (int) ($ventaPeriodo['id'] ?? 0);

        if ($idNormalizado > 0) {
            $idsVentas[] = $idNormalizado;

            if ($idVenta === 0) {
                $idVenta = $idNormalizado;
            }
        }
    }

    if ($idVenta === 0) {
        foreach ($ventas as $venta) {
            $idNormalizado = method_exists($venta, 'id') ? (int) $venta->id() : 0;

            if ($idNormalizado > 0) {
                $idsVentas[] = $idNormalizado;

                if ($idVenta === 0) {
                    $idVenta = $idNormalizado;
                }
            }
        }
    }

    $ventaEncontrada = null;
    $detalle = [];
    $comprobante = null;
    $estadosFiscales = [];
    $detallesVentas = [];
    $archivoPdf = null;

    if ($idVenta > 0) {
        $ventaEncontrada = $buscarVentaPorId->ejecutar($idVenta);
        $detalle = $obtenerDetalleVenta->ejecutar($idVenta);
        $comprobante = $obtenerComprobanteVenta->ejecutar($idVenta);
        $estadosFiscales = $obtenerEstadosFiscalesVentas->ejecutar($idsVentas);
        $detallesVentas = $obtenerDetallesVentas->ejecutar($idsVentas);
        $archivoPdf = $obtenerArchivoPdfVenta->ejecutar($idVenta);
    }

    $salida[] = 'Container VentaRepository: ' . get_class($ventaRepository);
    $salida[] = 'Container ComprobanteVentaRepository: ' . get_class($comprobanteVentaRepository);
    $salida[] = 'ListarVentas resuelto: ' . get_class($listarVentas);
    $salida[] = 'BuscarVentaPorId resuelto: ' . get_class($buscarVentaPorId);
    $salida[] = 'ObtenerDetalleVenta resuelto: ' . get_class($obtenerDetalleVenta);
    $salida[] = 'ObtenerComprobanteVenta resuelto: ' . get_class($obtenerComprobanteVenta);
    $salida[] = 'ListarVentasPeriodo resuelto: ' . get_class($listarVentasPeriodo);
    $salida[] = 'ObtenerResumenVentasPeriodo resuelto: ' . get_class($obtenerResumenVentasPeriodo);
    $salida[] = 'ObtenerEstadosFiscalesVentas resuelto: ' . get_class($obtenerEstadosFiscalesVentas);
    $salida[] = 'ObtenerDetallesVentas resuelto: ' . get_class($obtenerDetallesVentas);
    $salida[] = 'RenderizarTicketVenta resuelto sin ejecutar: ' . get_class($renderizarTicketVenta);
    $salida[] = 'GenerarPdfComprobanteVenta resuelto sin ejecutar: ' . get_class($generarPdfComprobanteVenta);
    $salida[] = 'ObtenerArchivoPdfVenta resuelto: ' . get_class($obtenerArchivoPdfVenta);
    $salida[] = 'Cantidad ventas listar(): ' . count($ventas);
    $salida[] = 'Cantidad ventas periodo: ' . count($ventasPeriodo);
    $salida[] = 'Venta usada para lectura: ' . $idVenta;
    $salida[] = 'buscarPorId devolvio entidad: ' . ($ventaEncontrada instanceof Venta ? 'SI' : 'NO');
    $salida[] = 'Entidad devuelta: ' . ($ventaEncontrada instanceof Venta ? get_class($ventaEncontrada) : 'NULL');
    $salida[] = 'Detalle cantidad: ' . count($detalle);
    $salida[] = 'Comprobante devuelto: ' . (is_array($comprobante) ? 'SI' : 'NO');
    $salida[] = 'Resumen cantidad_ventas: ' . (int) ($resumenPeriodo['cantidad_ventas'] ?? 0);
    $salida[] = 'Resumen total_vendido: ' . (string) ($resumenPeriodo['total_vendido'] ?? 0);
    $salida[] = 'Estados fiscales cantidad: ' . count($estadosFiscales);
    $salida[] = 'Detalles ventas agrupados: ' . count($detallesVentas);
    $salida[] = 'Archivo PDF existente devuelto: ' . (is_array($archivoPdf) ? 'SI' : 'NO');
    $salida[] = 'class_exists ListarVentas: ' . (class_exists(ListarVentas::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists BuscarVentaPorId: ' . (class_exists(BuscarVentaPorId::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists Venta: ' . (class_exists(Venta::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists MySQLVentaRepository: ' . (class_exists(MySQLVentaRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists HtmlPdfComprobanteVentaRepository: ' . (class_exists(HtmlPdfComprobanteVentaRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists RegistroVentas: ' . (class_exists(RegistroVentas::class) ? 'OK' : 'ERROR');
    $salida[] = 'interface_exists VentaRepository: ' . (interface_exists(VentaRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'interface_exists ComprobanteVentaRepository: ' . (interface_exists(ComprobanteVentaRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'Namespace viejo utilizado: NO en Ventas lectura/comprobantes';
    $salida[] = 'Dependencia externa temporal ConfiguracionVenta: SI';
    $salida[] = 'Namespace modular utilizado: SI';
} catch (Throwable $throwable) {
    $salida[] = 'ERROR CONTROLADO: ' . $throwable->getMessage();
    $salida[] = 'Namespace viejo utilizado: NO';
    $salida[] = 'Namespace modular utilizado: NO';
}

foreach ($salida as $linea) {
    echo $linea . PHP_EOL;
}
