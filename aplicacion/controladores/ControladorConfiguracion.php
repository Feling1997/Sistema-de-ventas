<?php
require_once __DIR__ . "/../modelos/Configuracion.php";
require_once __DIR__ . "/../modelos/RespaldoSistema.php";
require_once __DIR__ . "/../modelos/BackblazeB2.php";
require_once __DIR__ . "/../modelos/Stock.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";

class ControladorConfiguracion {
    private function permiso_admin(): bool {
        $ok = false;
        if (!require_login()) {
            flash_error("Tenes que iniciar sesion.");
            redirigir("index.php?c=auth&a=login");
        } else {
            if (!require_rol(["ADMIN"])) {
                flash_error("No tenes permiso para acceder a Configuracion.");
                redirigir("index.php?c=ventas&a=lista");
            } else
                $ok = true;
        }
        return $ok;
    }

    private function secciones(): array {
        $secciones = [
            "inicio" => ["titulo" => "Centro de configuracion", "icono" => "bi-grid-1x2-fill", "texto" => "Panel general"],
            "apariencia" => ["titulo" => "Apariencia", "icono" => "bi-palette-fill", "texto" => "Tema, colores, logo"],
            "comercio" => ["titulo" => "Comercio", "icono" => "bi-shop", "texto" => "Datos fiscales"],
            "menu" => ["titulo" => "Menu", "icono" => "bi-grid-fill", "texto" => "Modulos y orden"],
            "ventas" => ["titulo" => "Ventas", "icono" => "bi-cart-check", "texto" => "Descuentos e impresion"],
            "productos" => ["titulo" => "Productos", "icono" => "bi-box-seam-fill", "texto" => "Codigos e importacion"],
            "clientes" => ["titulo" => "Clientes", "icono" => "bi-people-fill", "texto" => "Campos y conducta"],
            "impresion" => ["titulo" => "PDF e impresion", "icono" => "bi-printer-fill", "texto" => "Tickets y PDF"],
            "notificaciones" => ["titulo" => "Notificaciones", "icono" => "bi-bell-fill", "texto" => "Alertas y sonidos"],
            "backup" => ["titulo" => "Copias seguridad", "icono" => "bi-cloud-arrow-up-fill", "texto" => "Backblaze y exportaciones"],
            "seguridad" => ["titulo" => "Seguridad", "icono" => "bi-shield-lock-fill", "texto" => "Sesiones y permisos"],
            "sistema" => ["titulo" => "Sistema", "icono" => "bi-gear-fill", "texto" => "Parametros avanzados"]
        ];
        return $secciones;
    }

    private function seccion_actual(): string {
        $secciones = $this->secciones();
        $seccion = trim((string)obtener_get("seccion", "inicio"));
        if (!isset($secciones[$seccion]))
            $seccion = "inicio";
        return $seccion;
    }

    public function index(): void {
        if ($this->permiso_admin()) {
            $config = Configuracion::obtener_todo();
            $secciones = $this->secciones();
            $seccion_actual = $this->seccion_actual();
            $modulos_navbar = menu_modulos_base();
            unset($modulos_navbar["inicio"]);
            unset($modulos_navbar["configuraciones"]);
            unset($modulos_navbar["usuarios"]);
            $carpeta_respaldos = realpath(__DIR__ . "/../../respaldos") ?: (__DIR__ . "/../../respaldos");
            $b2_configurado = BackblazeB2::configurado($config);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/configuracion/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function guardar(): void {
        if ($this->permiso_admin()) {
            $seccion = trim((string)obtener_post("seccion", "inicio"));
            $error = "";
            if ($_SERVER["REQUEST_METHOD"] !== "POST")
                $error = "Acceso invalido.";
            elseif (!csrf_valido(obtener_post("csrf", "")))
                $error = "Token invalido. Recarga la pagina.";
            if ($error === "") {
                $actual = Configuracion::obtener_todo();
                $datos = $_POST["config"] ?? [];
                if (!is_array($datos))
                    $datos = [];
                $datos = $this->completar_booleanos($seccion, $datos);
                if ($seccion === "apariencia") {
                    $datos["logo"] = Configuracion::guardar_archivo("logo_archivo", (string)($actual["logo"] ?? ""), "logo_sistema");
                    $datos["favicon"] = Configuracion::guardar_archivo("favicon_archivo", (string)($actual["favicon"] ?? ""), "favicon_sistema");
                    $datos["imagen_panel"] = Configuracion::guardar_archivo("imagen_panel_archivo", (string)($actual["imagen_panel"] ?? ""), "panel_fondo");
                }
                if ($seccion === "comercio")
                    $datos["logo_ticket"] = Configuracion::guardar_archivo("logo_ticket_archivo", (string)($actual["logo_ticket"] ?? ""), "ticket_logo");
                if ($seccion === "impresion")
                    $datos["logo_ticket"] = Configuracion::guardar_archivo("logo_ticket_archivo", (string)($actual["logo_ticket"] ?? ""), "ticket_logo");
                if ($seccion === "menu")
                    $datos = $this->normalizar_menu_post($datos);
                if ($seccion === "backup" && trim((string)($datos["backup_b2_application_key"] ?? "")) === "")
                    $datos["backup_b2_application_key"] = (string)($actual["backup_b2_application_key"] ?? "");
                registrar_log("Configuracion", "Guardando seccion " . $seccion);
                registrar_operacion("configuracion.guardar.intento", [
                    "seccion" => $seccion,
                    "campos" => array_keys($datos),
                    "valores" => $datos,
                ]);
                $ok_guardado = Configuracion::guardar($datos);
                if ($ok_guardado && $seccion === "productos" && array_key_exists("productos_cotizacion_dolar", $datos))
                    Stock::recalcular_costos_por_cotizacion();
                $verificacion = Configuracion::obtener_todo();
                $verificados = [];
                foreach ($datos as $clave => $valor)
                    $verificados[$clave] = $verificacion[$clave] ?? null;
                registrar_operacion("configuracion.guardar.resultado", [
                    "seccion" => $seccion,
                    "ok" => $ok_guardado ? "SI" : "NO",
                    "valores_guardados" => $verificados,
                ]);
                if ($ok_guardado)
                    flash_ok("Configuracion guardada.");
                else
                    flash_error("No se pudo guardar la configuracion. Revisa logs.");
            } else {
                registrar_operacion("configuracion.guardar.rechazado", [
                    "seccion" => $seccion,
                    "error" => $error,
                    "post" => $_POST,
                ]);
                flash_error($error);
            }
            redirigir("index.php?c=configuracion&a=index&seccion=" . urlencode($seccion));
        }
    }

    public function restablecer(): void {
        if ($this->permiso_admin()) {
            $seccion = trim((string)obtener_post("seccion", "apariencia"));
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                flash_error("Acceso invalido.");
            } else {
                if (Configuracion::restablecer_grupo($seccion))
                    flash_ok("Seccion restablecida.");
                else
                    flash_error("No se pudo restablecer la seccion.");
            }
            redirigir("index.php?c=configuracion&a=index&seccion=" . urlencode($seccion));
        }
    }

    public function previsualizar_logo_ticket(): void {
        if ($this->permiso_admin()) {
            header("Content-Type: application/json; charset=utf-8");
            $respuesta = ["ok" => false, "logo" => ""];
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                $respuesta["error"] = "Solicitud invalida.";
            } elseif (!isset($_FILES["logo_ticket_archivo"]) || !is_array($_FILES["logo_ticket_archivo"])) {
                $respuesta["error"] = "Archivo no recibido.";
            } else {
                $archivo = $_FILES["logo_ticket_archivo"];
                $tmp = (string)($archivo["tmp_name"] ?? "");
                $nombre = (string)($archivo["name"] ?? "");
                $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
                $permitidas = ["jpg", "jpeg", "png", "gif", "webp"];
                if (!is_uploaded_file($tmp) || !in_array($ext, $permitidas, true)) {
                    $respuesta["error"] = "Imagen no valida.";
                } else {
                    $formato = (string)obtener_post("formato_impresion_ticket", "80");
                    $modo_termico = (string)obtener_post("ticket_logo_termico", "1") === "1";
                    $carpeta_tmp = __DIR__ . "/../../almacenamiento/tickets/logos_procesados";
                    if (!is_dir($carpeta_tmp))
                        @mkdir($carpeta_tmp, 0777, true);
                    $tmp_copia = $carpeta_tmp . "/preview_logo_original." . $ext;
                    if (@copy($tmp, $tmp_copia)) {
                        $ruta = $modo_termico ? procesar_logo_ticket_termico_hd($tmp_copia, $formato === "58" ? 384 : 576, true) : ruta_relativa_proyecto($tmp_copia);
                        $png = resolver_ruta_proyecto($ruta);
                        $bytes = is_file($png) ? @file_get_contents($png) : false;
                        if (is_string($bytes) && $bytes !== "") {
                            $respuesta["ok"] = true;
                            $respuesta["logo"] = $modo_termico ? $ruta : "data:image/" . ($ext === "jpg" ? "jpeg" : $ext) . ";base64," . base64_encode($bytes);
                        } else
                            $respuesta["error"] = "No se pudo procesar la imagen.";
                    } else
                        $respuesta["error"] = "No se pudo copiar la imagen.";
                }
            }
            echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    public function logo_ticket_actual(): void {
        if ($this->permiso_admin()) {
            $config = Configuracion::obtener_todo();
            $logo_rel = trim((string)($config["logo_ticket"] ?? ""));
            $base = realpath(__DIR__ . "/../../");
            $logo_path = $logo_rel !== "" ? realpath(__DIR__ . "/../../" . $logo_rel) : false;
            if ($base === false || $logo_path === false || !str_starts_with($logo_path, $base) || !is_file($logo_path)) {
                http_response_code(404);
                return;
            }
            $modo_get = trim((string)obtener_get("termico", ""));
            $formato_get = trim((string)obtener_get("formato", ""));
            $modo_termico = $modo_get !== "" ? $modo_get === "1" : (string)($config["ticket_logo_termico"] ?? "1") === "1";
            $formato = in_array($formato_get, ["58", "80"], true) ? $formato_get : (string)($config["formato_impresion_ticket"] ?? "80");
            $ruta_logo = $modo_termico ? procesar_logo_ticket_termico_hd($logo_path, $formato === "58" ? 384 : 576, true) : ruta_relativa_proyecto($logo_path);
            $archivo_logo = resolver_ruta_proyecto($ruta_logo);
            if (!is_file($archivo_logo)) {
                http_response_code(500);
                return;
            }
            $ext = strtolower(pathinfo($archivo_logo, PATHINFO_EXTENSION));
            $mime = ["jpg" => "image/jpeg", "jpeg" => "image/jpeg", "png" => "image/png", "gif" => "image/gif", "webp" => "image/webp"][$ext] ?? "image/png";
            header("Content-Type: " . $mime);
            header("Cache-Control: no-store, max-age=0");
            readfile($archivo_logo);
        }
    }

    public function guardar_logo_ticket_procesado(): void {
        if ($this->permiso_admin()) {
            header("Content-Type: application/json; charset=utf-8");
            $respuesta = ["ok" => false, "ruta" => ""];
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                $respuesta["error"] = "Solicitud invalida.";
            } else {
                $data = trim((string)obtener_post("logo_png", ""));
                $formato = trim((string)obtener_post("formato", "80"));
                $formato = $formato === "58" ? "58" : "80";
                if (!preg_match('/^data:image\/png;base64,/', $data)) {
                    $respuesta["error"] = "Imagen procesada invalida.";
                } else {
                    $bytes = base64_decode(substr($data, strpos($data, ",") + 1), true);
                    if (!is_string($bytes) || $bytes === "") {
                        $respuesta["error"] = "No se pudo decodificar la imagen.";
                    } else {
                        $config = Configuracion::obtener_todo();
                        $logo_rel = trim((string)($config["logo_ticket"] ?? "ticket_logo"));
                        $base_nombre = pathinfo($logo_rel, PATHINFO_FILENAME);
                        $base_nombre = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $base_nombre) ?: "ticket_logo";
                        $carpeta = __DIR__ . "/../../almacenamiento/tickets/logos_procesados";
                        if (!is_dir($carpeta))
                            @mkdir($carpeta, 0777, true);
                        $destino = $carpeta . "/" . $base_nombre . "_termico_hd_" . $formato . ".png";
                        if (@file_put_contents($destino, $bytes) !== false) {
                            @file_put_contents($destino . ".ok", "canvas-hd");
                            $respuesta["ok"] = true;
                            $respuesta["ruta"] = ruta_relativa_proyecto($destino);
                        } else
                            $respuesta["error"] = "No se pudo guardar la imagen procesada.";
                    }
                }
            }
            echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    public function generar_respaldo(): void {
        if ($this->permiso_admin()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=configuracion&a=index&seccion=backup");
            } else {
                $resultado = $this->ejecutar_respaldo_destinos(["interno"], "");
                if ($resultado["ok"])
                    flash_ok($resultado["mensaje"]);
                else
                    flash_error($resultado["mensaje"]);
                redirigir("index.php?c=configuracion&a=index&seccion=backup");
            }
        }
    }

    public function ejecutar_respaldo(): void {
        if ($this->permiso_admin()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                flash_error("Acceso invalido.");
            } else {
                $destinos = $_POST["destinos"] ?? [];
                if (!is_array($destinos))
                    $destinos = [];
                $destinos = array_values(array_unique(array_map("strval", $destinos)));
                $carpeta = trim((string)obtener_post("carpeta_destino", ""));
                $resultado = $this->ejecutar_respaldo_destinos($destinos, $carpeta);
                if ($resultado["ok"])
                    flash_ok($resultado["mensaje"]);
                else
                    flash_error($resultado["mensaje"]);
            }
            redirigir("index.php?c=configuracion&a=index&seccion=backup");
        }
    }

    public function descargar_respaldo_pc(): void {
        if ($this->permiso_admin()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                http_response_code(400);
                header("Content-Type: text/plain; charset=utf-8");
                echo "Solicitud invalida.";
                return;
            }
            registrar_log("Backup", "POST recibido descarga PC");
            $respaldo = RespaldoSistema::generar();
            if (empty($respaldo["ok"])) {
                $mensaje = (string)($respaldo["mensaje"] ?? "No se pudo generar el respaldo.");
                Configuracion::guardar(["backup_ultimo_estado" => "error", "backup_ultimo_error" => $mensaje]);
                registrar_log("Backup", $mensaje);
                http_response_code(500);
                header("Content-Type: text/plain; charset=utf-8");
                echo $mensaje;
                return;
            }
            $ruta = (string)($respaldo["ruta"] ?? "");
            $nombre = basename((string)($respaldo["nombre"] ?? $ruta));
            Configuracion::guardar([
                "backup_ultimo" => date("Y-m-d H:i:s"),
                "backup_ultimo_estado" => "ok",
                "backup_ultimo_error" => "",
            ]);
            registrar_log("Backup", "Backup descargado a PC: " . $nombre);
            header("Content-Type: application/gzip");
            header("Content-Length: " . (string)filesize($ruta));
            header("Content-Disposition: attachment; filename=\"" . str_replace('"', '', $nombre) . "\"");
            header("Cache-Control: no-store, max-age=0");
            readfile($ruta);
            return;
        }
    }

    public function ejecutar_respaldo_programado(): void {
        if ($this->permiso_admin()) {
            header("Content-Type: application/json; charset=utf-8");
            $respuesta = ["ok" => false, "mensaje" => "No se pudo ejecutar el backup automatico."];
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                $respuesta["mensaje"] = "Solicitud invalida. Recarga la pagina.";
            } else {
                $config = Configuracion::obtener_todo();
                $hoy = date("Y-m-d");
                $ultimo_auto = (string)($config["backup_auto_ultimo_dia"] ?? "");
                $frecuencia = (string)($config["backup_frecuencia"] ?? "diario");
                $ya_realizado = $ultimo_auto === $hoy;
                if (!$ya_realizado && $frecuencia === "semanal" && $ultimo_auto !== "") {
                    $ts_ultimo = strtotime($ultimo_auto);
                    $ya_realizado = $ts_ultimo !== false && $ts_ultimo >= strtotime("-6 days");
                }
                if ($ya_realizado) {
                    $respuesta = ["ok" => true, "mensaje" => "El backup automatico ya fue realizado para esta frecuencia."];
                } elseif ((string)($config["backup_automatico"] ?? "0") !== "1" || $frecuencia === "manual") {
                    $respuesta["mensaje"] = "El backup automatico no esta activado.";
                } else {
                    $destinos = [];
                    $omitir_local_navegador = (string)obtener_post("omitir_local_navegador", "0") === "1";
                    if ((string)($config["backup_auto_local"] ?? "0") === "1" && !$omitir_local_navegador)
                        $destinos[] = "local_config";
                    if ((string)($config["backup_auto_backblaze"] ?? "0") === "1")
                        $destinos[] = "backblaze";
                    if (count($destinos) === 0) {
                        $respuesta = ["ok" => true, "mensaje" => "Backup local realizado desde el navegador."];
                        Configuracion::guardar(["backup_auto_ultimo_dia" => $hoy]);
                    } else {
                        $resultado = $this->ejecutar_respaldo_destinos($destinos, "");
                        if ($resultado["ok"])
                            Configuracion::guardar(["backup_auto_ultimo_dia" => $hoy]);
                        $respuesta = $resultado;
                    }
                }
            }
            echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    private function ejecutar_respaldo_destinos(array $destinos, string $carpeta_manual): array {
        $permitidos = ["interno", "carpeta", "local_config", "backblaze"];
        $destinos = array_values(array_intersect(array_unique($destinos), $permitidos));
        if (count($destinos) === 0)
            return ["ok" => false, "mensaje" => "Elegí al menos un destino para el backup."];

        registrar_log("Backup", "POST recibido");
        $config = Configuracion::obtener_todo();
        $respaldo = RespaldoSistema::generar();
        if (empty($respaldo["ok"])) {
            $mensaje = (string)($respaldo["mensaje"] ?? "No se pudo generar el respaldo.");
            Configuracion::guardar(["backup_ultimo_estado" => "error", "backup_ultimo_error" => $mensaje]);
            registrar_log("Backup", $mensaje);
            return ["ok" => false, "mensaje" => $mensaje];
        }

        $ruta = (string)($respaldo["ruta"] ?? "");
        $ok = [];
        $errores = [];
        foreach ($destinos as $destino) {
            if ($destino === "interno") {
                $ok[] = "carpeta interna: " . basename($ruta);
            } elseif ($destino === "carpeta") {
                $copia = RespaldoSistema::copiarA($ruta, $carpeta_manual);
                if (!empty($copia["ok"]))
                    $ok[] = "carpeta elegida: " . (string)($copia["ruta"] ?? "");
                else
                    $errores[] = "carpeta elegida: " . (string)($copia["mensaje"] ?? "No se pudo copiar.");
            } elseif ($destino === "local_config") {
                $carpeta_config = trim((string)($config["backup_local_carpeta"] ?? ""));
                if ((string)($config["backup_local_habilitado"] ?? "0") !== "1")
                    $errores[] = "copia local: destino local desactivado.";
                else {
                    $copia = RespaldoSistema::copiarA($ruta, $carpeta_config);
                    if (!empty($copia["ok"]))
                        $ok[] = "copia local: " . (string)($copia["ruta"] ?? "");
                    else
                        $errores[] = "copia local: " . (string)($copia["mensaje"] ?? "No se pudo copiar.");
                }
            } elseif ($destino === "backblaze") {
                $subida = BackblazeB2::subir($ruta, $config);
                if (!empty($subida["ok"]))
                    $ok[] = "Backblaze B2: " . (string)($subida["fileName"] ?? "subido");
                else
                    $errores[] = "Backblaze B2: " . (string)($subida["mensaje"] ?? "No se pudo subir.");
            }
        }

        $estado = count($errores) === 0 ? "ok" : (count($ok) > 0 ? "parcial" : "error");
        $mensaje_ok = count($ok) > 0 ? "Backup realizado en " . implode(" | ", $ok) . "." : "";
        $mensaje_error = count($errores) > 0 ? "Errores: " . implode(" | ", $errores) : "";
        $mensaje = trim($mensaje_ok . " " . $mensaje_error);
        Configuracion::guardar([
            "backup_ultimo" => date("Y-m-d H:i:s"),
            "backup_ultimo_estado" => $estado,
            "backup_ultimo_error" => count($errores) > 0 ? implode(" | ", $errores) : "",
        ]);
        registrar_log("Backup", $mensaje);
        return ["ok" => count($ok) > 0, "mensaje" => $mensaje, "estado" => $estado, "errores" => $errores];
    }

    private function completar_booleanos(string $seccion, array $datos): array {
        $booleanos = [
            "apariencia" => ["ui_sombras", "ui_animaciones"],
            "ventas" => ["ventas_rapidas", "ventas_consumidor_final_auto", "ventas_confirmar_cierre", "ventas_sonido_confirmacion", "controlar_stock_ventas"],
            "productos" => ["productos_multiples_listas", "productos_mostrar_stock_minimo", "productos_permitir_stock_negativo", "productos_activar_escaner", "productos_etiquetas", "productos_importacion_excel", "productos_reglas_automaticas"],
            "clientes" => ["clientes_campos_extra", "clientes_validar_documento"],
            "listas" => ["listas_actualizar_costo"],
            "notificaciones" => ["notificaciones_sonidos", "notificaciones_toasts", "notificaciones_alertas", "notificaciones_stock_bajo", "notificaciones_ventas", "notificaciones_backup"],
            "seguridad" => ["seguridad_2fa_futuro", "seguridad_bloqueos", "seguridad_logs"],
            "backup" => ["backup_b2_habilitado", "backup_google_drive_futuro", "backup_automatico", "backup_local_habilitado", "backup_auto_local", "backup_auto_backblaze"],
            "impresion" => ["ticket_imagen_completa", "ticket_logo_termico"],
            "sistema" => ["mostrar_reparaciones", "configuracion_separada"]
        ];
        foreach (($booleanos[$seccion] ?? []) as $clave) {
            if (!isset($datos[$clave]))
                $datos[$clave] = "0";
        }
        return $datos;
    }

    private function normalizar_menu_post(array $datos): array {
        $orden = $_POST["navbar_modulos_orden"] ?? [];
        $visibles = $_POST["navbar_modulos_visibles"] ?? [];
        if (!is_array($orden))
            $orden = [];
        if (!is_array($visibles))
            $visibles = [];
        $orden_limpio = [];
        foreach ($orden as $clave) {
            $clave = trim((string)$clave);
            if ($clave !== "" && !in_array($clave, $orden_limpio, true))
                $orden_limpio[] = $clave;
        }
        $visibles_limpio = [];
        foreach ($visibles as $clave) {
            $clave = trim((string)$clave);
            if ($clave !== "" && !in_array($clave, $visibles_limpio, true))
                $visibles_limpio[] = $clave;
        }
        if (count($visibles_limpio) === 0)
            $visibles_limpio = ["__none"];
        $datos["navbar_modulos_orden"] = implode(",", $orden_limpio);
        $datos["navbar_modulos_visibles"] = implode(",", $visibles_limpio);
        return $datos;
    }
}
