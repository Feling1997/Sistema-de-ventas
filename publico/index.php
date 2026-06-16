<?php

require __DIR__ . "/../vendor/autoload.php";

use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Infraestructura\Contenedor\RegistroClientes;
use Ventas\Infraestructura\Contenedor\RegistroImpresoras;
use Ventas\Infraestructura\Contenedor\RegistroListasPrecios;
use Ventas\Infraestructura\Contenedor\RegistroPresupuestos;
use Ventas\Infraestructura\Contenedor\RegistroProductos;
use Ventas\Infraestructura\Contenedor\RegistroStock;
use Ventas\Infraestructura\Contenedor\RegistroUnidadesMedida;
use Ventas\Infraestructura\Contenedor\RegistroUsuarios;
use Ventas\Infraestructura\Contenedor\RegistroVentas;
use Ventas\Infraestructura\Ventas\NuevaVenta\RegistroNuevaVenta;

require_once __DIR__ . "/../configuraciones/ayudas.php";
require_once __DIR__ . "/../configuraciones/base_datos.php";
require_once __DIR__ . "/../configuraciones/seguridad.php";
require_once __DIR__ . "/../configuraciones/csrf.php";
$container = new Container();
RegistroClientes::registrar($container);
RegistroUsuarios::registrar($container);
RegistroStock::registrar($container);
RegistroProductos::registrar($container);
RegistroVentas::registrar($container);
RegistroNuevaVenta::registrar($container);
RegistroListasPrecios::registrar($container);
RegistroUnidadesMedida::registrar($container);
RegistroPresupuestos::registrar($container);
RegistroImpresoras::registrar($container);
$tiempo_inicio_request = microtime(true);
$c=obtener_get("c","auth");//controlador
$a=obtener_get("a","login");//accion
register_shutdown_function(function () use ($tiempo_inicio_request, $c, $a) {
    $duracion = microtime(true) - $tiempo_inicio_request;
    if ($duracion >= 2.5)
        registrar_log("Rendimiento", "Accion lenta {$c}/{$a}: " . number_format($duracion, 3, ".", "") . "s");
});
$mapa=[
  "auth" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorAuth.php", "clase" => "ControladorAuth"],
  "usuarios" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorUsuarios.php", "clase" => "ControladorUsuarios"],
  "clientes" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorClientes.php", "clase" => "ControladorClientes"],
  "stock" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorStock.php", "clase" => "ControladorStock"],
  "productos" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorProductos.php", "clase" => "ControladorProductos"],
  "importacion" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorImportacion.php", "clase" => "ControladorImportacion"],
  "listas_precios" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorListasPrecios.php", "clase" => "ControladorListasPrecios"],
  "exportaciones" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorExportaciones.php", "clase" => "ControladorExportaciones"],
  "cuentas_corrientes" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorCuentasCorrientes.php", "clase" => "ControladorCuentasCorrientes"],
  "ventas" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorVentas.php", "clase" => "ControladorVentas"],
  "reparaciones" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorReparaciones.php", "clase" => "ControladorReparaciones"],
  "configuracion" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorConfiguracion.php", "clase" => "ControladorConfiguracion"],
  "configuraciones" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorConfiguraciones.php", "clase" => "ControladorConfiguraciones"],
];
$controlador_modulo = [
  "clientes" => "clientes",
  "stock" => "stock",
  "productos" => "productos",
  "importacion" => "productos",
  "listas_precios" => "listas_precios",
  "exportaciones" => "exportaciones",
  "cuentas_corrientes" => "cuentas_corrientes",
  "reparaciones" => "reparaciones",
  "configuracion" => "configuraciones",
  "configuraciones" => "configuraciones",
  "usuarios" => "usuarios",
];
$modulo_actual = $controlador_modulo[$c] ?? "";
if ($c === "ventas") {
    $acciones_nueva = ["nueva", "agregar", "confirmar", "vaciar", "quitar", "editar_item", "actualizar_item", "ticket", "ticket_pdf", "presupuesto_ticket", "presupuesto_pdf", "impresoras_json", "aplicar_lista", "buscar_productos_json"];
    $modulo_actual = in_array($a, $acciones_nueva, true) ? "nueva_venta" : "ventas";
}
if ($c !== "auth" && $modulo_actual !== "" && esta_logueado() && !usuario_puede_modulo($modulo_actual)) {
    flash_error("No tenes permiso para acceder a esa seccion.");
    $destino = usuario_puede_modulo("ventas") ? "index.php?c=ventas&a=inicio" : (usuario_puede_modulo("nueva_venta") ? "index.php?c=ventas&a=nueva" : "index.php?c=auth&a=salir");
    redirigir($destino);
    exit;
}
$archivo="";
$clase="";
//preguntamos si existe el controlador en el mapa
if(isset($mapa[$c])){
    $archivo=$mapa[$c]["archivo"];
    $clase=$mapa[$c]["clase"];
}
//preguntamos si existe el controlador o archivo
if($archivo!=="" && file_exists($archivo)){
    require_once $archivo;
    $ctrl=new $clase();
    //preguntamos si la accion o funcion existe
    if(method_exists($ctrl,$a))
        $ctrl->$a();
    else
        echo "Accion no encontrada";
}else{
    echo "Controlador no encontrado";
}
