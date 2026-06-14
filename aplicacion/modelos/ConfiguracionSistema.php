<?php

require_once __DIR__ . "/../../configuraciones/ayudas.php";

class ConfiguracionSistema {
    public static function valores_defecto(): array {
        $arca = self::configuracion_arca_base();
        $empresa = $arca["empresa"] ?? [];
        return [
            "nombre_comercio" => (string)($empresa["razon_social"] ?? "MI COMERCIO"),
            "razon_social" => (string)($empresa["razon_social"] ?? ""),
            "cuit" => (string)($empresa["cuit"] ?? ""),
            "condicion_iva" => (string)($empresa["condicion_iva"] ?? ""),
            "domicilio" => (string)($empresa["domicilio"] ?? ""),
            "localidad" => "",
            "provincia" => "",
            "telefonos" => "",
            "whatsapp" => "",
            "email" => "",
            "sitio_web" => "",
            "ingresos_brutos" => (string)($empresa["ingresos_brutos"] ?? ""),
            "inicio_actividades" => (string)($empresa["inicio_actividades"] ?? ""),
            "punto_venta" => (int)($empresa["punto_venta"] ?? 1),
            "formato_impresion_ticket" => "80",
            "texto_pie_ticket" => "Gracias por su compra",
            "ticket_fuente" => "Arial",
            "ticket_tamano_fuente" => "12",
            "controlar_stock_ventas" => "1",
            "productos_cotizacion_dolar" => "1",
            "balanza_modo" => "auto",
            "balanza_plu_digitos" => "5",
            "balanza_valor_decimales" => "3",
            "balanza_importe_decimales" => "2",
            "balanza_prefijos_cantidad" => "20,21,23,25,27,29",
            "balanza_prefijos_importe" => "22,24,26,28",
            "logo_ticket" => "",
            "ticket_imagen_completa" => "0",
            "ticket_logo_termico" => "1",
            "url_reparaciones" => "index.php?c=reparaciones&a=index",
            "mostrar_reparaciones" => "1",
            "atajo_reparaciones" => "F9",
            "configuracion_separada" => "1",
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
            "backup_b2_habilitado" => "0",
            "backup_b2_key_id" => "",
            "backup_b2_application_key" => "",
            "backup_b2_bucket_id" => "",
            "backup_b2_bucket_name" => "",
            "backup_b2_carpeta" => "ventas-reparaciones",
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
            "auth_modo" => "login",
        ];
    }

    public static function obtener(): array {
        $datos = self::valores_defecto();
        $archivo = self::archivo_configuracion();
        if (is_file($archivo)) {
            $json = @file_get_contents($archivo);
            if (is_string($json))
                $json = preg_replace('/^\xEF\xBB\xBF/', '', $json);
            $guardado = is_string($json) ? json_decode($json, true) : null;
            if (is_array($guardado))
                $datos = array_merge($datos, self::normalizar($guardado));
        }
        $datos = array_merge($datos, self::obtener_desde_db());
        return $datos;
    }

    public static function guardar(array $entrada): bool {
        $actual = self::obtener();
        $datos = self::normalizar(array_merge($actual, $entrada));
        if ((int)$datos["punto_venta"] <= 0)
            $datos["punto_venta"] = 1;

        $carpeta = dirname(self::archivo_configuracion());
        if (!is_dir($carpeta))
            @mkdir($carpeta, 0777, true);

        $json = json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            registrar_operacion("configuraciones.modelo.guardar_sistema.error", [
                "error" => "No se pudo generar JSON",
                "datos" => $datos,
            ]);
            return false;
        }

        $ok_json = @file_put_contents(self::archivo_configuracion(), $json) !== false;
        $ok_arca = self::sincronizar_arca($datos);
        $ok_reparaciones = self::sincronizar_reparaciones($datos);
        $ok_db = self::guardar_en_db($datos);
        if (!$ok_json)
            registrar_log("ConfiguracionSistema::guardar", "No se pudo escribir configuracion_sistema.json");
        if (!$ok_arca)
            registrar_log("ConfiguracionSistema::guardar", "No se pudo sincronizar arca.php");
        if (!$ok_reparaciones)
            registrar_log("ConfiguracionSistema::guardar", "No se pudo sincronizar reparaciones");
        registrar_operacion("configuraciones.modelo.guardar_sistema.resultado", [
            "ok_final" => ($ok_db || $ok_json) ? "SI" : "NO",
            "ok_db" => $ok_db ? "SI" : "NO",
            "ok_json" => $ok_json ? "SI" : "NO",
            "ok_arca" => $ok_arca ? "SI" : "NO",
            "ok_reparaciones" => $ok_reparaciones ? "SI" : "NO",
            "archivo_json" => self::archivo_configuracion(),
            "json_writable" => is_writable(is_file(self::archivo_configuracion()) ? self::archivo_configuracion() : dirname(self::archivo_configuracion())) ? "SI" : "NO",
            "campos" => array_keys($entrada),
        ]);
        return $ok_db || $ok_json;
    }

    public static function restablecer(): bool {
        $archivo = self::archivo_configuracion();
        if (is_file($archivo))
            @unlink($archivo);

        $datos = self::valores_defecto();
        $ok_arca = self::sincronizar_arca($datos);
        $ok_reparaciones = self::sincronizar_reparaciones($datos);
        $ok_db = self::guardar_en_db($datos);
        return $ok_db || $ok_arca || $ok_reparaciones;
    }

    private static function obtener_desde_db(): array {
        $datos = [];
        if (!function_exists("obtener_pdo"))
            require_once __DIR__ . "/../../configuraciones/base_datos.php";
        $pdo = obtener_pdo();
        if ($pdo === null)
            return $datos;
        try {
            asegurar_tabla_configuraciones($pdo);
            $permitidos = array_flip(array_keys(self::valores_defecto()));
            $st = $pdo->query("SELECT clave, valor FROM configuraciones");
            $rows = $st ? $st->fetchAll() : [];
            $claves_presentes = [];
            foreach ($rows as $row) {
                $clave = (string)($row["clave"] ?? "");
                if (isset($permitidos[$clave])) {
                    $datos[$clave] = (string)($row["valor"] ?? "");
                    $claves_presentes[$clave] = true;
                }
            }
            $normalizados = self::normalizar($datos);
            $datos = [];
            foreach ($claves_presentes as $clave => $_)
                $datos[$clave] = $normalizados[$clave] ?? "";
        } catch (Throwable $e) {
            registrar_log("ConfiguracionSistema::obtener_desde_db", $e->getMessage());
            $datos = [];
        }
        return $datos;
    }

    private static function guardar_en_db(array $datos): bool {
        if (!function_exists("obtener_pdo"))
            require_once __DIR__ . "/../../configuraciones/base_datos.php";
        $pdo = obtener_pdo();
        if ($pdo === null) {
            registrar_operacion("configuraciones.modelo.guardar_db.error", [
                "error" => "Sin conexion PDO",
                "campos" => array_keys($datos),
            ]);
            return false;
        }
        try {
            asegurar_tabla_configuraciones($pdo);
            $normalizados = self::normalizar($datos);
            $st = $pdo->prepare("INSERT INTO configuraciones (clave, valor, tipo, grupo) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), grupo = VALUES(grupo)");
            foreach ($normalizados as $clave => $valor)
                $st->execute([$clave, (string)$valor, self::tipo_config($clave), self::grupo_config($clave)]);
            if (function_exists("config_cache_reset"))
                config_cache_reset();
            return true;
        } catch (Throwable $e) {
            registrar_log("ConfiguracionSistema::guardar_en_db", $e->getMessage());
            registrar_operacion("configuraciones.modelo.guardar_db.error", [
                "error" => $e->getMessage(),
                "campos" => array_keys($datos),
            ]);
            return false;
        }
    }

    public static function obtener_configuracion_fiscal(): array {
        $arca = self::configuracion_arca_base();
        $datos = self::obtener();
        $arca["empresa"] = array_merge($arca["empresa"] ?? [], [
            "nombre_comercio" => $datos["nombre_comercio"],
            "cuit" => $datos["cuit"],
            "punto_venta" => (int)$datos["punto_venta"],
            "condicion_iva" => $datos["condicion_iva"],
            "razon_social" => self::razon_social_para_comprobante($datos),
            "domicilio" => self::domicilio_completo($datos),
            "ingresos_brutos" => $datos["ingresos_brutos"],
            "inicio_actividades" => $datos["inicio_actividades"],
            "telefonos" => $datos["telefonos"],
            "whatsapp" => $datos["whatsapp"],
            "email" => $datos["email"],
            "sitio_web" => $datos["sitio_web"],
            "formato_impresion_ticket" => $datos["formato_impresion_ticket"],
            "texto_pie_ticket" => $datos["texto_pie_ticket"],
            "ticket_fuente" => $datos["ticket_fuente"],
            "ticket_tamano_fuente" => $datos["ticket_tamano_fuente"],
            "logo_ticket" => $datos["logo_ticket"],
            "ticket_imagen_completa" => $datos["ticket_imagen_completa"],
            "ticket_logo_termico" => $datos["ticket_logo_termico"],
        ]);
        return $arca;
    }

    public static function domicilio_completo(array $datos): string {
        $partes = [];
        foreach (["domicilio", "localidad", "provincia"] as $clave) {
            $valor = trim((string)($datos[$clave] ?? ""));
            if ($valor !== "")
                $partes[] = $valor;
        }
        return implode(", ", $partes);
    }

    public static function razon_social_para_comprobante(array $datos): string {
        $razon = trim((string)($datos["razon_social"] ?? ""));
        if ($razon !== "")
            return $razon;
        return trim((string)($datos["nombre_comercio"] ?? ""));
    }

    private static function normalizar(array $entrada): array {
        $permitidos = array_flip(array_keys(self::valores_defecto()));
        $datos = [];
        foreach ($entrada as $clave => $valor) {
            $clave = (string)$clave;
            if (!isset($permitidos[$clave]))
                continue;
            if ($clave === "punto_venta")
                $datos[$clave] = max(1, (int)$valor);
            else if (in_array($clave, ["mostrar_reparaciones", "configuracion_separada", "navbar_mostrar_marca", "navbar_mostrar_config", "navbar_mostrar_usuario", "navbar_mostrar_rol", "navbar_mostrar_cambio_modulo", "navbar_mostrar_salir", "backup_b2_habilitado", "backup_automatico", "backup_local_habilitado", "backup_auto_local", "backup_auto_backblaze", "controlar_stock_ventas", "ticket_imagen_completa", "ticket_logo_termico"], true))
                $datos[$clave] = ((string)$valor === "1") ? "1" : "0";
            else if ($clave === "navbar_boton_opacidad") {
                $opacidad = max(0, min(100, (int)$valor));
                $datos[$clave] = (string)$opacidad;
            }
            else if ($clave === "productos_cotizacion_dolar") {
                $cotizacion = (float)str_replace(",", ".", trim((string)$valor));
                $datos[$clave] = (string)max(0.0001, $cotizacion);
            }
            else if ($clave === "navbar_fondo_modo") {
                $datos[$clave] = "colores";
            }
            else if ($clave === "auth_modo") {
                $modo = trim((string)$valor);
                $datos[$clave] = in_array($modo, ["login", "sin_login"], true) ? $modo : "login";
            }
            else if ($clave === "backup_frecuencia") {
                $frecuencia = trim((string)$valor);
                $datos[$clave] = in_array($frecuencia, ["diario", "semanal", "manual"], true) ? $frecuencia : "diario";
            }
            else if ($clave === "backup_hora") {
                $hora = trim((string)$valor);
                $datos[$clave] = preg_match('/^\d{2}:\d{2}$/', $hora) ? $hora : "18:55";
            }
            else if ($clave === "backup_aviso_minutos") {
                $datos[$clave] = (string)max(0, min(180, (int)$valor));
            }
            else if ($clave === "formato_impresion_ticket") {
                $formato = trim((string)$valor);
                $datos[$clave] = in_array($formato, ["a4", "80", "58"], true) ? $formato : "80";
            }
            else if ($clave === "ticket_fuente") {
                $fuente = trim((string)$valor);
                $datos[$clave] = in_array($fuente, ["Arial", "Verdana", "Courier New", "Tahoma"], true) ? $fuente : "Arial";
            }
            else if ($clave === "ticket_tamano_fuente") {
                $datos[$clave] = (string)max(10, min(18, (int)$valor));
            }
            else if ($clave === "balanza_modo") {
                $modo = trim((string)$valor);
                $datos[$clave] = in_array($modo, ["auto", "cantidad", "importe"], true) ? $modo : "auto";
            }
            else if ($clave === "balanza_plu_digitos") {
                $datos[$clave] = (string)max(1, min(8, (int)$valor));
            }
            else if (in_array($clave, ["balanza_valor_decimales", "balanza_importe_decimales"], true)) {
                $datos[$clave] = (string)max(0, min(4, (int)$valor));
            }
            else if (in_array($clave, ["balanza_prefijos_cantidad", "balanza_prefijos_importe"], true)) {
                $partes = preg_split('/[,\s;]+/', (string)$valor) ?: [];
                $limpios = [];
                foreach ($partes as $parte) {
                    $prefijo = preg_replace('/\D+/', '', (string)$parte) ?? "";
                    if ($prefijo !== "" && strlen($prefijo) <= 4)
                        $limpios[] = $prefijo;
                }
                $datos[$clave] = implode(",", array_values(array_unique($limpios)));
            }
            else if ($clave === "color_acento") {
                $color = trim((string)$valor);
                $datos[$clave] = preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : "#1f6f8b";
            } else if (in_array($clave, ["color_secundario", "color_fondo", "color_fondo_secundario", "color_tarjetas", "color_texto", "color_texto_suave", "color_borde", "color_panel_inicio", "color_panel_inicio_2", "navbar_color_1", "navbar_color_2", "navbar_texto_color", "navbar_boton_fondo", "navbar_boton_borde"], true)) {
                $color = trim((string)$valor);
                $datos[$clave] = preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : (string)self::valores_defecto()[$clave];
            } else if ($clave === "tema_paneles") {
                $tema = trim((string)$valor);
                $datos[$clave] = in_array($tema, ["claro", "compacto", "alto_contraste"], true) ? $tema : "claro";
            }
            else
                $datos[$clave] = trim((string)$valor);
        }
        return $datos;
    }

    private static function tipo_config(string $clave): string {
        if (str_contains($clave, "color"))
            return "color";
        if (in_array($clave, ["ticket_imagen_completa", "ticket_logo_termico"], true) || str_starts_with($clave, "mostrar_") || str_contains($clave, "_habilitado") || str_contains($clave, "_auto") || str_contains($clave, "_sonido") || str_contains($clave, "_toasts") || str_contains($clave, "_alertas") || str_contains($clave, "_logs") || str_contains($clave, "_animaciones") || str_contains($clave, "_sombras") || str_contains($clave, "_escaner") || str_contains($clave, "_etiquetas"))
            return "booleano";
        if (str_contains($clave, "decimales") || str_contains($clave, "tiempo") || str_contains($clave, "tamano") || str_contains($clave, "radio") || $clave === "punto_venta" || $clave === "navbar_boton_opacidad" || $clave === "productos_cotizacion_dolar")
            return "numero";
        if (str_contains($clave, "logo") || str_contains($clave, "favicon") || str_contains($clave, "imagen"))
            return "archivo";
        return "texto";
    }

    private static function grupo_config(string $clave): string {
        if (in_array($clave, ["nombre_comercio", "razon_social", "cuit", "condicion_iva", "domicilio", "localidad", "provincia", "telefonos", "whatsapp", "email", "sitio_web", "ingresos_brutos", "inicio_actividades", "punto_venta"], true))
            return "comercio";
        if (str_starts_with($clave, "ventas_") || in_array($clave, ["controlar_stock_ventas"], true))
            return "ventas";
        if (str_starts_with($clave, "productos_") || str_starts_with($clave, "balanza_"))
            return "productos";
        if (str_starts_with($clave, "clientes_"))
            return "clientes";
        if (str_starts_with($clave, "listas_"))
            return "listas";
        if (str_starts_with($clave, "notificaciones_"))
            return "notificaciones";
        if (str_starts_with($clave, "seguridad_") || $clave === "auth_modo")
            return "seguridad";
        if (str_starts_with($clave, "backup_"))
            return "backup";
        if (str_contains($clave, "ticket") || str_starts_with($clave, "formato_impresion"))
            return "impresion";
        if (str_starts_with($clave, "navbar_"))
            return "menu";
        if (in_array($clave, ["logo", "favicon", "color_acento", "color_secundario", "color_fondo", "color_fondo_secundario", "color_tarjetas", "color_texto", "color_texto_suave", "color_borde", "color_panel_inicio", "color_panel_inicio_2", "tema_paneles", "tema_modo", "ui_radio_bordes", "ui_tamano_tarjetas", "ui_sombras", "ui_animaciones", "imagen_panel"], true))
            return "apariencia";
        return "sistema";
    }

    private static function archivo_configuracion(): string {
        return __DIR__ . "/../../almacenamiento/configuracion_sistema.json";
    }

    private static function archivo_arca(): string {
        return __DIR__ . "/../../configuraciones/arca.php";
    }

    private static function archivo_reparaciones_config(): string {
        return __DIR__ . "/../../reparaciones_python/comercio_config.json";
    }

    private static function configuracion_arca_base(): array {
        $archivo = self::archivo_arca();
        if (is_file($archivo)) {
            $config = require $archivo;
            if (is_array($config))
                return $config;
        }
        return [
            "habilitado" => false,
            "modo" => "homologacion",
            "proveedor" => "api_rest",
            "timeout_segundos" => 20,
            "api_rest" => ["endpoint" => "", "token" => ""],
            "empresa" => [],
            "comprobante_defecto" => ["tipo" => 6, "concepto" => 1, "moneda" => "PES", "cotizacion" => 1, "iva_porcentaje" => 21, "copia" => "ORIGINAL", "remito" => ""],
        ];
    }

    private static function sincronizar_arca(array $datos): bool {
        $config = self::configuracion_arca_base();
        $config["empresa"] = array_merge($config["empresa"] ?? [], [
            "cuit" => $datos["cuit"],
            "punto_venta" => (int)$datos["punto_venta"],
            "condicion_iva" => $datos["condicion_iva"],
            "razon_social" => self::razon_social_para_comprobante($datos),
            "domicilio" => self::domicilio_completo($datos),
            "ingresos_brutos" => $datos["ingresos_brutos"],
            "inicio_actividades" => $datos["inicio_actividades"],
        ]);
        $contenido = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        return @file_put_contents(self::archivo_arca(), $contenido) !== false;
    }

    private static function sincronizar_reparaciones(array $datos): bool {
        $config = [
            "nombre" => self::razon_social_para_comprobante($datos) !== "" ? self::razon_social_para_comprobante($datos) : "MI COMERCIO",
            "telefono" => trim((string)($datos["telefonos"] ?? "")),
            "direccion" => self::domicilio_completo($datos),
            "documento" => trim((string)($datos["cuit"] ?? "")),
            "email" => trim((string)($datos["email"] ?? "")),
            "observaciones" => trim((string)($datos["sitio_web"] ?? "")),
            "imagen_panel" => trim((string)($datos["imagen_panel"] ?? "")),
            "color_fondo" => trim((string)($datos["color_fondo"] ?? "#f4f6f8")),
            "color_tarjetas" => trim((string)($datos["color_tarjetas"] ?? "#ffffff")),
            "color_texto" => trim((string)($datos["color_texto"] ?? "#203040")),
            "color_texto_suave" => trim((string)($datos["color_texto_suave"] ?? "#657789")),
            "color_borde" => trim((string)($datos["color_borde"] ?? "#dbe3ea")),
            "color_panel_inicio" => trim((string)($datos["color_panel_inicio"] ?? "#155e75")),
            "color_panel_inicio_2" => trim((string)($datos["color_panel_inicio_2"] ?? "#48aaa5")),
        ];
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json))
            return false;
        return @file_put_contents(self::archivo_reparaciones_config(), $json) !== false;
    }
}
