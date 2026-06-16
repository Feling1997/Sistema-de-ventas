<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ventas\Clientes\Application\ActualizarCliente;
use Ventas\Clientes\Application\BuscarClientePorId;
use Ventas\Clientes\Application\CrearCliente;
use Ventas\Clientes\Application\EliminarCliente;
use Ventas\Clientes\Application\ListarClientes;
use Ventas\Clientes\Domain\Repositorios\ClienteRepository;
use Ventas\Clientes\Infrastructure\RegistroClientes;
use Ventas\Infraestructura\Contenedor\Container;

$ok = false;
$cantidad = 0;
$resultadoBusqueda = 'null';
$claseListar = '';
$claseBuscar = '';
$claseCrear = '';
$claseActualizar = '';
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
    RegistroClientes::registrar($container);

    $listarClientes = $container->get(ListarClientes::class);
    $buscarClientePorId = $container->get(BuscarClientePorId::class);
    $crearCliente = $container->get(CrearCliente::class);
    $actualizarCliente = $container->get(ActualizarCliente::class);
    $eliminarCliente = $container->get(EliminarCliente::class);
    $repositorio = $container->get(ClienteRepository::class);

    $clientes = $listarClientes->ejecutar();
    $cliente = $buscarClientePorId->ejecutar(1);

    $refListar = new \ReflectionClass($listarClientes);
    $propListar = $refListar->getProperty('clienteRepository');
    $propListar->setAccessible(true);
    $repoListar = $propListar->getValue($listarClientes);

    $refBuscar = new \ReflectionClass($buscarClientePorId);
    $propBuscar = $refBuscar->getProperty('clienteRepository');
    $propBuscar->setAccessible(true);
    $repoBuscar = $propBuscar->getValue($buscarClientePorId);

    $cantidad = count($clientes);
    $resultadoBusqueda = $cliente === null ? 'null' : 'entidad';
    $claseListar = get_class($listarClientes);
    $claseBuscar = get_class($buscarClientePorId);
    $claseCrear = get_class($crearCliente);
    $claseActualizar = get_class($actualizarCliente);
    $claseEliminar = get_class($eliminarCliente);
    $claseRepositorio = get_class($repositorio);
    $claseRepositorioListar = get_class($repoListar);
    $claseRepositorioBuscar = get_class($repoBuscar);
    $claseEntidad = $cliente === null ? 'null' : get_class($cliente);
    $namespaceViejoUtilizado = str_starts_with($claseRepositorio, 'Ventas\\Infraestructura\\Persistencia\\MySQL\\Clientes')
        || str_starts_with($claseListar, 'Ventas\\Aplicacion\\Clientes')
        || str_starts_with($claseEntidad, 'Ventas\\Dominio\\Clientes');
    $namespaceModularUtilizado = str_starts_with($claseRepositorio, 'Ventas\\Clientes\\Infrastructure')
        && str_starts_with($claseListar, 'Ventas\\Clientes\\Application')
        && ($claseEntidad === 'null' || str_starts_with($claseEntidad, 'Ventas\\Clientes\\Domain'));
    $ok = true;
} catch (\Throwable $e) {
    $mensaje = $e->getMessage();
}

echo 'Validacion modulo Clientes modular' . PHP_EOL;
echo 'OK: ' . ($ok ? 'SI' : 'NO') . PHP_EOL;
echo 'Cantidad de clientes encontrados: ' . $cantidad . PHP_EOL;
echo 'buscarPorId(1): ' . $resultadoBusqueda . PHP_EOL;
echo 'Clase ListarClientes: ' . $claseListar . PHP_EOL;
echo 'Clase BuscarClientePorId: ' . $claseBuscar . PHP_EOL;
echo 'Clase CrearCliente: ' . $claseCrear . PHP_EOL;
echo 'Clase ActualizarCliente: ' . $claseActualizar . PHP_EOL;
echo 'Clase EliminarCliente: ' . $claseEliminar . PHP_EOL;
echo 'Repositorio resuelto: ' . $claseRepositorio . PHP_EOL;
echo 'Repositorio en Listar: ' . $claseRepositorioListar . PHP_EOL;
echo 'Repositorio en Buscar: ' . $claseRepositorioBuscar . PHP_EOL;
echo 'Entidad devuelta: ' . $claseEntidad . PHP_EOL;
echo 'Namespace viejo utilizado: ' . ($namespaceViejoUtilizado ? 'SI' : 'NO') . PHP_EOL;
echo 'Namespace modular utilizado: ' . ($namespaceModularUtilizado ? 'SI' : 'NO') . PHP_EOL;

if ($mensaje !== '') {
    echo 'Error: ' . $mensaje . PHP_EOL;
}
