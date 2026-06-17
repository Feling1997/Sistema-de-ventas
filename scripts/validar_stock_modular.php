<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Stock\Application\BuscarStockPorId;
use Ventas\Stock\Application\ListarStock;
use Ventas\Stock\Application\ListarStockGeneral;
use Ventas\Stock\Domain\Repositorios\StockRepository;
use Ventas\Stock\Infrastructure\RegistroStock;

$ok = false;
$cantidad = 0;
$cantidadGenerales = 0;
$idBusqueda = 0;
$resultadoBusqueda = 'null';
$claseListar = '';
$claseListarGeneral = '';
$claseBuscar = '';
$claseRepositorio = '';
$claseRepositorioListar = '';
$claseRepositorioBuscar = '';
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
    RegistroStock::registrar($container);

    $listarStock = $container->get(ListarStock::class);
    $listarStockGeneral = $container->get(ListarStockGeneral::class);
    $buscarStockPorId = $container->get(BuscarStockPorId::class);
    $repositorio = $container->get(StockRepository::class);

    $stocks = $listarStock->ejecutar();
    $stocksGenerales = $listarStockGeneral->ejecutar();

    foreach ($stocks as $stockListado) {
        if ($idBusqueda === 0 && $stockListado->id() !== null && $stockListado->id() > 0) {
            $idBusqueda = $stockListado->id();
        }
    }

    $stock = $idBusqueda > 0 ? $buscarStockPorId->ejecutar($idBusqueda) : null;

    $refListar = new \ReflectionClass($listarStock);
    $propListar = $refListar->getProperty('stockRepository');
    $propListar->setAccessible(true);
    $repoListar = $propListar->getValue($listarStock);

    $refBuscar = new \ReflectionClass($buscarStockPorId);
    $propBuscar = $refBuscar->getProperty('stockRepository');
    $propBuscar->setAccessible(true);
    $repoBuscar = $propBuscar->getValue($buscarStockPorId);

    $cantidad = count($stocks);
    $cantidadGenerales = count($stocksGenerales);
    $resultadoBusqueda = $stock === null ? 'null' : 'entidad';
    $claseListar = get_class($listarStock);
    $claseListarGeneral = get_class($listarStockGeneral);
    $claseBuscar = get_class($buscarStockPorId);
    $claseRepositorio = get_class($repositorio);
    $claseRepositorioListar = get_class($repoListar);
    $claseRepositorioBuscar = get_class($repoBuscar);
    $claseEntidad = $stock === null ? 'null' : get_class($stock);
    $namespaceViejoUtilizado = str_starts_with($claseRepositorio, 'Ventas\\Infraestructura\\Persistencia\\MySQL\\Stock')
        || str_starts_with($claseListar, 'Ventas\\Aplicacion\\Stock')
        || str_starts_with($claseEntidad, 'Ventas\\Dominio\\Stock');
    $namespaceModularUtilizado = str_starts_with($claseRepositorio, 'Ventas\\Stock\\Infrastructure')
        && str_starts_with($claseListar, 'Ventas\\Stock\\Application')
        && ($claseEntidad === 'null' || str_starts_with($claseEntidad, 'Ventas\\Stock\\Domain'));
    $ok = true;
} catch (\Throwable $e) {
    $mensaje = $e->getMessage();
}

echo 'Validacion modulo Stock modular' . PHP_EOL;
echo 'OK: ' . ($ok ? 'SI' : 'NO') . PHP_EOL;
echo 'Cantidad de stock encontrados: ' . $cantidad . PHP_EOL;
echo 'Cantidad de stock general activo: ' . $cantidadGenerales . PHP_EOL;
echo 'buscarPorId(' . $idBusqueda . '): ' . $resultadoBusqueda . PHP_EOL;
echo 'Clase ListarStock: ' . $claseListar . PHP_EOL;
echo 'Clase ListarStockGeneral: ' . $claseListarGeneral . PHP_EOL;
echo 'Clase BuscarStockPorId: ' . $claseBuscar . PHP_EOL;
echo 'Repositorio resuelto: ' . $claseRepositorio . PHP_EOL;
echo 'Repositorio en Listar: ' . $claseRepositorioListar . PHP_EOL;
echo 'Repositorio en Buscar: ' . $claseRepositorioBuscar . PHP_EOL;
echo 'Entidad devuelta: ' . $claseEntidad . PHP_EOL;
echo 'Namespace viejo utilizado: ' . ($namespaceViejoUtilizado ? 'SI' : 'NO') . PHP_EOL;
echo 'Namespace modular utilizado: ' . ($namespaceModularUtilizado ? 'SI' : 'NO') . PHP_EOL;

if ($mensaje !== '') {
    echo 'Error: ' . $mensaje . PHP_EOL;
}
