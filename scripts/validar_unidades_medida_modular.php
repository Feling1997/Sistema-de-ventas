<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ventas\Infraestructura\Contenedor\Container;
use Ventas\UnidadesMedida\Application\BuscarUnidadMedidaPorId;
use Ventas\UnidadesMedida\Application\ListarUnidadesMedida;
use Ventas\UnidadesMedida\Infrastructure\RegistroUnidadesMedida;

$ok = false;
$cantidad = 0;
$resultadoBusqueda = 'null';
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
    RegistroUnidadesMedida::registrar($container);

    $listarUnidadesMedida = $container->get(ListarUnidadesMedida::class);
    $buscarUnidadMedidaPorId = $container->get(BuscarUnidadMedidaPorId::class);

    $unidades = $listarUnidadesMedida->ejecutar();
    $unidad = $buscarUnidadMedidaPorId->ejecutar(1);

    $cantidad = count($unidades);
    $resultadoBusqueda = $unidad === null ? 'null' : 'entidad';
    $ok = true;
} catch (\Throwable $e) {
    $mensaje = $e->getMessage();
}

echo 'Validacion modulo UnidadesMedida modular' . PHP_EOL;
echo 'OK: ' . ($ok ? 'SI' : 'NO') . PHP_EOL;
echo 'Cantidad de unidades encontradas: ' . $cantidad . PHP_EOL;
echo 'buscarPorId(1): ' . $resultadoBusqueda . PHP_EOL;
if ($mensaje !== '') {
    echo 'Error: ' . $mensaje . PHP_EOL;
}
