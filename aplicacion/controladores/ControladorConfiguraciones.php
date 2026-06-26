<?php

require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";

class ControladorConfiguraciones {
    private function contenedor_configuracion(): \Ventas\Infraestructura\Contenedor\Container {
        global $container;

        if (!$container instanceof \Ventas\Infraestructura\Contenedor\Container) {
            $container = new \Ventas\Infraestructura\Contenedor\Container();
        }

        if (!$container->has(\Ventas\Configuracion\Application\GuardarArchivoConfiguracion::class)) {
            \Ventas\Configuracion\Infrastructure\RegistroConfiguracion::registrar($container);
        }

        $resultado = $container;

        return $resultado;
    }

    private function guardar_archivo_configuracion_modular(string $campo, string $actual, string $nombre_base): string {
        $container = $this->contenedor_configuracion();
        $caso_uso = $container->get(\Ventas\Configuracion\Application\GuardarArchivoConfiguracion::class);
        $resultado = $caso_uso->ejecutar($campo, $actual, $nombre_base);

        return $resultado;
    }

    private function obtener_configuracion_general_modular(): array {
        $container = $this->contenedor_configuracion();
        $caso_uso = $container->get(\Ventas\Configuracion\Application\ObtenerConfiguracionGeneral::class);
        $resultado = $caso_uso->ejecutar();

        return $resultado;
    }

    private function guardar_configuracion_modular(array $datos): bool {
        $container = $this->contenedor_configuracion();
        $caso_uso = $container->get(\Ventas\Configuracion\Application\GuardarConfiguracion::class);
        $resultado = $caso_uso->ejecutar($datos);

        return $resultado;
    }

    private function restablecer_configuracion_modular(): bool {
        $container = $this->contenedor_configuracion();
        $caso_uso = $container->get(\Ventas\Configuracion\Application\RestablecerGrupoConfiguracion::class);
        $grupos = ["comercio", "ventas", "productos", "clientes", "listas", "notificaciones", "seguridad", "backup", "impresion", "menu", "apariencia", "sistema"];
        $resultado = true;

        foreach ($grupos as $grupo) {
            $resultado = $caso_uso->ejecutar($grupo) && $resultado;
        }

        return $resultado;
    }

    private function contenedor_backups(): \Ventas\Infraestructura\Contenedor\Container {
        $resultado = new \Ventas\Infraestructura\Contenedor\Container();
        \Ventas\Backups\Infrastructure\RegistroBackups::registrar($resultado);

        return $resultado;
    }

    private function generar_respaldo_modular(): array {
        $container = $this->contenedor_backups();
        $caso_uso = $container->get(\Ventas\Backups\Application\GenerarRespaldoSistema::class);
        $resultado = $caso_uso->ejecutar();

        return $resultado;
    }

    private function copiar_respaldo_modular(string $origen, string $destino): array {
        $container = $this->contenedor_backups();
        $caso_uso = $container->get(\Ventas\Backups\Application\CopiarRespaldoLocal::class);
        $resultado = $caso_uso->ejecutar($origen, $destino);

        return $resultado;
    }

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

    private function guardar_imagen_config(string $campo, string $actual, string $nombre_base): string {
        $ruta = $actual;
        if (isset($_FILES[$campo]) && is_array($_FILES[$campo])) {
            $archivo = $_FILES[$campo];
            $error = (int)($archivo["error"] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_OK) {
                $tmp = (string)($archivo["tmp_name"] ?? "");
                $nombre = (string)($archivo["name"] ?? "");
                $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
                $permitidas = ["jpg", "jpeg", "png", "gif"];
                if (is_uploaded_file($tmp) && in_array($ext, $permitidas, true)) {
                    $carpeta = __DIR__ . "/../../publico/assets/img";
                    if (!is_dir($carpeta))
                        @mkdir($carpeta, 0777, true);
                    $destino = $carpeta . "/" . $nombre_base . "." . $ext;
                    if (@move_uploaded_file($tmp, $destino))
                        $ruta = "publico/assets/img/" . $nombre_base . "." . $ext;
                } else
                    flash_error("Imagen no valida. Usa JPG, PNG o GIF.");
            }
        }
        return $ruta;
    }

    private function guardar_logo_ticket(string $actual): string {
        $resultado = $this->guardar_archivo_configuracion_modular("logo_ticket_archivo", $actual, "ticket_logo");

        return $resultado;
    }

    private function guardar_imagen_panel(string $actual): string {
        return $this->guardar_imagen_config("imagen_panel_archivo", $actual, "panel_fondo");
    }

    private function guardar_imagen_navbar(string $actual): string {
        return $this->guardar_imagen_config("navbar_imagen_archivo", $actual, "navbar_fondo");
    }

    public function sistema(): void {
        if ($this->permiso_admin()) {
            redirigir("index.php?c=configuracion&a=index");
        }
    }

    public function backup(): void {
        if ($this->permiso_admin()) {
            $config = $this->obtener_configuracion_general_modular();
            $carpeta_respaldos = realpath(__DIR__ . "/../../respaldos") ?: (__DIR__ . "/../../respaldos");
            $container = $this->contenedor_backups();
            $verificador = $container->get(\Ventas\Backups\Application\VerificarBackblazeConfigurado::class);
            $b2_configurado = $verificador->ejecutar($config);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/configuraciones/backup.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function guardar_sistema(): void {
        if ($this->permiso_admin()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                registrar_operacion("configuraciones.guardar_sistema.rechazado", [
                    "error" => "Metodo invalido",
                    "post" => $_POST,
                ]);
                flash_error("Acceso invalido.");
                redirigir("index.php?c=configuraciones&a=sistema" . ((string)obtener_post("seccion_navbar", "") === "reparaciones" ? "&seccion=reparaciones" : ""));
            }

            $csrf = obtener_post("csrf", "");
            if (!csrf_valido((string)$csrf)) {
                registrar_operacion("configuraciones.guardar_sistema.rechazado", [
                    "error" => "Token invalido",
                    "post" => $_POST,
                ]);
                flash_error("Token invalido. Recarga la pagina.");
                redirigir("index.php?c=configuraciones&a=sistema" . ((string)obtener_post("seccion_navbar", "") === "reparaciones" ? "&seccion=reparaciones" : ""));
            }

            $config_actual = $this->obtener_configuracion_general_modular();
            $orden_navbar = $_POST["navbar_modulos_orden"] ?? [];
            $visibles_navbar = $_POST["navbar_modulos_visibles"] ?? [];
            if (!is_array($orden_navbar))
                $orden_navbar = [];
            if (!is_array($visibles_navbar))
                $visibles_navbar = [];
            $bloqueados_navbar = ["configuraciones", "usuarios", "inicio"];
            $orden_navbar = array_values(array_filter($orden_navbar, fn($clave) => !in_array((string)$clave, $bloqueados_navbar, true)));
            $visibles_navbar = array_values(array_filter($visibles_navbar, fn($clave) => !in_array((string)$clave, $bloqueados_navbar, true)));
            if (count($visibles_navbar) === 0)
                $visibles_navbar = ["__none"];
            $datos = [
                "nombre_comercio" => obtener_post("nombre_comercio", ""),
                "razon_social" => obtener_post("razon_social", ""),
                "cuit" => obtener_post("cuit", ""),
                "condicion_iva" => obtener_post("condicion_iva", ""),
                "domicilio" => obtener_post("domicilio", ""),
                "localidad" => obtener_post("localidad", ""),
                "provincia" => obtener_post("provincia", ""),
                "telefonos" => obtener_post("telefonos", ""),
                "whatsapp" => obtener_post("whatsapp", ""),
                "email" => obtener_post("email", ""),
                "sitio_web" => obtener_post("sitio_web", ""),
                "ingresos_brutos" => obtener_post("ingresos_brutos", ""),
                "inicio_actividades" => obtener_post("inicio_actividades", ""),
                "punto_venta" => obtener_post("punto_venta", 1),
                "formato_impresion_ticket" => obtener_post("formato_impresion_ticket", "80"),
                "texto_pie_ticket" => obtener_post("texto_pie_ticket", ""),
                "controlar_stock_ventas" => obtener_post("controlar_stock_ventas", "0"),
                "balanza_modo" => obtener_post("balanza_modo", "auto"),
                "balanza_plu_digitos" => obtener_post("balanza_plu_digitos", "5"),
                "balanza_valor_decimales" => obtener_post("balanza_valor_decimales", "3"),
                "balanza_importe_decimales" => obtener_post("balanza_importe_decimales", "2"),
                "balanza_prefijos_cantidad" => obtener_post("balanza_prefijos_cantidad", "20,21,23,25,27,29"),
                "balanza_prefijos_importe" => obtener_post("balanza_prefijos_importe", "22,24,26,28"),
                "logo_ticket" => $this->guardar_logo_ticket((string)($config_actual["logo_ticket"] ?? "")),
                "url_reparaciones" => normalizar_url_reparaciones((string)obtener_post("url_reparaciones", "")),
                "mostrar_reparaciones" => obtener_post("mostrar_reparaciones", "0"),
                "atajo_reparaciones" => obtener_post("atajo_reparaciones", "F9"),
                "color_acento" => obtener_post("color_acento", "#1f6f8b"),
                "color_fondo" => obtener_post("color_fondo", "#f4f6f8"),
                "color_fondo_secundario" => obtener_post("color_fondo_secundario", "#f9fbfc"),
                "color_tarjetas" => obtener_post("color_tarjetas", "#ffffff"),
                "color_texto" => obtener_post("color_texto", "#203040"),
                "color_texto_suave" => obtener_post("color_texto_suave", "#657789"),
                "color_borde" => obtener_post("color_borde", "#dbe3ea"),
                "color_panel_inicio" => obtener_post("color_panel_inicio", "#155e75"),
                "color_panel_inicio_2" => obtener_post("color_panel_inicio_2", "#48aaa5"),
                "imagen_panel" => $this->guardar_imagen_panel((string)($config_actual["imagen_panel"] ?? "")),
                "navbar_marca_texto" => obtener_post("navbar_marca_texto", "MI COMERCIO"),
                "navbar_mostrar_marca" => "1",
                "navbar_mostrar_config" => "1",
                "navbar_mostrar_usuario" => "1",
                "navbar_mostrar_rol" => obtener_post("navbar_mostrar_rol", "0"),
                "navbar_mostrar_cambio_modulo" => obtener_post("navbar_mostrar_cambio_modulo", "0"),
                "navbar_mostrar_salir" => "1",
                "navbar_fondo_modo" => "colores",
                "navbar_color_1" => obtener_post("navbar_color_1", "#000000"),
                "navbar_color_2" => obtener_post("navbar_color_2", "#1f2937"),
                "navbar_texto_color" => obtener_post("navbar_texto_color", "#ffffff"),
                "navbar_boton_fondo" => obtener_post("navbar_boton_fondo", "#ffffff"),
                "navbar_boton_borde" => obtener_post("navbar_boton_borde", "#ffffff"),
                "navbar_boton_opacidad" => obtener_post("navbar_boton_opacidad", "10"),
                "navbar_imagen" => "",
                "navbar_modulos_orden" => implode(",", array_map("trim", $orden_navbar)),
                "navbar_modulos_visibles" => implode(",", array_map("trim", $visibles_navbar)),
                "tema_paneles" => obtener_post("tema_paneles", "claro"),
                "backup_b2_habilitado" => obtener_post("backup_b2_habilitado", "0"),
                "backup_b2_key_id" => obtener_post("backup_b2_key_id", ""),
                "backup_b2_application_key" => trim((string)obtener_post("backup_b2_application_key", "")) !== ""
                    ? obtener_post("backup_b2_application_key", "")
                    : (string)($config_actual["backup_b2_application_key"] ?? ""),
                "backup_b2_bucket_id" => obtener_post("backup_b2_bucket_id", ""),
                "backup_b2_bucket_name" => obtener_post("backup_b2_bucket_name", ""),
                "backup_b2_carpeta" => obtener_post("backup_b2_carpeta", "ventas-reparaciones"),
                "auth_modo" => obtener_post("auth_modo", "login"),
            ];

            if (texto_invalido((string)$datos["nombre_comercio"])) {
                registrar_operacion("configuraciones.guardar_sistema.rechazado", [
                    "error" => "Nombre comercio invalido",
                    "datos" => $datos,
                ]);
                flash_error("El nombre del comercio es obligatorio.");
                redirigir("index.php?c=configuraciones&a=sistema" . ((string)obtener_post("seccion_navbar", "") === "reparaciones" ? "&seccion=reparaciones" : ""));
            }

            registrar_operacion("configuraciones.guardar_sistema.intento", [
                "campos" => array_keys($datos),
                "datos" => $datos,
            ]);
            $ok_guardado = $this->guardar_configuracion_modular($datos);
            $verificacion = $this->obtener_configuracion_general_modular();
            $verificados = [];
            foreach ($datos as $clave => $valor)
                $verificados[$clave] = $verificacion[$clave] ?? null;
            registrar_operacion("configuraciones.guardar_sistema.resultado", [
                "ok" => $ok_guardado ? "SI" : "NO",
                "valores_guardados" => $verificados,
            ]);

            if ($ok_guardado)
                flash_ok("Configuracion del sistema guardada.");
            else
                flash_error("No se pudo guardar toda la configuracion. Revisar permisos de escritura.");
            redirigir("index.php?c=configuraciones&a=sistema" . ((string)obtener_post("seccion_navbar", "") === "reparaciones" ? "&seccion=reparaciones" : ""));
        }
    }

    public function restablecer_sistema(): void {
        if ($this->permiso_admin()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=configuraciones&a=sistema");
            }

            $csrf = obtener_post("csrf", "");
            if (!csrf_valido((string)$csrf)) {
                flash_error("Token invalido. Recarga la pagina.");
                redirigir("index.php?c=configuraciones&a=sistema");
            }

            if ($this->restablecer_configuracion_modular())
                flash_ok("Configuracion restablecida a valores predeterminados.");
            else
                flash_error("No se pudo restablecer la configuracion. Revisar permisos de escritura.");
            redirigir("index.php?c=configuraciones&a=sistema");
        }
    }

    public function generar_respaldo(): void {
        if ($this->permiso_admin()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=configuraciones&a=sistema");
            }

            $csrf = obtener_post("csrf", "");
            if (!csrf_valido((string)$csrf)) {
                flash_error("Token invalido. Recarga la pagina.");
                redirigir("index.php?c=configuraciones&a=sistema");
            }

            $respaldo = $this->generar_respaldo_modular();
            if (empty($respaldo["ok"])) {
                flash_error((string)($respaldo["mensaje"] ?? "No se pudo generar el respaldo."));
                redirigir("index.php?c=configuraciones&a=sistema");
            }

            $ruta = (string)$respaldo["ruta"];
            $nombre = (string)$respaldo["nombre"];
            if (!is_file($ruta)) {
                flash_error("El archivo de respaldo no se encontro.");
                redirigir("index.php?c=configuraciones&a=sistema");
            }
            $config = $this->obtener_configuracion_general_modular();
            $container = $this->contenedor_backups();
            $verificador = $container->get(\Ventas\Backups\Application\VerificarBackblazeConfigurado::class);
            if ($verificador->ejecutar($config)) {
                $container = $this->contenedor_backups();
                $casoSubida = $container->get(\Ventas\Backups\Application\SubirRespaldoBackblaze::class);
                $subida = $casoSubida->ejecutar($ruta, $config);
                if (empty($subida["ok"]))
                    registrar_log("BackblazeB2", (string)($subida["mensaje"] ?? "No se pudo subir respaldo."));
            }

            while (ob_get_level() > 0)
                ob_end_clean();
            header("Content-Type: application/gzip");
            header("Content-Disposition: attachment; filename=\"" . basename($nombre) . "\"");
            header("Content-Length: " . filesize($ruta));
            header("Cache-Control: no-store");
            readfile($ruta);
            return;
        }
    }

    public function ejecutar_respaldo(): void {
        if ($this->permiso_admin()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=configuraciones&a=backup");
            }

            $csrf = obtener_post("csrf", "");
            if (!csrf_valido((string)$csrf)) {
                flash_error("Token invalido. Recarga la pagina.");
                redirigir("index.php?c=configuraciones&a=backup");
            }

            $destino = (string)obtener_post("destino", "descargar");
            $respaldo = $this->generar_respaldo_modular();
            if (empty($respaldo["ok"])) {
                flash_error((string)($respaldo["mensaje"] ?? "No se pudo generar el respaldo."));
                redirigir("index.php?c=configuraciones&a=backup");
            }

            $ruta = (string)$respaldo["ruta"];
            $nombre = (string)$respaldo["nombre"];

            if ($destino === "descargar") {
                while (ob_get_level() > 0)
                    ob_end_clean();
                header("Content-Type: application/gzip");
                header("Content-Disposition: attachment; filename=\"" . basename($nombre) . "\"");
                header("Content-Length: " . filesize($ruta));
                header("Cache-Control: no-store");
                readfile($ruta);
                return;
            }

            if ($destino === "carpeta") {
                $carpeta_destino = (string)obtener_post("carpeta_destino", "");
                $copia = $this->copiar_respaldo_modular($ruta, $carpeta_destino);
                if (!empty($copia["ok"]))
                    flash_ok("Respaldo copiado en: " . (string)$copia["ruta"]);
                else
                    flash_error((string)($copia["mensaje"] ?? "No se pudo copiar el respaldo."));
                redirigir("index.php?c=configuraciones&a=backup");
            }

            if ($destino === "backblaze") {
                $config = $this->obtener_configuracion_general_modular();
                $container = $this->contenedor_backups();
                $casoSubida = $container->get(\Ventas\Backups\Application\SubirRespaldoBackblaze::class);
                $subida = $casoSubida->ejecutar($ruta, $config);
                if (!empty($subida["ok"]))
                    flash_ok("Respaldo subido a Backblaze B2: " . (string)($subida["fileName"] ?? basename($ruta)));
                else
                    flash_error((string)($subida["mensaje"] ?? "No se pudo subir a Backblaze B2."));
                redirigir("index.php?c=configuraciones&a=backup");
            }

            flash_error("Destino de respaldo invalido.");
            redirigir("index.php?c=configuraciones&a=backup");
        }
    }

    public function probar_backblaze(): void {
        if ($this->permiso_admin()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=configuraciones&a=sistema");
            }
            $csrf = obtener_post("csrf", "");
            if (!csrf_valido((string)$csrf)) {
                flash_error("Token invalido. Recarga la pagina.");
                redirigir("index.php?c=configuraciones&a=sistema");
            }

            $config = $this->obtener_configuracion_general_modular();
            $container = $this->contenedor_backups();
            $caso = $container->get(\Ventas\Backups\Application\ProbarConexionBackblaze::class);
            $res = $caso->ejecutar($config);
            if (!empty($res["ok"]))
                flash_ok((string)$res["mensaje"]);
            else
                flash_error((string)($res["mensaje"] ?? "No se pudo conectar con Backblaze B2."));
            redirigir("index.php?c=configuraciones&a=sistema");
        }
    }

    public function subir_respaldo_backblaze(): void {
        if ($this->permiso_admin()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=configuraciones&a=sistema");
            }
            $csrf = obtener_post("csrf", "");
            if (!csrf_valido((string)$csrf)) {
                flash_error("Token invalido. Recarga la pagina.");
                redirigir("index.php?c=configuraciones&a=sistema");
            }

            $respaldo = $this->generar_respaldo_modular();
            if (empty($respaldo["ok"])) {
                flash_error((string)($respaldo["mensaje"] ?? "No se pudo generar el respaldo."));
                redirigir("index.php?c=configuraciones&a=sistema");
            }

            $config = $this->obtener_configuracion_general_modular();
            $container = $this->contenedor_backups();
            $casoSubida = $container->get(\Ventas\Backups\Application\SubirRespaldoBackblaze::class);
            $subida = $casoSubida->ejecutar((string)$respaldo["ruta"], $config);
            if (!empty($subida["ok"]))
                flash_ok("Respaldo generado y subido a Backblaze B2: " . (string)($subida["fileName"] ?? ""));
            else
                flash_error((string)($subida["mensaje"] ?? "No se pudo subir el respaldo a Backblaze B2."));
            redirigir("index.php?c=configuraciones&a=sistema");
        }
    }

    public function reparaciones(): void {
        if ($this->permiso_admin()) {
            $config = $this->obtener_configuracion_general_modular();
            $opciones_reparaciones = $this->obtener_opciones_reparaciones($config);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/configuraciones/reparaciones.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    private function obtener_opciones_reparaciones(array $config): array {
        $opciones = [];
        $opciones_string = (string)($config["opciones_reparaciones"] ?? "");
        if (!empty($opciones_string)) {
            $items = explode(",", $opciones_string);
            foreach ($items as $item) {
                $parts = explode(":", trim($item));
                if (count($parts) >= 2) {
                    $opciones[trim($parts[0])] = "1";
                }
            }
        }
        return $opciones;
    }

    public function guardar_reparaciones(): void {
        if ($this->permiso_admin()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=configuraciones&a=reparaciones");
            }

            $csrf = obtener_post("csrf", "");
            if (!csrf_valido((string)$csrf)) {
                flash_error("Token invalido. Recarga la pagina.");
                redirigir("index.php?c=configuraciones&a=reparaciones");
            }

            $config_actual = $this->obtener_configuracion_general_modular();
            
            // Procesar opciones del menú
            $opciones_reparaciones = $_POST["opciones_reparaciones"] ?? [];
            if (!is_array($opciones_reparaciones)) {
                $opciones_reparaciones = [];
            }
            $opciones_string = implode(",", array_keys($opciones_reparaciones));

            // Campos de lista
            $campos_lista = $_POST["campos_lista_reparaciones"] ?? [];
            if (!is_array($campos_lista)) {
                $campos_lista = [];
            }

            $datos = [
                "mostrar_reparaciones" => obtener_post("mostrar_reparaciones", "1"),
                "atajo_reparaciones" => obtener_post("atajo_reparaciones", "F9"),
                "url_reparaciones" => normalizar_url_reparaciones((string)obtener_post("url_reparaciones", "")),
                "pagina_inicio_reparaciones" => obtener_post("pagina_inicio_reparaciones", "lista"),
                "crear_reparaciones_habilitado" => obtener_post("crear_reparaciones_habilitado", "1"),
                "editar_reparaciones_habilitado" => obtener_post("editar_reparaciones_habilitado", "1"),
                "opciones_reparaciones" => $opciones_string,
                "opciones_reparaciones_custom" => obtener_post("opciones_reparaciones_custom", ""),
                "campos_lista_reparaciones_cliente" => in_array("cliente", $campos_lista) ? "1" : "0",
                "campos_lista_reparaciones_equipo" => in_array("equipo", $campos_lista) ? "1" : "0",
                "campos_lista_reparaciones_estado" => in_array("estado", $campos_lista) ? "1" : "0",
                "campos_lista_reparaciones_fecha" => in_array("fecha", $campos_lista) ? "1" : "0",
                "campos_lista_reparaciones_tecnico" => in_array("tecnico", $campos_lista) ? "1" : "0",
                "campos_lista_reparaciones_presupuesto" => in_array("presupuesto", $campos_lista) ? "1" : "0",
            ];

            // Fusionar con configuración actual para mantener otros valores
            $config_completa = array_merge($config_actual, $datos);

            if ($this->guardar_configuracion_modular($config_completa))
                flash_ok("Configuración de reparaciones guardada correctamente.");
            else
                flash_error("No se pudo guardar la configuración. Revisar permisos de escritura.");
            
            redirigir("index.php?c=configuraciones&a=reparaciones");
        }
    }
}
