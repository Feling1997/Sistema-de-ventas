<?php
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/ListaPrecio.php";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ModeloImportacion {
    private const MAX_FILAS_ANALISIS = 2000;
    private const MAX_PREVIEW = 80;

    public static function hojas(string $ruta): array {
        $hojas = [];
        $autoload = __DIR__ . "/../../vendor/autoload.php";
        if (is_file($autoload) && is_file($ruta)) {
            try {
                require_once $autoload;
                $reader = IOFactory::createReaderForFile($ruta);
                $hojas = $reader->listWorksheetNames($ruta);
            } catch (Throwable $e) {
                registrar_log("ModeloImportacion::hojas", $e->getMessage());
            }
        }
        return is_array($hojas) ? $hojas : [];
    }

    public static function analizar(string $ruta, int $indice_hoja = 0): array {
        $resultado = self::resultado_base();
        $autoload = __DIR__ . "/../../vendor/autoload.php";
        if (!is_file($autoload)) {
            $resultado["errores"][] = ["fila" => 0, "mensaje" => "PhpSpreadsheet no esta instalado."];
        } elseif (!is_file($ruta)) {
            $resultado["errores"][] = ["fila" => 0, "mensaje" => "Archivo no encontrado."];
        } else {
            try {
                require_once $autoload;
                $reader = IOFactory::createReaderForFile($ruta);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($ruta);
                $cantidad_hojas = $spreadsheet->getSheetCount();
                if ($indice_hoja < 0 || $indice_hoja >= $cantidad_hojas)
                    $indice_hoja = 0;
                $hoja = $spreadsheet->getSheet($indice_hoja);
                $resultado["hoja"] = $indice_hoja;
                $resultado["hoja_nombre"] = $hoja->getTitle();
                $resultado["hojas"] = $spreadsheet->getSheetNames();
                $resultado = self::analizar_hoja($hoja, $resultado);
                $spreadsheet->disconnectWorksheets();
            } catch (Throwable $e) {
                registrar_log("ModeloImportacion::analizar", $e->getMessage());
                $mensaje = self::mensaje_error_excel($e);
                $resultado["errores"][] = ["fila" => 0, "mensaje" => $mensaje];
            }
        }
        $resultado["resumen"]["advertencias"] = count($resultado["advertencias"]);
        $resultado["resumen"]["errores"] = count($resultado["errores"]);
        return $resultado;
    }

    public static function importar(string $ruta, int $indice_hoja, string $archivo_nombre, int $id_usuario): array {
        $resultado = self::analizar($ruta, $indice_hoja);
        $resultado["ok"] = false;
        $resultado["mensaje"] = "";
        $pdo = obtener_pdo();
        if ($pdo === null) {
            $resultado["mensaje"] = "Sin conexion a base de datos.";
            $resultado["errores"][] = ["fila" => 0, "mensaje" => $resultado["mensaje"]];
        } elseif (count($resultado["errores"]) > 0) {
            $resultado["mensaje"] = "El archivo tiene errores graves.";
        } else {
            try {
                self::asegurar_tablas($pdo);
                $resultado["resumen"]["nuevos"] = 0;
                $resultado["resumen"]["actualizados"] = 0;
                $resultado["resumen"]["omitidos"] = 0;
                $pdo->beginTransaction();
                foreach ($resultado["filas"] as &$fila) {
                    if ((string)$fila["accion"] === "Ignorar") {
                        $resultado["resumen"]["omitidos"]++;
                        continue;
                    }
                    $id_producto = (int)($fila["id_producto"] ?? 0);
                    $codigo = (string)$fila["codigo"];
                    $nombre = (string)$fila["nombre"];
                    if ($id_producto > 0) {
                        $st = $pdo->prepare("UPDATE productos SET nombre = ?, cod_barras = ? WHERE id = ?");
                        $st->execute([$nombre, $codigo, $id_producto]);
                        $resultado["resumen"]["actualizados"]++;
                        $fila["accion"] = "Actualizar";
                        $fila["detalle"][] = "Producto actualizado";
                    } else {
                        $precio_final = self::precio_final_para_producto($fila["precios"]);
                        $st = $pdo->prepare("INSERT INTO productos (nombre, cod_barras, id_stock, id_asociado, factor_conversion, ganancia, precio_final, activo) VALUES (?, ?, NULL, NULL, 1, 0, ?, 1)");
                        $st->execute([$nombre, $codigo, $precio_final]);
                        $id_producto = (int)$pdo->lastInsertId();
                        $fila["id_producto"] = $id_producto;
                        $resultado["resumen"]["nuevos"]++;
                        $fila["accion"] = "Nuevo";
                        $fila["detalle"][] = "Producto creado";
                    }
                    foreach ($fila["precios"] as $precio) {
                        $id_lista = (int)$precio["id_lista"];
                        $valor = (float)$precio["precio"];
                        $st_actual = $pdo->prepare("SELECT precio FROM producto_precios WHERE id_producto = ? AND id_lista = ? LIMIT 1");
                        $st_actual->execute([$id_producto, $id_lista]);
                        $actual = $st_actual->fetch();
                        $precio_anterior = $actual ? (float)$actual["precio"] : 0.0;
                        $st_precio = $pdo->prepare("INSERT INTO producto_precios (id_producto, id_lista, porcentaje, precio) VALUES (?, ?, 0, ?) ON DUPLICATE KEY UPDATE precio = VALUES(precio)");
                        $st_precio->execute([$id_producto, $id_lista, $valor]);
                        if (abs($precio_anterior - $valor) >= 0.01) {
                            $hist = $pdo->prepare("INSERT INTO historial_precios (id_producto, id_lista, precio_anterior, precio_nuevo, origen) VALUES (?, ?, ?, ?, 'importacion_excel')");
                            $hist->execute([$id_producto, $id_lista, $precio_anterior, $valor]);
                        }
                    }
                }
                unset($fila);
                self::guardar_log($pdo, $id_usuario, $archivo_nombre, $resultado["resumen"]);
                $pdo->commit();
                $resultado["ok"] = true;
                $resultado["mensaje"] = "Importacion completada.";
            } catch (Throwable $e) {
                if ($pdo->inTransaction())
                    $pdo->rollBack();
                registrar_log("ModeloImportacion::importar", $e->getMessage());
                $resultado["mensaje"] = "Error grave durante la importacion. No se guardaron cambios.";
                $resultado["errores"][] = ["fila" => 0, "mensaje" => $resultado["mensaje"]];
            }
        }
        $resultado["resumen"]["advertencias"] = count($resultado["advertencias"]);
        $resultado["resumen"]["errores"] = count($resultado["errores"]);
        return $resultado;
    }

    private static function resultado_base(): array {
        $resultado = [
            "ok" => true,
            "hoja" => 0,
            "hoja_nombre" => "",
            "hojas" => [],
            "columnas" => ["codigo" => "", "nombre" => "", "listas" => []],
            "filas" => [],
            "preview" => [],
            "advertencias" => [],
            "errores" => [],
            "resumen" => [
                "nuevos" => 0,
                "actualizados" => 0,
                "omitidos" => 0,
                "advertencias" => 0,
                "errores" => 0
            ]
        ];
        return $resultado;
    }

    private static function analizar_hoja(Worksheet $hoja, array $resultado): array {
        $highestRow = min((int)$hoja->getHighestDataRow(), self::MAX_FILAS_ANALISIS);
        $highestColumn = $hoja->getHighestDataColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        $encabezados = self::detectar_encabezados($hoja, $highestRow, $highestColumnIndex);
        if (empty($encabezados["fila"])) {
            $resultado["errores"][] = ["fila" => 0, "mensaje" => "No se encontro una fila de encabezados reconocible."];
        } else {
            $resultado["columnas"] = [
                "codigo" => (string)($encabezados["nombres"][$encabezados["codigo"]] ?? ""),
                "nombre" => (string)($encabezados["nombres"][$encabezados["nombre"]] ?? ""),
                "listas" => $encabezados["listas"]
            ];
            if (empty($encabezados["codigo"]))
                $resultado["errores"][] = ["fila" => (int)$encabezados["fila"], "mensaje" => "No se detecto columna de codigo."];
            if (empty($encabezados["nombre"]))
                $resultado["errores"][] = ["fila" => (int)$encabezados["fila"], "mensaje" => "No se detecto columna de nombre o descripcion."];
            if (count($resultado["errores"]) === 0)
                $resultado = self::analizar_filas($hoja, $highestRow, $highestColumnIndex, $encabezados, $resultado);
        }
        return $resultado;
    }

    private static function detectar_encabezados(Worksheet $hoja, int $highestRow, int $highestColumnIndex): array {
        $detectado = ["fila" => 0, "codigo" => 0, "nombre" => 0, "listas" => [], "nombres" => [], "desconocidas" => []];
        $listas = self::listas_normalizadas();
        $limite = min($highestRow, 30);
        for ($fila = 1; $fila <= $limite && (int)$detectado["fila"] === 0; $fila++) {
            $nombres = [];
            $codigo = 0;
            $nombre = 0;
            $listas_detectadas = [];
            $desconocidas = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $valor = self::celda_texto($hoja, $fila, $col);
                $nombres[$col] = $valor;
                $normal = self::normalizar_clave($valor);
                if ($normal !== "") {
                    if ($codigo === 0 && in_array($normal, ["codigo", "cod", "codigobarras", "ean", "plu", "codbarras"], true))
                        $codigo = $col;
                    if ($nombre === 0 && in_array($normal, ["nombre", "descripcion", "producto"], true))
                        $nombre = $col;
                    $id_lista = self::id_lista_por_normalizado($normal, $listas);
                    if ($id_lista > 0)
                        $listas_detectadas[$col] = ["id" => $id_lista, "nombre" => $listas[$id_lista]["nombre"], "columna" => $valor];
                    elseif (!in_array($normal, ["codigo", "cod", "codigobarras", "ean", "plu", "codbarras", "nombre", "descripcion", "producto"], true))
                        $desconocidas[$col] = $valor;
                }
            }
            if (($codigo > 0 && $nombre > 0) || ($nombre > 0 && count($listas_detectadas) > 0)) {
                $detectado = ["fila" => $fila, "codigo" => $codigo, "nombre" => $nombre, "listas" => $listas_detectadas, "nombres" => $nombres, "desconocidas" => $desconocidas];
            }
        }
        return $detectado;
    }

    private static function analizar_filas(Worksheet $hoja, int $highestRow, int $highestColumnIndex, array $encabezados, array $resultado): array {
        $productos = self::productos_por_codigo();
        $advertencias_listas = [];
        foreach ($encabezados["desconocidas"] as $col => $titulo) {
            if (self::columna_parece_lista($hoja, (int)$encabezados["fila"] + 1, $highestRow, (int)$col)) {
                $clave = self::normalizar_clave((string)$titulo);
                $advertencias_listas[$clave] = true;
                $resultado["advertencias"][] = ["fila" => (int)$encabezados["fila"], "mensaje" => "La lista " . trim((string)$titulo) . " no existe y fue ignorada"];
            }
        }
        $codigos_vistos = [];
        for ($fila = (int)$encabezados["fila"] + 1; $fila <= $highestRow; $fila++) {
            $fila_vacia = self::fila_vacia($hoja, $fila, $highestColumnIndex);
            $es_titulo_repetido = self::es_titulo_repetido($hoja, $fila, $encabezados);
            if (!$fila_vacia && !$es_titulo_repetido) {
                $codigo = self::limpiar_codigo(self::celda_texto($hoja, $fila, (int)$encabezados["codigo"]));
                $nombre = self::limpiar_nombre(self::celda_texto($hoja, $fila, (int)$encabezados["nombre"]));
                $detalle = [];
                $accion = "Nuevo";
                $id_producto = 0;
                if ($codigo === "") {
                    $accion = "Ignorar";
                    $detalle[] = "Codigo vacio";
                }
                if ($nombre === "") {
                    $accion = "Ignorar";
                    $detalle[] = "Nombre o descripcion vacio";
                }
                if ($accion !== "Ignorar" && isset($codigos_vistos[$codigo])) {
                    $accion = "Ignorar";
                    $detalle[] = "Codigo duplicado en el archivo";
                    $resultado["advertencias"][] = ["fila" => $fila, "mensaje" => "Codigo duplicado en el archivo"];
                }
                if ($accion !== "Ignorar")
                    $codigos_vistos[$codigo] = true;
                $accion_base = $accion;
                if ($accion !== "Ignorar") {
                    if (isset($productos[$codigo])) {
                        $accion = "Actualizar";
                        $accion_base = "Actualizar";
                        $id_producto = (int)$productos[$codigo]["id"];
                    }
                }
                $precios = [];
                foreach ($encabezados["listas"] as $col => $lista) {
                    $raw = self::celda_texto($hoja, $fila, (int)$col);
                    $precio = self::parsear_precio($raw);
                    if ($precio["estado"] === "ok")
                        $precios[] = ["id_lista" => (int)$lista["id"], "lista" => (string)$lista["nombre"], "precio" => (float)$precio["valor"]];
                    elseif ($precio["estado"] === "invalido") {
                        $detalle[] = "Precio invalido en lista " . (string)$lista["nombre"];
                        $resultado["advertencias"][] = ["fila" => $fila, "mensaje" => "Precio invalido en lista " . (string)$lista["nombre"]];
                    }
                }
                if ($accion !== "Ignorar" && count($detalle) > 0)
                    $accion = "Advertencia";
                $fila_resultado = [
                    "fila" => $fila,
                    "codigo" => $codigo,
                    "nombre" => $nombre,
                    "accion" => $accion,
                    "accion_base" => $accion_base,
                    "id_producto" => $id_producto,
                    "precios" => $precios,
                    "detalle" => $detalle
                ];
                $resultado["filas"][] = $fila_resultado;
                if (count($resultado["preview"]) < self::MAX_PREVIEW)
                    $resultado["preview"][] = $fila_resultado;
            }
        }
        foreach ($resultado["filas"] as $fila) {
            $accion_resumen = (string)($fila["accion_base"] ?? $fila["accion"]);
            if ($accion_resumen === "Nuevo")
                $resultado["resumen"]["nuevos"]++;
            elseif ($accion_resumen === "Actualizar")
                $resultado["resumen"]["actualizados"]++;
            elseif ($accion_resumen === "Ignorar")
                $resultado["resumen"]["omitidos"]++;
        }
        return $resultado;
    }

    private static function listas_normalizadas(): array {
        $mapa = [];
        foreach (ListaPrecio::listar(true) as $lista) {
            $id = (int)$lista["id"];
            $nombre = (string)($lista["nombre"] ?? "");
            $mapa[$id] = [
                "id" => $id,
                "nombre" => $nombre,
                "claves" => array_values(array_unique([
                    self::normalizar_clave($nombre),
                    self::normalizar_clave(preg_replace('/^lista\s+/i', '', $nombre) ?? $nombre),
                    self::normalizar_clave("lista " . $nombre)
                ]))
            ];
        }
        return $mapa;
    }

    private static function id_lista_por_normalizado(string $normal, array $listas): int {
        $id = 0;
        $normal_sin_lista = preg_replace('/^lista/', '', $normal) ?? $normal;
        foreach ($listas as $lista) {
            $claves = $lista["claves"] ?? [];
            if (in_array($normal, $claves, true) || in_array($normal_sin_lista, $claves, true)) {
                $id = (int)$lista["id"];
                break;
            }
        }
        return $id;
    }

    private static function productos_por_codigo(): array {
        $mapa = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $st = $pdo->query("SELECT id, cod_barras, nombre FROM productos WHERE cod_barras IS NOT NULL AND cod_barras <> ''");
                $rows = $st ? $st->fetchAll() : [];
                foreach ($rows as $row)
                    $mapa[(string)$row["cod_barras"]] = $row;
            } catch (Throwable $e) {
                registrar_log("ModeloImportacion::productos_por_codigo", $e->getMessage());
            }
        }
        return $mapa;
    }

    private static function celda_texto(Worksheet $hoja, int $fila, int $col): string {
        $texto = "";
        if ($fila > 0 && $col > 0) {
            try {
                $coordenada = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila;
                $cell = $hoja->getCell($coordenada);
                $valor = $cell->getCalculatedValue();
                if (SpreadsheetDate::isDateTime($cell) && is_numeric($valor))
                    $texto = date("Y-m-d", SpreadsheetDate::excelToTimestamp((float)$valor));
                elseif (is_scalar($valor) || $valor === null)
                    $texto = trim((string)$valor);
            } catch (Throwable $e) {
                registrar_log("ModeloImportacion::celda_texto", $e->getMessage());
            }
        }
        return preg_replace('/\s+/u', ' ', $texto) ?? $texto;
    }

    private static function fila_vacia(Worksheet $hoja, int $fila, int $highestColumnIndex): bool {
        $vacia = true;
        for ($col = 1; $col <= $highestColumnIndex && $vacia; $col++) {
            if (self::celda_texto($hoja, $fila, $col) !== "")
                $vacia = false;
        }
        return $vacia;
    }

    private static function es_titulo_repetido(Worksheet $hoja, int $fila, array $encabezados): bool {
        $repetido = false;
        $codigo = self::normalizar_clave(self::celda_texto($hoja, $fila, (int)$encabezados["codigo"]));
        $nombre = self::normalizar_clave(self::celda_texto($hoja, $fila, (int)$encabezados["nombre"]));
        $codigo_header = self::normalizar_clave((string)($encabezados["nombres"][$encabezados["codigo"]] ?? ""));
        $nombre_header = self::normalizar_clave((string)($encabezados["nombres"][$encabezados["nombre"]] ?? ""));
        if ($codigo !== "" && $nombre !== "" && $codigo === $codigo_header && $nombre === $nombre_header)
            $repetido = true;
        return $repetido;
    }

    private static function columna_parece_lista(Worksheet $hoja, int $desde, int $hasta, int $col): bool {
        $parece = false;
        $limite = min($hasta, $desde + 40);
        for ($fila = $desde; $fila <= $limite && !$parece; $fila++) {
            $precio = self::parsear_precio(self::celda_texto($hoja, $fila, $col));
            if ($precio["estado"] === "ok" || $precio["estado"] === "invalido")
                $parece = true;
        }
        return $parece;
    }

    private static function normalizar_clave(string $texto): string {
        $texto = trim($texto);
        $texto = strtr($texto, [
            "á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u", "ñ" => "n", "ü" => "u",
            "Á" => "a", "É" => "e", "Í" => "i", "Ó" => "o", "Ú" => "u", "Ñ" => "n", "Ü" => "u",
            "Ã¡" => "a", "Ã©" => "e", "Ã­" => "i", "Ã³" => "o", "Ãº" => "u", "Ã±" => "n"
        ]);
        $texto = mb_strtolower($texto, "UTF-8");
        $texto = preg_replace('/[^a-z0-9]+/u', '', $texto) ?? "";
        return $texto;
    }

    private static function limpiar_codigo(string $codigo): string {
        $codigo = trim($codigo);
        $codigo = preg_replace('/[^\pL\pN_\-\.]/u', '', $codigo) ?? "";
        return substr($codigo, 0, 80);
    }

    private static function limpiar_nombre(string $nombre): string {
        $nombre = trim(preg_replace('/\s+/u', ' ', $nombre) ?? $nombre);
        $nombre = preg_replace('/[\x00-\x1F\x7F]/u', '', $nombre) ?? "";
        return substr($nombre, 0, 180);
    }

    private static function parsear_precio(string $valor): array {
        $resultado = ["estado" => "vacio", "valor" => 0.0];
        $texto = trim($valor);
        $normal = self::normalizar_clave($texto);
        if ($texto !== "" && !in_array($normal, ["sinstock", "sinprecio", "n/a", "na"], true)) {
            $limpio = preg_replace('/[^0-9,\.\-]/', '', $texto) ?? "";
            if ($limpio === "" || $limpio === "-" || substr_count($limpio, "-") > 1) {
                $resultado["estado"] = "invalido";
            } else {
                $pos_punto = strrpos($limpio, ".");
                $pos_coma = strrpos($limpio, ",");
                if ($pos_punto !== false && $pos_coma !== false) {
                    if ($pos_coma > $pos_punto) {
                        $limpio = str_replace(".", "", $limpio);
                        $limpio = str_replace(",", ".", $limpio);
                    } else
                        $limpio = str_replace(",", "", $limpio);
                } elseif ($pos_coma !== false) {
                    $limpio = str_replace(".", "", $limpio);
                    $limpio = str_replace(",", ".", $limpio);
                } elseif (substr_count($limpio, ".") > 1) {
                    $ultimo = strrpos($limpio, ".");
                    $limpio = str_replace(".", "", substr($limpio, 0, $ultimo)) . substr($limpio, $ultimo);
                }
                if (is_numeric($limpio) && (float)$limpio >= 0) {
                    $resultado["estado"] = "ok";
                    $resultado["valor"] = round((float)$limpio, 2);
                } else
                    $resultado["estado"] = "invalido";
            }
        }
        return $resultado;
    }

    private static function precio_final_para_producto(array $precios): float {
        $precio = 0.0;
        foreach ($precios as $p) {
            if ((float)$p["precio"] > 0) {
                $precio = (float)$p["precio"];
                break;
            }
        }
        return $precio;
    }

    private static function asegurar_tablas(PDO $pdo): void {
        ListaPrecio::asegurar_tablas();
        $pdo->exec("CREATE TABLE IF NOT EXISTS importaciones_productos_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            id_usuario INT NULL,
            archivo VARCHAR(255) NOT NULL,
            nuevos INT NOT NULL DEFAULT 0,
            actualizados INT NOT NULL DEFAULT 0,
            omitidos INT NOT NULL DEFAULT 0,
            KEY idx_importaciones_productos_fecha (fecha)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private static function guardar_log(PDO $pdo, int $id_usuario, string $archivo, array $resumen): void {
        $st = $pdo->prepare("INSERT INTO importaciones_productos_log (id_usuario, archivo, nuevos, actualizados, omitidos) VALUES (?, ?, ?, ?, ?)");
        $st->execute([
            $id_usuario > 0 ? $id_usuario : null,
            substr($archivo, 0, 255),
            (int)($resumen["nuevos"] ?? 0),
            (int)($resumen["actualizados"] ?? 0),
            (int)($resumen["omitidos"] ?? 0)
        ]);
    }

    private static function mensaje_error_excel(Throwable $e): string {
        $mensaje = "No se pudo leer el Excel.";
        $txt = $e->getMessage();
        if (stripos($txt, "ZipArchive") !== false || stripos($txt, "zip") !== false)
            $mensaje = "No se pudo leer .xlsx/.xlsm porque la extension zip de PHP no esta habilitada.";
        return $mensaje;
    }
}
