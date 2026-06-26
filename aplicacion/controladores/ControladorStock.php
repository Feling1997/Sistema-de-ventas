<?php
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";

class ControladorStock {
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

    private function buscar_stock_por_id_array(int $id): ?array {
        global $container;
        $buscarStockPorId = $container->get(\Ventas\Stock\Application\BuscarStockPorId::class);
        $stock = $buscarStockPorId->ejecutar($id);
        $resultado = null;
        if ($stock !== null) {
            $resultado = $this->stockDominioAArray($stock);
        }
        return $resultado;
    }

    private function cotizacion_dolar_stock(): float {
        global $container;
        $obtenerCotizacionDolarStock = $container->get(\Ventas\Stock\Application\ObtenerCotizacionDolarStock::class);
        $resultado = $obtenerCotizacionDolarStock->ejecutar();

        return $resultado;
    }

    private function costo_stock_en_pesos(float $costo_origen, string $moneda): float {
        global $container;
        $obtenerCostoEnPesosStock = $container->get(\Ventas\Stock\Application\ObtenerCostoEnPesosStock::class);
        $resultado = $obtenerCostoEnPesosStock->ejecutar($costo_origen, $moneda);

        return $resultado;
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

    private function asegurar_unidad_desde_form(string $unidad, array $datos): string {
        global $container;
        $asegurarUnidadMedidaDesdeFormulario = $container->get(\Ventas\UnidadesMedida\Application\AsegurarUnidadMedidaDesdeFormulario::class);
        return $asegurarUnidadMedidaDesdeFormulario->ejecutar($unidad, $datos);
    }

    private function permiso(): bool {
        $ok = false;
        if (!require_login()) {
            flash_error("Tenés que iniciar sesión.");
            redirigir("index.php?c=auth&a=login");
        } else {
            if (!require_rol(["ADMIN","VENDEDOR"])) {
                flash_error("No tenés permisos para Stock.");
                redirigir("index.php?c=ventas&a=lista");
            } else
                $ok = true;
        }
        return $ok;
    }

    public function index(): void {
        if ($this->permiso()) {
            $orden_stock = orden_parametros([
                "nombre" => "s.nombre",
                "descripcion" => "s.nombre",
                "stock" => "s.cantidad",
                "precio" => "s.precio_costo",
                "estado" => "s.activo",
                "fecha" => "s.creado_en"
            ], "nombre", "ASC");
            global $container;
            $listarStock = $container->get(\Ventas\Stock\Application\ListarStock::class);
            $items = [];
            foreach ($listarStock->ejecutar() as $stock_dominio) {
                $items[] = [
                    "id" => $stock_dominio->id(),
                    "nombre" => $stock_dominio->nombre(),
                    "unidad" => $stock_dominio->unidad(),
                    "tipo_stock" => $stock_dominio->tipoStock(),
                    "cantidad" => $stock_dominio->cantidad(),
                    "stock_minimo" => $stock_dominio->stockMinimo(),
                    "stock_maximo" => $stock_dominio->stockMaximo(),
                    "precio_costo" => $stock_dominio->precioCosto(),
                    "moneda_costo" => $stock_dominio->monedaCosto(),
                    "costo_origen" => $stock_dominio->costoOrigen(),
                    "activo" => $stock_dominio->activo() ? 1 : 0,
                    "unidad_decimales" => $stock_dominio->unidadDecimales(),
                    "creado_en" => $stock_dominio->creadoEn(),
                ];
            }
            usort($items, function (array $a, array $b) use ($orden_stock): int {
                $campo = (string)($orden_stock["campo"] ?? "nombre");
                $direccion = strtoupper((string)($orden_stock["direccion"] ?? "ASC"));
                $columnas = [
                    "nombre" => "nombre",
                    "descripcion" => "nombre",
                    "stock" => "cantidad",
                    "precio" => "precio_costo",
                    "estado" => "activo",
                    "fecha" => "creado_en",
                ];
                $columna = $columnas[$campo] ?? "nombre";
                $valor_a = $a[$columna] ?? "";
                $valor_b = $b[$columna] ?? "";
                $comparacion = is_numeric($valor_a) && is_numeric($valor_b)
                    ? ((float)$valor_a <=> (float)$valor_b)
                    : strcasecmp((string)$valor_a, (string)$valor_b);
                if ($comparacion === 0) {
                    $comparacion = ((int)($a["id"] ?? 0)) <=> ((int)($b["id"] ?? 0));
                }
                if ($direccion === "DESC") {
                    $comparacion *= -1;
                }
                return $comparacion;
            });
            $texto_buscar = trim((string)obtener_get("buscar", ""));
            $campo_buscar = trim((string)obtener_get("campo", "todos"));
            $metodo_buscar = trim((string)obtener_get("metodo", "contiene"));
            $campos_busqueda = [
                "id" => "ID",
                "nombre" => "Nombre",
                "unidad" => "Unidad",
                "tipo_stock" => "Tipo stock",
                "cantidad" => "Cantidad",
                "stock_minimo" => "Stock minimo",
                "stock_maximo" => "Stock maximo",
                "precio_costo" => "Precio costo"
            ];
            $items = filtrar_registros_busqueda($items, $texto_buscar, $campo_buscar, $campos_busqueda, $metodo_buscar);
            $id_usuario = (int)($_SESSION["usuario_logueado"]["id"] ?? 0);
            $filtro_alertas_stock = trim((string)obtener_get("filtro_alertas_stock", "bajo"));
            if (!in_array($filtro_alertas_stock, ["bajo", "criticos"], true))
                $filtro_alertas_stock = "bajo";
            $mostrar_alertas_leidas = (int)obtener_get("mostrar_alertas_leidas", 0) === 1;
            global $container;
            $alertasStockBajo = $container->get(\Ventas\Stock\Application\AlertasStockBajo::class);
            $resumenAlertasStockBajo = $container->get(\Ventas\Stock\Application\ResumenAlertasStockBajo::class);
            $alertas_stock_bajo = $alertasStockBajo->ejecutar($id_usuario, $mostrar_alertas_leidas, $filtro_alertas_stock);
            $resumen_alertas_stock = $resumenAlertasStockBajo->ejecutar($id_usuario);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/stock/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function nuevo(): void {
        if ($this->permiso()) {
            $modo = "crear";
            $s = ["id" => 0, "nombre" => "", "unidad" => "u", "tipo_stock" => "general", "cantidad" => 0, "stock_minimo" => 0, "stock_maximo" => 0, "precio_costo" => 0, "moneda_costo" => "ARS", "costo_origen" => 0, "activo" => 1];
            $datos_form = obtener_form_data("stock_form");
            if ($datos_form !== [])
                $s = array_merge($s, $datos_form);
            global $container;
            $listarUnidadesMedida = $container->get(\Ventas\UnidadesMedida\Application\ListarUnidadesMedida::class);
            $unidades_medida = [];
            foreach ($listarUnidadesMedida->ejecutar() as $unidad_medida_dominio) {
                $unidades_medida[] = [
                    "id" => $unidad_medida_dominio->id(),
                    "nombre" => $unidad_medida_dominio->nombre(),
                    "abreviatura" => $unidad_medida_dominio->abreviatura(),
                    "tipo" => $unidad_medida_dominio->tipo(),
                    "decimales" => $unidad_medida_dominio->decimales(),
                    "activo" => $unidad_medida_dominio->activo() ? 1 : 0,
                ];
            }
            $cotizacion_dolar_stock = $this->cotizacion_dolar_stock();
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/stock/formulario.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function crear(): void {
        if ($this->permiso()) {
            $error = "";
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $csrf = obtener_post("csrf", "");
                if (!csrf_valido($csrf))
                    $error = "Token inválido. Recargá la página.";
                else {
                    $nombre = trim((string)obtener_post("nombre", ""));
                    $unidad = $this->resolver_unidad_form((string)obtener_post("unidad", "u"));
                    $tipo_stock = trim((string)obtener_post("tipo_stock", "general"));
                    $cantidad = parsear_numero_form(obtener_post("cantidad", 0), 0);
                    $stock_minimo = parsear_numero_form(obtener_post("stock_minimo", 0), 0);
                    $stock_maximo = parsear_numero_form(obtener_post("stock_maximo", 0), 0);
                    $precio_costo = parsear_numero_form(obtener_post("precio_costo", 0), 0);
                    $moneda_costo = strtoupper(trim((string)obtener_post("moneda_costo", "ARS"))) === "USD" ? "USD" : "ARS";
                    $activo = (int)obtener_post("activo", 1);
                    if (texto_invalido($nombre))
                        $error = "Nombre inválido (vacío o placeholder).";
                    else {
                        if (texto_invalido($unidad))
                            $unidad = "u";
                        $unidad = $this->asegurar_unidad_desde_form($unidad, $this->datos_nueva_unidad_post());
                        if ($cantidad < 0)
                            $cantidad = 0;
                        if ($precio_costo < 0)
                            $precio_costo = 0;
                        if ($stock_minimo < 0)
                            $stock_minimo = 0;
                        if ($stock_maximo < 0)
                            $stock_maximo = 0;
                        global $container;
                        $costo_origen = max(0, (float)$precio_costo);
                        $precio_costo = $this->costo_stock_en_pesos($costo_origen, $moneda_costo);
                        $crearStock = $container->get(\Ventas\Stock\Application\CrearStock::class);
                        $ok = $crearStock->ejecutar($nombre, $unidad, $cantidad, $precio_costo, $activo, $stock_minimo, $stock_maximo, $tipo_stock, $moneda_costo, $costo_origen);
                        if ($ok) {
                            flash_ok("Stock creado correctamente.");
                            redirigir("index.php?c=stock&a=index");
                        } else
                            $error = "No se pudo crear el stock (ver logs).";
                    }
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                flash_form_data("stock_form", [
                    "id" => 0,
                    "nombre" => $nombre ?? "",
                    "unidad" => $unidad ?? "u",
                    "tipo_stock" => $tipo_stock ?? "general",
                    "cantidad" => $cantidad ?? 0,
                    "stock_minimo" => $stock_minimo ?? 0,
                    "stock_maximo" => $stock_maximo ?? 0,
                    "precio_costo" => $precio_costo ?? 0,
                    "moneda_costo" => $moneda_costo ?? "ARS",
                    "costo_origen" => $precio_costo ?? 0,
                    "activo" => $activo ?? 1
                ]);
                redirigir("index.php?c=stock&a=nuevo");
            }
        }
    }

    public function editar(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            global $container;
            $buscarStockPorId = $container->get(\Ventas\Stock\Application\BuscarStockPorId::class);
            $listarUnidadesMedida = $container->get(\Ventas\UnidadesMedida\Application\ListarUnidadesMedida::class);
            $stock_dominio = $buscarStockPorId->ejecutar($id);
            if ($stock_dominio === null) {
                flash_error("Stock no encontrado.");
                redirigir("index.php?c=stock&a=index");
            } else {
                $s = [
                    "id" => $stock_dominio->id(),
                    "nombre" => $stock_dominio->nombre(),
                    "unidad" => $stock_dominio->unidad(),
                    "tipo_stock" => $stock_dominio->tipoStock(),
                    "cantidad" => $stock_dominio->cantidad(),
                    "stock_minimo" => $stock_dominio->stockMinimo(),
                    "stock_maximo" => $stock_dominio->stockMaximo(),
                    "precio_costo" => $stock_dominio->precioCosto(),
                    "moneda_costo" => $stock_dominio->monedaCosto(),
                    "costo_origen" => $stock_dominio->costoOrigen(),
                    "activo" => $stock_dominio->activo() ? 1 : 0,
                    "unidad_decimales" => $stock_dominio->unidadDecimales(),
                    "creado_en" => $stock_dominio->creadoEn(),
                ];
                $modo = "editar";
                $datos_form = obtener_form_data("stock_form");
                if ($datos_form !== [])
                    $s = array_merge($s, $datos_form);
                $unidades_medida = [];
                foreach ($listarUnidadesMedida->ejecutar() as $unidad_medida_dominio) {
                    $unidades_medida[] = [
                        "id" => $unidad_medida_dominio->id(),
                        "nombre" => $unidad_medida_dominio->nombre(),
                        "abreviatura" => $unidad_medida_dominio->abreviatura(),
                        "tipo" => $unidad_medida_dominio->tipo(),
                        "decimales" => $unidad_medida_dominio->decimales(),
                        "activo" => $unidad_medida_dominio->activo() ? 1 : 0,
                    ];
                }
                $cotizacion_dolar_stock = $this->cotizacion_dolar_stock();
                include __DIR__ . "/../vistas/parciales/encabezado.php";
                include __DIR__ . "/../vistas/stock/formulario.php";
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
                    global $container;
                    $buscarStockPorId = $container->get(\Ventas\Stock\Application\BuscarStockPorId::class);
                    $stock_actual = $buscarStockPorId->ejecutar($id);
                    if ($stock_actual === null)
                        $error = "Stock no encontrado.";
                    else {
                        $s_actual = $this->stockDominioAArray($stock_actual);
                        $nombre = trim((string)obtener_post("nombre", ""));
                        $unidad = $this->resolver_unidad_form((string)obtener_post("unidad", "u"));
                        $tipo_stock = trim((string)obtener_post("tipo_stock", (string)($s_actual["tipo_stock"] ?? "general")));
                        $cantidad = parsear_numero_form(obtener_post("cantidad", 0), 0);
                        $stock_minimo = parsear_numero_form(obtener_post("stock_minimo", 0), 0);
                        $stock_maximo = parsear_numero_form(obtener_post("stock_maximo", 0), 0);
                        $precio_costo = parsear_numero_form(obtener_post("precio_costo", 0), 0);
                        $moneda_costo = strtoupper(trim((string)obtener_post("moneda_costo", "ARS"))) === "USD" ? "USD" : "ARS";
                        $activo = (int)obtener_post("activo", 1);
                        if (texto_invalido($nombre))
                            $error = "Nombre inválido (vacío o placeholder).";
                        else {
                            if (texto_invalido($unidad))
                                $unidad = "u";
                            $unidad = $this->asegurar_unidad_desde_form($unidad, $this->datos_nueva_unidad_post());
                            if ($cantidad < 0)
                                $cantidad = 0;
                            if ($precio_costo < 0)
                                $precio_costo = 0;
                            if ($stock_minimo < 0)
                                $stock_minimo = 0;
                            if ($stock_maximo < 0)
                                $stock_maximo = 0;
                            $costo_origen = max(0, (float)$precio_costo);
                            $costo_nuevo = $this->costo_stock_en_pesos($costo_origen, $moneda_costo);
                            $actualizarStock = $container->get(\Ventas\Stock\Application\ActualizarStock::class);
                            $ok = $actualizarStock->ejecutar($id, $nombre, $unidad, $cantidad, $costo_nuevo, $activo, $stock_minimo, $stock_maximo, $tipo_stock, $moneda_costo, $costo_origen);
                            if ($ok) {
                                $costo_anterior = (float)$s_actual["precio_costo"];
                                if (abs($costo_anterior - $costo_nuevo) > 0.00001) {
                                    $recalcularPreciosProductosPorStock = $container->get(\Ventas\Stock\Application\RecalcularPreciosProductosPorStock::class);
                                    $ok_recalc = $recalcularPreciosProductosPorStock->ejecutar($id);
                                    if ($ok_recalc)
                                        flash_ok("Stock actualizado y precios de productos recalculados.");
                                    else
                                        flash_ok("Stock actualizado. (No se pudo recalcular precios: ver logs)");
                                } else
                                    flash_ok("Stock actualizado correctamente.");
                                redirigir("index.php?c=stock&a=index");
                            } else
                                $error = "No se pudo actualizar el stock (ver logs).";
                        }
                    }
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                flash_form_data("stock_form", [
                    "id" => $id ?? 0,
                    "nombre" => $nombre ?? "",
                    "unidad" => $unidad ?? "u",
                    "tipo_stock" => $tipo_stock ?? "general",
                    "cantidad" => $cantidad ?? 0,
                    "stock_minimo" => $stock_minimo ?? 0,
                    "stock_maximo" => $stock_maximo ?? 0,
                    "precio_costo" => $precio_costo ?? 0,
                    "moneda_costo" => $moneda_costo ?? "ARS",
                    "costo_origen" => $precio_costo ?? 0,
                    "activo" => $activo ?? 1
                ]);
                $id_redirigir = (int)($id ?? 0);
                if ($id_redirigir > 0)
                    redirigir("index.php?c=stock&a=editar&id=" . $id_redirigir);
                else
                    redirigir("index.php?c=stock&a=index");
            }
        }
    }

    public function agregar(): void {
        if ($this->permiso()) {
            $error = "";
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $csrf = obtener_post("csrf", "");
                if (!csrf_valido($csrf))
                    $error = "Token invalido. Recarga la pagina.";
                else {
                    $id = (int)obtener_post("id", 0);
                    $cantidad = parsear_numero_form(obtener_post("cantidad_agregar", 0), 0);
                    global $container;
                    $buscarStockPorId = $container->get(\Ventas\Stock\Application\BuscarStockPorId::class);
                    $s = $buscarStockPorId->ejecutar($id);
                    if ($s === null)
                        $error = "Stock no encontrado.";
                    else if ($cantidad <= 0)
                        $error = "Ingresa una cantidad mayor a cero.";
                    else {
                        $sumarCantidadStock = $container->get(\Ventas\Stock\Application\SumarCantidadStock::class);
                        $ok = $sumarCantidadStock->ejecutar($id, $cantidad);
                        if ($ok)
                            flash_ok("Stock agregado correctamente.");
                        else
                            flash_error("No se pudo agregar stock (ver logs).");
                        redirigir("index.php?c=stock&a=index");
                    }
                }
            } else
                $error = "Acceso invalido.";
            if ($error !== "") {
                flash_error($error);
                redirigir("index.php?c=stock&a=index");
            }
        }
    }

    public function marcar_alerta_leida(): void {
        if ($this->permiso()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=stock&a=index");
                return;
            }
            $id_producto = (int)obtener_post("id_producto", 0);
            $id_usuario = (int)($_SESSION["usuario_logueado"]["id"] ?? 0);
            if ($id_producto <= 0) {
                flash_error("Producto invalido.");
            } else {
                global $container;
                $marcarAlertaLeida = $container->get(\Ventas\Stock\Application\MarcarAlertaLeida::class);
                $ok = $marcarAlertaLeida->ejecutar($id_producto, $id_usuario);
                if ($ok)
                    flash_ok("Alerta de stock marcada como leida.");
                else
                    flash_error("No se pudo marcar la alerta como leida.");
            }
            redirigir("index.php?c=stock&a=index");
        }
    }

    public function productos(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            global $container;
            $buscarStockPorId = $container->get(\Ventas\Stock\Application\BuscarStockPorId::class);
            $listarStock = $container->get(\Ventas\Stock\Application\ListarStock::class);
            $listarListasPrecios = $container->get(\Ventas\ListasPrecios\Application\ListarListasPrecios::class);
            $listarProductosPorStock = $container->get(\Ventas\Productos\Application\ListarProductosPorStock::class);
            $stock_dominio = $buscarStockPorId->ejecutar($id);
            if ($stock_dominio === null) {
                flash_error("Stock no encontrado.");
                redirigir("index.php?c=stock&a=index");
            } else {
                $s = [
                    "id" => $stock_dominio->id(),
                    "nombre" => $stock_dominio->nombre(),
                    "unidad" => $stock_dominio->unidad(),
                    "tipo_stock" => $stock_dominio->tipoStock(),
                    "cantidad" => $stock_dominio->cantidad(),
                    "stock_minimo" => $stock_dominio->stockMinimo(),
                    "stock_maximo" => $stock_dominio->stockMaximo(),
                    "precio_costo" => $stock_dominio->precioCosto(),
                    "moneda_costo" => $stock_dominio->monedaCosto(),
                    "costo_origen" => $stock_dominio->costoOrigen(),
                    "activo" => $stock_dominio->activo() ? 1 : 0,
                    "unidad_decimales" => $stock_dominio->unidadDecimales(),
                    "creado_en" => $stock_dominio->creadoEn(),
                ];
                $items = [];
                foreach ($listarStock->ejecutar() as $stock_item_dominio) {
                    $items[] = [
                        "id" => $stock_item_dominio->id(),
                        "nombre" => $stock_item_dominio->nombre(),
                        "unidad" => $stock_item_dominio->unidad(),
                        "tipo_stock" => $stock_item_dominio->tipoStock(),
                        "cantidad" => $stock_item_dominio->cantidad(),
                        "stock_minimo" => $stock_item_dominio->stockMinimo(),
                        "stock_maximo" => $stock_item_dominio->stockMaximo(),
                        "precio_costo" => $stock_item_dominio->precioCosto(),
                        "moneda_costo" => $stock_item_dominio->monedaCosto(),
                        "costo_origen" => $stock_item_dominio->costoOrigen(),
                        "activo" => $stock_item_dominio->activo() ? 1 : 0,
                        "unidad_decimales" => $stock_item_dominio->unidadDecimales(),
                        "creado_en" => $stock_item_dominio->creadoEn(),
                    ];
                }
                $listas_precios = [];
                foreach ($listarListasPrecios->ejecutar() as $lista_precio_dominio) {
                    $listas_precios[] = [
                        "id" => $lista_precio_dominio->id(),
                        "nombre" => $lista_precio_dominio->nombre(),
                        "activo" => $lista_precio_dominio->activo() ? 1 : 0,
                        "creado_en" => $lista_precio_dominio->creadoEn(),
                    ];
                }
                $orden_productos_stock = orden_parametros([
                    "nombre" => "nombre",
                    "descripcion" => "nombre",
                    "codigo" => "cod_barras",
                    "codigo_barras" => "cod_barras",
                    "precio" => "precio_final",
                    "stock" => "factor_conversion",
                    "estado" => "activo",
                    "fecha" => "creado_en"
                ], "nombre", "ASC");
                $productos = [];
                foreach ($listarProductosPorStock->ejecutar($id) as $producto_dominio) {
                    $productos[] = [
                        "id" => $producto_dominio->id(),
                        "nombre" => $producto_dominio->nombre(),
                        "cod_barras" => $producto_dominio->codBarras(),
                        "id_stock" => $producto_dominio->idStock(),
                        "factor_conversion" => $producto_dominio->factorConversion(),
                        "ganancia" => $producto_dominio->ganancia(),
                        "precio_final" => $producto_dominio->precioFinal(),
                        "activo" => $producto_dominio->activo() ? 1 : 0,
                        "creado_en" => $producto_dominio->creadoEn(),
                    ];
                }
                usort($productos, function (array $a, array $b) use ($orden_productos_stock): int {
                    $campo = (string)($orden_productos_stock["campo"] ?? "nombre");
                    $direccion = strtoupper((string)($orden_productos_stock["direccion"] ?? "ASC"));
                    $columnas = [
                        "nombre" => "nombre",
                        "descripcion" => "nombre",
                        "codigo" => "cod_barras",
                        "codigo_barras" => "cod_barras",
                        "precio" => "precio_final",
                        "stock" => "factor_conversion",
                        "estado" => "activo",
                        "fecha" => "creado_en",
                    ];
                    $columna = $columnas[$campo] ?? "nombre";
                    $valor_a = $a[$columna] ?? "";
                    $valor_b = $b[$columna] ?? "";
                    $comparacion = is_numeric($valor_a) && is_numeric($valor_b)
                        ? ((float)$valor_a <=> (float)$valor_b)
                        : strcasecmp((string)$valor_a, (string)$valor_b);
                    if ($comparacion === 0) {
                        $comparacion = ((int)($a["id"] ?? 0)) <=> ((int)($b["id"] ?? 0));
                    }
                    if ($direccion === "DESC") {
                        $comparacion *= -1;
                    }
                    return $comparacion;
                });
                $texto_buscar = trim((string)obtener_get("buscar", ""));
                $campo_buscar = trim((string)obtener_get("campo", "todos"));
                $metodo_buscar = trim((string)obtener_get("metodo", "contiene"));
                $campos_busqueda = [
                    "id" => "ID",
                    "nombre" => "Nombre",
                    "cod_barras" => "Código de barras",
                    "factor_conversion" => "Factor",
                    "ganancia" => "Ganancia",
                    "precio_final" => "Precio final",
                    "activo" => "Activo",
                    "creado_en" => "Fecha"
                ];
                $productos = filtrar_registros_busqueda($productos, $texto_buscar, $campo_buscar, $campos_busqueda, $metodo_buscar);
                include __DIR__ . "/../vistas/parciales/encabezado.php";
                include __DIR__ . "/../vistas/stock/index.php";
                include __DIR__ . "/../vistas/parciales/pie.php";
            }
        }
    }

    public function exportar(): void {
        if ($this->permiso()) {
            $formato = strtolower((string)obtener_get("formato", "html"));
            global $container;
            $listarStock = $container->get(\Ventas\Stock\Application\ListarStock::class);
            $items = [];
            foreach ($listarStock->ejecutar() as $stock_dominio) {
                $items[] = [
                    "id" => $stock_dominio->id(),
                    "nombre" => $stock_dominio->nombre(),
                    "unidad" => $stock_dominio->unidad(),
                    "tipo_stock" => $stock_dominio->tipoStock(),
                    "cantidad" => $stock_dominio->cantidad(),
                    "stock_minimo" => $stock_dominio->stockMinimo(),
                    "stock_maximo" => $stock_dominio->stockMaximo(),
                    "precio_costo" => $stock_dominio->precioCosto(),
                    "moneda_costo" => $stock_dominio->monedaCosto(),
                    "costo_origen" => $stock_dominio->costoOrigen(),
                    "activo" => $stock_dominio->activo() ? 1 : 0,
                    "unidad_decimales" => $stock_dominio->unidadDecimales(),
                    "creado_en" => $stock_dominio->creadoEn(),
                ];
            }
            $titulo = "Stock actual";
            $base_archivo = "stock_actual";
            if ($formato === "csv" || $formato === "xls" || $formato === "excel") {
                header("Content-Type: text/csv; charset=utf-8");
                header("Content-Disposition: attachment; filename=\"" . $this->nombre_archivo($base_archivo, "csv") . "\"");
                $out = fopen("php://output", "w");
                if ($out !== false) {
                    fprintf($out, "\xEF\xBB\xBF");
                    fputcsv($out, ["Stock", "Cantidad", "Minimo", "Maximo", "Unidad", "Costo"], ";");
                    foreach ($items as $it)
                        fputcsv($out, [$it["nombre"], stock_para_mostrar($it["cantidad"] ?? 0, 3), numero_para_mostrar($it["stock_minimo"] ?? 0, 3), numero_para_mostrar($it["stock_maximo"] ?? 0, 3), $it["unidad"], numero_precio_para_exportar($it["precio_costo"] ?? 0, 2)], ";");
                    fclose($out);
                }
                return;
            }
            $html = $this->html_stock($items, $titulo);
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
                    return;
                }
            }
            echo $html;
        }
    }

    public function exportar_reporte(): void {
        if ($this->permiso()) {
            $tipo = strtolower((string)obtener_get("tipo", "stock"));
            $formato = strtolower((string)obtener_get("formato", "pdf"));
            if (!in_array($formato, ["html", "pdf", "xls", "excel", "csv"], true))
                $formato = "pdf";
            if ($tipo === "faltantes_minimo")
                redirigir("index.php?c=stock&a=exportar_faltantes&solo_minimo=1&formato=" . urlencode($formato));
            else if ($tipo === "faltantes_completo")
                redirigir("index.php?c=stock&a=exportar_faltantes&solo_minimo=0&formato=" . urlencode($formato));
            else
                redirigir("index.php?c=stock&a=exportar&formato=" . urlencode($formato));
        }
    }

    public function exportar_productos(): void {
        if ($this->permiso()) {
            global $container;
            $obtenerPrecioProductoCargado = $container->get(\Ventas\ListasPrecios\Application\ObtenerPrecioProductoCargado::class);
            $listarListasPrecios = $container->get(\Ventas\ListasPrecios\Application\ListarListasPrecios::class);
            $id = (int)obtener_get("id", 0);
            $s = $this->buscar_stock_por_id_array($id);
            if ($s === null) {
                flash_error("Stock no encontrado.");
                redirigir("index.php?c=stock&a=index");
                return;
            }
            $formato = strtolower((string)obtener_get("formato", "html"));
            $id_lista = (int)obtener_get("id_lista_precio", 0);
            if ($id_lista <= 0) {
                flash_error("Selecciona una lista de precios cargada.");
                redirigir("index.php?c=exportaciones&a=index");
                return;
            }
            $productos = $this->listar_productos_por_stock($id);
            $titulo = "Articulos de " . (string)($s["nombre"] ?? "stock");
            $nombre_lista = "";
            foreach ($listarListasPrecios->ejecutar(true) as $lista) {
                if ((int)$lista->id() === $id_lista)
                    $nombre_lista = (string)$lista->nombre();
            }
            if ($nombre_lista === "") {
                flash_error("Selecciona una lista de precios cargada.");
                redirigir("index.php?c=exportaciones&a=index");
                return;
            }
            $titulo .= " - " . $nombre_lista;
            $base_archivo = "articulos_stock_" . (string)($s["nombre"] ?? "stock") . "_" . $nombre_lista;
            foreach ($productos as &$p) {
                $precio_lista = $obtenerPrecioProductoCargado->ejecutar((int)($p["id"] ?? 0), $id_lista);
                $p["precio_final"] = ($precio_lista !== null && (float)$precio_lista["precio"] > 0) ? (float)$precio_lista["precio"] : 0;
            }
            unset($p);
            if ($formato === "csv" || $formato === "xls" || $formato === "excel") {
                header("Content-Type: text/csv; charset=utf-8");
                header("Content-Disposition: attachment; filename=\"" . $this->nombre_archivo($base_archivo, "csv") . "\"");
                $out = fopen("php://output", "w");
                if ($out !== false) {
                    fprintf($out, "\xEF\xBB\xBF");
                    fputcsv($out, ["Articulo", "Codigo", "Factor", "Precio", "Estado"], ";");
                    foreach ($productos as $p)
                        fputcsv($out, [$p["nombre"], $p["cod_barras"], numero_para_mostrar($p["factor_conversion"] ?? 0, 4), numero_precio_para_exportar($p["precio_final"] ?? 0, 2), ((int)($p["activo"] ?? 0) === 1) ? "Alta" : "Baja"], ";");
                    fclose($out);
                }
                return;
            }
            $html = $this->html_productos_stock($productos, $titulo, $s);
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
                    return;
                }
            }
            echo $html;
        }
    }

    public function faltantes(): void {
        if ($this->permiso()) {
            $solo_minimo = (int)obtener_get("solo_minimo", 1) === 1;
            global $container;
            $listarFaltantes = $container->get(\Ventas\Stock\Application\ListarFaltantes::class);
            $items = $listarFaltantes->ejecutar($solo_minimo);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/stock/faltantes.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function exportar_faltantes(): void {
        if ($this->permiso()) {
            $solo_minimo = (int)obtener_get("solo_minimo", 1) === 1;
            $formato = strtolower((string)obtener_get("formato", "html"));
            global $container;
            $listarFaltantes = $container->get(\Ventas\Stock\Application\ListarFaltantes::class);
            $items = $listarFaltantes->ejecutar($solo_minimo);
            $titulo = $solo_minimo ? "Pedido sugerido por stock minimo" : "Reporte completo de faltantes";
            $base_archivo = $solo_minimo ? "pedido_sugerido_stock_minimo" : "faltantes_stock_completo";
            if ($formato === "csv" || $formato === "xls" || $formato === "excel") {
                header("Content-Type: text/csv; charset=utf-8");
                header("Content-Disposition: attachment; filename=\"" . $this->nombre_archivo($base_archivo, "csv") . "\"");
                $out = fopen("php://output", "w");
                fprintf($out, "\xEF\xBB\xBF");
                fputcsv($out, ["Stock", "Cantidad", "Minimo", "Maximo", "Unidad", "Sugerido"], ";");
                foreach ($items as $it)
                    fputcsv($out, [$it["nombre"], stock_para_mostrar($it["cantidad"] ?? 0, 3), $it["stock_minimo"], $it["stock_maximo"], $it["unidad"], $it["cantidad_sugerida"]], ";");
                fclose($out);
                return;
            }
            $html = $this->html_faltantes($items, $titulo);
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
                    return;
                }
            }
            echo $html;
        }
    }

    private function html_faltantes(array $items, string $titulo): string {
        $filas = "";
        foreach ($items as $it) {
            $sugerido = (float)($it["cantidad_sugerida"] ?? 0);
            $filas .= "<tr><td>" . htmlspecialchars((string)$it["nombre"]) . "</td><td class='num'>" . htmlspecialchars(stock_para_mostrar($it["cantidad"] ?? 0, 3)) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($it["stock_minimo"] ?? 0, 3)) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($it["stock_maximo"] ?? 0, 3)) . "</td><td>" . htmlspecialchars((string)$it["unidad"]) . "</td><td class='num'><b>" . htmlspecialchars(numero_para_mostrar($sugerido, 3)) . "</b></td></tr>";
        }
        return reporte_html_tabla($titulo, "Pedido y control de reposicion de stock", ["Stock", "Cantidad", "Minimo", "Maximo", "Unidad", "Sugerido a pedir"], $filas, 6);
    }

    private function html_stock(array $items, string $titulo): string {
        $filas = "";
        foreach ($items as $it)
            $filas .= "<tr><td>" . htmlspecialchars((string)($it["nombre"] ?? "")) . "</td><td class='num'>" . htmlspecialchars(stock_para_mostrar($it["cantidad"] ?? 0, 3)) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($it["stock_minimo"] ?? 0, 3)) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($it["stock_maximo"] ?? 0, 3)) . "</td><td>" . htmlspecialchars((string)($it["unidad"] ?? "")) . "</td><td class='num'>" . htmlspecialchars(precio_para_mostrar($it["precio_costo"] ?? 0)) . "</td></tr>";
        return reporte_html_tabla($titulo, "Existencias actuales del sistema", ["Stock", "Cantidad", "Minimo", "Maximo", "Unidad", "Costo"], $filas, 6);
    }

    private function html_productos_stock(array $productos, string $titulo, array $stock): string {
        $detalle = "Stock base: " . (string)($stock["nombre"] ?? "") . " | Cantidad: " . stock_para_mostrar($stock["cantidad"] ?? 0, 3) . " " . (string)($stock["unidad"] ?? "");
        $filas = "";
        foreach ($productos as $p)
            $filas .= "<tr><td>" . htmlspecialchars((string)($p["nombre"] ?? "")) . "</td><td>" . htmlspecialchars((string)($p["cod_barras"] ?? "")) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($p["factor_conversion"] ?? 0, 4)) . "</td><td class='num'>" . htmlspecialchars(precio_para_mostrar($p["precio_final"] ?? 0)) . "</td><td>" . (((int)($p["activo"] ?? 0) === 1) ? "Alta" : "Baja") . "</td></tr>";
        return reporte_html_tabla($titulo, "Articulos asociados al stock seleccionado", ["Articulo", "Codigo", "Factor", "Precio", "Estado"], $filas, 5, $detalle);
    }

    private function listar_productos_por_stock(int $id_stock, string $orden_sql = "nombre ASC"): array {
        global $container;
        $listarProductosPorStock = $container->get(\Ventas\Productos\Application\ListarProductosPorStock::class);
        $lista = [];
        foreach ($listarProductosPorStock->ejecutar($id_stock) as $producto_dominio) {
            $lista[] = [
                "id" => $producto_dominio->id(),
                "nombre" => $producto_dominio->nombre(),
                "cod_barras" => $producto_dominio->codBarras(),
                "id_stock" => $producto_dominio->idStock(),
                "factor_conversion" => $producto_dominio->factorConversion(),
                "ganancia" => $producto_dominio->ganancia(),
                "precio_final" => $producto_dominio->precioFinal(),
                "activo" => $producto_dominio->activo() ? 1 : 0,
                "creado_en" => $producto_dominio->creadoEn(),
            ];
        }
        return $lista;
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



    public function eliminar(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            $s = $this->buscar_stock_por_id_array($id);
            if ($s === null) {
                flash_error("Stock no encontrado.");
                redirigir("index.php?c=stock&a=index");
            } else {
                global $container;
                $estaAsociadoAProductosStock = $container->get(\Ventas\Stock\Application\EstaAsociadoAProductosStock::class);
                if ($estaAsociadoAProductosStock->ejecutar($id)) {
                    flash_error("No se puede eliminar: el stock está asociado a productos.");
                    redirigir("index.php?c=stock&a=index");
                } else {
                    $eliminarStock = $container->get(\Ventas\Stock\Application\EliminarStock::class);
                    $ok = $eliminarStock->ejecutar($id);
                    if ($ok)
                        flash_ok("Stock eliminado.");
                    else
                        flash_error("No se pudo eliminar (ver logs).");
                    redirigir("index.php?c=stock&a=index");
                }
            }
        }
    }
}
