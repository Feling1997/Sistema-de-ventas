<?php

declare(strict_types=1);

use Ventas\Impresoras\Application\ListarImpresoras;
use Ventas\Impresoras\Domain\Repositorios\ImpresoraRepository;
use Ventas\Impresoras\Infrastructure\PowerShellImpresoraRepository;
use Ventas\Impresoras\Infrastructure\RegistroImpresoras;
use Ventas\Infraestructura\Contenedor\Container;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$ok = false;
$error = '';
$cantidad = 0;
$impresoras = [];
$claseListar = '';
$claseRepositorioResuelto = '';
$claseRepositorioEnListar = '';
$namespaceViejoUtilizado = false;
$namespaceModularUtilizado = false;
$aviso = '';

try {
    $container = new Container();
    RegistroImpresoras::registrar($container);

    $listarImpresoras = $container->get(ListarImpresoras::class);
    $repositorio = $container->get(ImpresoraRepository::class);
    $impresoras = $listarImpresoras->ejecutar();
    $cantidad = count($impresoras);
    $claseListar = $listarImpresoras::class;
    $claseRepositorioResuelto = $repositorio::class;

    $reflection = new ReflectionClass($listarImpresoras);
    $property = $reflection->getProperty('impresoraRepository');
    $property->setAccessible(true);
    $repositorioEnListar = $property->getValue($listarImpresoras);
    $claseRepositorioEnListar = is_object($repositorioEnListar) ? $repositorioEnListar::class : '';
    $namespaceViejoUtilizado = str_starts_with($claseListar, 'Ventas\\Aplicacion\\')
        || str_starts_with($claseRepositorioResuelto, 'Ventas\\Infraestructura\\');
    $namespaceModularUtilizado = str_starts_with($claseListar, 'Ventas\\Impresoras\\')
        && str_starts_with($claseRepositorioResuelto, 'Ventas\\Impresoras\\');
    $ok = $listarImpresoras instanceof ListarImpresoras
        && $repositorio instanceof PowerShellImpresoraRepository
        && is_array($impresoras);

    if ($cantidad === 0) {
        $aviso = 'No se encontraron impresoras o el entorno no permite listarlas.';
    }
} catch (Throwable $throwable) {
    $error = $throwable->getMessage();
}

echo 'Validacion modulo Impresoras modular' . PHP_EOL;
echo 'OK: ' . ($ok ? 'SI' : 'NO') . PHP_EOL;
echo 'Cantidad de impresoras encontradas: ' . $cantidad . PHP_EOL;
echo 'Clase ListarImpresoras: ' . $claseListar . PHP_EOL;
echo 'Repositorio resuelto: ' . $claseRepositorioResuelto . PHP_EOL;
echo 'Repositorio en Listar: ' . $claseRepositorioEnListar . PHP_EOL;
echo 'Namespace viejo utilizado: ' . ($namespaceViejoUtilizado ? 'SI' : 'NO') . PHP_EOL;
echo 'Namespace modular utilizado: ' . ($namespaceModularUtilizado ? 'SI' : 'NO') . PHP_EOL;

if ($error !== '') {
    echo 'Error controlado: ' . $error . PHP_EOL;
}

if ($aviso !== '') {
    echo 'Aviso controlado: ' . $aviso . PHP_EOL;
}
