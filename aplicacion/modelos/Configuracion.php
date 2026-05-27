<?php
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/ConfiguracionSistema.php";

if (!function_exists("procesar_logo_termico")) {
    function procesar_logo_termico(string $ruta_imagen, int $ancho, bool $modo_termico = true, string $destino = ""): string {
        $resultado = $destino !== "" ? $destino : $ruta_imagen;
        $ancho = max(120, min(900, $ancho));
        if (function_exists("imagecreatefromstring") && function_exists("imagefilter")) {
            $bytes = @file_get_contents($ruta_imagen);
            $origen = is_string($bytes) ? @imagecreatefromstring($bytes) : false;
            if ($origen !== false) {
                $w = imagesx($origen);
                $h = imagesy($origen);
                if ($w > 0 && $h > 0) {
                    $alto = max(1, (int)round(($h * $ancho) / $w));
                    $lienzo = imagecreatetruecolor($ancho, $alto);
                    if ($lienzo !== false) {
                        $blanco = imagecolorallocate($lienzo, 255, 255, 255);
                        imagefilledrectangle($lienzo, 0, 0, $ancho, $alto, $blanco);
                        imagecopyresampled($lienzo, $origen, 0, 0, 0, 0, $ancho, $alto, $w, $h);
                        if ($modo_termico) {
                            $muestras = [];
                            $paso_x = max(1, (int)floor($ancho / 24));
                            $paso_y = max(1, (int)floor($alto / 24));
                            for ($x = 0; $x < $ancho; $x += $paso_x) {
                                foreach ([0, max(0, $alto - 1)] as $y) {
                                    $rgb = imagecolorat($lienzo, $x, $y);
                                    $muestras[] = ((($rgb >> 16) & 0xFF) + (($rgb >> 8) & 0xFF) + ($rgb & 0xFF)) / 3;
                                }
                            }
                            for ($y = 0; $y < $alto; $y += $paso_y) {
                                foreach ([0, max(0, $ancho - 1)] as $x) {
                                    $rgb = imagecolorat($lienzo, $x, $y);
                                    $muestras[] = ((($rgb >> 16) & 0xFF) + (($rgb >> 8) & 0xFF) + ($rgb & 0xFF)) / 3;
                                }
                            }
                            sort($muestras);
                            $fondo = count($muestras) > 0 ? (float)$muestras[(int)floor(count($muestras) / 2)] : 255.0;
                            $fondo_oscuro = $fondo < 128;
                            $mapa = [];
                            $visitado = [];
                            $min_x = $ancho;
                            $min_y = $alto;
                            $max_x = -1;
                            $max_y = -1;
                            $mapa = [];
                            for ($y = 0; $y < $alto; $y++) {
                                $mapa[$y] = [];
                                $visitado[$y] = [];
                                for ($x = 0; $x < $ancho; $x++) {
                                    $rgb = imagecolorat($lienzo, $x, $y);
                                    $r = ($rgb >> 16) & 0xFF;
                                    $g = ($rgb >> 8) & 0xFF;
                                    $b = $rgb & 0xFF;
                                    $gris = (int)round(($r * 0.299) + ($g * 0.587) + ($b * 0.114));
                                    $distancia_fondo = abs($gris - $fondo);
                                    $umbral_claro = min(205, max(80, $fondo - 38));
                                    $umbral_oscuro = max(50, min(175, $fondo + 38));
                                    $es_logo = $fondo_oscuro ? ($gris > $umbral_oscuro) : ($gris < $umbral_claro);
                                    if ($distancia_fondo < 18)
                                        $es_logo = false;
                                    $mapa[$y][$x] = $es_logo;
                                    $visitado[$y][$x] = false;
                                }
                            }
                            $cola = [];
                            for ($x = 0; $x < $ancho; $x++) {
                                if (!empty($mapa[0][$x])) $cola[] = [$x, 0];
                                if (!empty($mapa[$alto - 1][$x])) $cola[] = [$x, $alto - 1];
                            }
                            for ($y = 0; $y < $alto; $y++) {
                                if (!empty($mapa[$y][0])) $cola[] = [0, $y];
                                if (!empty($mapa[$y][$ancho - 1])) $cola[] = [$ancho - 1, $y];
                            }
                            while (!empty($cola)) {
                                [$cx, $cy] = array_pop($cola);
                                if ($cx < 0 || $cy < 0 || $cx >= $ancho || $cy >= $alto || empty($mapa[$cy][$cx]) || !empty($visitado[$cy][$cx]))
                                    continue;
                                $visitado[$cy][$cx] = true;
                                $mapa[$cy][$cx] = false;
                                $cola[] = [$cx + 1, $cy];
                                $cola[] = [$cx - 1, $cy];
                                $cola[] = [$cx, $cy + 1];
                                $cola[] = [$cx, $cy - 1];
                            }
                            for ($y = 0; $y < $alto; $y++) {
                                $corrida = 0;
                                for ($x = 0; $x < $ancho; $x++) {
                                    $corrida = !empty($mapa[$y][$x]) ? $corrida + 1 : 0;
                                    if ($corrida > (int)($ancho * 0.72)) {
                                        for ($rx = $x - $corrida + 1; $rx <= $x; $rx++)
                                            $mapa[$y][$rx] = false;
                                    }
                                }
                            }
                            for ($x = 0; $x < $ancho; $x++) {
                                $corrida = 0;
                                for ($y = 0; $y < $alto; $y++) {
                                    $corrida = !empty($mapa[$y][$x]) ? $corrida + 1 : 0;
                                    if ($corrida > (int)($alto * 0.72)) {
                                        for ($ry = $y - $corrida + 1; $ry <= $y; $ry++)
                                            $mapa[$ry][$x] = false;
                                    }
                                }
                            }
                            for ($y = 0; $y < $alto; $y++) {
                                for ($x = 0; $x < $ancho; $x++) {
                                    if (!empty($mapa[$y][$x])) {
                                        $min_x = min($min_x, $x);
                                        $min_y = min($min_y, $y);
                                        $max_x = max($max_x, $x);
                                        $max_y = max($max_y, $y);
                                    }
                                }
                            }
                            $negro = imagecolorallocate($lienzo, 0, 0, 0);
                            $blanco = imagecolorallocate($lienzo, 255, 255, 255);
                            for ($y = 0; $y < $alto; $y++) {
                                for ($x = 0; $x < $ancho; $x++) {
                                    $es_logo = $mapa[$y][$x];
                                    if (!$es_logo) {
                                        for ($dy = -1; $dy <= 1; $dy++) {
                                            for ($dx = -1; $dx <= 1; $dx++) {
                                                if (($dx !== 0 || $dy !== 0) && !empty($mapa[$y + $dy][$x + $dx]))
                                                    $es_logo = true;
                                            }
                                        }
                                    }
                                    imagesetpixel($lienzo, $x, $y, $es_logo ? $negro : $blanco);
                                }
                            }
                            if ($max_x >= $min_x && $max_y >= $min_y) {
                                $padding = max(12, (int)round($ancho * 0.035));
                                $crop_x = max(0, $min_x - $padding);
                                $crop_y = max(0, $min_y - $padding);
                                $crop_w = min($ancho - $crop_x, ($max_x - $min_x + 1) + ($padding * 2));
                                $crop_h = min($alto - $crop_y, ($max_y - $min_y + 1) + ($padding * 2));
                                $contenido = imagecrop($lienzo, ["x" => $crop_x, "y" => $crop_y, "width" => $crop_w, "height" => $crop_h]);
                                if ($contenido !== false) {
                                    $final_alto = max(1, (int)round(($crop_h * $ancho) / $crop_w));
                                    $final = imagecreatetruecolor($ancho, $final_alto);
                                    if ($final !== false) {
                                        $blanco_final = imagecolorallocate($final, 255, 255, 255);
                                        imagefilledrectangle($final, 0, 0, $ancho, $final_alto, $blanco_final);
                                        imagecopyresampled($final, $contenido, 0, 0, 0, 0, $ancho, $final_alto, $crop_w, $crop_h);
                                        imagedestroy($lienzo);
                                        $lienzo = $final;
                                    }
                                    imagedestroy($contenido);
                                }
                            }
                        } else
                            imagefilter($lienzo, IMG_FILTER_GRAYSCALE);
                        imagepng($lienzo, $resultado, 9);
                        imagedestroy($lienzo);
                    }
                }
                imagedestroy($origen);
            } elseif ($destino !== "" && is_file($ruta_imagen))
                @copy($ruta_imagen, $destino);
        } elseif ($destino !== "" && is_file($ruta_imagen))
            @copy($ruta_imagen, $destino);
        return $resultado;
    }
}

if (!function_exists("ruta_relativa_proyecto")) {
    function ruta_relativa_proyecto(string $ruta): string {
        $base = realpath(__DIR__ . "/../../");
        $real = realpath($ruta);
        if ($base !== false && $real !== false && str_starts_with($real, $base)) {
            $rel = ltrim(str_replace("\\", "/", substr($real, strlen($base))), "/");
            return $rel;
        }
        return str_replace("\\", "/", ltrim($ruta, "/"));
    }
}

if (!function_exists("resolver_ruta_proyecto")) {
    function resolver_ruta_proyecto(string $ruta): string {
        if ($ruta === "")
            return "";
        if (preg_match('/^[a-zA-Z]:[\\\\\\/]/', $ruta) || str_starts_with($ruta, "\\\\"))
            return $ruta;
        return __DIR__ . "/../../" . ltrim($ruta, "/\\");
    }
}

if (!function_exists("procesar_logo_ticket_termico")) {
    function procesar_logo_ticket_termico(string $ruta_original, string $formato_ticket, bool $modo_termico = true): string {
        $ancho = $formato_ticket === "58" ? 384 : 576;
        return procesar_logo_ticket_termico_hd($ruta_original, $ancho, $modo_termico);
    }
}

if (!function_exists("procesar_logo_ticket_termico_hd")) {
    function procesar_logo_ticket_termico_hd(string $ruta_original, int $ancho_ticket, bool $modo_termico = true): string {
        $ruta_resuelta = resolver_ruta_proyecto($ruta_original);
        if ($ruta_resuelta === "" || !is_file($ruta_resuelta))
            return $ruta_original;
        if (!$modo_termico)
            return ruta_relativa_proyecto($ruta_resuelta);

        $ancho = $ancho_ticket <= 384 ? 384 : 576;
        $formato = $ancho === 384 ? "58" : "80";
        $carpeta = __DIR__ . "/../../almacenamiento/tickets/logos_procesados";
        if (!is_dir($carpeta))
            @mkdir($carpeta, 0777, true);

        $base = pathinfo($ruta_resuelta, PATHINFO_FILENAME);
        $base = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $base) ?: "logo_original";
        $destino = $carpeta . "/" . $base . "_termico_hd_" . $formato . ".png";
        $marca_canvas = $destino . ".ok";
        $puede_procesar_php = function_exists("imagecreatefromstring") && function_exists("imagecopyresampled");
        if (!$puede_procesar_php && is_file($destino) && is_file($marca_canvas))
            return ruta_relativa_proyecto($destino);
        if ($puede_procesar_php && is_file($destino))
            return ruta_relativa_proyecto($destino);
        if (!$puede_procesar_php)
            return ruta_relativa_proyecto($ruta_resuelta);
        $origen_mtime = @filemtime($ruta_resuelta) ?: 0;
        $destino_mtime = is_file($destino) ? (@filemtime($destino) ?: 0) : 0;
        if (!is_file($destino) || $destino_mtime < $origen_mtime) {
            $tmp_hd = $carpeta . "/" . $base . "_termico_hd_tmp_" . $formato . ".png";
            procesar_logo_termico($ruta_resuelta, $ancho * 4, true, $tmp_hd);
            if (function_exists("imagecreatefrompng") && function_exists("imagecopyresampled") && is_file($tmp_hd)) {
                $origen = @imagecreatefrompng($tmp_hd);
                if ($origen !== false) {
                    $w = imagesx($origen);
                    $h = imagesy($origen);
                    $alto = max(1, (int)round(($h * $ancho) / max(1, $w)));
                    $final = imagecreatetruecolor($ancho, $alto);
                    if ($final !== false) {
                        $blanco = imagecolorallocate($final, 255, 255, 255);
                        imagefilledrectangle($final, 0, 0, $ancho, $alto, $blanco);
                        imagecopyresampled($final, $origen, 0, 0, 0, 0, $ancho, $alto, $w, $h);
                        imagepng($final, $destino, 9);
                        imagedestroy($final);
                    }
                    imagedestroy($origen);
                }
            }
            if (!is_file($destino) && is_file($tmp_hd))
                @copy($tmp_hd, $destino);
            if (is_file($tmp_hd))
                @unlink($tmp_hd);
        }
        return is_file($destino) ? ruta_relativa_proyecto($destino) : ruta_relativa_proyecto($ruta_resuelta);
    }
}

class Configuracion {
    public static function asegurar_tabla(): bool {
        $ok = false;
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
                self::sembrar_defectos($pdo);
                $ok = true;
            } catch (Throwable $e) {
                registrar_log("Configuracion::asegurar_tabla", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function obtener_todo(): array {
        self::asegurar_tabla();
        config_cache_reset();
        $datos = config_todas();
        return $datos;
    }

    public static function obtener_grupo(string $grupo): array {
        $datos = [];
        $todos = self::obtener_todo();
        foreach (self::metadatos() as $clave => $meta) {
            if ((string)($meta["grupo"] ?? "") === $grupo)
                $datos[$clave] = (string)($todos[$clave] ?? ($meta["defecto"] ?? ""));
        }
        return $datos;
    }

    public static function metadatos(): array {
        $defaults = configuraciones_defecto_db();
        $meta = [];
        foreach ($defaults as $clave => $valor)
            $meta[$clave] = ["tipo" => self::tipo_por_clave($clave), "grupo" => self::grupo_por_clave($clave), "defecto" => (string)$valor];
        return $meta;
    }

    public static function guardar(array $datos): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null && self::asegurar_tabla()) {
            try {
                $pdo->beginTransaction();
                $meta = self::metadatos();
                $st = $pdo->prepare("INSERT INTO configuraciones (clave, valor, tipo, grupo) VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), grupo = VALUES(grupo)");
                if (array_key_exists("nombre_comercio", $datos))
                    $datos["navbar_marca_texto"] = trim((string)$datos["nombre_comercio"]);
                foreach ($datos as $clave => $valor) {
                    $clave = trim((string)$clave);
                    if ($clave !== "" && isset($meta[$clave])) {
                        $valor_limpio = self::normalizar_valor($clave, $valor, $meta[$clave]);
                        $st->execute([$clave, $valor_limpio, (string)$meta[$clave]["tipo"], (string)$meta[$clave]["grupo"]]);
                    }
                }
                $pdo->commit();
                config_cache_reset();
                self::sincronizar_configuracion_legacy();
                $ok = true;
            } catch (Throwable $e) {
                if ($pdo->inTransaction())
                    $pdo->rollBack();
                registrar_log("Configuracion::guardar", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function restablecer_grupo(string $grupo): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null && self::asegurar_tabla()) {
            try {
                $meta = self::metadatos();
                $st = $pdo->prepare("DELETE FROM configuraciones WHERE clave = ?");
                foreach ($meta as $clave => $item) {
                    if ((string)($item["grupo"] ?? "") === $grupo)
                        $st->execute([$clave]);
                }
                config_cache_reset();
                self::sincronizar_configuracion_legacy();
                $ok = true;
            } catch (Throwable $e) {
                registrar_log("Configuracion::restablecer_grupo", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function guardar_archivo(string $campo, string $actual, string $nombre_base): string {
        $ruta = $actual;
        if (isset($_FILES[$campo]) && is_array($_FILES[$campo])) {
            $archivo = $_FILES[$campo];
            $error = (int)($archivo["error"] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_OK) {
                $tmp = (string)($archivo["tmp_name"] ?? "");
                $nombre = (string)($archivo["name"] ?? "");
                $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
                $permitidas = ["jpg", "jpeg", "png", "gif", "ico", "webp"];
                if (is_uploaded_file($tmp) && in_array($ext, $permitidas, true)) {
                    $carpeta = __DIR__ . "/../../publico/assets/img";
                    if (!is_dir($carpeta))
                        @mkdir($carpeta, 0777, true);
                    $destino = $carpeta . "/" . $nombre_base . "." . $ext;
                    if ($nombre_base === "ticket_logo") {
                        $formato = (string)(($_POST["config"]["formato_impresion_ticket"] ?? "") ?: (ConfiguracionSistema::obtener()["formato_impresion_ticket"] ?? "80"));
                        $modo_termico = (string)($_POST["config"]["ticket_logo_termico"] ?? "1") === "1";
                        $destino = $carpeta . "/" . $nombre_base . "_original." . $ext;
                        if (@move_uploaded_file($tmp, $destino)) {
                            $ruta = "publico/assets/img/" . $nombre_base . "_original." . $ext;
                            $ancho_ticket = $formato === "58" ? 384 : 576;
                            procesar_logo_ticket_termico_hd($ruta, $ancho_ticket, $modo_termico);
                        } else
                            flash_error("No se pudo guardar el archivo " . $nombre . ". Revisa permisos de publico/assets/img.");
                    } elseif (@move_uploaded_file($tmp, $destino))
                        $ruta = "publico/assets/img/" . $nombre_base . "." . $ext;
                    else
                        flash_error("No se pudo guardar el archivo " . $nombre . ". Revisa permisos de publico/assets/img.");
                } else
                    flash_error("Archivo de imagen no valido.");
            } elseif ($error !== UPLOAD_ERR_NO_FILE) {
                $mensajes = [
                    UPLOAD_ERR_INI_SIZE => "El archivo supera el tamano permitido por PHP.",
                    UPLOAD_ERR_FORM_SIZE => "El archivo supera el tamano permitido por el formulario.",
                    UPLOAD_ERR_PARTIAL => "El archivo se subio incompleto.",
                    UPLOAD_ERR_NO_TMP_DIR => "Falta la carpeta temporal de PHP.",
                    UPLOAD_ERR_CANT_WRITE => "No se pudo escribir el archivo en disco.",
                    UPLOAD_ERR_EXTENSION => "Una extension de PHP bloqueo la subida.",
                ];
                flash_error($mensajes[$error] ?? "No se pudo subir el archivo.");
            }
        }
        return $ruta;
    }

    private static function sembrar_defectos(PDO $pdo): void {
        $st_count = $pdo->query("SELECT COUNT(*) AS total FROM configuraciones");
        $total = $st_count ? (int)($st_count->fetch()["total"] ?? 0) : 0;
        if ($total === 0) {
            $legacy = ConfiguracionSistema::obtener();
            $datos = array_merge(configuraciones_defecto_db(), $legacy);
            $meta = self::metadatos();
            $st = $pdo->prepare("INSERT INTO configuraciones (clave, valor, tipo, grupo) VALUES (?, ?, ?, ?)");
            foreach ($datos as $clave => $valor) {
                if (isset($meta[$clave]))
                    $st->execute([$clave, (string)$valor, (string)$meta[$clave]["tipo"], (string)$meta[$clave]["grupo"]]);
            }
        }
    }

    private static function tipo_por_clave(string $clave): string {
        $tipo = "texto";
        if (str_contains($clave, "color"))
            $tipo = "color";
        elseif (in_array($clave, ["ticket_imagen_completa", "ticket_logo_termico"], true) || str_starts_with($clave, "mostrar_") || str_contains($clave, "_habilitado") || str_contains($clave, "_auto") || str_contains($clave, "_sonido") || str_contains($clave, "_toasts") || str_contains($clave, "_alertas") || str_contains($clave, "_logs") || str_contains($clave, "_animaciones") || str_contains($clave, "_sombras") || str_contains($clave, "_escaner") || str_contains($clave, "_etiquetas"))
            $tipo = "booleano";
        elseif (str_contains($clave, "decimales") || str_contains($clave, "tiempo") || str_contains($clave, "tamano") || str_contains($clave, "radio") || $clave === "punto_venta" || $clave === "navbar_boton_opacidad")
            $tipo = "numero";
        elseif (str_contains($clave, "logo") || str_contains($clave, "favicon") || str_contains($clave, "imagen"))
            $tipo = "archivo";
        return $tipo;
    }

    private static function grupo_por_clave(string $clave): string {
        $grupo = "sistema";
        if (in_array($clave, ["nombre_comercio", "razon_social", "cuit", "condicion_iva", "domicilio", "localidad", "provincia", "telefonos", "whatsapp", "email", "sitio_web", "ingresos_brutos", "inicio_actividades", "punto_venta"], true))
            $grupo = "comercio";
        elseif (str_starts_with($clave, "ventas_") || in_array($clave, ["controlar_stock_ventas"], true))
            $grupo = "ventas";
        elseif (str_starts_with($clave, "productos_") || str_starts_with($clave, "balanza_"))
            $grupo = "productos";
        elseif (str_starts_with($clave, "clientes_"))
            $grupo = "clientes";
        elseif (str_starts_with($clave, "listas_"))
            $grupo = "listas";
        elseif (str_starts_with($clave, "notificaciones_"))
            $grupo = "notificaciones";
        elseif (str_starts_with($clave, "seguridad_") || $clave === "auth_modo")
            $grupo = "seguridad";
        elseif ($clave === "configuracion_separada")
            $grupo = "sistema";
        elseif (str_starts_with($clave, "backup_"))
            $grupo = "backup";
        elseif (str_contains($clave, "ticket") || str_starts_with($clave, "formato_impresion"))
            $grupo = "impresion";
        elseif (str_starts_with($clave, "navbar_"))
            $grupo = "menu";
        elseif (in_array($clave, ["logo", "favicon", "color_acento", "color_secundario", "color_fondo", "color_fondo_secundario", "color_tarjetas", "color_texto", "color_texto_suave", "color_borde", "color_panel_inicio", "color_panel_inicio_2", "tema_paneles", "tema_modo", "ui_radio_bordes", "ui_tamano_tarjetas", "ui_sombras", "ui_animaciones", "imagen_panel"], true))
            $grupo = "apariencia";
        return $grupo;
    }

    private static function normalizar_valor(string $clave, $valor, array $meta): string {
        $tipo = (string)($meta["tipo"] ?? "texto");
        $texto = trim(is_scalar($valor) || $valor === null ? (string)$valor : "");
        if ($tipo === "booleano")
            $texto = $texto === "1" ? "1" : "0";
        elseif ($tipo === "color")
            $texto = preg_match('/^#[0-9a-fA-F]{6}$/', $texto) ? $texto : (string)($meta["defecto"] ?? "#000000");
        elseif ($tipo === "numero")
            $texto = (string)max(0, (int)$texto);
        elseif ($clave === "tema_modo")
            $texto = in_array($texto, ["claro", "oscuro", "automatico"], true) ? $texto : "claro";
        elseif ($clave === "tema_paneles")
            $texto = in_array($texto, ["claro", "compacto", "alto_contraste"], true) ? $texto : "claro";
        elseif ($clave === "formato_impresion_ticket")
            $texto = in_array($texto, ["58", "80", "a4"], true) ? $texto : "80";
        elseif ($clave === "backup_frecuencia")
            $texto = in_array($texto, ["diario", "semanal", "manual"], true) ? $texto : "diario";
        elseif ($clave === "backup_hora")
            $texto = preg_match('/^\d{2}:\d{2}$/', $texto) ? $texto : "18:55";
        elseif ($clave === "backup_aviso_minutos")
            $texto = (string)max(0, min(180, (int)$texto));
        elseif ($clave === "ui_tamano_tarjetas")
            $texto = in_array($texto, ["compacto", "medio", "grande"], true) ? $texto : "medio";
        return $texto;
    }

    private static function sincronizar_configuracion_legacy(): void {
        $datos = config_todas();
        ConfiguracionSistema::guardar($datos);
        config_cache_reset();
    }
}
