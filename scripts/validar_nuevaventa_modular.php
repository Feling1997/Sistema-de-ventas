<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Infraestructura\Ventas\NuevaVenta\RegistroNuevaVenta;
use Ventas\Productos\Application\BuscarProductoPorCodigoOPLU;
use Ventas\Productos\Application\ObtenerProductoParaVenta;
use Ventas\Productos\Domain\Repositorios\ProductoRepository;
use Ventas\Productos\Infrastructure\RegistroProductos;
use Ventas\Stock\Application\BuscarStockPorId;
use Ventas\Stock\Domain\Repositorios\StockRepository;
use Ventas\Stock\Infrastructure\RegistroStock;

$container = new Container();

RegistroStock::registrar($container);
RegistroProductos::registrar($container);
RegistroNuevaVenta::registrar($container);

$buscarStockPorId = $container->get(BuscarStockPorId::class);
$obtenerProductoParaVenta = $container->get(ObtenerProductoParaVenta::class);
$buscarProductoPorCodigoOPLU = $container->get(BuscarProductoPorCodigoOPLU::class);
$stockRepository = $container->get(StockRepository::class);
$productoRepository = $container->get(ProductoRepository::class);

$clases = [
    get_class($buscarStockPorId),
    get_class($obtenerProductoParaVenta),
    get_class($buscarProductoPorCodigoOPLU),
    get_class($stockRepository),
    get_class($productoRepository),
];

$namespaceViejoUtilizado = false;
$namespaceModularUtilizado = true;

foreach ($clases as $clase) {
    if (
        str_starts_with($clase, 'Ventas\\Aplicacion\\Stock')
        || str_starts_with($clase, 'Ventas\\Dominio\\Stock')
        || str_starts_with($clase, 'Ventas\\Aplicacion\\Productos')
        || str_starts_with($clase, 'Ventas\\Dominio\\Productos')
        || str_starts_with($clase, 'Ventas\\Infraestructura\\Persistencia\\MySQL\\Stock')
        || str_starts_with($clase, 'Ventas\\Infraestructura\\Persistencia\\MySQL\\Productos')
    ) {
        $namespaceViejoUtilizado = true;
    }

    if (
        !str_starts_with($clase, 'Ventas\\Stock')
        && !str_starts_with($clase, 'Ventas\\Productos')
    ) {
        $namespaceModularUtilizado = false;
    }
}

echo 'BuscarStockPorId: ' . get_class($buscarStockPorId) . PHP_EOL;
echo 'ObtenerProductoParaVenta: ' . get_class($obtenerProductoParaVenta) . PHP_EOL;
echo 'BuscarProductoPorCodigoOPLU: ' . get_class($buscarProductoPorCodigoOPLU) . PHP_EOL;
echo 'StockRepository: ' . get_class($stockRepository) . PHP_EOL;
echo 'ProductoRepository: ' . get_class($productoRepository) . PHP_EOL;
echo 'namespace viejo utilizado: ' . ($namespaceViejoUtilizado ? 'SI' : 'NO') . PHP_EOL;
echo 'namespace modular utilizado: ' . ($namespaceModularUtilizado ? 'SI' : 'NO') . PHP_EOL;
