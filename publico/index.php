<?php

require_once __DIR__ . "/../configuraciones/ayudas.php";
require_once __DIR__ . "/../configuraciones/base_datos.php";
require_once __DIR__ . "/../configuraciones/seguridad.php";
require_once __DIR__ . "/../configuraciones/csrf.php";
$c=obtener_get("c","auth");//controlador
$a=obtener_get("a","login");//accion
$mapa=[
  "auth" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorAuth.php", "clase" => "ControladorAuth"],
  "usuarios" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorUsuarios.php", "clase" => "ControladorUsuarios"],
  "clientes" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorClientes.php", "clase" => "ControladorClientes"],
  "stock" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorStock.php", "clase" => "ControladorStock"],
  "productos" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorProductos.php", "clase" => "ControladorProductos"],
  "listas_precios" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorListasPrecios.php", "clase" => "ControladorListasPrecios"],
  "exportaciones" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorExportaciones.php", "clase" => "ControladorExportaciones"],
  "cuentas_corrientes" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorCuentasCorrientes.php", "clase" => "ControladorCuentasCorrientes"],
  "ventas" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorVentas.php", "clase" => "ControladorVentas"],
  "reparaciones" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorReparaciones.php", "clase" => "ControladorReparaciones"],
  "configuraciones" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorConfiguraciones.php", "clase" => "ControladorConfiguraciones"],
];
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
