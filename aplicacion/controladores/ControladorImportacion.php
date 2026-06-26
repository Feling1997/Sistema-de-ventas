<?php
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";

class ControladorImportacion {
    private const MAX_BYTES = 8388608;

    private function permiso(): bool {
        $ok = false;
        if (!require_login()) {
            flash_error("Tenes que iniciar sesion.");
            redirigir("index.php?c=auth&a=login");
        } else {
            if (!require_rol(["ADMIN", "VENDEDOR"])) {
                flash_error("No tenes permisos para importar productos.");
                redirigir("index.php?c=productos&a=index");
            } else
                $ok = true;
        }
        return $ok;
    }

    private function carpeta_importaciones(): string {
        $carpeta = __DIR__ . "/../../almacenamiento/importaciones";
        if (!is_dir($carpeta))
            @mkdir($carpeta, 0777, true);
        return $carpeta;
    }

    private function limpiar_importaciones_viejas(): void {
        $carpeta = $this->carpeta_importaciones();
        $archivos = glob($carpeta . "/*");
        if (is_array($archivos)) {
            foreach ($archivos as $archivo) {
                if (is_file($archivo) && filemtime($archivo) !== false && filemtime($archivo) < (time() - 86400))
                    @unlink($archivo);
            }
        }
    }

    private function extension_valida(string $nombre): bool {
        $ok = false;
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        if (in_array($ext, ["xlsx", "xls", "xlsm"], true))
            $ok = true;
        return $ok;
    }

    private function guardar_archivo_subido(array $archivo): array {
        $resultado = ["ok" => false, "ruta" => "", "nombre" => "", "error" => ""];
        $nombre = basename((string)($archivo["name"] ?? ""));
        $tmp = (string)($archivo["tmp_name"] ?? "");
        $error = (int)($archivo["error"] ?? UPLOAD_ERR_NO_FILE);
        $tamano = (int)($archivo["size"] ?? 0);
        if ($error !== UPLOAD_ERR_OK)
            $resultado["error"] = "No se pudo recibir el archivo.";
        else {
            if (!$this->extension_valida($nombre))
                $resultado["error"] = "El archivo debe ser .xlsx, .xls o .xlsm.";
            elseif ($tamano <= 0 || $tamano > self::MAX_BYTES)
                $resultado["error"] = "El archivo supera el tamano maximo permitido de 8 MB.";
            elseif (!is_uploaded_file($tmp))
                $resultado["error"] = "Archivo invalido.";
            else {
                $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
                $destino = $this->carpeta_importaciones() . "/" . date("Ymd_His") . "_" . bin2hex(random_bytes(6)) . "." . $ext;
                if (@move_uploaded_file($tmp, $destino)) {
                    $resultado = ["ok" => true, "ruta" => $destino, "nombre" => $nombre, "error" => ""];
                } else
                    $resultado["error"] = "No se pudo guardar el archivo temporal.";
            }
        }
        return $resultado;
    }

    private function guardar_sesion_importacion(string $ruta, string $nombre, int $hoja): void {
        iniciar_sesion();
        $_SESSION["importacion_productos"] = [
            "ruta" => $ruta,
            "nombre" => $nombre,
            "hoja" => $hoja,
            "creado" => time()
        ];
    }

    private function obtener_sesion_importacion(): array {
        iniciar_sesion();
        $datos = [];
        if (isset($_SESSION["importacion_productos"]) && is_array($_SESSION["importacion_productos"]))
            $datos = $_SESSION["importacion_productos"];
        return $datos;
    }

    private function limpiar_sesion_importacion(): void {
        iniciar_sesion();
        if (isset($_SESSION["importacion_productos"]["ruta"]) && is_file((string)$_SESSION["importacion_productos"]["ruta"]))
            @unlink((string)$_SESSION["importacion_productos"]["ruta"]);
        unset($_SESSION["importacion_productos"]);
    }

    private function ordenar_filas_importacion(array $filas, string $orden, string $direccion): array {
        $permitidos = [
            "fecha" => "fila",
            "codigo" => "codigo",
            "codigo_barras" => "codigo",
            "nombre" => "nombre",
            "descripcion" => "nombre",
            "estado" => "accion"
        ];
        if (!array_key_exists($orden, $permitidos))
            $orden = "fecha";
        $clave = $permitidos[$orden];
        $desc = strtoupper($direccion) === "DESC";
        usort($filas, function ($a, $b) use ($clave, $desc) {
            $va = $a[$clave] ?? "";
            $vb = $b[$clave] ?? "";
            $cmp = is_numeric($va) && is_numeric($vb) ? ((float)$va <=> (float)$vb) : strcasecmp((string)$va, (string)$vb);
            return $desc ? -$cmp : $cmp;
        });
        return $filas;
    }

    private function registrar_importacion_modular(): void {
        global $container;

        if (!$container->has(\Ventas\Importacion\Application\ListarHojasImportacionExcel::class)) {
            \Ventas\Importacion\Infrastructure\RegistroImportacion::registrar($container);
        }
    }

    private function listar_hojas_importacion_excel(string $ruta): array {
        global $container;

        $this->registrar_importacion_modular();
        $caso_uso = $container->get(\Ventas\Importacion\Application\ListarHojasImportacionExcel::class);
        $resultado = $caso_uso->ejecutar($ruta);

        return $resultado;
    }

    private function analizar_importacion_productos(string $ruta, int $hoja): array {
        global $container;

        $this->registrar_importacion_modular();
        $caso_uso = $container->get(\Ventas\Importacion\Application\AnalizarImportacionProductos::class);
        $resultado = $caso_uso->ejecutar($ruta, $hoja);

        return $resultado;
    }

    private function importar_productos_desde_excel(string $ruta, int $hoja, string $nombre, int $id_usuario): array {
        global $container;

        $this->registrar_importacion_modular();
        $caso_uso = $container->get(\Ventas\Importacion\Application\ImportarProductosDesdeExcel::class);
        $resultado = $caso_uso->ejecutar($ruta, $hoja, $nombre, $id_usuario);

        return $resultado;
    }

    public function index(): void {
        if ($this->permiso()) {
            $this->limpiar_importaciones_viejas();
            $orden_importacion = orden_parametros([
                "fecha" => "fila",
                "codigo" => "codigo",
                "codigo_barras" => "codigo",
                "nombre" => "nombre",
                "descripcion" => "nombre",
                "estado" => "accion"
            ], "fecha", "ASC");
            $sesion = $this->obtener_sesion_importacion();
            $analisis = null;
            $hojas = [];
            $archivo_nombre = "";
            $hoja_seleccionada = 0;
            if (!empty($sesion["ruta"]) && is_file((string)$sesion["ruta"])) {
                $archivo_nombre = (string)($sesion["nombre"] ?? "");
                $hoja_seleccionada = (int)($sesion["hoja"] ?? 0);
                $hojas = $this->listar_hojas_importacion_excel((string)$sesion["ruta"]);
                $analisis = $this->analizar_importacion_productos((string)$sesion["ruta"], $hoja_seleccionada);
                if (is_array($analisis) && isset($analisis["preview"]) && is_array($analisis["preview"]))
                    $analisis["preview"] = $this->ordenar_filas_importacion($analisis["preview"], $orden_importacion["campo"], $orden_importacion["direccion"]);
            }
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/importacion/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function analizar(): void {
        if ($this->permiso()) {
            $error = "";
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", "")))
                $error = "Acceso invalido. Recarga la pagina.";
            else {
                $hoja = (int)obtener_post("hoja", 0);
                $sesion = $this->obtener_sesion_importacion();
                $ruta = (string)($sesion["ruta"] ?? "");
                $nombre = (string)($sesion["nombre"] ?? "");
                if (isset($_FILES["archivo"]) && (int)($_FILES["archivo"]["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $subida = $this->guardar_archivo_subido($_FILES["archivo"]);
                    if (!empty($subida["ok"])) {
                        if ($ruta !== "" && is_file($ruta))
                            @unlink($ruta);
                        $ruta = (string)$subida["ruta"];
                        $nombre = (string)$subida["nombre"];
                        $hoja = 0;
                    } else
                        $error = (string)$subida["error"];
                }
                if ($error === "") {
                    if ($ruta === "" || !is_file($ruta))
                        $error = "Selecciona un archivo Excel para analizar.";
                    else {
                        $this->guardar_sesion_importacion($ruta, $nombre, $hoja);
                        flash_ok("Archivo analizado. Revisa la vista previa antes de importar.");
                    }
                }
            }
            if ($error !== "")
                flash_error($error);
            redirigir("index.php?c=importacion&a=index");
        }
    }

    public function importar(): void {
        if ($this->permiso()) {
            $resultado = null;
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                flash_error("Acceso invalido. Recarga la pagina.");
                redirigir("index.php?c=importacion&a=index");
            } else {
                $sesion = $this->obtener_sesion_importacion();
                $ruta = (string)($sesion["ruta"] ?? "");
                $nombre = (string)($sesion["nombre"] ?? "");
                $hoja = (int)obtener_post("hoja", (int)($sesion["hoja"] ?? 0));
                if ($ruta === "" || !is_file($ruta)) {
                    flash_error("Primero analiza un archivo Excel.");
                    redirigir("index.php?c=importacion&a=index");
                } else {
                    $id_usuario = (int)($_SESSION["usuario_logueado"]["id"] ?? 0);
                    $resultado = $this->importar_productos_desde_excel($ruta, $hoja, $nombre, $id_usuario);
                    if (!empty($resultado["ok"])) {
                        $this->limpiar_sesion_importacion();
                        flash_ok("Importacion finalizada.");
                    } else
                        flash_error((string)($resultado["mensaje"] ?? "No se pudo importar el archivo."));
                    iniciar_sesion();
                    $_SESSION["resultado_importacion_productos"] = $resultado;
                    redirigir("index.php?c=importacion&a=resultado");
                }
            }
        }
    }

    public function resultado(): void {
        if ($this->permiso()) {
            iniciar_sesion();
            $resultado = [];
            if (isset($_SESSION["resultado_importacion_productos"]) && is_array($_SESSION["resultado_importacion_productos"]))
                $resultado = $_SESSION["resultado_importacion_productos"];
            unset($_SESSION["resultado_importacion_productos"]);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/importacion/resultado.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }
}
