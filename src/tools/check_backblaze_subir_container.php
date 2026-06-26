<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$container = new Ventas\Core\Infrastructure\Container\Container();
Ventas\Backups\Infrastructure\RegistroBackups::registrar($container);

echo $container->has(Ventas\Backups\Application\SubirRespaldoBackblaze::class) ? "HAS_SUBIR\n" : "NO_SUBIR\n";
echo get_class($container->get(Ventas\Backups\Application\SubirRespaldoBackblaze::class)) . "\n";
