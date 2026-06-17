<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Productos\Application\BuscarProductoFormularioPorId;
use Ventas\Productos\Application\BuscarProductoPorCodigoOPLU;
use Ventas\Productos\Application\BuscarProductoPorId;
use Ventas\Productos\Application\BuscarProductosParaVenta;
use Ventas\Productos\Application\EliminarProductosNoVendidos;
use Ventas\Productos\Application\ListarProductos;
use Ventas\Productos\Application\ListarProductosPorStock;
use Ventas\Productos\Application\ListarProductosVista;
use Ventas\Productos\Application\ObtenerPreciosProducto;
use Ventas\Productos\Application\ObtenerProductoParaVenta;
use Ventas\Productos\Domain\Repositorios\ProductoRepository;
use Ventas\Productos\Infrastructure\RegistroProductos;

$ok = false;
$cantidad = 0;
$cantidadVista = 0;
$cantidadPorStock = 0;
$cantidadVenta = 0;
$cantidadPrecios = 0;
$idBusqueda = 0;
$idStockBusqueda = 0;
$resultadoBusqueda = 'null';
$resultadoFormulario = 'null';
$resultadoVenta = 'null';
$resultadoCodigo = 'null';
$claseListar = '';
$claseBuscar = '';
$claseFormulario = '';
$claseVista = '';
$clasePorStock = '';
$claseBuscarVenta = '';
$claseObtenerVenta = '';
$clasePrecios = '';
$claseCodigo = '';
$claseEliminar = '';
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
    RegistroProductos::registrar($container);

    $listarProductos = $container->get(ListarProductos::class);
    $buscarProductoPorId = $container->get(BuscarProductoPorId::class);
    $buscarProductoFormularioPorId = $container->get(BuscarProductoFormularioPorId::class);
    $listarProductosVista = $container->get(ListarProductosVista::class);
    $listarProductosPorStock = $container->get(ListarProductosPorStock::class);
    $buscarProductosParaVenta = $container->get(BuscarProductosParaVenta::class);
    $obtenerProductoParaVenta = $container->get(ObtenerProductoParaVenta::class);
    $obtenerPreciosProducto = $container->get(ObtenerPreciosProducto::class);
    $buscarProductoPorCodigoOPLU = $container->get(BuscarProductoPorCodigoOPLU::class);
    $eliminarProductosNoVendidos = $container->get(EliminarProductosNoVendidos::class);
    $repositorio = $container->get(ProductoRepository::class);

    $productos = $listarProductos->ejecutar();

    foreach ($productos as $productoListado) {
        if ($idBusqueda === 0 && $productoListado->id() !== null && $productoListado->id() > 0) {
            $idBusqueda = $productoListado->id();
            $idStockBusqueda = $productoListado->idStock() ?? 0;
        }
    }

    $producto = $idBusqueda > 0 ? $buscarProductoPorId->ejecutar($idBusqueda) : null;
    $productoFormulario = $idBusqueda > 0 ? $buscarProductoFormularioPorId->ejecutar($idBusqueda) : null;
    $productosVista = $listarProductosVista->ejecutar('nombre', 'ASC', 1);
    $productosPorStock = $idStockBusqueda > 0 ? $listarProductosPorStock->ejecutar($idStockBusqueda) : [];
    $productosVenta = $buscarProductosParaVenta->ejecutar('', 'nombre', 1, 5);
    $productoVenta = $idBusqueda > 0 ? $obtenerProductoParaVenta->ejecutar($idBusqueda) : null;
    $precios = $idBusqueda > 0 ? $obtenerPreciosProducto->ejecutar($idBusqueda) : [];
    $productoCodigo = ($producto !== null && $producto->codBarras() !== null) ? $buscarProductoPorCodigoOPLU->ejecutar($producto->codBarras()) : null;

    $refListar = new \ReflectionClass($listarProductos);
    $propListar = $refListar->getProperty('productoRepository');
    $propListar->setAccessible(true);
    $repoListar = $propListar->getValue($listarProductos);

    $refBuscar = new \ReflectionClass($buscarProductoPorId);
    $propBuscar = $refBuscar->getProperty('productoRepository');
    $propBuscar->setAccessible(true);
    $repoBuscar = $propBuscar->getValue($buscarProductoPorId);

    $cantidad = count($productos);
    $cantidadVista = count($productosVista);
    $cantidadPorStock = count($productosPorStock);
    $cantidadVenta = count($productosVenta);
    $cantidadPrecios = count($precios);
    $resultadoBusqueda = $producto === null ? 'null' : 'entidad';
    $resultadoFormulario = $productoFormulario === null ? 'null' : 'array';
    $resultadoVenta = $productoVenta === null ? 'null' : 'array';
    $resultadoCodigo = $productoCodigo === null ? 'null' : 'array';
    $claseListar = get_class($listarProductos);
    $claseBuscar = get_class($buscarProductoPorId);
    $claseFormulario = get_class($buscarProductoFormularioPorId);
    $claseVista = get_class($listarProductosVista);
    $clasePorStock = get_class($listarProductosPorStock);
    $claseBuscarVenta = get_class($buscarProductosParaVenta);
    $claseObtenerVenta = get_class($obtenerProductoParaVenta);
    $clasePrecios = get_class($obtenerPreciosProducto);
    $claseCodigo = get_class($buscarProductoPorCodigoOPLU);
    $claseEliminar = get_class($eliminarProductosNoVendidos);
    $claseRepositorio = get_class($repositorio);
    $claseRepositorioListar = get_class($repoListar);
    $claseRepositorioBuscar = get_class($repoBuscar);
    $claseEntidad = $producto === null ? 'null' : get_class($producto);
    $namespaceViejoUtilizado = str_starts_with($claseRepositorio, 'Ventas\\Infraestructura\\Persistencia\\MySQL\\Productos')
        || str_starts_with($claseListar, 'Ventas\\Aplicacion\\Productos')
        || str_starts_with($claseEntidad, 'Ventas\\Dominio\\Productos');
    $namespaceModularUtilizado = str_starts_with($claseRepositorio, 'Ventas\\Productos\\Infrastructure')
        && str_starts_with($claseListar, 'Ventas\\Productos\\Application')
        && ($claseEntidad === 'null' || str_starts_with($claseEntidad, 'Ventas\\Productos\\Domain'));
    $ok = true;
} catch (\Throwable $e) {
    $mensaje = $e->getMessage();
}

echo 'Validacion modulo Productos modular' . PHP_EOL;
echo 'OK: ' . ($ok ? 'SI' : 'NO') . PHP_EOL;
echo 'Cantidad de productos encontrados: ' . $cantidad . PHP_EOL;
echo 'Cantidad de productos vista: ' . $cantidadVista . PHP_EOL;
echo 'Cantidad de productos por stock(' . $idStockBusqueda . '): ' . $cantidadPorStock . PHP_EOL;
echo 'Cantidad de productos para venta: ' . $cantidadVenta . PHP_EOL;
echo 'Cantidad de precios del producto: ' . $cantidadPrecios . PHP_EOL;
echo 'buscarPorId(' . $idBusqueda . '): ' . $resultadoBusqueda . PHP_EOL;
echo 'buscarFormularioPorId(' . $idBusqueda . '): ' . $resultadoFormulario . PHP_EOL;
echo 'obtenerProductoParaVenta(' . $idBusqueda . '): ' . $resultadoVenta . PHP_EOL;
echo 'buscarPorCodigoOPluVenta: ' . $resultadoCodigo . PHP_EOL;
echo 'Clase ListarProductos: ' . $claseListar . PHP_EOL;
echo 'Clase BuscarProductoPorId: ' . $claseBuscar . PHP_EOL;
echo 'Clase BuscarProductoFormularioPorId: ' . $claseFormulario . PHP_EOL;
echo 'Clase ListarProductosVista: ' . $claseVista . PHP_EOL;
echo 'Clase ListarProductosPorStock: ' . $clasePorStock . PHP_EOL;
echo 'Clase BuscarProductosParaVenta: ' . $claseBuscarVenta . PHP_EOL;
echo 'Clase ObtenerProductoParaVenta: ' . $claseObtenerVenta . PHP_EOL;
echo 'Clase ObtenerPreciosProducto: ' . $clasePrecios . PHP_EOL;
echo 'Clase BuscarProductoPorCodigoOPLU: ' . $claseCodigo . PHP_EOL;
echo 'Clase EliminarProductosNoVendidos: ' . $claseEliminar . PHP_EOL;
echo 'Repositorio resuelto: ' . $claseRepositorio . PHP_EOL;
echo 'Repositorio en Listar: ' . $claseRepositorioListar . PHP_EOL;
echo 'Repositorio en Buscar: ' . $claseRepositorioBuscar . PHP_EOL;
echo 'Entidad devuelta: ' . $claseEntidad . PHP_EOL;
echo 'Namespace viejo utilizado: ' . ($namespaceViejoUtilizado ? 'SI' : 'NO') . PHP_EOL;
echo 'Namespace modular utilizado: ' . ($namespaceModularUtilizado ? 'SI' : 'NO') . PHP_EOL;

if ($mensaje !== '') {
    echo 'Error: ' . $mensaje . PHP_EOL;
}
