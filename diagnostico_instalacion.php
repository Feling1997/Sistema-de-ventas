<?php
require_once __DIR__ . "/configuraciones/base_datos.php";
require_once __DIR__ . "/configuraciones/ayudas.php";
require_once __DIR__ . "/aplicacion/modelos/ListaPrecio.php";

header("Content-Type: text/plain; charset=utf-8");

$root = __DIR__;
$json = __DIR__ . "/almacenamiento/configuracion_sistema.json";
$probe_dir = __DIR__ . "/almacenamiento";
$probe_file = $probe_dir . "/diagnostico_escritura.txt";
$log_app = __DIR__ . "/almacenamiento/logs/app.log";
$log_operaciones = __DIR__ . "/almacenamiento/logs/operaciones.log";
$log_php = __DIR__ . "/almacenamiento/logs/php_error.log";
$app_root = dirname(dirname(__DIR__));
$public_dir = getenv("PUBLIC") ?: "C:/Users/Public";
$log_instalador_publico = $public_dir . "/ventas_reparaciones_instalador.log";
$log_instalador = $app_root . "/logs/instalador.log";
$log_launcher = $app_root . "/logs/launcher.log";
$log_mysql = $app_root . "/mysql/data/mysql_error.log";
$log_apache = $app_root . "/apache/logs/error.log";
if (!is_dir(dirname($log_operaciones)))
    @mkdir(dirname($log_operaciones), 0777, true);

function diagnostico_tail(string $archivo, int $lineas = 20): array {
    if (!is_file($archivo))
        return [];
    $contenido = @file($archivo, FILE_IGNORE_NEW_LINES);
    if (!is_array($contenido))
        return [];
    return array_slice($contenido, -$lineas);
}

echo "ROOT=" . $root . PHP_EOL;
echo "PHP=" . PHP_VERSION . PHP_EOL;
echo "JSON=" . $json . PHP_EOL;
echo "JSON_EXISTE=" . (is_file($json) ? "SI" : "NO") . PHP_EOL;
echo "JSON_WRITABLE=" . (is_writable(is_file($json) ? $json : dirname($json)) ? "SI" : "NO") . PHP_EOL;
echo "LOG_APP=" . $log_app . PHP_EOL;
echo "LOG_OPERACIONES=" . $log_operaciones . PHP_EOL;
echo "LOG_PHP=" . $log_php . PHP_EOL;
echo "LOG_INSTALADOR_PUBLICO=" . $log_instalador_publico . PHP_EOL;
echo "LOG_INSTALADOR=" . $log_instalador . PHP_EOL;
echo "LOG_LAUNCHER=" . $log_launcher . PHP_EOL;
echo "LOG_MYSQL=" . $log_mysql . PHP_EOL;
echo "LOG_APACHE=" . $log_apache . PHP_EOL;
echo "LOG_DIR_WRITABLE=" . (is_writable(dirname($log_operaciones)) ? "SI" : "NO") . PHP_EOL;

if (!is_dir($probe_dir))
    @mkdir($probe_dir, 0777, true);
$ok_file = @file_put_contents($probe_file, "ok " . date("Y-m-d H:i:s")) !== false;
echo "WRITE_FILE=" . ($ok_file ? "OK" : "ERROR") . PHP_EOL;
registrar_operacion("diagnostico_instalacion.ejecutado", [
    "root" => $root,
    "json" => $json,
    "write_file" => $ok_file ? "OK" : "ERROR",
]);

$pdo = obtener_pdo();
echo "DB_CONEXION=" . ($pdo !== null ? "OK" : "ERROR") . PHP_EOL;
if ($pdo !== null) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS diagnostico_instalacion (
            id INT AUTO_INCREMENT PRIMARY KEY,
            creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            nota VARCHAR(80) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $st = $pdo->prepare("INSERT INTO diagnostico_instalacion (nota) VALUES (?)");
        $ok_db = $st->execute(["ok"]);
        echo "DB_INSERT=" . ($ok_db ? "OK" : "ERROR") . PHP_EOL;
        $row = $pdo->query("SELECT COUNT(*) AS total FROM diagnostico_instalacion")->fetch();
        echo "DB_TOTAL_DIAG=" . (int)($row["total"] ?? 0) . PHP_EOL;
        try {
            $listas = ListaPrecio::listar(false);
            echo "DB_LISTAS_TOTAL=" . count($listas) . PHP_EOL;
            foreach ($listas as $lista)
                echo "DB_LISTA=" . (int)($lista["id"] ?? 0) . "|" . (string)($lista["nombre"] ?? "") . "|" . (string)($lista["activo"] ?? "") . PHP_EOL;
        } catch (Throwable $e) {
            echo "DB_LISTAS_ERROR=" . $e->getMessage() . PHP_EOL;
        }
    } catch (Throwable $e) {
        echo "DB_INSERT=ERROR" . PHP_EOL;
        echo "DB_ERROR=" . $e->getMessage() . PHP_EOL;
    }
}

echo "CONFIG_NOMBRE=" . config("nombre_comercio", "") . PHP_EOL;
echo "CONFIG_AUTH_MODO=" . config("auth_modo", "") . PHP_EOL;
echo "CONFIG_NAVBAR_MARCA=" . config("navbar_marca_texto", "") . PHP_EOL;

echo "APP_LOG_TAIL=" . PHP_EOL;
foreach (diagnostico_tail($log_app, 12) as $linea)
    echo $linea . PHP_EOL;

echo "OPERACIONES_TAIL=" . PHP_EOL;
foreach (diagnostico_tail($log_operaciones, 30) as $linea)
    echo $linea . PHP_EOL;

echo "PHP_ERROR_TAIL=" . PHP_EOL;
foreach (diagnostico_tail($log_php, 30) as $linea)
    echo $linea . PHP_EOL;

echo "INSTALADOR_PUBLICO_TAIL=" . PHP_EOL;
foreach (diagnostico_tail($log_instalador_publico, 30) as $linea)
    echo $linea . PHP_EOL;

echo "INSTALADOR_TAIL=" . PHP_EOL;
foreach (diagnostico_tail($log_instalador, 30) as $linea)
    echo $linea . PHP_EOL;

echo "LAUNCHER_TAIL=" . PHP_EOL;
foreach (diagnostico_tail($log_launcher, 30) as $linea)
    echo $linea . PHP_EOL;

echo "MYSQL_TAIL=" . PHP_EOL;
foreach (diagnostico_tail($log_mysql, 30) as $linea)
    echo $linea . PHP_EOL;

echo "APACHE_TAIL=" . PHP_EOL;
foreach (diagnostico_tail($log_apache, 30) as $linea)
    echo $linea . PHP_EOL;
