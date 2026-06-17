<?php

declare(strict_types=1);

use Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerFormularioVenta;
use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Infraestructura\Ventas\NuevaVenta\RegistroNuevaVenta;
use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;
use Ventas\ListasPrecios\Infrastructure\MySQLListaPrecioRepository;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$salida = [];
$salida[] = 'Validacion ListasPrecios modular en NuevaVenta legacy';

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

    RegistroNuevaVenta::registrar($container);

    $repositorio = $container->get(ListaPrecioRepository::class);
    $obtenerFormularioVenta = $container->get(ObtenerFormularioVenta::class);

    $reflection = new ReflectionClass($obtenerFormularioVenta);
    $propiedad = $reflection->getProperty('listaPrecioRepository');
    $propiedad->setAccessible(true);
    $repositorioEnCasoUso = $propiedad->getValue($obtenerFormularioVenta);

    $claseRepositorio = get_class($repositorio);
    $claseRepositorioCasoUso = is_object($repositorioEnCasoUso) ? get_class($repositorioEnCasoUso) : 'null';

    $salida[] = ListaPrecioRepository::class . ' => ' . $claseRepositorio;
    $salida[] = ObtenerFormularioVenta::class . ' => ' . get_class($obtenerFormularioVenta);
    $salida[] = 'Repositorio en ObtenerFormularioVenta: ' . $claseRepositorioCasoUso;
    $salida[] = 'class_exists MySQLListaPrecioRepository: ' . (class_exists(MySQLListaPrecioRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'interface_exists ListaPrecioRepository: ' . (interface_exists(ListaPrecioRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'Namespace viejo utilizado: ' . (str_starts_with($claseRepositorioCasoUso, 'Ventas\\Infraestructura\\Persistencia\\MySQL\\ListasPrecios') ? 'SI' : 'NO');
    $salida[] = 'Namespace modular utilizado: ' . (str_starts_with($claseRepositorioCasoUso, 'Ventas\\ListasPrecios\\Infrastructure') ? 'SI' : 'NO');
} catch (Throwable $throwable) {
    $salida[] = 'ERROR CONTROLADO: ' . $throwable->getMessage();
    $salida[] = 'Namespace viejo utilizado: NO';
    $salida[] = 'Namespace modular utilizado: NO';
}

foreach ($salida as $linea) {
    echo $linea . PHP_EOL;
}
