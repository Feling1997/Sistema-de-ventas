<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$container = new Ventas\Core\Infrastructure\Container\Container();
Ventas\Backups\Infrastructure\RegistroBackups::registrar($container);

echo $container->has(Ventas\Backups\Domain\Repositorios\BackblazeStorageRepository::class) ? "HAS_STORAGE\n" : "NO_STORAGE\n";
echo $container->has(Ventas\Backups\Infrastructure\BackblazeB2HttpRepository::class) ? "HAS_B2HTTP\n" : "NO_B2HTTP\n";
echo get_class($container->get(Ventas\Backups\Application\ProbarConexionBackblaze::class)) . "\n";
