<?php
require_once __DIR__ . "/../aplicacion/modelos/Venta.php";
require_once __DIR__ . "/../aplicacion/modelos/FacturaFiscal.php";

$limite = 10;
if (isset($argv[1]) && is_numeric($argv[1]))
    $limite = max(1, (int)$argv[1]);

$resumen = FacturaFiscal::procesar_cola($limite);
echo "Procesados: " . $resumen["procesados"] . PHP_EOL;
echo "Aprobados: " . $resumen["aprobados"] . PHP_EOL;
echo "Pendientes: " . $resumen["pendientes"] . PHP_EOL;
echo "Errores: " . $resumen["errores"] . PHP_EOL;
