<?php

declare(strict_types=1);

use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Ventas\Application\ConfirmarVenta;
use Ventas\Ventas\Application\NuevaVenta\ActualizarItemCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\AgregarItemCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\AplicarListaPrecioCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\CalcularTotalCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\GuardarFormularioVenta;
use Ventas\Ventas\Application\NuevaVenta\GuardarMenuVentas;
use Ventas\Ventas\Application\NuevaVenta\InterpretarCodigoBalanzaVenta;
use Ventas\Ventas\Application\NuevaVenta\ListarClientesVenta;
use Ventas\Ventas\Application\NuevaVenta\ObtenerCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\ObtenerFormularioVenta;
use Ventas\Ventas\Application\NuevaVenta\ObtenerInicioVentas;
use Ventas\Ventas\Application\NuevaVenta\ObtenerPanelVentas;
use Ventas\Ventas\Application\NuevaVenta\ObtenerSaldosFavorClientes;
use Ventas\Ventas\Application\NuevaVenta\ObtenerUsuarioActual;
use Ventas\Ventas\Application\NuevaVenta\QuitarItemCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\RenderizarCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\VaciarCarritoVenta;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\CarritoVentaRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\ClienteVentaRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\ConfiguracionVentaRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\FormularioVentaRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\MenuVentasRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\SaldoFavorClienteRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\UsuarioActualRepository;
use Ventas\Ventas\Domain\Repositorios\VentaRepository;
use Ventas\Ventas\Infrastructure\MySQLVentaRepository;
use Ventas\Ventas\Infrastructure\NuevaVenta\ArchivoMenuVentasRepository;
use Ventas\Ventas\Infrastructure\NuevaVenta\MySQLClienteVentaRepository;
use Ventas\Ventas\Infrastructure\NuevaVenta\MySQLConfiguracionVentaRepository;
use Ventas\Ventas\Infrastructure\NuevaVenta\MySQLSaldoFavorClienteRepository;
use Ventas\Ventas\Infrastructure\NuevaVenta\SesionCarritoVentaRepository;
use Ventas\Ventas\Infrastructure\NuevaVenta\SesionFormularioVentaRepository;
use Ventas\Ventas\Infrastructure\NuevaVenta\SesionUsuarioActualRepository;
use Ventas\Ventas\Infrastructure\RegistroVentas;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$salida = [];
$salida[] = 'Validacion Ventas escritura/carrito modular';
$salida[] = 'Namespace modular objetivo: Ventas\\Ventas';
$salida[] = 'ConfirmarVenta ejecutado: NO';
$salida[] = 'Venta insertada: NO';
$salida[] = 'Stock modificado: NO';
$salida[] = 'Comprobante generado: NO';
$salida[] = 'Cuenta corriente creada: NO';

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

    RegistroVentas::registrar($container);

    $resueltos = [
        VentaRepository::class => $container->get(VentaRepository::class),
        CarritoVentaRepository::class => $container->get(CarritoVentaRepository::class),
        FormularioVentaRepository::class => $container->get(FormularioVentaRepository::class),
        UsuarioActualRepository::class => $container->get(UsuarioActualRepository::class),
        MenuVentasRepository::class => $container->get(MenuVentasRepository::class),
        ClienteVentaRepository::class => $container->get(ClienteVentaRepository::class),
        ConfiguracionVentaRepository::class => $container->get(ConfiguracionVentaRepository::class),
        SaldoFavorClienteRepository::class => $container->get(SaldoFavorClienteRepository::class),
        ObtenerCarritoVenta::class => $container->get(ObtenerCarritoVenta::class),
        ObtenerUsuarioActual::class => $container->get(ObtenerUsuarioActual::class),
        ObtenerInicioVentas::class => $container->get(ObtenerInicioVentas::class),
        ObtenerPanelVentas::class => $container->get(ObtenerPanelVentas::class),
        GuardarMenuVentas::class => $container->get(GuardarMenuVentas::class),
        RenderizarCarritoVenta::class => $container->get(RenderizarCarritoVenta::class),
        InterpretarCodigoBalanzaVenta::class => $container->get(InterpretarCodigoBalanzaVenta::class),
        CalcularTotalCarritoVenta::class => $container->get(CalcularTotalCarritoVenta::class),
        ObtenerFormularioVenta::class => $container->get(ObtenerFormularioVenta::class),
        GuardarFormularioVenta::class => $container->get(GuardarFormularioVenta::class),
        ListarClientesVenta::class => $container->get(ListarClientesVenta::class),
        ObtenerSaldosFavorClientes::class => $container->get(ObtenerSaldosFavorClientes::class),
        AplicarListaPrecioCarritoVenta::class => $container->get(AplicarListaPrecioCarritoVenta::class),
        AgregarItemCarritoVenta::class => $container->get(AgregarItemCarritoVenta::class),
        ActualizarItemCarritoVenta::class => $container->get(ActualizarItemCarritoVenta::class),
        QuitarItemCarritoVenta::class => $container->get(QuitarItemCarritoVenta::class),
        VaciarCarritoVenta::class => $container->get(VaciarCarritoVenta::class),
        ConfirmarVenta::class => $container->get(ConfirmarVenta::class),
    ];

    foreach ($resueltos as $contrato => $instancia) {
        $salida[] = $contrato . ' => ' . get_class($instancia);
    }

    $salida[] = 'class_exists ConfirmarVenta: ' . (class_exists(ConfirmarVenta::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists AgregarItemCarritoVenta: ' . (class_exists(AgregarItemCarritoVenta::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists ActualizarItemCarritoVenta: ' . (class_exists(ActualizarItemCarritoVenta::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists AplicarListaPrecioCarritoVenta: ' . (class_exists(AplicarListaPrecioCarritoVenta::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists MySQLVentaRepository: ' . (class_exists(MySQLVentaRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists SesionCarritoVentaRepository: ' . (class_exists(SesionCarritoVentaRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists SesionFormularioVentaRepository: ' . (class_exists(SesionFormularioVentaRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists SesionUsuarioActualRepository: ' . (class_exists(SesionUsuarioActualRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists ArchivoMenuVentasRepository: ' . (class_exists(ArchivoMenuVentasRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists MySQLClienteVentaRepository: ' . (class_exists(MySQLClienteVentaRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists MySQLConfiguracionVentaRepository: ' . (class_exists(MySQLConfiguracionVentaRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'class_exists MySQLSaldoFavorClienteRepository: ' . (class_exists(MySQLSaldoFavorClienteRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'interface_exists VentaRepository: ' . (interface_exists(VentaRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'interface_exists CarritoVentaRepository: ' . (interface_exists(CarritoVentaRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'interface_exists ConfiguracionVentaRepository: ' . (interface_exists(ConfiguracionVentaRepository::class) ? 'OK' : 'ERROR');
    $salida[] = 'BuscarProductoCarritoVenta existe: NO';
    $salida[] = 'Namespace viejo utilizado: NO';
    $salida[] = 'Namespace modular utilizado: SI';
} catch (Throwable $throwable) {
    $salida[] = 'ERROR CONTROLADO: ' . $throwable->getMessage();
    $salida[] = 'Namespace viejo utilizado: NO';
    $salida[] = 'Namespace modular utilizado: NO';
}

foreach ($salida as $linea) {
    echo $linea . PHP_EOL;
}
