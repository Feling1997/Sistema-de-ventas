<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ventas\Infraestructura\Contenedor\Container;
use Ventas\ListasPrecios\Application\BuscarListaPrecioPorId;
use Ventas\ListasPrecios\Application\ListarListasPrecios;
use Ventas\ListasPrecios\Application\ObtenerListaPrecioPredeterminada;
use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;
use Ventas\ListasPrecios\Infrastructure\RegistroListasPrecios;

$ok = false;
$cantidad = 0;
$idPredeterminada = 0;
$resultadoBusqueda = 'null';
$claseListar = '';
$claseBuscar = '';
$clasePredeterminada = '';
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
    RegistroListasPrecios::registrar($container);

    $listarListasPrecios = $container->get(ListarListasPrecios::class);
    $buscarListaPrecioPorId = $container->get(BuscarListaPrecioPorId::class);
    $obtenerListaPrecioPredeterminada = $container->get(ObtenerListaPrecioPredeterminada::class);
    $repositorio = $container->get(ListaPrecioRepository::class);

    $listas = $listarListasPrecios->ejecutar();
    $idPredeterminada = $obtenerListaPrecioPredeterminada->ejecutar();
    $lista = $buscarListaPrecioPorId->ejecutar($idPredeterminada);

    $refListar = new \ReflectionClass($listarListasPrecios);
    $propListar = $refListar->getProperty('listaPrecioRepository');
    $propListar->setAccessible(true);
    $repoListar = $propListar->getValue($listarListasPrecios);

    $refBuscar = new \ReflectionClass($buscarListaPrecioPorId);
    $propBuscar = $refBuscar->getProperty('listaPrecioRepository');
    $propBuscar->setAccessible(true);
    $repoBuscar = $propBuscar->getValue($buscarListaPrecioPorId);

    $cantidad = count($listas);
    $resultadoBusqueda = $lista === null ? 'null' : 'entidad';
    $claseListar = get_class($listarListasPrecios);
    $claseBuscar = get_class($buscarListaPrecioPorId);
    $clasePredeterminada = get_class($obtenerListaPrecioPredeterminada);
    $claseRepositorio = get_class($repositorio);
    $claseRepositorioListar = get_class($repoListar);
    $claseRepositorioBuscar = get_class($repoBuscar);
    $claseEntidad = $lista === null ? 'null' : get_class($lista);
    $namespaceViejoUtilizado = str_starts_with($claseRepositorio, 'Ventas\\Infraestructura\\Persistencia\\MySQL\\ListasPrecios')
        || str_starts_with($claseListar, 'Ventas\\Aplicacion\\ListasPrecios')
        || str_starts_with($claseEntidad, 'Ventas\\Dominio\\ListasPrecios');
    $namespaceModularUtilizado = str_starts_with($claseRepositorio, 'Ventas\\ListasPrecios\\Infrastructure')
        && str_starts_with($claseListar, 'Ventas\\ListasPrecios\\Application')
        && ($claseEntidad === 'null' || str_starts_with($claseEntidad, 'Ventas\\ListasPrecios\\Domain'));
    $ok = true;
} catch (\Throwable $e) {
    $mensaje = $e->getMessage();
}

echo 'Validacion modulo ListasPrecios modular' . PHP_EOL;
echo 'OK: ' . ($ok ? 'SI' : 'NO') . PHP_EOL;
echo 'Cantidad de listas encontradas: ' . $cantidad . PHP_EOL;
echo 'idPredeterminada: ' . $idPredeterminada . PHP_EOL;
echo 'buscarPorId(idPredeterminada): ' . $resultadoBusqueda . PHP_EOL;
echo 'Clase ListarListasPrecios: ' . $claseListar . PHP_EOL;
echo 'Clase BuscarListaPrecioPorId: ' . $claseBuscar . PHP_EOL;
echo 'Clase ObtenerListaPrecioPredeterminada: ' . $clasePredeterminada . PHP_EOL;
echo 'Repositorio resuelto: ' . $claseRepositorio . PHP_EOL;
echo 'Repositorio en Listar: ' . $claseRepositorioListar . PHP_EOL;
echo 'Repositorio en Buscar: ' . $claseRepositorioBuscar . PHP_EOL;
echo 'Entidad devuelta: ' . $claseEntidad . PHP_EOL;
echo 'Namespace viejo utilizado: ' . ($namespaceViejoUtilizado ? 'SI' : 'NO') . PHP_EOL;
echo 'Namespace modular utilizado: ' . ($namespaceModularUtilizado ? 'SI' : 'NO') . PHP_EOL;
if ($mensaje !== '') {
    echo 'Error: ' . $mensaje . PHP_EOL;
}
