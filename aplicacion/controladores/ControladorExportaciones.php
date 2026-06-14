<?php
require_once __DIR__ . "/../modelos/Stock.php";
require_once __DIR__ . "/../modelos/ListaPrecio.php";
require_once __DIR__ . "/../modelos/Venta.php";
require_once __DIR__ . "/../modelos/ConfiguracionSistema.php";
require_once __DIR__ . "/../modelos/Producto.php";
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";

class ControladorExportaciones {
    private function permiso(): bool {
        if (!require_login()) {
            flash_error("Tenes que iniciar sesion.");
            redirigir("index.php?c=auth&a=login");
            return false;
        }
        if (!require_rol(["ADMIN", "VENDEDOR"])) {
            flash_error("No tenes permisos para exportaciones.");
            redirigir("index.php?c=ventas&a=lista");
            return false;
        }
        return true;
    }

    public function index(): void {
        if ($this->permiso()) {
            $listas = ListaPrecio::listar(true);
            $stocks = Stock::listar_todos();
            usort($listas, fn($a, $b) => strcasecmp((string)($a["nombre"] ?? ""), (string)($b["nombre"] ?? "")));
            usort($stocks, fn($a, $b) => strcasecmp((string)($a["nombre"] ?? ""), (string)($b["nombre"] ?? "")));
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/exportaciones/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function ir(): void {
        if ($this->permiso()) {
            $reporte = strtolower((string)obtener_get("reporte", ""));
            $formato = $this->formato();
            if (str_starts_with($reporte, "estadisticas_")) {
                $tipo = substr($reporte, strlen("estadisticas_"));
                $query = [
                    "c" => "exportaciones",
                    "a" => "estadisticas",
                    "tipo" => $tipo,
                    "fecha_desde" => trim((string)obtener_get("fecha_desde", "")),
                    "fecha_hasta" => trim((string)obtener_get("fecha_hasta", "")),
                    "orden" => trim((string)obtener_get("orden", "")),
                    "limite" => max(0, (int)obtener_get("limite", 0)),
                    "formato" => $formato,
                ];
                redirigir("index.php?" . http_build_query($query));
                return;
            }
            if ($reporte === "cambios_precios") {
                $this->exportar_cambios_precios(
                    trim((string)obtener_get("fecha_desde", "")),
                    trim((string)obtener_get("fecha_hasta", "")),
                    max(0, (int)obtener_get("id_lista_precio", 0)),
                    $formato
                );
                return;
            }
            if ($reporte === "stock_actual")
                redirigir("index.php?c=stock&a=exportar&formato=" . urlencode($formato));
            else if ($reporte === "pedido_minimo")
                redirigir("index.php?c=stock&a=exportar_faltantes&solo_minimo=1&formato=" . urlencode($formato));
            else if ($reporte === "faltantes_completo")
                redirigir("index.php?c=stock&a=exportar_faltantes&solo_minimo=0&formato=" . urlencode($formato));
            else if ($reporte === "productos_stock") {
                $id = max(0, (int)obtener_get("id_stock", 0));
                $id_lista = max(0, (int)obtener_get("id_lista_precio", 0));
                if ($id_lista <= 0) {
                    flash_error("Selecciona una lista de precios cargada.");
                    redirigir("index.php?c=exportaciones&a=index");
                    return;
                }
                redirigir("index.php?c=stock&a=exportar_productos&id=" . $id . "&id_lista_precio=" . $id_lista . "&formato=" . urlencode($formato));
            } else if ($reporte === "articulos") {
                $alcance = (string)obtener_get("alcance", "alta") === "todos" ? "todos" : "alta";
                $id_lista = max(0, (int)obtener_get("id_lista_precio", 0));
                if ($id_lista <= 0) {
                    flash_error("Selecciona una lista de precios cargada.");
                    redirigir("index.php?c=exportaciones&a=index");
                    return;
                }
                redirigir("index.php?c=productos&a=exportar_altas&alcance=" . $alcance . "&id_lista_precio=" . $id_lista . "&formato=" . urlencode($formato));
            } else if ($reporte === "lista_precios") {
                $id_lista = max(0, (int)obtener_get("id_lista_precio", 0));
                if ($id_lista <= 0) {
                    flash_error("Selecciona una lista de precios cargada.");
                    redirigir("index.php?c=exportaciones&a=index");
                    return;
                }
                redirigir("index.php?c=listas_precios&a=exportar&id=" . $id_lista . "&formato=" . urlencode($formato));
            } else if ($reporte === "balanza") {
                $id_lista = max(0, (int)obtener_get("id_lista_precio", 0));
                if ($id_lista <= 0) {
                    flash_error("Selecciona una lista de precios cargada.");
                    redirigir("index.php?c=exportaciones&a=index");
                    return;
                }
                $this->exportar_balanza($id_lista, "csv");
            } else {
                flash_error("Selecciona un reporte para exportar.");
                redirigir("index.php?c=exportaciones&a=index");
            }
        }
    }

    private function exportar_balanza(int $id_lista, string $formato): void {
        $config = ConfiguracionSistema::obtener();
        $plu_digitos = max(1, min(8, (int)($config["balanza_plu_digitos"] ?? 5)));
        $nombre_lista = "";
        foreach (ListaPrecio::listar(true) as $lista) {
            if ((int)$lista["id"] === $id_lista) {
                $nombre_lista = (string)$lista["nombre"];
                break;
            }
        }
        if ($nombre_lista === "") {
            flash_error("Selecciona una lista de precios cargada.");
            redirigir("index.php?c=exportaciones&a=index");
            return;
        }

        $rows = [];
        $filas = "";
        foreach (ListaPrecio::productos_para_exportar($id_lista) as $producto) {
            $codigo = preg_replace('/\D+/', '', (string)($producto["cod_barras"] ?? "")) ?? "";
            $precio = (float)($producto["precio_lista"] ?? 0);
            if ($codigo === "" || $precio <= 0)
                continue;
            $plu = str_pad(substr($codigo, -$plu_digitos), $plu_digitos, "0", STR_PAD_LEFT);
            $unidad = strtolower(trim((string)($producto["unidad"] ?? "")));
            $tipo = in_array($unidad, ["kg", "kilo", "kilos", "g", "gr", "gramo", "gramos"], true) ? "PESABLE" : "UNITARIO";
            $nombre = trim((string)($producto["nombre"] ?? ""));
            $descripcion = mb_substr($nombre, 0, 40, "UTF-8");
            $row = [
                "Categoria" => "",
                "PLU" => $plu,
                "Producto" => $descripcion,
                "PLU_2" => $plu,
                "Precio de venta  x Kg" => numero_precio_para_exportar($precio, 2),
                "Precio Fraccionado" => numero_precio_para_exportar($precio, 2),
                "Unidad de Venta" => $tipo === "PESABLE" ? "Kg" : "Unidad",
                "Vencimiento" => "180",
            ];
            $rows[] = $row;
            $filas .= "<tr><td></td><td>" . htmlspecialchars($plu) . "</td><td>" . htmlspecialchars($descripcion) . "</td><td>" . htmlspecialchars($plu) . "</td><td class='num'>" . htmlspecialchars(precio_para_mostrar($precio)) . "</td><td class='num'>" . htmlspecialchars(precio_para_mostrar($precio)) . "</td><td>" . htmlspecialchars($tipo === "PESABLE" ? "Kg" : "Unidad") . "</td><td class='num'>180</td></tr>";
        }

        $headers = ["Categoria", "PLU", "Producto", "PLU", "Precio de venta  x Kg", "Precio Fraccionado", "Unidad de Venta", "Vencimiento"];
        $this->responder_csv_limpio("balanza_plu_" . $nombre_lista, $headers, $rows);
    }

    private function responder_csv_limpio(string $archivo, array $headers, array $rows): void {
        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"" . $this->nombre_archivo($archivo, "csv") . "\"");
        $out = fopen("php://output", "w");
        if ($out !== false) {
            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ";");
            foreach ($rows as $row)
                fputcsv($out, array_values($row), ";");
            fclose($out);
        }
    }

    private function exportar_cambios_precios(string $desde, string $hasta, int $id_lista, string $formato): void {
        $rows = ListaPrecio::historial_precios($desde, $hasta, $id_lista);
        $nombre_lista = $id_lista > 0 ? $this->nombre_lista($id_lista) : "";
        $filas = "";
        $csv = [];
        foreach ($rows as $r) {
            $filas .= "<tr><td>" . htmlspecialchars((string)$r["creado_en"]) . "</td><td>" . htmlspecialchars((string)$r["lista"]) . "</td><td>" . htmlspecialchars((string)$r["codigo"]) . "</td><td>" . htmlspecialchars((string)$r["producto"]) . "</td><td class='num'>" . htmlspecialchars(precio_para_mostrar($r["precio_anterior"] ?? 0)) . "</td><td class='num'>" . htmlspecialchars(precio_para_mostrar($r["precio_nuevo"] ?? 0)) . "</td><td>" . htmlspecialchars((string)$r["origen"]) . "</td></tr>";
            $csv[] = [
                "Fecha" => (string)$r["creado_en"],
                "Lista" => (string)$r["lista"],
                "Codigo/PLU" => (string)$r["codigo"],
                "Producto" => (string)$r["producto"],
                "Precio anterior" => numero_precio_para_exportar($r["precio_anterior"] ?? 0, 2),
                "Precio nuevo" => numero_precio_para_exportar($r["precio_nuevo"] ?? 0, 2),
                "Origen" => (string)$r["origen"],
            ];
        }
        $subtitulo = $this->subtitulo_periodo($desde, $hasta) . ($id_lista > 0 ? " | Lista " . $nombre_lista : " | Todas las listas");
        $archivo = $id_lista > 0 && $nombre_lista !== "" ? "cambios_precios_" . $nombre_lista : "cambios_precios_todas_las_listas";
        $headers = ["Fecha", "Lista", "Codigo/PLU", "Producto", "Precio anterior", "Precio nuevo", "Origen"];
        $this->responder($archivo, "Cambios de precios", $subtitulo, $headers, $filas, 7, $csv, $formato);
    }

    public function importar_balanza(): void {
        if (!$this->permiso())
            return;
        flash_error("La balanza es solo para exportacion. Para importar precios usa Importar listas de precios desde Excel.");
        redirigir("index.php?c=exportaciones&a=index");
    }

    public function importar_articulos_excel(): void {
        if (!$this->permiso())
            return;
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido((string)obtener_post("csrf", ""))) {
            flash_error("Acceso invalido.");
            redirigir("index.php?c=exportaciones&a=index");
            return;
        }
        if (empty($_FILES["archivo_articulos"]) || !is_array($_FILES["archivo_articulos"]) || (int)($_FILES["archivo_articulos"]["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash_error("Selecciona un archivo CSV exportado desde Excel.");
            redirigir("index.php?c=exportaciones&a=index");
            return;
        }
        $tmp = (string)($_FILES["archivo_articulos"]["tmp_name"] ?? "");
        $nombre_archivo = (string)($_FILES["archivo_articulos"]["name"] ?? "");
        $importar_disponibles = (int)obtener_post("importar_disponibles", 1) === 1;
        $crear_productos = (int)obtener_post("crear_productos", 0) === 1;
        $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
        if (in_array($extension, ["xlsx", "xlsm"], true) && !class_exists("ZipArchive")) {
            flash_error("Para importar ." . $extension . " falta habilitar ZIP en PHP. Guarda el Excel como CSV y volve a importarlo.");
            redirigir("index.php?c=exportaciones&a=index");
            return;
        }
        $resultado = $this->importar_csv_articulos_completo($tmp, $nombre_archivo, $importar_disponibles, $crear_productos);
        if (!empty($resultado["columnas_sin_lista"]))
            flash_error("Columnas de precios sin lista cargada: " . implode(", ", $resultado["columnas_sin_lista"]) . ". Crealas en Listas de precios o corregi el nombre de la columna.");
        if (!empty($resultado["productos_no_encontrados"])) {
            $muestra = array_slice(array_unique($resultado["productos_no_encontrados"]), 0, 12);
            flash_error("Productos no encontrados, no se crearon: " . implode(", ", $muestra) . (count($resultado["productos_no_encontrados"]) > 12 ? "..." : "") . ". Marca Crear productos que no existen si queres cargarlos desde el archivo.");
        }
        if (!empty($resultado["requiere_confirmacion"])) {
            flash_error("No se importo nada porque el archivo tiene columnas sin lista cargada. Marca la opcion de importar los datos disponibles si queres cargar solo las listas que coinciden.");
            redirigir("index.php?c=exportaciones&a=index");
            return;
        }
        flash_ok(
            "Importacion finalizada. Productos nuevos: " . $resultado["productos_creados"] .
            " | Productos actualizados: " . $resultado["productos_actualizados"] .
            " | Codigos generados: " . $resultado["codigos_generados"] .
            " | Precios cargados: " . $resultado["precios_cargados"] .
            " | Sin cambios: " . $resultado["sin_cambios"] .
            " | Omitidos: " . $resultado["omitidos"]
        );
        redirigir("index.php?c=exportaciones&a=index");
    }

    private function importar_csv_articulos_completo(string $archivo, string $nombre_archivo = "", bool $importar_disponibles = true, bool $crear_productos = false): array {
        $res = [
            "productos_creados" => 0,
            "productos_actualizados" => 0,
            "precios_cargados" => 0,
            "sin_cambios" => 0,
            "omitidos" => 0,
            "codigos_generados" => 0,
            "productos_no_encontrados" => [],
            "columnas_sin_lista" => [],
            "requiere_confirmacion" => false,
        ];
        $pdo = obtener_pdo();
        if ($pdo === null)
            return $res;
        ListaPrecio::asegurar_tablas();
        Stock::asegurar_columnas_minmax();

        $filas = $this->leer_filas_importacion($archivo, $nombre_archivo);
        if (count($filas) === 0)
            return $res;
        $headers_originales = array_shift($filas);
        if (!is_array($headers_originales) || count($headers_originales) === 0)
            return $res;
        $headers_originales = array_map(fn($h) => trim($this->normalizar_texto_csv((string)$h)), $headers_originales);
        $headers = array_map(fn($h) => $this->normalizar_header($h), $headers_originales);
        $listas_por_nombre = $this->listas_importacion_por_nombre();
        $columnas = $this->mapear_columnas_importacion($headers, $headers_originales, $listas_por_nombre);
        $res["columnas_sin_lista"] = $columnas["columnas_sin_lista"];
        if (!$importar_disponibles && count($res["columnas_sin_lista"]) > 0) {
            $res["requiere_confirmacion"] = true;
            return $res;
        }
        if ($columnas["nombre"] < 0 && $columnas["codigo"] < 0) {
            $res["omitidos"]++;
            return $res;
        }
        if (count($columnas["listas"]) === 0) {
            $res["omitidos"] = count($filas);
            return $res;
        }
        $id_lista_costo = $this->id_lista_costo_importacion();

        try {
            $pdo->beginTransaction();
            foreach ($filas as $row) {
                $row = array_map(fn($v) => trim($this->normalizar_texto_csv((string)$v)), $row);
                $nombre = $this->valor_por_indice($row, $columnas["nombre"]);
                $codigo = preg_replace('/\D+/', '', $this->valor_por_indice($row, $columnas["codigo"])) ?? "";
                if ($nombre === "" && $codigo === "") {
                    $res["omitidos"]++;
                    continue;
                }

                $producto = $codigo !== "" ? $this->buscar_producto_por_codigo_importacion($codigo) : null;
                if ($producto === null && $nombre !== "")
                    $producto = $this->buscar_producto_por_nombre_importacion($nombre);
                if ($producto === null) {
                    if (!$crear_productos || $nombre === "") {
                        $res["productos_no_encontrados"][] = $codigo !== "" ? (($nombre !== "" ? $nombre : "Sin descripcion") . " [" . $codigo . "]") : $nombre;
                        $res["omitidos"]++;
                        continue;
                    }
                    if ($codigo === "") {
                        $codigo = $this->generar_codigo_barras_importacion();
                        $res["codigos_generados"]++;
                    }
                    $unidad = $this->valor_por_indice($row, $columnas["unidad"]);
                    if ($unidad === "")
                        $unidad = "u";
                    $cantidad = max(0, $this->parsear_numero_importacion($this->valor_por_indice($row, $columnas["stock"]), 0));
                    $costo = $this->precio_desde_lista_importacion($row, $columnas, $id_lista_costo);
                    if ($costo <= 0)
                        $costo = $this->parsear_numero_importacion($this->valor_por_indice($row, $columnas["costo"]), 0);
                    $precio_final = $this->primer_precio_importacion($row, $columnas);
                    if ($precio_final <= 0)
                        $precio_final = $costo;
                    $id_stock = Stock::crear_retornar_id($nombre, $unidad, $cantidad, $costo, 1, 0, 0);
                    $id_producto = Producto::crear_retornar_id($nombre, $codigo, $id_stock > 0 ? $id_stock : null, 1, 0, $precio_final, 1);
                    if ($id_producto <= 0) {
                        $res["productos_no_encontrados"][] = $codigo !== "" ? ($nombre . " [" . $codigo . "]") : $nombre;
                        $res["omitidos"]++;
                        continue;
                    }
                    $res["productos_creados"]++;
                } else {
                    if ($codigo === "") {
                        $codigo_existente = preg_replace('/\D+/', '', (string)($producto["cod_barras"] ?? "")) ?? "";
                        if ($codigo_existente !== "")
                            $codigo = $codigo_existente;
                        else {
                            $codigo = $this->generar_codigo_barras_importacion();
                            $res["codigos_generados"]++;
                        }
                    }

                    $id_producto = (int)$producto["id"];
                    if ($codigo !== preg_replace('/\D+/', '', (string)($producto["cod_barras"] ?? "")))
                        $this->actualizar_codigo_producto_importacion($id_producto, $codigo);
                    $res["productos_actualizados"]++;
                }

                $costo_importado = $this->precio_desde_lista_importacion($row, $columnas, $id_lista_costo);
                if ($costo_importado > 0 && $producto !== null)
                    $this->actualizar_costo_stock_producto_importacion($producto, $costo_importado);

                $precios_cargados = 0;
                foreach ($columnas["listas"] as $idx => $id_lista) {
                    $precio = $this->parsear_numero_importacion($this->valor_por_indice($row, $idx), 0);
                    if ($precio <= 0)
                        continue;
                    $porcentaje = 0.0;
                    if ((int)$id_lista !== $id_lista_costo && $costo_importado > 0)
                        $porcentaje = (($precio / $costo_importado) - 1) * 100;
                    if (ListaPrecio::guardar_precio_producto_origen($id_producto, $id_lista, $porcentaje, $precio, "importacion_excel")) {
                        $precios_cargados++;
                        $res["precios_cargados"]++;
                    }
                }
                if ($precios_cargados === 0)
                    $res["sin_cambios"]++;
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            registrar_log("Exportaciones::importar_articulos_excel", $e->getMessage());
        }
        return $res;
    }

    private function leer_filas_importacion(string $archivo, string $nombre_archivo = ""): array {
        $ext = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
        if (in_array($ext, ["xlsx", "xlsm"], true)) {
            $filas = $this->leer_xlsx_simple($archivo);
            if (count($filas) > 0)
                return $filas;
        }
        return $this->leer_csv_importacion($archivo);
    }

    private function leer_csv_importacion(string $archivo): array {
        $filas = [];
        $fh = @fopen($archivo, "r");
        if ($fh === false)
            return [];
        $primera = fgets($fh);
        if ($primera === false) {
            fclose($fh);
            return [];
        }
        $delimitador = $this->detectar_delimitador_csv($primera);
        rewind($fh);
        while (($row = fgetcsv($fh, 0, $delimitador)) !== false)
            $filas[] = $row;
        fclose($fh);
        return $filas;
    }

    private function leer_xlsx_simple(string $archivo): array {
        if (!class_exists("ZipArchive"))
            return [];
        $zip = new ZipArchive();
        if ($zip->open($archivo) !== true)
            return [];
        $shared = [];
        $shared_xml = $zip->getFromName("xl/sharedStrings.xml");
        if (is_string($shared_xml) && $shared_xml !== "") {
            $xml = @simplexml_load_string($shared_xml);
            if ($xml !== false) {
                foreach ($xml->si as $si) {
                    $texto = "";
                    if (isset($si->t))
                        $texto = (string)$si->t;
                    else if (isset($si->r)) {
                        foreach ($si->r as $run)
                            $texto .= (string)$run->t;
                    }
                    $shared[] = $texto;
                }
            }
        }
        $sheet_path = "xl/worksheets/sheet1.xml";
        $sheet_xml = $zip->getFromName($sheet_path);
        if (!is_string($sheet_xml) || $sheet_xml === "") {
            $zip->close();
            return [];
        }
        $xml = @simplexml_load_string($sheet_xml);
        $zip->close();
        if ($xml === false)
            return [];
        $filas = [];
        foreach ($xml->sheetData->row as $row_xml) {
            $fila = [];
            foreach ($row_xml->c as $cell) {
                $ref = (string)($cell["r"] ?? "");
                $idx = $this->indice_columna_excel($ref);
                $tipo = (string)($cell["t"] ?? "");
                $valor = "";
                if ($tipo === "s") {
                    $shared_idx = (int)($cell->v ?? -1);
                    $valor = $shared[$shared_idx] ?? "";
                } else if ($tipo === "inlineStr") {
                    $valor = (string)($cell->is->t ?? "");
                } else {
                    $valor = (string)($cell->v ?? "");
                }
                if ($idx < 0)
                    $idx = count($fila);
                $fila[$idx] = $valor;
            }
            if (count($fila) > 0) {
                ksort($fila);
                $max = max(array_keys($fila));
                $completa = [];
                for ($i = 0; $i <= $max; $i++)
                    $completa[$i] = $fila[$i] ?? "";
                $filas[] = $completa;
            }
        }
        return $filas;
    }

    private function indice_columna_excel(string $ref): int {
        if (!preg_match('/^([A-Z]+)/i', $ref, $m))
            return -1;
        $letras = strtoupper($m[1]);
        $idx = 0;
        for ($i = 0; $i < strlen($letras); $i++)
            $idx = ($idx * 26) + (ord($letras[$i]) - 64);
        return $idx - 1;
    }

    private function detectar_delimitador_csv(string $linea): string {
        $candidatos = [";" => substr_count($linea, ";"), "," => substr_count($linea, ","), "\t" => substr_count($linea, "\t")];
        arsort($candidatos);
        $delim = (string)array_key_first($candidatos);
        return $delim !== "" ? $delim : ";";
    }

    private function normalizar_texto_csv(string $valor): string {
        $valor = preg_replace('/^\xEF\xBB\xBF/', '', $valor) ?? $valor;
        if (!mb_check_encoding($valor, "UTF-8")) {
            $convertido = @mb_convert_encoding($valor, "UTF-8", "Windows-1252");
            if (is_string($convertido))
                $valor = $convertido;
        }
        return trim($valor);
    }

    private function header_clave_importacion(string $header): string {
        $header = strtolower(trim($header));
        $header = str_replace("mayotista", "mayorista", $header);
        $header = str_replace(["á", "é", "í", "ó", "ú", "ñ"], ["a", "e", "i", "o", "u", "n"], $header);
        $header = preg_replace('/[^a-z0-9]+/', ' ', $header) ?? $header;
        return trim(preg_replace('/\s+/', ' ', $header) ?? $header);
    }

    private function listas_importacion_por_nombre(): array {
        $res = [];
        foreach (ListaPrecio::listar(true) as $lista) {
            $clave = $this->header_clave_importacion((string)($lista["nombre"] ?? ""));
            if ($clave !== "")
                $res[$clave] = (int)$lista["id"];
        }
        return $res;
    }

    private function claves_posibles_lista_importacion(string $nombre_columna): array {
        $base = trim($nombre_columna);
        $variantes = [
            $base,
            preg_replace('/^lista\s+/i', '', $base) ?? $base,
            preg_replace('/^precio\s+/i', '', $base) ?? $base,
            preg_replace('/^precio\s+de\s+/i', '', $base) ?? $base,
            preg_replace('/^precio\s+lista\s+/i', '', $base) ?? $base,
        ];
        $claves = [];
        foreach ($variantes as $variante) {
            $clave = $this->header_clave_importacion((string)$variante);
            if ($clave !== "")
                $claves[] = $clave;
        }
        return array_values(array_unique($claves));
    }

    private function mapear_columnas_importacion(array $headers, array $headers_originales, array $listas_por_nombre): array {
        $map = [
            "codigo" => -1,
            "nombre" => -1,
            "unidad" => -1,
            "stock" => -1,
            "stock_minimo" => -1,
            "stock_maximo" => -1,
            "costo" => -1,
            "factor" => -1,
            "ganancia" => -1,
            "activo" => -1,
            "listas" => [],
            "columnas_sin_lista" => [],
        ];
        $conocidas = [];
        foreach ($headers as $idx => $header) {
            $clave = $this->header_clave_importacion((string)$header);
            if (isset($listas_por_nombre[$clave]))
                continue;
            if (in_array($clave, ["codigo", "cod barras", "codigo barras", "codigo de barras", "barcode", "ean", "plu"], true)) {
                $map["codigo"] = $idx;
                $conocidas[$idx] = true;
            } else if (in_array($clave, ["producto", "articulo", "nombre", "descripcion", "detalle"], true)) {
                $map["nombre"] = $idx;
                $conocidas[$idx] = true;
            } else if (in_array($clave, ["unidad", "unidad de venta", "medida"], true)) {
                $map["unidad"] = $idx;
                $conocidas[$idx] = true;
            } else if (in_array($clave, ["stock", "cantidad", "existencia", "existencias", "cantidad actual"], true)) {
                $map["stock"] = $idx;
                $conocidas[$idx] = true;
            } else if (in_array($clave, ["stock minimo", "minimo", "cantidad minima"], true)) {
                $map["stock_minimo"] = $idx;
                $conocidas[$idx] = true;
            } else if (in_array($clave, ["stock maximo", "maximo", "cantidad maxima"], true)) {
                $map["stock_maximo"] = $idx;
                $conocidas[$idx] = true;
            } else if (in_array($clave, ["costo stock", "costo unitario stock"], true)) {
                $map["costo"] = $idx;
                $conocidas[$idx] = true;
            } else if (in_array($clave, ["factor", "factor conversion", "conversion"], true)) {
                $map["factor"] = $idx;
                $conocidas[$idx] = true;
            } else if (in_array($clave, ["ganancia", "margen", "porcentaje", "utilidad"], true)) {
                $map["ganancia"] = $idx;
                $conocidas[$idx] = true;
            } else if (in_array($clave, ["activo", "alta", "estado"], true)) {
                $map["activo"] = $idx;
                $conocidas[$idx] = true;
            }
        }

        foreach ($headers as $idx => $header) {
            if (isset($conocidas[$idx]))
                continue;
            $clave = $this->header_clave_importacion((string)$header);
            if ($clave === "" || in_array($clave, ["categoria", "rubro", "familia", "marca", "proveedor", "observacion", "observaciones"], true))
                continue;
            $nombre_columna = trim((string)($headers_originales[$idx] ?? $header));
            foreach ($this->claves_posibles_lista_importacion($nombre_columna) as $clave_lista) {
                if (isset($listas_por_nombre[$clave_lista])) {
                    $map["listas"][$idx] = (int)$listas_por_nombre[$clave_lista];
                    continue 2;
                }
            }
            $map["columnas_sin_lista"][] = $nombre_columna;
        }
        $map["columnas_sin_lista"] = array_values(array_unique($map["columnas_sin_lista"]));
        return $map;
    }

    private function valor_por_indice(array $row, int $idx): string {
        if ($idx < 0 || !isset($row[$idx]))
            return "";
        return trim((string)$row[$idx]);
    }

    private function parsear_numero_importacion(string $valor, float $defecto = 0.0): float {
        $texto = trim($valor);
        if ($texto === "")
            return $defecto;
        $texto = preg_replace('/[^0-9,.\-]+/', '', $texto) ?? "";
        if ($texto === "" || $texto === "-")
            return $defecto;
        return parsear_numero_form($texto, $defecto);
    }

    private function id_lista_costo_importacion(): int {
        foreach (ListaPrecio::listar(true) as $lista) {
            if ($this->header_clave_importacion((string)($lista["nombre"] ?? "")) === "costo")
                return (int)$lista["id"];
        }
        return 0;
    }

    private function precio_desde_lista_importacion(array $row, array $columnas, int $id_lista): float {
        if ($id_lista <= 0)
            return 0.0;
        foreach ($columnas["listas"] as $idx => $lista_columna) {
            if ((int)$lista_columna !== $id_lista)
                continue;
            return $this->parsear_numero_importacion($this->valor_por_indice($row, (int)$idx), 0);
        }
        return 0.0;
    }

    private function primer_precio_importacion(array $row, array $columnas): float {
        foreach ($columnas["listas"] as $idx => $_id_lista) {
            $precio = $this->parsear_numero_importacion($this->valor_por_indice($row, (int)$idx), 0);
            if ($precio > 0)
                return $precio;
        }
        return 0.0;
    }

    private function buscar_producto_por_codigo_importacion(string $codigo): ?array {
        $codigo = preg_replace('/\D+/', '', $codigo) ?? "";
        if ($codigo === "")
            return null;
        $pdo = obtener_pdo();
        if ($pdo === null)
            return null;
        $st = $pdo->prepare("SELECT id, nombre, cod_barras, id_stock FROM productos WHERE cod_barras = ? OR TRIM(LEADING '0' FROM cod_barras) = TRIM(LEADING '0' FROM ?) LIMIT 1");
        $st->execute([$codigo, $codigo]);
        $row = $st->fetch();
        return $row ?: null;
    }

    private function buscar_producto_por_nombre_importacion(string $nombre): ?array {
        $pdo = obtener_pdo();
        if ($pdo === null || trim($nombre) === "")
            return null;
        $st = $pdo->prepare("SELECT id, nombre, cod_barras, id_stock FROM productos WHERE LOWER(nombre) = LOWER(?) LIMIT 1");
        $st->execute([$nombre]);
        $row = $st->fetch();
        return $row ?: null;
    }

    private function generar_codigo_barras_importacion(): string {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return (string)random_int(9000000000000, 9999999999999);
        for ($i = 0; $i < 50; $i++) {
            $codigo = "99" . str_pad((string)random_int(0, 99999999999), 11, "0", STR_PAD_LEFT);
            $st = $pdo->prepare("SELECT id FROM productos WHERE cod_barras = ? LIMIT 1");
            $st->execute([$codigo]);
            if (!$st->fetch())
                return $codigo;
        }
        return "99" . str_pad((string)time(), 11, "0", STR_PAD_LEFT);
    }

    private function buscar_stock_por_nombre_importacion(string $nombre): int {
        $pdo = obtener_pdo();
        if ($pdo === null || trim($nombre) === "")
            return 0;
        $st = $pdo->prepare("SELECT id FROM stock WHERE LOWER(nombre) = LOWER(?) LIMIT 1");
        $st->execute([$nombre]);
        $row = $st->fetch();
        return $row ? (int)$row["id"] : 0;
    }

    private function actualizar_stock_importacion(int $id_stock, string $nombre, string $unidad, float $cantidad, float $costo, int $activo, float $stock_minimo, float $stock_maximo): void {
        Stock::asegurar_columnas_minmax();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_stock <= 0)
            return;
        $st = $pdo->prepare("UPDATE stock SET nombre = ?, unidad = ?, cantidad = ?, stock_minimo = ?, stock_maximo = ?, precio_costo = ?, moneda_costo = 'ARS', costo_origen = ?, activo = ? WHERE id = ?");
        $st->execute([$nombre, $unidad, $cantidad, $stock_minimo, $stock_maximo, $costo, $costo, $activo, $id_stock]);
    }

    private function actualizar_codigo_producto_importacion(int $id_producto, string $codigo): void {
        $pdo = obtener_pdo();
        if ($pdo === null || $id_producto <= 0)
            return;
        $st = $pdo->prepare("UPDATE productos SET cod_barras = ? WHERE id = ?");
        $st->execute([$codigo, $id_producto]);
    }

    private function actualizar_costo_stock_producto_importacion(array $producto, float $costo): void {
        Stock::asegurar_columnas_minmax();
        $id_stock = (int)($producto["id_stock"] ?? 0);
        if ($id_stock <= 0 || $costo <= 0)
            return;
        $pdo = obtener_pdo();
        if ($pdo === null)
            return;
        $st = $pdo->prepare("UPDATE stock SET precio_costo = ?, moneda_costo = 'ARS', costo_origen = ? WHERE id = ?");
        $st->execute([$costo, $costo, $id_stock]);
    }

    private function importar_csv_balanza(string $archivo, int $id_lista): array {
        $res = ["creados" => 0, "actualizados" => 0, "sin_cambios" => 0, "omitidos" => 0];
        $fh = @fopen($archivo, "r");
        if ($fh === false)
            return $res;
        $primera = fgets($fh);
        if ($primera === false) {
            fclose($fh);
            return $res;
        }
        $delimitador = substr_count($primera, ";") >= substr_count($primera, ",") ? ";" : ",";
        rewind($fh);
        $headers = fgetcsv($fh, 0, $delimitador);
        if (!is_array($headers)) {
            fclose($fh);
            return $res;
        }
        $headers = array_map(fn($h) => $this->normalizar_header((string)$h), $headers);
        while (($row = fgetcsv($fh, 0, $delimitador)) !== false) {
            $plu = $this->valor_csv($row, $headers, ["plu", "codigo", "codigo/plu"]);
            $nombre = $this->valor_csv($row, $headers, ["producto", "descripcion", "articulo", "nombre"]);
            $precio = parsear_numero_form($this->valor_csv($row, $headers, ["precio fraccionado", "precio de venta x kg", "precio", "precio venta"]), 0);
            $unidad = $this->valor_csv($row, $headers, ["unidad de venta", "unidad"]);
            if ($plu === "" || $nombre === "" || $precio <= 0) {
                $res["omitidos"]++;
                continue;
            }
            $producto = $this->buscar_producto_por_plu($plu);
            if ($producto === null) {
                $id_stock = Stock::crear_retornar_id($nombre, $unidad !== "" ? $unidad : "u", 0, 0, 1);
                $id_producto = Producto::crear_retornar_id($nombre, $plu, $id_stock > 0 ? $id_stock : null, 1, 0, $precio, 1);
                if ($id_producto <= 0) {
                    $res["omitidos"]++;
                    continue;
                }
                ListaPrecio::guardar_precio_producto_origen($id_producto, $id_lista, 0, $precio, "importacion_balanza");
                $res["creados"]++;
                continue;
            }
            $id_producto = (int)$producto["id"];
            $actual_nombre = trim((string)($producto["nombre"] ?? ""));
            $precio_actual_info = ListaPrecio::precio_producto_cargado($id_producto, $id_lista);
            $precio_actual = $precio_actual_info !== null ? (float)$precio_actual_info["precio"] : 0.0;
            $unidad_actual = trim((string)($producto["stock_unidad"] ?? ""));
            $cambio = abs($precio_actual - $precio) >= 0.01 || $actual_nombre !== $nombre || ($unidad !== "" && $unidad_actual !== $unidad);
            if ($actual_nombre !== $nombre)
                $this->actualizar_nombre_producto($id_producto, $nombre);
            if ($unidad !== "")
                $this->actualizar_unidad_stock_producto($id_producto, $unidad);
            ListaPrecio::guardar_precio_producto_origen($id_producto, $id_lista, 0, $precio, "importacion_balanza");
            $cambio ? $res["actualizados"]++ : $res["sin_cambios"]++;
        }
        fclose($fh);
        return $res;
    }

    private function normalizar_header(string $header): string {
        $header = trim($header);
        $header = preg_replace('/\s+/', ' ', $header) ?? $header;
        return strtolower($header);
    }

    private function valor_csv(array $row, array $headers, array $nombres): string {
        foreach ($headers as $idx => $header) {
            if (in_array($header, $nombres, true) && isset($row[$idx]) && trim((string)$row[$idx]) !== "")
                return trim((string)$row[$idx]);
        }
        return "";
    }

    private function buscar_producto_por_plu(string $plu): ?array {
        $codigo = preg_replace('/\D+/', '', $plu) ?? "";
        if ($codigo === "")
            return null;
        $pdo = obtener_pdo();
        if ($pdo === null)
            return null;
        $st = $pdo->prepare("SELECT p.id, p.nombre, p.cod_barras, COALESCE(s.unidad, '') AS stock_unidad FROM productos p LEFT JOIN stock s ON s.id = p.id_stock WHERE p.cod_barras = ? OR TRIM(LEADING '0' FROM p.cod_barras) = TRIM(LEADING '0' FROM ?) LIMIT 1");
        $st->execute([$codigo, $codigo]);
        $row = $st->fetch();
        return $row ?: null;
    }

    private function actualizar_nombre_producto(int $id_producto, string $nombre): void {
        $pdo = obtener_pdo();
        if ($pdo === null || $id_producto <= 0 || trim($nombre) === "")
            return;
        $st = $pdo->prepare("UPDATE productos SET nombre = ? WHERE id = ?");
        $st->execute([$nombre, $id_producto]);
    }

    private function actualizar_unidad_stock_producto(int $id_producto, string $unidad): void {
        $pdo = obtener_pdo();
        if ($pdo === null || $id_producto <= 0 || trim($unidad) === "")
            return;
        $st = $pdo->prepare("UPDATE stock s INNER JOIN productos p ON p.id_stock = s.id SET s.unidad = ? WHERE p.id = ?");
        $st->execute([$unidad, $id_producto]);
    }

    public function estadisticas(): void {
        if ($this->permiso()) {
            $tipo = strtolower((string)obtener_get("tipo", "resumen"));
            $formato = $this->formato();
            $desde = trim((string)obtener_get("fecha_desde", ""));
            $hasta = trim((string)obtener_get("fecha_hasta", ""));
            
            $orden = trim((string)obtener_get("orden", ""));
            if (($tipo === "productos" || $tipo === "articulos_detalle") && $orden === "")
                $orden = "cantidad";
            else if ($orden === "")
                $orden = "total";
            $limite = max(0, (int)obtener_get("limite", 0));

            $subtitulo = $this->subtitulo_periodo($desde, $hasta);
            if ($tipo === "productos")
                $this->exportar_productos_vendidos($desde, $hasta, $formato, $subtitulo, $orden, $limite);
            else if ($tipo === "articulos_detalle")
                $this->exportar_articulos_detalle($desde, $hasta, $formato, $subtitulo, $orden, $limite);
            else if ($tipo === "mensuales")
                $this->exportar_ventas_mensuales($desde, $hasta, $formato, $subtitulo);
            else if ($tipo === "tickets")
                $this->exportar_tickets($desde, $hasta, $formato, $subtitulo);
            else if ($tipo === "stock_ventas")
                $this->exportar_stock_ventas($desde, $hasta, $formato, $subtitulo);
            else if ($tipo === "diarias")
                $this->exportar_ventas_diarias($desde, $hasta, $formato, $subtitulo);
            else
                $this->exportar_resumen($desde, $hasta, $formato, $subtitulo);
        }
    }

    private function exportar_resumen(string $desde, string $hasta, string $formato, string $subtitulo): void {
        $resumen = Venta::obtener_resumen_periodo($desde, $hasta);
        $filas = "<tr><td>Ventas realizadas</td><td class='num'>" . (int)$resumen["cantidad_ventas"] . "</td></tr>";
        $filas .= "<tr><td>Total vendido</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($resumen["total_vendido"] ?? 0)) . "</td></tr>";
        $filas .= "<tr><td>Ganancia estimada</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($resumen["ganancia"] ?? 0)) . "</td></tr>";
        $rows = [
            ["Concepto" => "Ventas realizadas", "Valor" => (string)(int)$resumen["cantidad_ventas"]],
            ["Concepto" => "Total vendido", "Valor" => numero_para_mostrar($resumen["total_vendido"] ?? 0, 2)],
            ["Concepto" => "Ganancia estimada", "Valor" => numero_para_mostrar($resumen["ganancia"] ?? 0, 2)],
        ];
        $this->responder("estadisticas_resumen", "Resumen estadistico", $subtitulo, ["Concepto", "Valor"], $filas, 2, $rows, $formato);
    }

    private function exportar_ventas_diarias(string $desde, string $hasta, string $formato, string $subtitulo): void {
        $rows = $this->ventas_diarias($desde, $hasta);
        $filas = "";
        foreach ($rows as $r)
            $filas .= "<tr><td>" . htmlspecialchars((string)$r["fecha"]) . "</td><td class='num'>" . (int)$r["ventas"] . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["total"] ?? 0)) . "</td></tr>";
        $this->responder("ventas_diarias", "Ventas por dia", $subtitulo, ["Fecha", "Ventas", "Total"], $filas, 3, $rows, $formato);
    }

    private function exportar_productos_vendidos(string $desde, string $hasta, string $formato, string $subtitulo, string $orden = "total", int $limite = 0): void {
        $rows = $this->productos_vendidos($desde, $hasta, $orden, $limite);
        $filas = "";
        foreach ($rows as $r)
            $filas .= "<tr><td>" . htmlspecialchars((string)$r["producto"]) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($r["cantidad"] ?? 0, 3)) . "</td><td class='num'>" . (int)($r["ventas"] ?? 0) . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["total"] ?? 0)) . "</td></tr>";
        $this->responder("articulos_mas_vendidos", "Articulos mas vendidos", $subtitulo, ["Producto", "Cantidad", "Ventas", "Total"], $filas, 4, $rows, $formato);
    }

    private function exportar_articulos_detalle(string $desde, string $hasta, string $formato, string $subtitulo, string $orden = "total", int $limite = 0): void {
        $rows = $this->articulos_detalle($desde, $hasta, $orden, $limite);
        $filas = "";
        $csv = [];
        foreach ($rows as $r) {
            $filas .= "<tr><td>" . htmlspecialchars((string)$r["producto"]) . "</td><td>" . htmlspecialchars((string)$r["codigo"]) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($r["unidades"] ?? 0, 3)) . "</td><td class='num'>" . (int)($r["ventas"] ?? 0) . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["total"] ?? 0)) . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["costo"] ?? 0)) . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["ganancia"] ?? 0)) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($r["margen"] ?? 0, 2)) . "%</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($r["participacion"] ?? 0, 2)) . "%</td><td class='num'>" . htmlspecialchars(stock_para_mostrar($r["stock_actual"] ?? 0, 3)) . "</td></tr>";
            $csv[] = [
                "Articulo" => (string)$r["producto"],
                "Codigo" => (string)$r["codigo"],
                "Unidades" => numero_para_mostrar($r["unidades"] ?? 0, 3),
                "Ventas" => (string)(int)($r["ventas"] ?? 0),
                "Total" => numero_para_mostrar($r["total"] ?? 0, 2),
                "Costo" => numero_para_mostrar($r["costo"] ?? 0, 2),
                "Ganancia" => numero_para_mostrar($r["ganancia"] ?? 0, 2),
                "Margen" => numero_para_mostrar($r["margen"] ?? 0, 2),
                "% total" => numero_para_mostrar($r["participacion"] ?? 0, 2),
                "Stock" => stock_para_mostrar($r["stock_actual"] ?? 0, 3),
            ];
        }
        $headers = ["Articulo", "Codigo", "Unidades", "Ventas", "Total", "Costo", "Ganancia", "Margen", "% total", "Stock"];
        $this->responder("articulos_mas_vendidos_detallado", "Ventas por articulos detallado", $subtitulo, $headers, $filas, 10, $csv, $formato);
    }

    private function exportar_ventas_mensuales(string $desde, string $hasta, string $formato, string $subtitulo): void {
        $rows = $this->ventas_mensuales($desde, $hasta);
        $filas = "";
        foreach ($rows as $r)
            $filas .= "<tr><td>" . htmlspecialchars((string)$r["periodo"]) . "</td><td class='num'>" . (int)$r["ventas"] . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($r["unidades"] ?? 0, 3)) . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["total"] ?? 0)) . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["ticket_promedio"] ?? 0)) . "</td></tr>";
        $this->responder("ventas_mensuales", "Ventas por mes", $subtitulo, ["Mes", "Ventas", "Unidades", "Total", "Ticket promedio"], $filas, 5, $rows, $formato);
    }

    private function exportar_tickets(string $desde, string $hasta, string $formato, string $subtitulo): void {
        $rows = $this->tickets_resumen($desde, $hasta);
        $filas = "";
        foreach ($rows as $r)
            $filas .= "<tr><td>" . htmlspecialchars((string)$r["rango"]) . "</td><td class='num'>" . (int)$r["ventas"] . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["total"] ?? 0)) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($r["participacion"] ?? 0, 2)) . "%</td></tr>";
        $this->responder("tickets_por_rango", "Tickets por rango de importe", $subtitulo, ["Rango", "Ventas", "Total", "% ventas"], $filas, 4, $rows, $formato);
    }

    private function exportar_stock_ventas(string $desde, string $hasta, string $formato, string $subtitulo): void {
        $rows = $this->stock_ventas($desde, $hasta);
        $filas = "";
        foreach ($rows as $r)
            $filas .= "<tr><td>" . htmlspecialchars((string)$r["stock"]) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($r["vendido"] ?? 0, 3)) . "</td><td class='num'>" . htmlspecialchars(stock_para_mostrar($r["stock_actual"] ?? 0, 3)) . "</td><td>" . htmlspecialchars((string)$r["unidad"]) . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["total"] ?? 0)) . "</td></tr>";
        $this->responder("stock_vs_ventas", "Stock vendido y existencia actual", $subtitulo, ["Stock", "Vendido", "Actual", "Unidad", "Total vendido"], $filas, 5, $rows, $formato);
    }

    private function ventas_diarias(string $desde, string $hasta): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                Venta::asegurar_columnas_detalle();
                $params = [];
                $where = $this->where_periodo($desde, $hasta, $params, "v.fecha");
                $sql = "SELECT DATE(v.fecha) AS fecha, COUNT(*) AS ventas, COALESCE(SUM(v.total), 0) AS total FROM ventas v";
                if ($where !== "")
                    $sql .= " WHERE " . $where;
                $sql .= " GROUP BY DATE(v.fecha) ORDER BY fecha DESC";
                $st = $pdo->prepare($sql);
                $st->execute($params);
                $lista = $st->fetchAll() ?: [];
            } catch (Throwable $e) {
                registrar_log("Exportaciones::ventas_diarias", $e->getMessage());
            }
        }
        return $lista;
    }

    private function productos_vendidos(string $desde, string $hasta, string $orden = "total", int $limite = 0): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $params = [];
                $where = $this->where_periodo($desde, $hasta, $params, "v.fecha");
                $sql = "SELECT p.nombre AS producto,
                               COALESCE(SUM(d.cantidad), 0) AS cantidad,
                               COUNT(DISTINCT v.id) AS ventas,
                               COALESCE(SUM(d.subtotal), 0) AS total
                        FROM detalle_venta d
                        INNER JOIN ventas v ON v.id = d.id_venta
                        INNER JOIN productos p ON p.id = d.id_producto";
                if ($where !== "")
                    $sql .= " WHERE " . $where;
                
                $sql .= " GROUP BY p.id, p.nombre";

                if ($orden === "cantidad")
                    $sql .= " ORDER BY cantidad DESC, producto ASC";
                else if ($orden === "ventas")
                    $sql .= " ORDER BY ventas DESC, cantidad DESC, producto ASC";
                else if ($orden === "nombre")
                    $sql .= " ORDER BY producto ASC";
                else
                    $sql .= " ORDER BY total DESC, cantidad DESC, producto ASC";

                if ($limite > 0)
                    $sql .= " LIMIT " . (int)$limite;

                $st = $pdo->prepare($sql);
                $st->execute($params);
                $lista = $st->fetchAll() ?: [];
            } catch (Throwable $e) {
                registrar_log("Exportaciones::productos_vendidos", $e->getMessage());
            }
        }
        return $lista;
    }

    private function articulos_detalle(string $desde, string $hasta, string $orden = "total", int $limite = 0): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $params = [];
                $where = $this->where_periodo($desde, $hasta, $params, "v.fecha");
                $where_sql = $where !== "" ? " WHERE " . $where : "";
                $sql_total = "SELECT COALESCE(SUM(d.subtotal), 0) AS total FROM detalle_venta d INNER JOIN ventas v ON v.id = d.id_venta" . $where_sql;
                $st_total = $pdo->prepare($sql_total);
                $st_total->execute($params);
                $total_general = (float)(($st_total->fetch()["total"] ?? 0));

                $sql = "SELECT p.nombre AS producto, p.cod_barras AS codigo,
                               COALESCE(SUM(d.cantidad), 0) AS unidades,
                               COUNT(DISTINCT v.id) AS ventas,
                               COALESCE(SUM(d.subtotal), 0) AS total,
                               COALESCE(SUM(COALESCE(NULLIF(d.costo_unit, 0), COALESCE(s.precio_costo, 0) * p.factor_conversion, 0) * d.cantidad), 0) AS costo,
                               COALESCE(SUM(d.subtotal - (COALESCE(NULLIF(d.costo_unit, 0), COALESCE(s.precio_costo, 0) * p.factor_conversion, 0) * d.cantidad)), 0) AS ganancia,
                               COALESCE(
                                   COALESCE(SUM(d.subtotal - (COALESCE(NULLIF(d.costo_unit, 0), COALESCE(s.precio_costo, 0) * p.factor_conversion, 0) * d.cantidad)), 0)
                                   / NULLIF(COALESCE(SUM(d.subtotal), 0), 0) * 100,
                                   0
                               ) AS margen,
                               COALESCE(s.cantidad, 0) AS stock_actual
                        FROM detalle_venta d
                        INNER JOIN ventas v ON v.id = d.id_venta
                        INNER JOIN productos p ON p.id = d.id_producto
                        LEFT JOIN stock s ON s.id = p.id_stock";
                if ($where !== "")
                    $sql .= " WHERE " . $where;
                
                $sql .= " GROUP BY p.id, p.nombre, p.cod_barras, s.cantidad";

                if ($orden === "cantidad")
                    $sql .= " ORDER BY unidades DESC, producto ASC";
                else if ($orden === "ventas")
                    $sql .= " ORDER BY ventas DESC, unidades DESC, producto ASC";
                else if ($orden === "ganancia")
                    $sql .= " ORDER BY ganancia DESC, producto ASC";
                else if ($orden === "margen")
                    $sql .= " ORDER BY margen DESC, ganancia DESC, producto ASC";
                else if ($orden === "stock_mayor")
                    $sql .= " ORDER BY stock_actual DESC, producto ASC";
                else if ($orden === "stock_menor")
                    $sql .= " ORDER BY stock_actual ASC, producto ASC";
                else if ($orden === "nombre")
                    $sql .= " ORDER BY producto ASC";
                else
                    $sql .= " ORDER BY total DESC, unidades DESC";

                if ($limite > 0)
                    $sql .= " LIMIT " . (int)$limite;

                $st = $pdo->prepare($sql);
                $st->execute($params);
                foreach (($st->fetchAll() ?: []) as $row) {
                    $total = (float)($row["total"] ?? 0);
                    $ganancia = (float)($row["ganancia"] ?? 0);
                    $row["margen"] = $total > 0 ? ($ganancia / $total) * 100 : 0;
                    $row["participacion"] = $total_general > 0 ? ($total / $total_general) * 100 : 0;
                    $lista[] = $row;
                }
            } catch (Throwable $e) {
                registrar_log("Exportaciones::articulos_detalle", $e->getMessage());
            }
        }
        return $lista;
    }

    private function ventas_mensuales(string $desde, string $hasta): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $params = [];
                $where = $this->where_periodo($desde, $hasta, $params, "v.fecha");
                $sql = "SELECT DATE_FORMAT(v.fecha, '%Y-%m') AS periodo,
                               COUNT(DISTINCT v.id) AS ventas,
                               COALESCE(SUM(v.total), 0) AS total,
                               COALESCE(SUM(v.total) / NULLIF(COUNT(DISTINCT v.id), 0), 0) AS ticket_promedio,
                               COALESCE((
                                   SELECT SUM(d.cantidad)
                                   FROM detalle_venta d
                                   INNER JOIN ventas v2 ON v2.id = d.id_venta
                                   WHERE DATE_FORMAT(v2.fecha, '%Y-%m') = DATE_FORMAT(v.fecha, '%Y-%m')
                               ), 0) AS unidades
                        FROM ventas v";
                if ($where !== "")
                    $sql .= " WHERE " . $where;
                $sql .= " GROUP BY DATE_FORMAT(v.fecha, '%Y-%m') ORDER BY periodo DESC";
                $st = $pdo->prepare($sql);
                $st->execute($params);
                $lista = $st->fetchAll() ?: [];
            } catch (Throwable $e) {
                registrar_log("Exportaciones::ventas_mensuales", $e->getMessage());
            }
        }
        return $lista;
    }

    private function tickets_resumen(string $desde, string $hasta): array {
        $rangos = [
            ["rango" => "Hasta 10.000", "min" => 0, "max" => 10000],
            ["rango" => "10.001 a 50.000", "min" => 10000.01, "max" => 50000],
            ["rango" => "50.001 a 100.000", "min" => 50000.01, "max" => 100000],
            ["rango" => "Mas de 100.000", "min" => 100000.01, "max" => null],
        ];
        $lista = [];
        $ventas_total = 0;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $params = [];
                $where = $this->where_periodo($desde, $hasta, $params, "fecha");
                $sql_total = "SELECT COUNT(*) AS total FROM ventas" . ($where !== "" ? " WHERE " . $where : "");
                $st_total = $pdo->prepare($sql_total);
                $st_total->execute($params);
                $ventas_total = (int)(($st_total->fetch()["total"] ?? 0));

                foreach ($rangos as $rango) {
                    $params_rango = $params;
                    $condiciones = [];
                    if ($where !== "")
                        $condiciones[] = $where;
                    $condiciones[] = "total >= ?";
                    $params_rango[] = $rango["min"];
                    if ($rango["max"] !== null) {
                        $condiciones[] = "total <= ?";
                        $params_rango[] = $rango["max"];
                    }
                    $sql = "SELECT COUNT(*) AS ventas, COALESCE(SUM(total), 0) AS total FROM ventas WHERE " . implode(" AND ", $condiciones);
                    $st = $pdo->prepare($sql);
                    $st->execute($params_rango);
                    $row = $st->fetch() ?: [];
                    $ventas = (int)($row["ventas"] ?? 0);
                    $lista[] = [
                        "rango" => $rango["rango"],
                        "ventas" => $ventas,
                        "total" => (float)($row["total"] ?? 0),
                        "participacion" => $ventas_total > 0 ? ($ventas / $ventas_total) * 100 : 0,
                    ];
                }
            } catch (Throwable $e) {
                registrar_log("Exportaciones::tickets_resumen", $e->getMessage());
            }
        }
        return $lista;
    }

    private function stock_ventas(string $desde, string $hasta): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $params = [];
                $where = $this->where_periodo($desde, $hasta, $params, "v.fecha");
                $sql = "SELECT COALESCE(s.nombre, 'Sin stock') AS stock,
                               COALESCE(SUM(d.cantidad * p.factor_conversion), 0) AS vendido,
                               COALESCE(s.cantidad, 0) AS stock_actual,
                               COALESCE(s.unidad, '') AS unidad,
                               COALESCE(SUM(d.subtotal), 0) AS total
                        FROM detalle_venta d
                        INNER JOIN ventas v ON v.id = d.id_venta
                        INNER JOIN productos p ON p.id = d.id_producto
                        LEFT JOIN stock s ON s.id = p.id_stock";
                if ($where !== "")
                    $sql .= " WHERE " . $where;
                $sql .= " GROUP BY s.id, s.nombre, s.cantidad, s.unidad ORDER BY vendido DESC";
                $st = $pdo->prepare($sql);
                $st->execute($params);
                $lista = $st->fetchAll() ?: [];
            } catch (Throwable $e) {
                registrar_log("Exportaciones::stock_ventas", $e->getMessage());
            }
        }
        return $lista;
    }

    private function responder(string $archivo, string $titulo, string $subtitulo, array $headers, string $filas, int $colspan, array $rows, string $formato): void {
        if ($formato === "csv") {
            header("Content-Type: text/csv; charset=utf-8");
            header("Content-Disposition: attachment; filename=\"" . $this->nombre_archivo($archivo, "csv") . "\"");
            $out = fopen("php://output", "w");
            if ($out !== false) {
                fprintf($out, "\xEF\xBB\xBF");
                fputcsv($out, $headers, ";");
                foreach ($rows as $row)
                    fputcsv($out, array_values($row), ";");
                fclose($out);
            }
            return;
        }
        if ($formato === "xls" || $formato === "excel") {
            $this->responder_csv_limpio($archivo, $headers, $rows);
            return;
        }
        $html = reporte_html_tabla($titulo, $subtitulo, $headers, $filas, $colspan);
        if ($formato === "pdf") {
            $autoload = __DIR__ . "/../../vendor/autoload.php";
            if (file_exists($autoload)) {
                require_once $autoload;
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html, "UTF-8");
                $dompdf->setPaper("A4", "portrait");
                $dompdf->render();
                header("Content-Type: application/pdf");
                header("Content-Disposition: attachment; filename=\"" . $this->nombre_archivo($archivo, "pdf") . "\"");
                echo $dompdf->output();
                return;
            }
        }
        echo $html;
    }

    private function excel_tabla_simple(array $headers, array $rows): string {
        $thead = "";
        foreach ($headers as $header)
            $thead .= "<th>" . htmlspecialchars((string)$header) . "</th>";

        $tbody = "";
        foreach ($rows as $row) {
            $tbody .= "<tr>";
            $values = array_values($row);
            for ($i = 0; $i < count($headers); $i++) {
                $value = $values[$i] ?? "";
                $style = in_array((string)$headers[$i], ["PLU", "Codigo", "Codigo/PLU"], true) ? " style='mso-number-format:\"\\@\";'" : "";
                $tbody .= "<td$style>" . htmlspecialchars((string)$value) . "</td>";
            }
            $tbody .= "</tr>";
        }

        return "<!doctype html><html><head><meta charset='utf-8'></head><body><table border='1'><thead><tr>$thead</tr></thead><tbody>$tbody</tbody></table></body></html>";
    }

    private function formato(): string {
        $formato = strtolower((string)obtener_get("formato", "html"));
        return in_array($formato, ["html", "pdf", "xls", "excel", "csv"], true) ? $formato : "html";
    }

    private function nombre_archivo(string $base, string $extension): string {
        return $this->slug_archivo($base) . "_" . date("Ymd_His") . "." . ltrim($extension, ".");
    }

    private function slug_archivo(string $texto): string {
        $texto = trim($texto);
        $texto = strtr($texto, [
            "á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u", "ñ" => "n",
            "Á" => "a", "É" => "e", "Í" => "i", "Ó" => "o", "Ú" => "u", "Ñ" => "n",
            "ü" => "u", "Ü" => "u"
        ]);
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/', '_', $texto) ?? "";
        $texto = trim($texto, "_");
        return $texto !== "" ? $texto : "exportacion";
    }

    private function nombre_lista(int $id_lista): string {
        foreach (ListaPrecio::listar(true) as $lista) {
            if ((int)$lista["id"] === $id_lista)
                return (string)$lista["nombre"];
        }
        return "";
    }

    private function subtitulo_periodo(string $desde, string $hasta): string {
        if ($desde === "" && $hasta === "")
            return "Todos los periodos registrados";
        if ($desde !== "" && $hasta !== "")
            return "Periodo " . $desde . " a " . $hasta;
        if ($desde !== "")
            return "Desde " . $desde;
        return "Hasta " . $hasta;
    }

    private function where_periodo(string $desde, string $hasta, array &$params, string $campo): string {
        $where = [];
        if ($desde !== "") {
            $where[] = "$campo >= ?";
            $params[] = $desde . " 00:00:00";
        }
        if ($hasta !== "") {
            $where[] = "$campo <= ?";
            $params[] = $hasta . " 23:59:59";
        }
        return implode(" AND ", $where);
    }
}
