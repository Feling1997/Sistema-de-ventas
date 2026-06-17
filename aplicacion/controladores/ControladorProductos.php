<?php
require_once __DIR__ . "/../modelos/Producto.php";
require_once __DIR__ . "/../modelos/Stock.php";
require_once __DIR__ . "/../modelos/UnidadMedida.php";
require_once __DIR__ . "/../modelos/ListaPrecio.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";
require_once __DIR__ . "/../../configuraciones/base_datos.php";

class ControladorProductos {
    private function generar_codigo_barras_interno(): string {
        do {
            $codigo = "P" . date("YmdHis") . random_int(10, 99);
        } while (Producto::cod_barras_existe($codigo, 0));
        return $codigo;
    }

    private function permiso(): bool {
        $ok = false;
        if (!require_login()) {
            flash_error("Ten??s que iniciar sesi??n.");
            redirigir("index.php?c=auth&a=login");
        } else {
            if (!require_rol(["ADMIN","VENDEDOR"])) {
                flash_error("No ten??s permisos para Productos.");
                redirigir("index.php?c=ventas&a=lista");
            } else
                $ok = true;
        }
        return $ok;
    }

    private function stockDominioAArray(object $stock): array {
        return [
            "id" => $stock->id(),
            "nombre" => $stock->nombre(),
            "unidad" => $stock->unidad(),
            "tipo_stock" => $stock->tipoStock(),
            "cantidad" => $stock->cantidad(),
            "stock_minimo" => $stock->stockMinimo(),
            "stock_maximo" => $stock->stockMaximo(),
            "precio_costo" => $stock->precioCosto(),
            "moneda_costo" => $stock->monedaCosto(),
            "costo_origen" => $stock->costoOrigen(),
            "activo" => $stock->activo() ? 1 : 0,
            "unidad_decimales" => $stock->unidadDecimales(),
            "creado_en" => $stock->creadoEn(),
        ];
    }

    private function listaPrecioDominioAArray(object $lista): array {
        return [
            "id" => $lista->id(),
            "nombre" => $lista->nombre(),
            "activo" => $lista->activo() ? 1 : 0,
            "creado_en" => $lista->creadoEn(),
        ];
    }

    private function unidadMedidaDominioAArray(object $unidad): array {
        return [
            "id" => $unidad->id(),
            "nombre" => $unidad->nombre(),
            "abreviatura" => $unidad->abreviatura(),
            "tipo" => $unidad->tipo(),
            "decimales" => $unidad->decimales(),
            "activo" => $unidad->activo() ? 1 : 0,
        ];
    }

    private function listar_stock_para_select(): array {
        return Stock::listar_generales_activos();
    }

    private function listar_unidades(): array {
        return UnidadMedida::listar(true);
    }

    private function datos_nueva_unidad_post(): array {
        $simple = trim((string)obtener_post("nueva_unidad_simple", ""));
        if ($simple !== "") {
            $simple = ucfirst($simple);
            return [
                "nombre" => $simple,
                "tipo" => "cantidad",
                "decimales" => 0,
            ];
        }
        return [
            "nombre" => trim((string)obtener_post("nueva_unidad_nombre", "")),
            "tipo" => trim((string)obtener_post("nueva_unidad_tipo", "cantidad")),
            "decimales" => (int)obtener_post("nueva_unidad_decimales", 0),
        ];
    }

    private function resolver_unidad_form(string $unidad): string {
        $unidad = trim($unidad);
        if ($unidad === "__otra_unidad__") {
            $simple = trim((string)obtener_post("nueva_unidad_simple", ""));
            if ($simple !== "")
                $unidad = strtolower($simple);
            else
                $unidad = "";
        }
        return $unidad;
    }

    public function crear_unidad_json(): void {
        $respuesta = ["ok" => false, "error" => "No se pudo crear la unidad.", "unidad" => null];
        if ($this->permiso()) {
            header("Content-Type: application/json; charset=utf-8");
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                $respuesta["error"] = "Metodo invalido.";
            } else {
                $csrf = obtener_post("csrf", "");
                $nombre = trim((string)obtener_post("nombre", ""));
                $abreviatura = trim((string)obtener_post("abreviatura", ""));
                $tipo = trim((string)obtener_post("tipo", "cantidad"));
                $decimales = (int)obtener_post("decimales", 0);
                if (!csrf_valido($csrf))
                    $respuesta["error"] = "Token invalido. Recarga la pagina.";
                else if (texto_invalido($nombre))
                    $respuesta["error"] = "El nombre es obligatorio.";
                else if (texto_invalido($abreviatura))
                    $respuesta["error"] = "La abreviatura es obligatoria.";
                else if (UnidadMedida::buscar_por_abreviatura($abreviatura) !== null)
                    $respuesta["error"] = "Ya existe una unidad con esa abreviatura.";
                else {
                    $unidad = UnidadMedida::crear_sin_duplicar($nombre, $abreviatura, $tipo, $decimales, 1);
                    if ($unidad !== null) {
                        flash_ok("Unidad creada correctamente");
                        $respuesta = ["ok" => true, "error" => "", "unidad" => [
                            "id" => (int)($unidad["id"] ?? 0),
                            "nombre" => (string)($unidad["nombre"] ?? ""),
                            "abreviatura" => (string)($unidad["abreviatura"] ?? ""),
                            "tipo" => (string)($unidad["tipo"] ?? ""),
                            "decimales" => (int)($unidad["decimales"] ?? 0),
                        ]];
                    }
                }
            }
        }
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    }

    private function producto_usa_stock_general(array $producto): bool {
        $id_stock = (int)($producto["id_stock"] ?? 0);
        if ($id_stock <= 0)
            return false;
        if ((string)($producto["stock_tipo_stock"] ?? "") === "general")
            return true;
        if ((string)($producto["stock_tipo_stock"] ?? "") === "propio")
            return false;
        if (Stock::contar_productos_asociados($id_stock) > 1)
            return true;
        $nombre_producto = strtolower(trim((string)($producto["nombre"] ?? "")));
        $nombre_stock = strtolower(trim((string)($producto["stock_nombre"] ?? "")));
        $nombre_producto = preg_replace('/\s+/', ' ', $nombre_producto) ?? $nombre_producto;
        $nombre_stock = preg_replace('/\s+/', ' ', $nombre_stock) ?? $nombre_stock;
        return $nombre_stock !== "" && $nombre_producto !== "" && $nombre_stock !== $nombre_producto;
    }

    public function index(): void {
        if ($this->permiso()) {
            global $container;
            $listarProductosVista = $container->get(\Ventas\Productos\Application\ListarProductosVista::class);
            $listarListasPrecios = $container->get(\Ventas\ListasPrecios\Application\ListarListasPrecios::class);
            $obtenerListaPrecioPredeterminada = $container->get(\Ventas\ListasPrecios\Application\ObtenerListaPrecioPredeterminada::class);
            $orden_productos = orden_parametros([
                "nombre" => "nombre",
                "descripcion" => "nombre",
                "codigo" => "codigo",
                "codigo_barras" => "codigo_barras",
                "precio" => "precio",
                "stock" => "stock",
                "estado" => "estado",
                "fecha" => "fecha"
            ], "nombre", "ASC");
            $texto_buscar = trim((string)obtener_get("buscar", ""));
            $campo_buscar = trim((string)obtener_get("campo", "todos"));
            $metodo_buscar = trim((string)obtener_get("metodo", "contiene"));
            $listas_precios = [];
            foreach ($listarListasPrecios->ejecutar() as $lista_dominio)
                $listas_precios[] = $this->listaPrecioDominioAArray($lista_dominio);
            $id_lista_precio_actual = (int)obtener_get("id_lista_precio", $obtenerListaPrecioPredeterminada->ejecutar());
            if ($id_lista_precio_actual <= 0)
                $id_lista_precio_actual = $obtenerListaPrecioPredeterminada->ejecutar();
            $productos = $listarProductosVista->ejecutar((string)$orden_productos["campo"], (string)$orden_productos["direccion"], $id_lista_precio_actual);
            $campos_busqueda = [
                "id" => "ID",
                "nombre" => "Nombre",
                "cod_barras" => "C??digo de barras",
                "stock_nombre" => "Stock",
                "stock_cantidad" => "Cantidad stock",
                "factor_conversion" => "Factor",
                "ganancia" => "Ganancia",
                "precio_final" => "Precio final"
            ];
            $productos = filtrar_registros_busqueda($productos, $texto_buscar, $campo_buscar, $campos_busqueda, $metodo_buscar);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/productos/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    private function guardar_listas_producto(int $id_producto, float $costo, float $precio_final = 0): void {
        $precios = $_POST["precio_lista"] ?? [];
        $porcentajes = $_POST["porcentaje_lista"] ?? [];
        if (!is_array($precios))
            $precios = [];
        if (!is_array($porcentajes))
            $porcentajes = [];
        foreach (ListaPrecio::listar(true) as $lista) {
            $id_lista = (int)$lista["id"];
            $precio = parsear_numero_form($precios[$id_lista] ?? 0, 0);
            $porcentaje = parsear_numero_form($porcentajes[$id_lista] ?? 0, 0);
            if (ListaPrecio::es_lista_costo($lista)) {
                $precio = $costo;
                $porcentaje = 0;
            }
            if (!ListaPrecio::es_lista_costo($lista) && $costo > 0 && $porcentaje > 0)
                $precio = $costo * (1 + ($porcentaje / 100));
            else if ($precio <= 0 && $costo > 0)
                $precio = $costo * (1 + ($porcentaje / 100));
            if (!ListaPrecio::es_lista_costo($lista) && $porcentaje <= 0 && $costo > 0 && $precio > 0)
                $porcentaje = (($precio / $costo) - 1) * 100;
            if (ListaPrecio::es_lista_costo($lista))
                $porcentaje = 0;
            ListaPrecio::guardar_precio_producto($id_producto, $id_lista, $porcentaje, $precio);
        }
    }

    public function nuevo(): void {
        if ($this->permiso()) {
            global $container;
            $listarListasPrecios = $container->get(\Ventas\ListasPrecios\Application\ListarListasPrecios::class);
            $listarStockGeneral = $container->get(\Ventas\Stock\Application\ListarStockGeneral::class);
            $listarUnidadesMedida = $container->get(\Ventas\UnidadesMedida\Application\ListarUnidadesMedida::class);
            $listas_precios = [];
            foreach ($listarListasPrecios->ejecutar() as $lista_dominio)
                $listas_precios[] = $this->listaPrecioDominioAArray($lista_dominio);
            if (count($listas_precios) === 0) {
                flash_error("Primero carga una lista de precios.");
                redirigir("index.php?c=listas_precios&a=index");
            } else {
                $stocks = [];
                foreach ($listarStockGeneral->ejecutar() as $stock_dominio)
                    $stocks[] = $this->stockDominioAArray($stock_dominio);
                $unidades_medida = [];
                foreach ($listarUnidadesMedida->ejecutar() as $unidad_dominio)
                    $unidades_medida[] = $this->unidadMedidaDominioAArray($unidad_dominio);
                $modo = "crear";
                $id_stock_pre = (int)obtener_get("id_stock", 0);
                if ($id_stock_pre <= 0)
                    $id_stock_pre = 0;
                if ($id_stock_pre > 0) {
                    $stock_pre_encontrado = false;
                    foreach ($stocks as $stock_item) {
                        if ((int)($stock_item["id"] ?? 0) === $id_stock_pre)
                            $stock_pre_encontrado = true;
                    }
                    if (!$stock_pre_encontrado)
                        $id_stock_pre = 0;
                }
                $nombre_stock_pre = trim((string)obtener_get("nombre_stock", ""));
                if (texto_invalido($nombre_stock_pre))
                    $nombre_stock_pre = "";
                $p = ["id" => 0, "nombre" => ($nombre_stock_pre !== "" ? $nombre_stock_pre : ""), "cod_barras" => "", "id_stock" => ($id_stock_pre > 0 ? $id_stock_pre : null), "id_asociado" => null, "factor_conversion" => 1, "ganancia" => 0, "precio_final" => 0, "activo" => 1, "usa_stock_general" => ($id_stock_pre > 0 ? 1 : 0), "stock_unidad" => "u", "stock_cantidad" => 0, "stock_minimo" => 0, "stock_maximo" => 0, "stock_precio_costo" => 0, "stock_moneda_costo" => "ARS", "stock_costo_origen" => 0];
                $precios_producto = [];
                include __DIR__ . "/../vistas/parciales/encabezado.php";
                include __DIR__ . "/../vistas/productos/formulario.php";
                include __DIR__ . "/../vistas/parciales/pie.php";
            }
        }
    }

    public function exportar_altas(): void {
        if ($this->permiso()) {
            $formato = strtolower((string)obtener_get("formato", "csv"));
            $solo_alta = (string)obtener_get("alcance", "alta") !== "todos";
            $id_lista = (int)obtener_get("id_lista_precio", 0);
            $nombre_lista = "";
            foreach (ListaPrecio::listar(true) as $lista) {
                if ((int)$lista["id"] === $id_lista)
                    $nombre_lista = (string)$lista["nombre"];
            }
            if ($id_lista <= 0 || $nombre_lista === "") {
                flash_error("Selecciona una lista de precios cargada.");
                redirigir("index.php?c=exportaciones&a=index");
                return;
            }
            $productos = Producto::listar_para_exportar($solo_alta);
            foreach ($productos as &$p) {
                $precio = 0.0;
                if ($id_lista > 0) {
                    $precio_lista = ListaPrecio::precio_producto_cargado((int)($p["id"] ?? 0), $id_lista);
                    if ($precio_lista !== null && (float)$precio_lista["precio"] > 0)
                        $precio = (float)$precio_lista["precio"];
                }
                $p["precio_exportar"] = $precio;
            }
            unset($p);
            $titulo = $solo_alta ? "Articulos dados de alta" : "Todos los articulos";
            if ($id_lista > 0)
                $titulo .= " - " . $nombre_lista;
            $base_archivo = $solo_alta ? "articulos_en_alta" : "todos_los_articulos";
            $base_archivo .= "_" . $nombre_lista;
            if ($formato === "csv" || $formato === "xls" || $formato === "excel") {
                header("Content-Type: text/csv; charset=utf-8");
                header("Content-Disposition: attachment; filename=\"" . $this->nombre_archivo($base_archivo, "csv") . "\"");
                $out = fopen("php://output", "w");
                if ($out !== false) {
                    fprintf($out, "\xEF\xBB\xBF");
                    fputcsv($out, ["Nombre", "Codigo", "Stock", "Cantidad", "Unidad", "Precio", "Estado"], ";");
                    foreach ($productos as $p) {
                        fputcsv($out, [
                            (string)($p["nombre"] ?? ""),
                            (string)($p["cod_barras"] ?? ""),
                            (string)($p["stock_nombre"] ?? ""),
                            stock_para_mostrar($p["stock_cantidad"] ?? 0, 4),
                            (string)($p["stock_unidad"] ?? ""),
                            numero_precio_para_exportar($p["precio_exportar"] ?? 0, 2),
                            ((int)($p["activo"] ?? 0) === 1) ? "Alta" : "Baja"
                        ], ";");
                    }
                    fclose($out);
                }
                exit;
            }
            $html = $this->html_productos_exportar($productos, $titulo);
            if ($formato === "pdf") {
                $autoload = __DIR__ . "/../../vendor/autoload.php";
                if (file_exists($autoload)) {
                    require_once $autoload;
                    $dompdf = new \Dompdf\Dompdf();
                    $dompdf->loadHtml($html, "UTF-8");
                    $dompdf->setPaper("A4", "portrait");
                    $dompdf->render();
                    header("Content-Type: application/pdf");
                    header("Content-Disposition: attachment; filename=\"" . $this->nombre_archivo($base_archivo, "pdf") . "\"");
                    echo $dompdf->output();
                    exit;
                }
            }
            echo $html;
            exit;
        }
    }

    private function html_productos_exportar(array $productos, string $titulo): string {
        $filas = "";
        foreach ($productos as $p) {
            $filas .= "<tr><td>" . htmlspecialchars((string)($p["nombre"] ?? "")) . "</td><td>" . htmlspecialchars((string)($p["cod_barras"] ?? "")) . "</td><td>" . htmlspecialchars((string)($p["stock_nombre"] ?? "")) . "</td><td class='num'>" . htmlspecialchars(stock_para_mostrar($p["stock_cantidad"] ?? 0, 4)) . "</td><td>" . htmlspecialchars((string)($p["stock_unidad"] ?? "")) . "</td><td class='num'>" . htmlspecialchars(precio_para_mostrar($p["precio_exportar"] ?? 0)) . "</td><td>" . (((int)($p["activo"] ?? 0) === 1) ? "Alta" : "Baja") . "</td></tr>";
        }
        return reporte_html_tabla($titulo, "Catalogo de articulos para consulta o entrega al cliente", ["Articulo", "Codigo", "Stock", "Cantidad", "Unidad", "Precio", "Estado"], $filas, 7);
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

    public function crear(): void {
        if ($this->permiso()) {
            if (count(ListaPrecio::listar(true)) === 0) {
                flash_error("Primero carga una lista de precios.");
                redirigir("index.php?c=listas_precios&a=index");
                return;
            }
            $error = "";
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $csrf = obtener_post("csrf", "");
                if (!csrf_valido($csrf))
                    $error = "Token inválido. Recargá la página.";
                else {
                    $nombre = trim((string)obtener_post("nombre", ""));
                    $cod_barras = trim((string)obtener_post("cod_barras", ""));
                    $id_stock_raw = trim((string)obtener_post("id_stock", ""));
                    $factor_conversion = parsear_numero_form(obtener_post("consumo_por_venta", obtener_post("factor_conversion", 1)), 1);
                    $ganancia = parsear_numero_form(obtener_post("ganancia", 0), 0);
                    $precio_manual = parsear_numero_form(obtener_post("precio_final_manual", 0), 0);
                    $usa_stock_general = (int)obtener_post("usa_stock_general", 0) === 1;
                    $unidad_stock = $this->resolver_unidad_form((string)obtener_post("unidad_stock", "u"));
                    $cantidad_stock = parsear_numero_form(obtener_post("cantidad_stock", 0), 0);
                    $stock_minimo = parsear_numero_form(obtener_post("stock_minimo", 0), 0);
                    $stock_maximo = parsear_numero_form(obtener_post("stock_maximo", 0), 0);
                    $precio_costo_form = parsear_numero_form(obtener_post("precio_costo", 0), 0);
                    $moneda_costo = strtoupper(trim((string)obtener_post("moneda_costo", "ARS"))) === "USD" ? "USD" : "ARS";
                    $activo = (int)obtener_post("activo", 1);
                    $id_stock = 0;
                    if ($id_stock_raw !== "" && ctype_digit($id_stock_raw))
                        $id_stock = (int)$id_stock_raw;
                    if (texto_invalido($nombre))
                        $error = "Nombre inválido (vacío o placeholder).";
                    else {
                        if (texto_invalido($cod_barras))
                            $cod_barras = $this->generar_codigo_barras_interno();
                        if (Producto::cod_barras_existe($cod_barras, 0))
                            $error = "El código del producto ya existe.";
                        else {
                            if (texto_invalido($unidad_stock))
                                $unidad_stock = "u";
                            if ($cantidad_stock < 0)
                                $cantidad_stock = 0;
                            if ($stock_minimo < 0)
                                $stock_minimo = 0;
                            if ($stock_maximo < 0)
                                $stock_maximo = 0;
                            if ($precio_costo_form < 0)
                                $precio_costo_form = 0;
                            if ($factor_conversion <= 0 && !$usa_stock_general)
                                $factor_conversion = 1;
                            if ($ganancia < 0)
                                $ganancia = 0;
                            if (!$usa_stock_general)
                                $factor_conversion = 1;
                            if ($usa_stock_general && $factor_conversion <= 0)
                                $error = "El consumo por venta debe ser mayor a cero.";
                            if (!$usa_stock_general) {
                                $unidad_stock = UnidadMedida::asegurar_desde_form($unidad_stock, $this->datos_nueva_unidad_post());
                                $id_stock = Stock::crear_retornar_id_con_tipo($nombre, $unidad_stock, $cantidad_stock, $precio_costo_form, $activo, $stock_minimo, $stock_maximo, "propio", $moneda_costo);
                                if ($id_stock <= 0)
                                    $error = "No se pudo crear el stock automatico del producto.";
                            }
                            if ($usa_stock_general && $id_stock <= 0)
                                $error = "Tenés que seleccionar un stock principal.";
                            if ($error === "") {
                                if (!Producto::stock_existe($id_stock))
                                    $error = "El stock principal seleccionado no existe.";
                                if ($error === "" && $usa_stock_general) {
                                    $stock_general = Stock::buscar_por_id($id_stock);
                                    if ($stock_general === null || (string)($stock_general["tipo_stock"] ?? "") !== "general" || (int)($stock_general["activo"] ?? 0) !== 1)
                                        $error = "Selecciona un stock general activo.";
                                }
                                if ($error === "") {
                                    $precio_costo = 0.0;
                                    $costo_stock = Producto::obtener_precio_costo_stock($id_stock);
                                    if ($costo_stock !== null)
                                        $precio_costo = $costo_stock;
                                    if ($factor_conversion <= 0 && $usa_stock_general)
                                        $error = "El consumo por venta debe ser mayor a cero.";
                                    if ($ganancia < 0)
                                        $ganancia = 0;
                                    if ($error === "") {
                                        $precio_final = Producto::calcular_precio_final($precio_costo, $factor_conversion, $ganancia);
                                        if ($precio_final <= 0 && $precio_manual > 0)
                                            $precio_final = $precio_manual;
                                        $id_producto_nuevo = Producto::crear_retornar_id($nombre, $cod_barras, $id_stock, $factor_conversion, $ganancia, $precio_final, $activo);
                                        if ($id_producto_nuevo > 0) {
                                            $this->guardar_listas_producto($id_producto_nuevo, $precio_costo, $precio_final);
                                            flash_ok($usa_stock_general ? "Producto creado con stock general asociado." : "Producto creado con stock propio.");
                                            redirigir("index.php?c=productos&a=index");
                                        } else {
                                            if (!$usa_stock_general && $id_stock > 0 && Stock::contar_productos_asociados($id_stock) === 0)
                                                Stock::eliminar($id_stock);
                                            $error = "No se pudo crear el producto (ver logs).";
                                        }
                                    }
                                }
                            }

                        }
                    }
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                $modo = "crear";
                $id_stock_pre = ($id_stock !== null ? (int)$id_stock : 0);
                $p = ["id" => 0, "nombre" => $nombre, "cod_barras" => $cod_barras, "id_stock" => ($id_stock_pre > 0 ? $id_stock_pre : null),
                    "id_asociado" => null, "factor_conversion" => $factor_conversion, "ganancia" => $ganancia,
                    "precio_final" => $precio_manual ?? 0, "activo" => $activo, "usa_stock_general" => !empty($usa_stock_general) ? 1 : 0,
                    "stock_unidad" => $unidad_stock ?? "u", "stock_cantidad" => $cantidad_stock ?? 0, "stock_minimo" => $stock_minimo ?? 0, "stock_maximo" => $stock_maximo ?? 0, "stock_precio_costo" => Stock::costo_en_pesos($precio_costo_form ?? 0, $moneda_costo ?? "ARS"), "stock_moneda_costo" => $moneda_costo ?? "ARS", "stock_costo_origen" => $precio_costo_form ?? 0];
                $stocks = $this->listar_stock_para_select();
                $unidades_medida = $this->listar_unidades();
                $listas_precios = ListaPrecio::listar(true);
                $precios_producto = [];
                include __DIR__ . "/../vistas/parciales/encabezado.php";
                include __DIR__ . "/../vistas/productos/formulario.php";
                include __DIR__ . "/../vistas/parciales/pie.php";
            }
        }
    }

    public function editar(): void {
        if ($this->permiso()) {
            global $container;
            $buscarProductoFormularioPorId = $container->get(\Ventas\Productos\Application\BuscarProductoFormularioPorId::class);
            $obtenerPreciosProducto = $container->get(\Ventas\Productos\Application\ObtenerPreciosProducto::class);
            $listarListasPrecios = $container->get(\Ventas\ListasPrecios\Application\ListarListasPrecios::class);
            $listarStockGeneral = $container->get(\Ventas\Stock\Application\ListarStockGeneral::class);
            $listarUnidadesMedida = $container->get(\Ventas\UnidadesMedida\Application\ListarUnidadesMedida::class);
            $id = (int)obtener_get("id", 0);
            $p = $buscarProductoFormularioPorId->ejecutar($id);
            if ($p === null) {
                flash_error("Producto no encontrado.");
                redirigir("index.php?c=productos&a=index");
            } else {
                $modo = "editar";
                $id_stock_actual = (int)($p["id_stock"] ?? 0);
                $datos_form = obtener_form_data("productos_form");
                if ($datos_form !== [])
                    $p = array_merge($p, $datos_form);
                $stocks = [];
                foreach ($listarStockGeneral->ejecutar() as $stock_dominio)
                    $stocks[] = $this->stockDominioAArray($stock_dominio);
                $unidades_medida = [];
                foreach ($listarUnidadesMedida->ejecutar() as $unidad_dominio)
                    $unidades_medida[] = $this->unidadMedidaDominioAArray($unidad_dominio);
                $listas_precios = [];
                foreach ($listarListasPrecios->ejecutar() as $lista_dominio)
                    $listas_precios[] = $this->listaPrecioDominioAArray($lista_dominio);
                $precios_producto = $obtenerPreciosProducto->ejecutar($id);
                include __DIR__ . "/../vistas/parciales/encabezado.php";
                include __DIR__ . "/../vistas/productos/formulario.php";
                include __DIR__ . "/../vistas/parciales/pie.php";
            }
        }
    }

    public function actualizar(): void {
        if ($this->permiso()) {
            $error = "";
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $csrf = obtener_post("csrf", "");
                if (!csrf_valido($csrf))
                    $error = "Token inválido. Recargá la página.";
                else {
                    $id = (int)obtener_post("id", 0);
                    $p_actual = Producto::buscar_por_id($id);
                    if ($p_actual === null)
                        $error = "Producto no encontrado.";
                    else {
                        $nombre = trim((string)obtener_post("nombre", ""));
                        $cod_barras = trim((string)obtener_post("cod_barras", ""));
                        $id_stock_raw = trim((string)obtener_post("id_stock", ""));
                        $factor_conversion = parsear_numero_form(obtener_post("consumo_por_venta", obtener_post("factor_conversion", 1)), 1);
                        $ganancia = parsear_numero_form(obtener_post("ganancia", 0), 0);
                        $precio_manual = parsear_numero_form(obtener_post("precio_final_manual", $p_actual["precio_final"] ?? 0), 0);
                        $usa_stock_general = (int)obtener_post("usa_stock_general", 0) === 1;
                        $unidad_stock = $this->resolver_unidad_form((string)obtener_post("unidad_stock", "u"));
                        $cantidad_stock = parsear_numero_form(obtener_post("cantidad_stock", $p_actual["stock_cantidad"] ?? 0), 0);
                        $stock_minimo = parsear_numero_form(obtener_post("stock_minimo", 0), 0);
                        $stock_maximo = parsear_numero_form(obtener_post("stock_maximo", 0), 0);
                        $precio_costo_form = parsear_numero_form(obtener_post("precio_costo", 0), 0);
                        $moneda_costo = strtoupper(trim((string)obtener_post("moneda_costo", "ARS"))) === "USD" ? "USD" : "ARS";
                        $activo = (int)obtener_post("activo", 1);
                        if (texto_invalido($nombre))
                            $error = "Nombre inválido (vacío o placeholder).";
                        else {
                            if (texto_invalido($cod_barras))
                                $cod_barras = trim((string)($p_actual["cod_barras"] ?? ""));
                            if (texto_invalido($cod_barras))
                                $cod_barras = $this->generar_codigo_barras_interno();
                            if (Producto::cod_barras_existe($cod_barras, $id))
                                $error = "Ya existe otro producto con ese código.";
                            else {
                                $id_stock = 0;
                                if ($id_stock_raw !== "" && ctype_digit($id_stock_raw))
                                    $id_stock = (int)$id_stock_raw;
                                if (texto_invalido($unidad_stock))
                                    $unidad_stock = "u";
                                if ($cantidad_stock < 0)
                                    $cantidad_stock = 0;
                                if ($stock_minimo < 0)
                                    $stock_minimo = 0;
                                if ($stock_maximo < 0)
                                    $stock_maximo = 0;
                                if ($precio_costo_form < 0)
                                    $precio_costo_form = 0;
                                if ($factor_conversion <= 0 && !$usa_stock_general)
                                    $factor_conversion = 1;
                                if ($ganancia < 0)
                                    $ganancia = 0;
                                $id_stock_anterior = (int)($p_actual["id_stock"] ?? 0);
                                $usaba_stock_general = $this->producto_usa_stock_general($p_actual);
                                if (!$usa_stock_general)
                                    $factor_conversion = 1;
                                if ($usa_stock_general && $factor_conversion <= 0)
                                    $error = "El consumo por venta debe ser mayor a cero.";
                                if (!$usa_stock_general) {
                                    if (!$usaba_stock_general && $id_stock_anterior > 0 && Stock::contar_productos_asociados($id_stock_anterior) <= 1) {
                                        $id_stock = $id_stock_anterior;
                                        $unidad_stock = UnidadMedida::asegurar_desde_form($unidad_stock, $this->datos_nueva_unidad_post());
                                        Stock::actualizar_con_tipo($id_stock, $nombre, $unidad_stock, $cantidad_stock, $precio_costo_form, $activo, $stock_minimo, $stock_maximo, "propio", $moneda_costo);
                                    } else {
                                        $unidad_stock = UnidadMedida::asegurar_desde_form($unidad_stock, $this->datos_nueva_unidad_post());
                                        $id_stock = Stock::crear_retornar_id_con_tipo($nombre, $unidad_stock, $cantidad_stock, $precio_costo_form, $activo, $stock_minimo, $stock_maximo, "propio", $moneda_costo);
                                        if ($id_stock <= 0)
                                            $error = "No se pudo crear el stock automatico del producto.";
                                    }
                                }
                                if ($error === "") {
                                    if ($id_stock <= 0)
                                        $error = "Tenes que seleccionar un stock principal.";
                                    else {
                                        if (!Producto::stock_existe($id_stock))
                                            $error = "El stock principal seleccionado no existe.";
                                        if ($error === "" && $usa_stock_general) {
                                            $stock_general = Stock::buscar_por_id($id_stock);
                                            if ($stock_general === null || (string)($stock_general["tipo_stock"] ?? "") !== "general" || (int)($stock_general["activo"] ?? 0) !== 1)
                                                $error = "Selecciona un stock general activo.";
                                        }
                                        if ($error === "") {
                                            $precio_costo = 0.0;
                                            $costo_stock = Producto::obtener_precio_costo_stock($id_stock);
                                            if ($costo_stock !== null)
                                                $precio_costo = $costo_stock;

                                            $precio_final = Producto::calcular_precio_final($precio_costo, $factor_conversion, $ganancia);
                                            if ($precio_final <= 0 && $precio_manual > 0)
                                                $precio_final = $precio_manual;

                                            $ok = Producto::actualizar($id, $nombre, $cod_barras, $id_stock, $factor_conversion, $ganancia, $precio_final, $activo);

                                            if ($ok) {
                                                $this->guardar_listas_producto($id, $precio_costo, $precio_final);
                                                if (!$usaba_stock_general && !empty($id_stock_anterior) && $id_stock_anterior !== $id_stock && Stock::contar_productos_asociados($id_stock_anterior) === 0)
                                                    Stock::eliminar($id_stock_anterior);
                                                flash_ok("Producto actualizado correctamente.");
                                                redirigir("index.php?c=productos&a=index");
                                            } else
                                                $error = "No se pudo actualizar el producto (ver logs).";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                flash_form_data("productos_form", [
                    "id" => $id ?? 0,
                    "nombre" => $nombre ?? "",
                    "cod_barras" => $cod_barras ?? "",
                    "id_stock" => $id_stock ?? 0,
                    "factor_conversion" => $factor_conversion ?? 1,
                    "ganancia" => $ganancia ?? 0,
                    "precio_final" => $precio_final ?? ($precio_manual ?? 0),
                    "activo" => $activo ?? 1,
                    "usa_stock_general" => !empty($usa_stock_general) ? 1 : 0,
                    "stock_unidad" => $unidad_stock ?? "u",
                    "stock_cantidad" => $cantidad_stock ?? ($p_actual["stock_cantidad"] ?? 0),
                    "stock_minimo" => $stock_minimo ?? 0,
                    "stock_maximo" => $stock_maximo ?? 0,
                    "stock_precio_costo" => Stock::costo_en_pesos($precio_costo_form ?? 0, $moneda_costo ?? "ARS"),
                    "stock_moneda_costo" => $moneda_costo ?? "ARS",
                    "stock_costo_origen" => $precio_costo_form ?? 0,
                ]);
                $id_redirigir = (int)($id ?? 0);
                if ($id_redirigir > 0)
                    redirigir("index.php?c=productos&a=editar&id=" . $id_redirigir);
                else
                    redirigir("index.php?c=productos&a=index");
            }
        }
    }

    public function eliminar(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            $p = Producto::buscar_por_id($id);
          if ($p === null) {
                flash_error("Producto no encontrado.");
                redirigir("index.php?c=productos&a=index");
            } else {
                if (Producto::esta_en_detalle_venta($id)) {
                    flash_error("No se puede eliminar: el producto está en ventas (detalle_venta).");
                    redirigir("index.php?c=productos&a=index");
                } else {
                    $id_stock_producto = (int)($p["id_stock"] ?? 0);
                    $ok = Producto::eliminar($id);
                    if ($ok) {
                        if ($id_stock_producto > 0 && Stock::contar_productos_asociados($id_stock_producto) === 0)
                            Stock::eliminar($id_stock_producto);
                        flash_ok("Producto eliminado.");
                    } else
                        flash_error("No se pudo eliminar (ver logs).");
                    redirigir("index.php?c=productos&a=index");
                }
            }
        }
    }

    public function eliminar_no_vendidos(): void {
        if ($this->permiso()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                flash_error("Acceso invalido.");
            } else {
                global $container;
                $eliminarProductosNoVendidos = $container->get(\Ventas\Productos\Application\EliminarProductosNoVendidos::class);
                $eliminados = $eliminarProductosNoVendidos->ejecutar();
                if ($eliminados > 0)
                    flash_ok("Productos sin ventas eliminados: " . $eliminados . ".");
                else
                    flash_ok("No hay productos sin ventas para eliminar.");
            }
            redirigir("index.php?c=productos&a=index");
        }
    }

}
