<?php
require_once __DIR__ . "/seguridad.php";

function registrar_log(string $etiqueta,string $mensaje):void{
    $carpeta=__DIR__."/../almacenamiento/logs";
    //si no existe la carpeta la creamos
    if(!is_dir($carpeta))
        @mkdir($carpeta);
    $linea = "[" . date("Y-m-d H:i:s") . "] [$etiqueta] $mensaje\n";
    @file_put_contents($carpeta . "/app.log", $linea, FILE_APPEND);
}

function texto_invalido($texto):bool{
    $invalido=false;
    $t="";
    if(is_string($texto))
        //eliminamos espacios en blanco
        $t=trim($texto);
    if($t==="")
        $invalido=true;
    else{
        //pasamos a minúsculas
        $lower=mb_strtolower($t);
        $placeholder=["ingresar", "ingrese", "buscar", "seleccioná", "seleccione", "—"];
        foreach($placeholder as $p){
            //si empieza con alguno de los placeholder entonces es inválido
            if(str_starts_with($lower,$p))
                $invalido=true;
        }
    }
    return $invalido;
}

function obtener_post(string $clave, $defecto=""){
    $valor=$defecto;
    if(isset($_POST[$clave]))
        $valor=$_POST[$clave];
    return $valor;
}

function obtener_get(string $clave, $defecto=""){
    $valor=$defecto;
    if(isset($_GET[$clave]))
        $valor=$_GET[$clave];
    return $valor;
}

function configuraciones_defecto_db(): array {
    $datos = [
        "nombre_comercio" => "MI COMERCIO",
        "razon_social" => "",
        "cuit" => "",
        "condicion_iva" => "",
        "domicilio" => "",
        "localidad" => "",
        "provincia" => "",
        "telefonos" => "",
        "whatsapp" => "",
        "email" => "",
        "sitio_web" => "",
        "ingresos_brutos" => "",
        "inicio_actividades" => "",
        "punto_venta" => "1",
        "logo" => "",
        "favicon" => "",
        "logo_ticket" => "",
        "ticket_imagen_completa" => "0",
        "ticket_logo_termico" => "1",
        "color_acento" => "#1f6f8b",
        "color_secundario" => "#48aaa5",
        "color_fondo" => "#f4f6f8",
        "color_fondo_secundario" => "#f9fbfc",
        "color_tarjetas" => "#ffffff",
        "color_texto" => "#203040",
        "color_texto_suave" => "#657789",
        "color_borde" => "#dbe3ea",
        "color_panel_inicio" => "#155e75",
        "color_panel_inicio_2" => "#48aaa5",
        "navbar_marca_texto" => "MI COMERCIO",
        "navbar_mostrar_marca" => "1",
        "navbar_mostrar_config" => "1",
        "navbar_mostrar_usuario" => "1",
        "navbar_mostrar_rol" => "1",
        "navbar_mostrar_cambio_modulo" => "1",
        "navbar_mostrar_salir" => "1",
        "navbar_color_1" => "#000000",
        "navbar_color_2" => "#1f2937",
        "navbar_texto_color" => "#ffffff",
        "navbar_boton_fondo" => "#ffffff",
        "navbar_boton_borde" => "#ffffff",
        "navbar_boton_opacidad" => "10",
        "navbar_modulos_orden" => "ventas,nueva_venta,clientes,stock,productos,listas_precios,exportaciones,cuentas_corrientes,reparaciones",
        "navbar_modulos_visibles" => "ventas,nueva_venta,clientes,stock,productos,listas_precios,exportaciones,cuentas_corrientes,reparaciones",
        "tema_paneles" => "claro",
        "tema_modo" => "claro",
        "ui_radio_bordes" => "8",
        "ui_tamano_tarjetas" => "medio",
        "ui_sombras" => "1",
        "ui_animaciones" => "1",
        "imagen_panel" => "",
        "ventas_cantidad_decimales" => "3",
        "ventas_descuento_automatico" => "0",
        "ventas_rapidas" => "1",
        "ventas_cliente_defecto" => "Consumidor Final",
        "ventas_consumidor_final_auto" => "1",
        "ventas_confirmar_cierre" => "1",
        "ventas_sonido_confirmacion" => "0",
        "ventas_color_pendiente" => "#f59e0b",
        "ventas_color_completada" => "#16a34a",
        "ventas_color_cancelada" => "#dc2626",
        "formato_impresion_ticket" => "80",
        "texto_pie_ticket" => "Gracias por su compra",
        "ticket_fuente" => "Arial",
        "ticket_tamano_fuente" => "12",
        "controlar_stock_ventas" => "1",
        "productos_multiples_listas" => "1",
        "productos_mostrar_stock_minimo" => "1",
        "productos_permitir_stock_negativo" => "0",
        "productos_activar_escaner" => "1",
        "productos_formato_codigo_barras" => "ean13",
        "productos_etiquetas" => "1",
        "productos_importacion_excel" => "1",
        "productos_reglas_automaticas" => "0",
        "clientes_campos_extra" => "0",
        "clientes_validar_documento" => "0",
        "clientes_lista_defecto" => "",
        "listas_redondeo" => "0",
        "listas_actualizar_costo" => "0",
        "notificaciones_sonidos" => "0",
        "notificaciones_toasts" => "1",
        "notificaciones_alertas" => "1",
        "notificaciones_stock_bajo" => "1",
        "notificaciones_ventas" => "1",
        "notificaciones_backup" => "1",
        "seguridad_tiempo_sesion" => "120",
        "seguridad_2fa_futuro" => "0",
        "seguridad_ips_permitidas" => "",
        "seguridad_bloqueos" => "1",
        "seguridad_logs" => "1",
        "auth_modo" => "login",
        "backup_b2_habilitado" => "0",
        "backup_b2_key_id" => "",
        "backup_b2_application_key" => "",
        "backup_b2_bucket_id" => "",
        "backup_b2_bucket_name" => "",
        "backup_b2_carpeta" => "ventas-reparaciones",
        "backup_google_drive_futuro" => "0",
        "backup_automatico" => "1",
        "backup_frecuencia" => "diario",
        "backup_hora" => "18:55",
        "backup_aviso_minutos" => "5",
        "backup_local_habilitado" => "1",
        "backup_local_carpeta" => "",
        "backup_auto_local" => "1",
        "backup_auto_backblaze" => "0",
        "backup_ultimo" => "",
        "backup_ultimo_estado" => "",
        "backup_ultimo_error" => "",
        "backup_auto_ultimo_dia" => "",
        "url_reparaciones" => "index.php?c=reparaciones&a=index",
        "mostrar_reparaciones" => "1",
        "atajo_reparaciones" => "F9",
        "configuracion_separada" => "1",
        "balanza_modo" => "auto",
        "balanza_plu_digitos" => "5",
        "balanza_valor_decimales" => "3",
        "balanza_importe_decimales" => "2",
        "balanza_prefijos_cantidad" => "20,21,23,25,27,29",
        "balanza_prefijos_importe" => "22,24,26,28"
    ];
    return $datos;
}

function config_cache_reset(): void {
    $GLOBALS["config_cache_db"] = null;
}

function config_todas(): array {
    if (!array_key_exists("config_cache_db", $GLOBALS))
        $GLOBALS["config_cache_db"] = null;
    $datos = $GLOBALS["config_cache_db"];
    if (!is_array($datos)) {
        $datos = configuraciones_defecto_db();
        if (function_exists("obtener_pdo")) {
            $pdo = obtener_pdo();
            if ($pdo !== null) {
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS configuraciones (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        clave VARCHAR(120) NOT NULL,
                        valor LONGTEXT NULL,
                        tipo VARCHAR(40) NOT NULL DEFAULT 'texto',
                        grupo VARCHAR(60) NOT NULL DEFAULT 'sistema',
                        UNIQUE KEY uq_configuraciones_clave (clave),
                        KEY idx_configuraciones_grupo (grupo)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    $st = $pdo->query("SELECT clave, valor FROM configuraciones");
                    $rows = $st ? $st->fetchAll() : [];
                    foreach ($rows as $row)
                        $datos[(string)$row["clave"]] = (string)($row["valor"] ?? "");
                } catch (Throwable $e) {
                    registrar_log("config_todas", $e->getMessage());
                }
            }
        }
        $GLOBALS["config_cache_db"] = $datos;
    }
    return $datos;
}

function config(string $nombre, string $valor_defecto = ""): string {
    $datos = config_todas();
    $valor = array_key_exists($nombre, $datos) ? (string)$datos[$nombre] : $valor_defecto;
    return $valor;
}

function flash_ok(string $mensaje):void{
    iniciar_sesion();
    $_SESSION["flash_ok"]=$mensaje;
}

function flash_error(string $mensaje): void {
    iniciar_sesion();
    $_SESSION["flash_error"] = $mensaje;
}

function flash_form_data(string $clave, array $datos): void {
    iniciar_sesion();
    $_SESSION["flash_form_data"][$clave] = $datos;
}

function obtener_form_data(string $clave): array {
    iniciar_sesion();
    $datos = [];
    if (isset($_SESSION["flash_form_data"][$clave]) && is_array($_SESSION["flash_form_data"][$clave]))
        $datos = $_SESSION["flash_form_data"][$clave];
    unset($_SESSION["flash_form_data"][$clave]);
    if (isset($_SESSION["flash_form_data"]) && count($_SESSION["flash_form_data"]) === 0)
        unset($_SESSION["flash_form_data"]);
    return $datos;
}

function normalizar_texto_busqueda($valor): string {
    $texto = "";
    if (is_scalar($valor) || $valor === null)
        $texto = trim((string)$valor);
    if ($texto === "")
        return "";
    return mb_strtolower($texto, "UTF-8");
}

function valor_coincide_busqueda($valor, string $busqueda, string $metodo): bool {
    $texto = normalizar_texto_busqueda($valor);
    if ($busqueda === "")
        return true;
    if ($texto === "")
        return false;
    $ok = false;
    switch ($metodo) {
        case "exacto":
            $ok = ($texto === $busqueda);
            break;
        case "empieza":
            $ok = str_starts_with($texto, $busqueda);
            break;
        case "termina":
            $ok = str_ends_with($texto, $busqueda);
            break;
        default:
            $ok = str_contains($texto, $busqueda);
            break;
    }
    return $ok;
}

function orden_parametros(array $permitidos, string $defecto_campo, string $defecto_direccion = "ASC"): array {
    $campo = strtolower(trim((string)obtener_get("orden", $defecto_campo)));
    $direccion = strtoupper(trim((string)obtener_get("direccion", $defecto_direccion)));
    $defecto_direccion = strtoupper($defecto_direccion) === "DESC" ? "DESC" : "ASC";
    if (!array_key_exists($campo, $permitidos))
        $campo = $defecto_campo;
    if (!in_array($direccion, ["ASC", "DESC"], true))
        $direccion = $defecto_direccion;
    $resultado = [
        "campo" => $campo,
        "direccion" => $direccion,
        "sql" => (string)$permitidos[$campo] . " " . $direccion,
        "defecto_campo" => $defecto_campo,
        "defecto_direccion" => $defecto_direccion
    ];
    return $resultado;
}

function orden_tabla_th(string $etiqueta, string $campo, array $orden_actual, string $tipo = "texto", array $extra_get = []): string {
    $campo_actual = (string)($orden_actual["campo"] ?? "");
    $direccion_actual = strtoupper((string)($orden_actual["direccion"] ?? ""));
    $defecto_campo = (string)($orden_actual["defecto_campo"] ?? "");
    $defecto_direccion = strtoupper((string)($orden_actual["defecto_direccion"] ?? "ASC"));
    $activo = $campo_actual === $campo;
    $proxima_direccion = "asc";
    $icono = "bi bi-arrow-down-up sort-icon";
    if ($activo && $direccion_actual === "ASC") {
        $proxima_direccion = "desc";
        $icono = ($tipo === "numero" ? "bi bi-sort-numeric-down" : "bi bi-sort-alpha-down") . " sort-icon";
    } elseif ($activo && $direccion_actual === "DESC") {
        $proxima_direccion = "";
        $icono = ($tipo === "numero" ? "bi bi-sort-numeric-down" : "bi bi-sort-alpha-up-alt") . " sort-icon";
    }
    $params = array_merge($_GET, $extra_get);
    if ($proxima_direccion === "" || ($campo === $defecto_campo && strtoupper($proxima_direccion) === $defecto_direccion)) {
        unset($params["orden"], $params["direccion"]);
    } else {
        $params["orden"] = $campo;
        $params["direccion"] = $proxima_direccion;
    }
    $href = "index.php?" . http_build_query($params);
    $html = '<th><a class="sort-link js-sort-link" href="' . htmlspecialchars($href) . '" data-sort-key="' . htmlspecialchars($campo) . '" data-sort-direction="' . htmlspecialchars($proxima_direccion) . '">' . htmlspecialchars($etiqueta) . '<i class="' . htmlspecialchars($icono) . '" aria-hidden="true"></i></a></th>';
    return $html;
}

function filtrar_registros_busqueda(array $registros, string $texto, string $campo, array $campos_permitidos, string $metodo = "contiene"): array {
    $busqueda = normalizar_texto_busqueda($texto);
    if ($busqueda === "")
        return $registros;
    $metodos_validos = ["contiene", "exacto", "empieza", "termina"];
    if (!in_array($metodo, $metodos_validos, true))
        $metodo = "contiene";
    if (!isset($campos_permitidos[$campo]) && $campo !== "todos")
        $campo = "todos";
    $filtrados = [];
    foreach ($registros as $registro) {
        $coincide = false;
        foreach ($campos_permitidos as $clave => $alias) {
            if ($campo !== "todos" && $campo !== $clave)
                continue;
            $valor = $registro[$clave] ?? "";
            if (valor_coincide_busqueda($valor, $busqueda, $metodo)) {
                $coincide = true;
                break;
            }
        }
        if ($coincide)
            $filtrados[] = $registro;
    }
    return $filtrados;
}

function parsear_numero_form($valor, float $defecto = 0.0): float {
    if (is_int($valor) || is_float($valor))
        return (float)$valor;
    if (!is_string($valor))
        return $defecto;
    $texto = trim($valor);
    if ($texto === "")
        return $defecto;
    $texto = str_replace(" ", "", $texto);
    $pos_punto = strrpos($texto, ".");
    $pos_coma = strrpos($texto, ",");
    if ($pos_punto !== false && $pos_coma !== false) {
        if ($pos_coma > $pos_punto) {
            $texto = str_replace(".", "", $texto);
            $texto = str_replace(",", ".", $texto);
        } else
            $texto = str_replace(",", "", $texto);
    } else {
        if ($pos_coma !== false)
            $texto = str_replace(",", ".", $texto);
    }
    if (!is_numeric($texto))
        return $defecto;
    return (float)$texto;
}

function numero_para_input($valor, int $decimales = 4): string {
    $numero = parsear_numero_form($valor, 0);
    $texto = number_format($numero, $decimales, ".", "");
    $texto = rtrim(rtrim($texto, "0"), ".");
    if ($texto === "")
        $texto = "0";
    return $texto;
}

function numero_para_mostrar($valor, int $decimales = 2): string {
    $numero = parsear_numero_form($valor, 0);
    $texto = number_format($numero, $decimales, ",", ".");
    $texto = rtrim(rtrim($texto, "0"), ",");
    if (str_ends_with($texto, ","))
        $texto = substr($texto, 0, -1);
    return $texto;
}

function moneda_para_mostrar($valor, string $simbolo = "$"): string {
    $numero = parsear_numero_form($valor, 0);
    return $simbolo . " " . number_format($numero, 2, ",", ".");
}

function precio_para_mostrar($valor, string $simbolo = "$"): string {
    $numero = parsear_numero_form($valor, 0);
    if ($numero <= 0)
        return "SIN PRECIO";
    if ($simbolo === "")
        return number_format($numero, 2, ",", ".");
    return moneda_para_mostrar($numero, $simbolo);
}

function numero_precio_para_exportar($valor, int $decimales = 2): string {
    $numero = parsear_numero_form($valor, 0);
    if ($numero <= 0)
        return "SIN PRECIO";
    return numero_para_mostrar($numero, $decimales);
}

function stock_para_mostrar($valor, int $decimales = 3): string {
    $numero = parsear_numero_form($valor, 0);
    if (abs($numero) < 0.0000001)
        return "SIN STOCK";
    return numero_para_mostrar($numero, $decimales);
}

function reporte_empresa_datos(): array {
    $config = config_sistema_simple();
    $domicilio_partes = [];
    foreach (["domicilio", "localidad", "provincia"] as $clave) {
        $valor = trim((string)($config[$clave] ?? ""));
        if ($valor !== "")
            $domicilio_partes[] = $valor;
    }
    return [
        "nombre" => trim((string)($config["nombre_comercio"] ?? "MI COMERCIO")),
        "razon_social" => trim((string)($config["razon_social"] ?? "")),
        "cuit" => trim((string)($config["cuit"] ?? "")),
        "condicion_iva" => trim((string)($config["condicion_iva"] ?? "")),
        "domicilio" => implode(", ", $domicilio_partes),
        "telefonos" => trim((string)($config["telefonos"] ?? "")),
        "whatsapp" => trim((string)($config["whatsapp"] ?? "")),
        "email" => trim((string)($config["email"] ?? "")),
        "sitio_web" => trim((string)($config["sitio_web"] ?? "")),
        "ingresos_brutos" => trim((string)($config["ingresos_brutos"] ?? "")),
        "inicio_actividades" => trim((string)($config["inicio_actividades"] ?? "")),
    ];
}

function reporte_html_tabla(string $titulo, string $subtitulo, array $encabezados, string $filas, int $colspan, string $detalle = ""): string {
    $empresa = reporte_empresa_datos();
    $nombre = htmlspecialchars($empresa["nombre"] !== "" ? $empresa["nombre"] : "Comercio");
    $titulo_html = htmlspecialchars($titulo);
    $subtitulo_html = htmlspecialchars($subtitulo);
    $detalle_html = trim($detalle) !== "" ? "<div class='report-note'>" . htmlspecialchars($detalle) . "</div>" : "";
    $fecha = htmlspecialchars(date("d/m/Y H:i"));
    $thead = "";
    foreach ($encabezados as $th)
        $thead .= "<th>" . htmlspecialchars((string)$th) . "</th>";
    if ($filas === "")
        $filas = "<tr><td colspan='" . $colspan . "' class='center'>Sin datos para mostrar.</td></tr>";
    $datos = [];
    if ($empresa["cuit"] !== "")
        $datos[] = "CUIT: " . htmlspecialchars($empresa["cuit"]);
    if ($empresa["domicilio"] !== "")
        $datos[] = htmlspecialchars($empresa["domicilio"]);
    $datos_principales = implode(" | ", $datos);
    $contacto = [];
    if ($empresa["telefonos"] !== "")
        $contacto[] = "Tel: " . htmlspecialchars($empresa["telefonos"]);
    if ($empresa["whatsapp"] !== "")
        $contacto[] = "WhatsApp: " . htmlspecialchars($empresa["whatsapp"]);
    if ($empresa["email"] !== "")
        $contacto[] = htmlspecialchars($empresa["email"]);
    if ($empresa["sitio_web"] !== "")
        $contacto[] = htmlspecialchars($empresa["sitio_web"]);
    $contacto_html = implode(" | ", $contacto);
    return "<!doctype html><html lang='es'><head><meta charset='utf-8'><title>$titulo_html</title><style>
body{font-family:DejaVu Sans,Arial,sans-serif;color:#16202a;margin:0;background:#fff}
.page{padding:28px}
.print-actions{padding:16px 28px 0 28px}
.print-btn{border:1px solid #1f6f8b;background:#1f6f8b;color:#fff;border-radius:8px;padding:9px 14px;font-weight:700;cursor:pointer}
@media print{.print-actions{display:none}.page{padding:18px}}
.report-card{border:1px solid #cfd7e3;border-radius:8px;overflow:hidden}
.company{padding:18px 22px;border-bottom:3px solid #1f6f8b;background:#f7fafc}
.brand{font-size:24px;font-weight:800;letter-spacing:.4px;color:#12343f;text-transform:uppercase}
.legal{font-size:12px;color:#425466;margin-top:4px}
.contact{font-size:12px;color:#425466;margin-top:6px;line-height:1.45}
.title-row{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;padding:18px 22px;border-bottom:1px solid #e3e9f1}
.title h1{font-size:20px;margin:0 0 4px 0;color:#111827}
.title .sub{font-size:13px;color:#52606d}
.date-box{font-size:12px;color:#52606d;text-align:right;white-space:nowrap}
.report-note{margin:0 22px 14px 22px;padding:10px 12px;background:#eef7f8;border-left:4px solid #1f6f8b;font-size:13px;color:#243b45}
table{width:auto;max-width:100%;border-collapse:collapse;table-layout:auto}
th,td{border-bottom:1px solid #e4e8ee;padding:7px 9px;font-size:12px;vertical-align:top;white-space:nowrap}
td:first-child,th:first-child{white-space:normal;min-width:180px}
th{background:#edf2f7;color:#23313f;text-align:left;font-weight:700;text-transform:uppercase;font-size:11px}
tbody tr:nth-child(even){background:#fafcfd}.num{text-align:right}.center{text-align:center;color:#667085}
</style></head><body><div class='print-actions'><button class='print-btn' type='button' onclick='window.print()'>Imprimir</button></div><div class='page'><div class='report-card'><div class='company'><div class='brand'>$nombre</div><div class='legal'>$datos_principales</div><div class='contact'>$contacto_html</div></div><div class='title-row'><div class='title'><h1>$titulo_html</h1><div class='sub'>$subtitulo_html</div></div><div class='date-box'>Emitido<br><strong>$fecha</strong></div></div>$detalle_html<table><thead><tr>$thead</tr></thead><tbody>$filas</tbody></table></div></div></body></html>";
}

function config_sistema_simple(): array {
    $archivo = __DIR__ . "/../almacenamiento/configuracion_sistema.json";
    $defecto = [
        "url_reparaciones" => "index.php?c=reparaciones&a=index",
        "mostrar_reparaciones" => "1",
        "atajo_reparaciones" => "F9",
        "color_acento" => "#1f6f8b",
        "color_fondo" => "#f4f6f8",
        "color_fondo_secundario" => "#f9fbfc",
        "color_tarjetas" => "#ffffff",
        "color_texto" => "#203040",
        "color_texto_suave" => "#657789",
        "color_borde" => "#dbe3ea",
        "color_panel_inicio" => "#155e75",
        "color_panel_inicio_2" => "#48aaa5",
        "imagen_panel" => "",
        "navbar_marca_texto" => "MI COMERCIO",
        "navbar_mostrar_marca" => "1",
        "navbar_mostrar_config" => "1",
        "navbar_mostrar_usuario" => "1",
        "navbar_mostrar_rol" => "1",
        "navbar_mostrar_cambio_modulo" => "1",
        "navbar_mostrar_salir" => "1",
        "navbar_fondo_modo" => "colores",
        "navbar_color_1" => "#000000",
        "navbar_color_2" => "#1f2937",
        "navbar_texto_color" => "#ffffff",
        "navbar_boton_fondo" => "#ffffff",
        "navbar_boton_borde" => "#ffffff",
        "navbar_boton_opacidad" => "10",
        "navbar_imagen" => "",
        "navbar_modulos_orden" => "ventas,nueva_venta,clientes,stock,productos,listas_precios,exportaciones,cuentas_corrientes",
        "navbar_modulos_visibles" => "ventas,nueva_venta,clientes,stock,productos,listas_precios,exportaciones,cuentas_corrientes",
        "tema_paneles" => "claro",
        "controlar_stock_ventas" => "1",
    ];
    $datos_db = config_todas();
    if (!is_file($archivo))
        return array_merge($defecto, $datos_db);
    $json = @file_get_contents($archivo);
    $datos = is_string($json) ? json_decode($json, true) : null;
    if (!is_array($datos))
        return array_merge($defecto, $datos_db);
    return array_merge($defecto, $datos, $datos_db);
}

function normalizar_url_reparaciones(string $url): string {
    $url = trim($url);
    if ($url === "")
        return "index.php?c=reparaciones&a=index";
    if (preg_match('/^[a-zA-Z0-9_ -]+$/', $url))
        return "index.php?c=reparaciones&a=index";
    return $url;
}

function menu_modulos_base(): array {
    $config = config_sistema_simple();
    $modulos = [
        "inicio" => ["url" => "index.php?c=ventas&a=inicio", "icono" => "bi-house-door-fill", "clase" => "icono-inicio", "texto" => "Inicio"],
        "ventas" => ["url" => "index.php?c=ventas&a=lista", "icono" => "bi-cash-stack", "clase" => "icono-ventas", "texto" => "Ventas"],
        "nueva_venta" => ["url" => "index.php?c=ventas&a=nueva", "icono" => "bi-cart-plus-fill", "clase" => "icono-nueva", "texto" => "Nueva venta"],
        "clientes" => ["url" => "index.php?c=clientes&a=index", "icono" => "bi-people-fill", "clase" => "icono-clientes", "texto" => "Clientes"],
        "stock" => ["url" => "index.php?c=stock&a=index", "icono" => "bi-box-seam-fill", "clase" => "icono-stock", "texto" => "Stock"],
        "productos" => ["url" => "index.php?c=productos&a=index", "icono" => "bi-bag-fill", "clase" => "icono-productos", "texto" => "Productos"],
        "listas_precios" => ["url" => "index.php?c=listas_precios&a=index", "icono" => "bi-tags-fill", "clase" => "icono-productos", "texto" => "Listas"],
        "exportaciones" => ["url" => "index.php?c=exportaciones&a=index", "icono" => "bi-graph-up-arrow", "clase" => "icono-exportaciones", "texto" => "Exportar"],
        "cuentas_corrientes" => ["url" => "index.php?c=cuentas_corrientes&a=index", "icono" => "bi-credit-card-fill", "clase" => "icono-ventas", "texto" => "Cta. cte."],
        "configuraciones" => ["url" => "index.php?c=configuracion&a=index", "icono" => "bi-gear-fill", "clase" => "icono-configuraciones", "texto" => "Config"],
        "usuarios" => ["url" => "index.php?c=usuarios&a=index", "icono" => "bi-person-gear", "clase" => "icono-usuarios", "texto" => "Usuarios"]
    ];
    if ((string)($config["mostrar_reparaciones"] ?? "1") === "1") {
        $url = trim((string)($config["url_reparaciones"] ?? ""));
        $url = normalizar_url_reparaciones($url);
        $modulos["reparaciones"] = ["url" => $url, "icono" => "bi-tools", "clase" => "icono-reparaciones", "texto" => "Reparaciones"];
    }
    return $modulos;
}

function menu_claves_permitidas_por_rol(string $rol): array {
    $claves = ["inicio", "ventas", "nueva_venta", "clientes", "stock", "productos", "listas_precios", "exportaciones", "cuentas_corrientes", "reparaciones"];
    if ($rol === "ADMIN") {
        $claves[] = "configuraciones";
        $claves[] = "usuarios";
    }
    return $claves;
}

function menu_modulos_permitidos_por_rol(string $rol): array {
    $base = menu_modulos_base();
    $lista = [];
    foreach (menu_claves_permitidas_por_rol($rol) as $clave) {
        if (isset($base[$clave]))
            $lista[$clave] = $base[$clave];
    }
    return $lista;
}

function menu_preferencias_path_usuario(int $id_usuario): string {
    $carpeta = __DIR__ . "/../almacenamiento/preferencias_menu";
    if (!is_dir($carpeta))
        @mkdir($carpeta, 0777, true);
    return $carpeta . "/usuario_" . $id_usuario . ".json";
}

function menu_obtener_preferencias_usuario(int $id_usuario, string $rol): array {
    $permitidos = menu_modulos_permitidos_por_rol($rol);
    $claves_permitidas = array_keys($permitidos);
    $visibles_defecto = array_values(array_filter($claves_permitidas, fn($clave) => $clave !== "inicio"));
    if ($id_usuario <= 0)
        return $visibles_defecto;
    $archivo = menu_preferencias_path_usuario($id_usuario);
    if (!is_file($archivo))
        return $visibles_defecto;
    $json = @file_get_contents($archivo);
    if (!is_string($json) || trim($json) === "")
        return $visibles_defecto;
    $datos = json_decode($json, true);
    if (!is_array($datos))
        return $visibles_defecto;
    $seleccion = $datos["visibles"] ?? [];
    if (!is_array($seleccion))
        return $visibles_defecto;
    $filtrado = [];
    foreach ($seleccion as $clave) {
        if (is_string($clave) && in_array($clave, $claves_permitidas, true) && $clave !== "inicio")
            $filtrado[] = $clave;
    }
    return array_values(array_unique($filtrado));
}

function menu_guardar_preferencias_usuario(int $id_usuario, string $rol, array $seleccion): bool {
    if ($id_usuario <= 0)
        return false;
    $permitidas = menu_claves_permitidas_por_rol($rol);
    $visibles = [];
    foreach ($seleccion as $clave) {
        if (is_string($clave) && in_array($clave, $permitidas, true) && $clave !== "inicio")
            $visibles[] = $clave;
    }
    $payload = json_encode(["visibles" => array_values(array_unique($visibles))], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (!is_string($payload))
        return false;
    $archivo = menu_preferencias_path_usuario($id_usuario);
    return @file_put_contents($archivo, $payload) !== false;
}

function redirigir(string $url): void {
    header("Location: $url");
}
