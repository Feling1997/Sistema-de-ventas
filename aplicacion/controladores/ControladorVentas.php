<?php
require_once __DIR__ . "/../modelos/Venta.php";
require_once __DIR__ . "/../modelos/FacturaFiscal.php";
require_once __DIR__ . "/../modelos/Cliente.php";
require_once __DIR__ . "/../modelos/Presupuesto.php";
require_once __DIR__ . "/../modelos/Configuracion.php";
require_once __DIR__ . "/../modelos/ConfiguracionSistema.php";
require_once __DIR__ . "/../modelos/ListaPrecio.php";
require_once __DIR__ . "/../modelos/UnidadMedida.php";
require_once __DIR__ . "/../modelos/CuentaCorriente.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";
require_once __DIR__ . "/../../configuraciones/base_datos.php";

class ControladorVentas {
    private function permiso(): bool {
        $ok = false;
        if (!require_login()) {
            flash_error("Tenés que iniciar sesión.");
            redirigir("index.php?c=auth&a=login");
        } else {
            if (!require_rol(["ADMIN","VENDEDOR"])) {
                flash_error("No tenés permisos para Ventas.");
                redirigir("index.php?c=auth&a=login");
            } else
                $ok = true;
        }
        return $ok;
    }

    private function obtener_carrito(): array {
        iniciar_sesion();
        $carrito = [];
        if (isset($_SESSION["carrito"]) && is_array($_SESSION["carrito"]))
            $carrito = $_SESSION["carrito"];
        return $carrito;
    }

    private function guardar_carrito(array $carrito): void {
        iniciar_sesion();
        $_SESSION["carrito"] = $carrito;
    }

    private function vaciar_carrito_interno(): void {
        iniciar_sesion();
        $_SESSION["carrito"] = [];
    }

    private function controlar_stock_ventas(): bool {
        $config = ConfiguracionSistema::obtener();
        return (string)($config["controlar_stock_ventas"] ?? "1") === "1";
    }

    private function asegurar_indices_rendimiento(): void {
        static $verificado = false;
        if ($verificado)
            return;
        $pdo = obtener_pdo();
        if ($pdo === null)
            return;
        try {
            $indices = [];
            $st = $pdo->query("SHOW INDEX FROM productos");
            foreach ($st->fetchAll() as $row)
                $indices[(string)($row["Key_name"] ?? "")] = true;
            if (empty($indices["idx_productos_activo_nombre"]))
                $pdo->exec("CREATE INDEX idx_productos_activo_nombre ON productos (activo, nombre)");
            if (empty($indices["idx_productos_activo_id"]))
                $pdo->exec("CREATE INDEX idx_productos_activo_id ON productos (activo, id)");
            $verificado = true;
        } catch (Throwable $e) {
            registrar_log("ControladorVentas::asegurar_indices_rendimiento", $e->getMessage());
        }
    }

    private function obtener_form_venta(): array {
        $datos = [
            "id_cliente" => 1,
            "buscar_cliente" => "",
            "id_producto" => "",
            "cantidad" => 1,
            "descuento" => 0,
            "precio_unit" => "",
            "tipo_comprobante" => 98,
            "buscar_producto" => "",
            "id_lista_precio" => ListaPrecio::id_predeterminada()
        ];
        $flash = obtener_form_data("ventas_form");
        if ($flash !== [])
            $datos = array_merge($datos, $flash);
        return $datos;
    }

    private function guardar_form_venta(array $datos): void {
        flash_form_data("ventas_form", [
            "id_cliente" => (int)($datos["id_cliente"] ?? 1),
            "buscar_cliente" => (string)($datos["buscar_cliente"] ?? ""),
            "id_producto" => (string)($datos["id_producto"] ?? ""),
            "cantidad" => $datos["cantidad"] ?? 1,
            "descuento" => $datos["descuento"] ?? 0,
            "precio_unit" => (string)($datos["precio_unit"] ?? ""),
            "tipo_comprobante" => (int)($datos["tipo_comprobante"] ?? 98),
            "buscar_producto" => (string)($datos["buscar_producto"] ?? ""),
            "id_lista_precio" => (int)($datos["id_lista_precio"] ?? ListaPrecio::id_predeterminada())
        ]);
    }

    private function listar_clientes_select(): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                Cliente::asegurar_columnas_fiscales($pdo);
                $sql = "SELECT id, nombre, dni, tipo_documento, condicion_iva, email, id_lista_precio FROM clientes ORDER BY (id=1) DESC, nombre ASC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("ControladorVentas::listar_clientes_select", $e->getMessage());
            }
        }
        return $lista;
    }

    private function listar_productos_select(): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                ListaPrecio::asegurar_tablas();
                $sql = "SELECT p.id, p.nombre, p.cod_barras, p.precio_final, p.factor_conversion, p.id_stock, p.id_asociado,
                               GROUP_CONCAT(CONCAT(pp.id_lista, ':', pp.precio) SEPARATOR '|') AS precios_lista
                        FROM productos p
                        LEFT JOIN producto_precios pp ON pp.id_producto = p.id
                        WHERE p.activo = 1
                        GROUP BY p.id, p.nombre, p.cod_barras, p.precio_final, p.factor_conversion, p.id_stock, p.id_asociado
                        ORDER BY p.nombre ASC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("ControladorVentas::listar_productos_select", $e->getMessage());
            }
        }
        return $lista;
    }

    private function buscar_productos_venta(string $texto, string $modo, int $id_lista_precio, int $limite = 30): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo === null)
            return $lista;
        $texto = trim($texto);
        if ($texto === "")
            return $lista;
        $limite = max(1, min(50, $limite));
        try {
            ListaPrecio::asegurar_tablas();
            UnidadMedida::asegurar_tabla();
            $solo_codigo = $modo === "codigo";
            $digitos = preg_replace('/\D+/', '', $texto) ?? "";
            $where = ["p.activo = 1"];
            $params = [];
            if ($solo_codigo && $digitos !== "") {
                $where[] = "(p.cod_barras = ? OR TRIM(LEADING '0' FROM p.cod_barras) = TRIM(LEADING '0' FROM ?))";
                $params[] = $digitos;
                $params[] = $digitos;
            } else {
                $like = "%" . $texto . "%";
                $where[] = "(p.nombre LIKE ? OR p.cod_barras LIKE ?)";
                $params[] = $like;
                $params[] = $like;
            }
            $sql = "SELECT p.id, p.nombre, p.cod_barras, p.precio_final, p.factor_conversion, p.id_stock, p.id_asociado,
                           s.unidad AS stock_unidad, COALESCE(um.decimales, 3) AS unidad_decimales,
                           GROUP_CONCAT(CONCAT(pp.id_lista, ':', pp.precio) SEPARATOR '|') AS precios_lista
                    FROM productos p
                    LEFT JOIN stock s ON s.id = p.id_stock
                    LEFT JOIN unidades_medida um ON um.abreviatura = s.unidad
                    LEFT JOIN producto_precios pp ON pp.id_producto = p.id
                    WHERE " . implode(" AND ", $where) . "
                    GROUP BY p.id, p.nombre, p.cod_barras, p.precio_final, p.factor_conversion, p.id_stock, p.id_asociado, s.unidad, um.decimales
                    ORDER BY " . ($solo_codigo ? "CHAR_LENGTH(p.cod_barras) ASC, p.nombre ASC" : "p.nombre ASC") . "
                    LIMIT " . $limite;
            $st = $pdo->prepare($sql);
            $st->execute($params);
            foreach ($st->fetchAll() as $row) {
                $precio_info = ListaPrecio::precio_producto_cargado((int)$row["id"], $id_lista_precio);
                $lista[] = [
                    "id" => (int)$row["id"],
                    "nombre" => (string)$row["nombre"],
                    "cod_barras" => (string)($row["cod_barras"] ?? ""),
                    "precio" => $precio_info !== null ? (float)$precio_info["precio"] : 0.0,
                    "precio_texto" => $precio_info !== null && (float)$precio_info["precio"] > 0 ? precio_para_mostrar($precio_info["precio"]) : "SIN PRECIO",
                    "precios_lista" => (string)($row["precios_lista"] ?? ""),
                    "stock_unidad" => (string)($row["stock_unidad"] ?? "u"),
                    "unidad_decimales" => (int)($row["unidad_decimales"] ?? 3),
                ];
            }
        } catch (Throwable $e) {
            registrar_log("ControladorVentas::buscar_productos_venta", $e->getMessage());
        }
        return $lista;
    }

    private function render_carrito_rows(array $carrito): string {
        ob_start();
        if (count($carrito) > 0) {
            foreach ($carrito as $idx => $it):
                $sub = Venta::calcular_subtotal((float)($it["cantidad"] ?? 0), (float)($it["precio_unit"] ?? 0), (float)($it["descuento"] ?? 0));
                ?>
                <tr>
                  <td><?= htmlspecialchars($it["nombre"]) ?></td>
                  <td style="text-align:right;">
                    <input form="formActualizarItem<?= (int)$idx ?>" type="number" step="0.001" min="0.001" class="form-control form-control-sm text-end" name="cantidad" value="<?= htmlspecialchars(numero_para_input($it["cantidad"] ?? 1, 3)) ?>">
                  </td>
                  <td style="text-align:right;">
                    <input form="formActualizarItem<?= (int)$idx ?>" type="number" step="0.01" min="0" class="form-control form-control-sm text-end" name="precio_unit" value="<?= htmlspecialchars(numero_para_input($it["precio_unit"] ?? 0, 2)) ?>">
                  </td>
                  <td style="text-align:right;">
                    <input form="formActualizarItem<?= (int)$idx ?>" type="number" step="0.01" min="0" max="100" class="form-control form-control-sm text-end" name="descuento" value="<?= htmlspecialchars(numero_para_input($it["descuento"] ?? 0, 2)) ?>">
                  </td>
                  <td style="text-align:right;"><?= htmlspecialchars(moneda_para_mostrar($sub)) ?></td>
                  <td style="text-align:right;">
                    <div class="sales-line-actions">
                      <form id="formActualizarItem<?= (int)$idx ?>" method="POST" action="index.php?c=ventas&a=actualizar_item" class="m-0">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="idx" value="<?= (int)$idx ?>">
                        <button class="btn btn-sm btn-outline-primary">Guardar</button>
                      </form>
                      <a class="btn btn-sm btn-outline-secondary" href="index.php?c=ventas&a=editar_item&idx=<?= (int)$idx ?>">Editar</a>
                      <a class="btn btn-sm btn-outline-danger" href="index.php?c=ventas&a=quitar&idx=<?= (int)$idx ?>" onclick="return confirm('&iquest;Quitar item?');">Quitar</a>
                    </div>
                  </td>
                </tr>
                <?php
            endforeach;
        } else {
            ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Todav&iacute;a no hay productos cargados.</td></tr>
            <?php
        }
        return ob_get_clean();
    }

    private function calcular_total_carrito(array $carrito): float {
        $total = 0.0;
        foreach ($carrito as $it) {
            $cantidad = (float)($it["cantidad"] ?? 0);
            $precio_unit = (float)($it["precio_unit"] ?? 0);
            $descuento = (float)($it["descuento"] ?? 0);
            $sub = Venta::calcular_subtotal($cantidad, $precio_unit, $descuento);
            $total += $sub;
        }
        return $total;
    }

    private function buscar_producto_por_codigo_o_plu(string $codigo): ?array {
        $codigo = preg_replace('/\D+/', '', $codigo) ?? "";
        $codigo = trim($codigo);
        if ($codigo === "")
            return null;
        $pdo = obtener_pdo();
        if ($pdo === null)
            return null;
        try {
            $sql = "SELECT id, nombre, cod_barras, precio_final, factor_conversion, id_stock, id_asociado, activo
                    FROM productos
                    WHERE activo = 1 AND (cod_barras = ? OR TRIM(LEADING '0' FROM cod_barras) = TRIM(LEADING '0' FROM ?))
                    ORDER BY CHAR_LENGTH(cod_barras) ASC
                    LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->execute([$codigo, $codigo]);
            $row = $st->fetch();
            return $row ?: null;
        } catch (Throwable $e) {
            registrar_log("ControladorVentas::buscar_producto_por_codigo_o_plu", $e->getMessage());
        }
        return null;
    }

    private function config_balanza(): array {
        $config = ConfiguracionSistema::obtener();
        $prefijos_cantidad = array_values(array_filter(array_map("trim", explode(",", (string)($config["balanza_prefijos_cantidad"] ?? "20,21,23,25,27,29")))));
        $prefijos_importe = array_values(array_filter(array_map("trim", explode(",", (string)($config["balanza_prefijos_importe"] ?? "22,24,26,28")))));
        return [
            "modo" => in_array((string)($config["balanza_modo"] ?? "auto"), ["auto", "cantidad", "importe"], true) ? (string)$config["balanza_modo"] : "auto",
            "plu_digitos" => max(1, min(8, (int)($config["balanza_plu_digitos"] ?? 5))),
            "valor_decimales" => max(0, min(4, (int)($config["balanza_valor_decimales"] ?? 3))),
            "importe_decimales" => max(0, min(4, (int)($config["balanza_importe_decimales"] ?? 2))),
            "prefijos_cantidad" => $prefijos_cantidad,
            "prefijos_importe" => $prefijos_importe,
        ];
    }

    private function interpretar_codigo_balanza(string $codigo, int $id_lista_precio): ?array {
        $codigo = preg_replace('/\D+/', '', $codigo) ?? "";
        if (strlen($codigo) < 8)
            return null;

        $config = $this->config_balanza();
        $cuerpo = strlen($codigo) >= 13 ? substr($codigo, 0, 12) : $codigo;
        $plu_digitos = (int)$config["plu_digitos"];
        $formatos = [
            [2, $plu_digitos, 12 - 2 - $plu_digitos],
            [1, $plu_digitos, 12 - 1 - $plu_digitos],
            [2, 5, 5],
            [2, 4, 6],
            [2, 6, 4],
            [2, 3, 7],
            [1, 5, 6],
        ];
        $prefijos_importe = $config["prefijos_importe"];
        $prefijos_cantidad = $config["prefijos_cantidad"];
        $mejor = null;

        foreach ($formatos as $formato) {
            [$largo_prefijo, $largo_plu, $largo_valor] = $formato;
            if ($largo_valor <= 0 || strlen($cuerpo) < $largo_prefijo + $largo_plu + $largo_valor)
                continue;
            $prefijo = substr($cuerpo, 0, $largo_prefijo);
            $plu = substr($cuerpo, $largo_prefijo, $largo_plu);
            $valor = substr($cuerpo, $largo_prefijo + $largo_plu, $largo_valor);
            $producto = $this->buscar_producto_por_codigo_o_plu($plu);
            if ($producto === null)
                continue;

            $precio_lista_info = ListaPrecio::precio_producto_cargado((int)$producto["id"], $id_lista_precio);
            $precio_unit = $precio_lista_info !== null ? (float)$precio_lista_info["precio"] : (float)($producto["precio_final"] ?? 0);
            $raw = (int)$valor;
            if ($raw <= 0)
                continue;

            $candidatos = [];
            $cantidad = $raw / (10 ** (int)$config["valor_decimales"]);
            if ($cantidad > 0)
                $candidatos[] = ["modo" => "cantidad", "cantidad" => $cantidad, "precio_unit" => $precio_unit];
            if ($precio_unit > 0) {
                $importe = $raw / (10 ** (int)$config["importe_decimales"]);
                $cantidad_importe = $importe / $precio_unit;
                if ($cantidad_importe > 0)
                    $candidatos[] = ["modo" => "importe", "cantidad" => $cantidad_importe, "precio_unit" => $precio_unit];
            }

            foreach ($candidatos as $candidato) {
                $score = 10;
                $prefijo2 = substr($prefijo, 0, 2);
                if ($config["modo"] === $candidato["modo"])
                    $score += 100;
                else if ($config["modo"] !== "auto")
                    continue;
                if ($candidato["modo"] === "importe" && in_array($prefijo2, $prefijos_importe, true))
                    $score += 50;
                if ($candidato["modo"] === "cantidad" && in_array($prefijo2, $prefijos_cantidad, true))
                    $score += 50;
                if ($candidato["modo"] === "cantidad" && $mejor === null)
                    $score += 5;
                if ($candidato["cantidad"] > 0 && $candidato["cantidad"] <= 9999)
                    $score += 5;
                if ($mejor === null || $score > $mejor["score"]) {
                    $mejor = [
                        "score" => $score,
                        "producto" => $producto,
                        "cantidad" => (float)$candidato["cantidad"],
                        "precio_unit" => (float)$candidato["precio_unit"],
                        "modo" => $candidato["modo"],
                    ];
                }
            }
        }

        return $mejor;
    }

    public function lista(): void {
        if ($this->permiso()) {
            $fecha_desde = trim((string)obtener_get("fecha_desde", ""));
            $fecha_hasta = trim((string)obtener_get("fecha_hasta", ""));
            $texto_buscar = trim((string)obtener_get("buscar", ""));
            $campo_buscar = trim((string)obtener_get("campo", "todos"));
            $metodo_buscar = trim((string)obtener_get("metodo", "contiene"));
            $orden_ventas = orden_parametros([
                "fecha" => "v.fecha",
                "cliente" => "c.nombre",
                "nombre" => "c.nombre",
                "precio" => "v.total",
                "total" => "v.total"
            ], "fecha", "DESC");
            $ventas = Venta::listar_ventas_periodo($fecha_desde, $fecha_hasta, $orden_ventas["sql"]);
            $campos_busqueda = [
                "id" => "ID",
                "fecha" => "Fecha",
                "cliente_nombre" => "Cliente",
                "usuario_nombre" => "Vendedor",
                "total" => "Total"
            ];
            $ventas = filtrar_registros_busqueda($ventas, $texto_buscar, $campo_buscar, $campos_busqueda, $metodo_buscar);
            $ids_venta_filtradas = array_map(fn($venta) => (int)($venta["id"] ?? 0), $ventas);
            $resumen_periodo = [
                "cantidad_ventas" => count($ventas),
                "total_vendido" => 0.0,
                "ganancia" => Venta::obtener_ganancia_por_ids($ids_venta_filtradas)
            ];
            foreach ($ventas as $venta)
                $resumen_periodo["total_vendido"] += (float)($venta["total"] ?? 0);
            $estados_fiscales = FacturaFiscal::estado_por_ventas($ids_venta_filtradas);
            $detalles_ventas = [];
            foreach ($ids_venta_filtradas as $id_venta) {
                if ($id_venta > 0)
                    $detalles_ventas[$id_venta] = Venta::obtener_detalle($id_venta);
            }
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/ventas/lista.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function inicio(): void {
        if ($this->permiso()) {
            iniciar_sesion();
            $rol = (string)($_SESSION["usuario_logueado"]["rol"] ?? "");
            $modulos = [
                [
                    "titulo" => "Ventas",
                    "texto" => "Ver historial, filtrar y revisar comprobantes.",
                    "icono" => "bi-receipt-cutoff",
                    "clase" => "modulo-ventas",
                    "url" => "index.php?c=ventas&a=lista"
                ],
                [
                    "titulo" => "Nueva venta",
                    "texto" => "Cargar una venta rápida con cliente y productos.",
                    "icono" => "bi-cart-plus-fill",
                    "clase" => "modulo-nueva",
                    "url" => "index.php?c=ventas&a=nueva"
                ],
                [
                    "titulo" => "Clientes",
                    "texto" => "Buscar, crear y editar clientes.",
                    "icono" => "bi-people-fill",
                    "clase" => "modulo-clientes",
                    "url" => "index.php?c=clientes&a=index"
                ],
                [
                    "titulo" => "Stock",
                    "texto" => "Controlar cantidades, costos y movimientos base.",
                    "icono" => "bi-box-seam-fill",
                    "clase" => "modulo-stock",
                    "url" => "index.php?c=stock&a=index"
                ],
                [
                    "titulo" => "Productos",
                    "texto" => "Administrar productos y su relación con stock.",
                    "icono" => "bi-bag-fill",
                    "clase" => "modulo-productos",
                    "url" => "index.php?c=productos&a=index"
                ],
                [
                    "titulo" => "Exportaciones",
                    "texto" => "Descargar stock, listas, pedidos y estadisticas.",
                    "icono" => "bi-graph-up-arrow",
                    "clase" => "modulo-exportaciones",
                    "url" => "index.php?c=exportaciones&a=inicio" // Verifica que 'a' coincida con tu método
                ]
            ];
            if ($rol === "ADMIN") {
                $modulos[] = [
                    "titulo" => "Usuarios",
                    "texto" => "Administrar accesos, roles y estado.",
                    "icono" => "bi-person-gear",
                    "clase" => "modulo-usuarios",
                    "url" => "index.php?c=usuarios&a=index"
                ];
                $modulos[] = [
                    "titulo" => "Backup",
                    "texto" => "Copias completas a pendrive, carpeta, Drive o Backblaze.",
                    "icono" => "bi-database-fill-check",
                    "clase" => "modulo-backup",
                    "url" => "index.php?c=configuraciones&a=backup"
                ];
            }
            $config_sistema = ConfiguracionSistema::obtener();
            if ((string)($config_sistema["mostrar_reparaciones"] ?? "1") === "1") {
                $url_reparaciones = trim((string)($config_sistema["url_reparaciones"] ?? ""));
                $url_reparaciones = normalizar_url_reparaciones($url_reparaciones);
                $modulos[] = [
                    "titulo" => "Reparaciones",
                    "texto" => "Abrir el sistema Python desde Ventas.",
                    "icono" => "bi-tools",
                    "clase" => "modulo-reparaciones",
                    "url" => $url_reparaciones
                ];
            }
            $body_class = "bg-light page-home";
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/ventas/inicio.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function guardar_menu(): void {
        if ($this->permiso()) {
            $volver = (string)obtener_post("volver", "index.php?c=ventas&a=inicio");
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                flash_error("Acceso inválido.");
                redirigir($volver);
            }
            $csrf = obtener_post("csrf", "");
            if (!csrf_valido($csrf)) {
                flash_error("Token inválido. Recargá la página.");
                redirigir($volver);
            }
            iniciar_sesion();
            $id_usuario = (int)($_SESSION["usuario_logueado"]["id"] ?? 0);
            $rol = (string)($_SESSION["usuario_logueado"]["rol"] ?? "");
            $seleccion = $_POST["modulos_menu"] ?? [];
            if (!is_array($seleccion))
                $seleccion = [];
            $ok = menu_guardar_preferencias_usuario($id_usuario, $rol, $seleccion);
            if ($ok)
                flash_ok("Barra superior actualizada.");
            else
                flash_error("No se pudo guardar la barra superior.");
            redirigir($volver);
        }
    }

    public function nueva(): void {
        if ($this->permiso()) {
            $this->asegurar_indices_rendimiento();
            $clientes = $this->listar_clientes_select();
            $productos = [];
            $listas_precios = ListaPrecio::listar(true);
            $carrito = $this->obtener_carrito();
            $total = $this->calcular_total_carrito($carrito);
            $form_venta = $this->obtener_form_venta();
            $saldos_favor_clientes = CuentaCorriente::saldos_favor_clientes();
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/ventas/nueva.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function buscar_productos_json(): void {
        if (!$this->permiso())
            return;
        $texto = trim((string)obtener_get("q", ""));
        $modo = trim((string)obtener_get("modo", "general"));
        $id_lista_precio = (int)obtener_get("id_lista_precio", ListaPrecio::id_predeterminada());
        if ($id_lista_precio <= 0)
            $id_lista_precio = ListaPrecio::id_predeterminada();
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "ok" => true,
            "productos" => $this->buscar_productos_venta($texto, $modo, $id_lista_precio, 30),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function aparte(): void {
        if ($this->permiso()) {
            redirigir("index.php?c=ventas&a=panel");
        }
    }

    public function panel(): void {
        if ($this->permiso()) {
            iniciar_sesion();
            $rol = (string)($_SESSION["usuario_logueado"]["rol"] ?? "");
            $modulos = [
                [
                    "titulo" => "Ventas",
                    "texto" => "Ver historial, filtrar y revisar comprobantes.",
                    "icono" => "bi-receipt-cutoff",
                    "clase" => "modulo-ventas",
                    "url" => "index.php?c=ventas&a=lista"
                ],
                [
                    "titulo" => "Nueva venta",
                    "texto" => "Cargar una venta rápida con cliente y productos.",
                    "icono" => "bi-cart-plus-fill",
                    "clase" => "modulo-nueva",
                    "url" => "index.php?c=ventas&a=nueva"
                ],
                [
                    "titulo" => "Clientes",
                    "texto" => "Buscar, crear y editar clientes.",
                    "icono" => "bi-people-fill",
                    "clase" => "modulo-clientes",
                    "url" => "index.php?c=clientes&a=index"
                ],
                [
                    "titulo" => "Stock",
                    "texto" => "Controlar cantidades, costos y movimientos base.",
                    "icono" => "bi-box-seam-fill",
                    "clase" => "modulo-stock",
                    "url" => "index.php?c=stock&a=index"
                ],
                [
                    "titulo" => "Productos",
                    "texto" => "Administrar productos y su relación con stock.",
                    "icono" => "bi-bag-fill",
                    "clase" => "modulo-productos",
                    "url" => "index.php?c=productos&a=index"
                ],
                [
                    "titulo" => "Exportaciones",
                    "texto" => "Descargar stock, listas, pedidos y estadisticas.",
                    "icono" => "bi-graph-up-arrow",
                    "clase" => "modulo-exportaciones",
                    "url" => "index.php?c=exportaciones&a=index"
                ]
            ];
            if ($rol === "ADMIN") {
                $modulos[] = [
                    "titulo" => "Usuarios",
                    "texto" => "Administrar accesos, roles y estado.",
                    "icono" => "bi-person-gear",
                    "clase" => "modulo-usuarios",
                    "url" => "index.php?c=usuarios&a=index"
                ];
            }
            $body_class = "bg-light page-ventas-panel";
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            ?>
            <div class="module-shell">
              <div class="module-head">
                <div>
                  <h3 class="mb-1">Ventas</h3>
                  <div class="text-muted small">Panel principal de acceso rápido a los módulos de ventas.</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <a class="btn btn-outline-secondary" href="index.php?c=reparaciones&a=index">Ir a Reparaciones</a>
                </div>
              </div>
              <div class="desktop-grid">
                <?php foreach ($modulos as $modulo): ?>
                  <a class="desktop-tile <?= htmlspecialchars($modulo["clase"]) ?>" href="<?= htmlspecialchars($modulo["url"]) ?>">
                    <div class="desktop-icon">
                      <i class="bi <?= htmlspecialchars($modulo["icono"]) ?>"></i>
                    </div>
                    <div class="desktop-title"><?= htmlspecialchars($modulo["titulo"]) ?></div>
                    <div class="desktop-text"><?= htmlspecialchars($modulo["texto"]) ?></div>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
            <?php
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function agregar(): void {
        if ($this->permiso()) {
            $error = "";
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $csrf = obtener_post("csrf", "");
                if (!csrf_valido($csrf))
                    $error = "Token inválido. Recargá la página.";
                else {
                    $datos_form = [
                        "id_cliente" => (int)obtener_post("id_cliente", 1),
                        "buscar_cliente" => trim((string)obtener_post("buscar_cliente", "")),
                        "id_producto" => (string)obtener_post("id_producto", ""),
                        "cantidad" => (float)obtener_post("cantidad", 1),
                        "descuento" => (float)obtener_post("descuento", 0),
                        "precio_unit" => trim((string)obtener_post("precio_unit", "")),
                        "tipo_comprobante" => (int)obtener_post("tipo_comprobante", 98),
                        "buscar_producto" => trim((string)obtener_post("buscar_producto", "")),
                        "id_lista_precio" => (int)obtener_post("id_lista_precio", ListaPrecio::id_predeterminada())
                    ];
                    $id_producto = (int)obtener_post("id_producto", 0);
                    $cantidad = (float)obtener_post("cantidad", 0);
                    $descuento = (float)obtener_post("descuento", 0);
                    $precio_manual_raw = trim((string)obtener_post("precio_unit", ""));
                    $precio_manual = parsear_numero_form($precio_manual_raw, 0);
                    $aplicar_lista_existente = (int)obtener_post("aplicar_lista_existente", 0) === 1;
                    $buscar_producto_codigo = $datos_form["buscar_producto"];
                    if (preg_match('/^(\d+(?:[.,]\d+)?)\s*\*\s*(.+)$/', $buscar_producto_codigo, $m)) {
                        $cantidad_codigo = parsear_numero_form((string)$m[1], 1);
                        if ($cantidad_codigo > 0)
                            $cantidad = $cantidad_codigo;
                        $buscar_producto_codigo = trim((string)$m[2]);
                    }
                    $codigo_balanza = $this->interpretar_codigo_balanza($buscar_producto_codigo, (int)$datos_form["id_lista_precio"]);
                    if ($id_producto <= 0 && $codigo_balanza !== null) {
                        $id_producto = (int)$codigo_balanza["producto"]["id"];
                        $cantidad = (float)$codigo_balanza["cantidad"];
                        if ($precio_manual_raw === "" && (float)$codigo_balanza["precio_unit"] > 0) {
                            $precio_manual = (float)$codigo_balanza["precio_unit"];
                            $precio_manual_raw = (string)$codigo_balanza["precio_unit"];
                        }
                    }
                    if ($id_producto <= 0 && $buscar_producto_codigo !== "") {
                        $producto_codigo = $this->buscar_producto_por_codigo_o_plu($buscar_producto_codigo);
                        if ($producto_codigo !== null)
                            $id_producto = (int)$producto_codigo["id"];
                    }
                    if ($id_producto <= 0 || $cantidad <= 0)
                        $error = "Producto o cantidad inválidos.";
                    else {
                        if ($descuento < 0)
                            $descuento = 0;
                        if ($descuento > 100)
                            $descuento = 100;
                        $prod = Venta::obtener_producto_para_venta($id_producto);
                        if ($prod === null || (int)$prod["activo"] !== 1)
                            $error = "Producto no disponible.";
                        else {
                            $carrito = $this->obtener_carrito();
                            $precio_lista_info = ListaPrecio::precio_producto_cargado($id_producto, (int)$datos_form["id_lista_precio"]);
                            $precio_lista = $precio_lista_info !== null ? (float)$precio_lista_info["precio"] : null;
                            $usa_precio_manual = ($precio_manual_raw !== "" && $precio_manual >= 0);
                            if (!$usa_precio_manual && ($precio_lista === null || $precio_lista <= 0))
                                $error = "El producto no tiene precio cargado en la lista seleccionada.";
                            $precio_unit = $usa_precio_manual ? $precio_manual : (float)$precio_lista;
                            $factor = (float)$prod["factor_conversion"];
                            if ($factor < 0) { $factor = 0; }
                            $id_stock_consumo = Venta::obtener_id_stock_consumo($prod);
                            if ($this->controlar_stock_ventas() && $id_stock_consumo !== null) {
                                $stock = Venta::obtener_stock_por_id($id_stock_consumo);
                                if ($stock === null)
                                    $error = "Stock no encontrado para el producto.";
                                else {
                                    $consumo = Venta::calcular_consumo_stock($cantidad, $factor);
                                    foreach ($carrito as $item_cargado) {
                                        if ((int)($item_cargado["id_producto"] ?? 0) === $id_producto)
                                            $consumo += Venta::calcular_consumo_stock((float)($item_cargado["cantidad"] ?? 0), $factor);
                                    }
                                    $disp = (float)$stock["cantidad"];
                                    if ($consumo > $disp + 0.0000001)
                                        $error = "Stock insuficiente. Disponible: " . $disp;
                                }
                            }
                            if ($error === "") {
                                if ($aplicar_lista_existente) {
                                    foreach ($carrito as &$it) {
                                        $precio_lista_item_info = ListaPrecio::precio_producto_cargado((int)$it["id_producto"], (int)$datos_form["id_lista_precio"]);
                                        $precio_lista_item = $precio_lista_item_info !== null ? (float)$precio_lista_item_info["precio"] : null;
                                        if ($precio_lista_item !== null && $precio_lista_item > 0) {
                                            $it["precio_unit"] = $precio_lista_item;
                                        }
                                    }
                                    unset($it);
                                }
                                $carrito[] = ["id_producto" => $id_producto, "nombre" => (string)$prod["nombre"], "cantidad" => $cantidad, "precio_unit" => $precio_unit, "descuento" => $descuento];
                                $this->guardar_carrito($carrito);
                                $this->guardar_form_venta([
                                    "id_cliente" => $datos_form["id_cliente"],
                                    "buscar_cliente" => $datos_form["buscar_cliente"],
                                    "id_producto" => "",
                                    "cantidad" => 1,
                                    "descuento" => 0,
                                    "precio_unit" => "",
                                    "tipo_comprobante" => $datos_form["tipo_comprobante"],
                                    "id_lista_precio" => $datos_form["id_lista_precio"],
                                    "buscar_producto" => ""
                                ]);
                                $total = $this->calcular_total_carrito($carrito);
                                $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                                if ($isAjax) {
                                    header('Content-Type: application/json; charset=utf-8');
                                    echo json_encode([
                                        "success" => true,
                                        "carrito_html" => $this->render_carrito_rows($carrito),
                                        "total" => moneda_para_mostrar($total),
                                        "items" => count($carrito)
                                    ]);
                                    return;
                                }
                                flash_ok("Producto agregado al carrito.");
                                redirigir("index.php?c=ventas&a=nueva");
                            }
                        }
                    }
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(["success" => false, "error" => $error]);
                    return;
                }
                flash_error($error);
                $this->guardar_form_venta($datos_form ?? []);
                redirigir("index.php?c=ventas&a=nueva");
            }
        }
    }

    public function aplicar_lista(): void {
        if ($this->permiso()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=ventas&a=nueva");
            }
            $id_lista_precio = (int)obtener_post("id_lista_precio", ListaPrecio::id_predeterminada());
            $carrito = $this->obtener_carrito();
            if (count($carrito) === 0) {
                flash_error("No hay productos cargados para aplicar la lista.");
            } else {
                foreach ($carrito as &$it) {
                    $id_producto = (int)($it["id_producto"] ?? 0);
                    $precio_lista_item_info = ListaPrecio::precio_producto_cargado($id_producto, $id_lista_precio);
                    $precio_lista_item = $precio_lista_item_info !== null ? (float)$precio_lista_item_info["precio"] : null;
                    if ($precio_lista_item !== null && $precio_lista_item > 0) {
                        $it["precio_unit"] = $precio_lista_item;
                    }
                }
                unset($it);
                $this->guardar_carrito($carrito);
                flash_ok("Lista de precios aplicada a los productos cargados.");
            }
            $this->guardar_form_venta([
                "id_cliente" => (int)obtener_post("id_cliente", 1),
                "buscar_cliente" => trim((string)obtener_post("buscar_cliente", "")),
                "id_producto" => "",
                "cantidad" => 1,
                "descuento" => 0,
                "precio_unit" => "",
                "tipo_comprobante" => (int)obtener_post("tipo_comprobante", 98),
                "id_lista_precio" => $id_lista_precio,
                "buscar_producto" => ""
            ]);
            redirigir("index.php?c=ventas&a=nueva");
        }
    }

    public function quitar(): void {
        if ($this->permiso()) {
            $idx = (int)obtener_get("idx", -1);
            $id_producto = (int)obtener_get("id_producto", 0);
            $carrito = $this->obtener_carrito();
            $nuevo = [];
            foreach ($carrito as $i => $it) {
                if (($idx >= 0 && $i !== $idx) || ($idx < 0 && (int)$it["id_producto"] !== $id_producto))
                    $nuevo[] = $it;
            }
            $this->guardar_carrito($nuevo);
            flash_ok("Item quitado del carrito.");
            redirigir("index.php?c=ventas&a=nueva");
        }
    }

    public function actualizar_item(): void {
        if ($this->permiso()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=ventas&a=nueva");
            }
            $idx = (int)obtener_post("idx", -1);
            $carrito = $this->obtener_carrito();
            if ($idx < 0 || !isset($carrito[$idx])) {
                flash_error("Item invalido.");
                redirigir("index.php?c=ventas&a=nueva");
            }

            $cantidad = parsear_numero_form(obtener_post("cantidad", 0), 0);
            $precio_unit = parsear_numero_form(obtener_post("precio_unit", 0), 0);
            $descuento = parsear_numero_form(obtener_post("descuento", 0), 0);
            if ($cantidad <= 0) {
                flash_error("La cantidad debe ser mayor a cero.");
                redirigir("index.php?c=ventas&a=nueva");
            }
            if ($precio_unit < 0)
                $precio_unit = 0;
            if ($descuento < 0)
                $descuento = 0;
            if ($descuento > 100)
                $descuento = 100;

            $item = $carrito[$idx];
            $id_producto = (int)($item["id_producto"] ?? 0);
            $prod = Venta::obtener_producto_para_venta($id_producto);
            if ($prod === null || (int)$prod["activo"] !== 1) {
                flash_error("Producto no disponible.");
                redirigir("index.php?c=ventas&a=nueva");
            }
            $factor = (float)($prod["factor_conversion"] ?? 0);
            if ($factor < 0)
                $factor = 0;
            $id_stock_consumo = Venta::obtener_id_stock_consumo($prod);
            if ($this->controlar_stock_ventas() && $id_stock_consumo !== null) {
                $stock = Venta::obtener_stock_por_id($id_stock_consumo);
                if ($stock === null) {
                    flash_error("Stock no encontrado para el producto.");
                    redirigir("index.php?c=ventas&a=nueva");
                }
                $consumo = Venta::calcular_consumo_stock($cantidad, $factor);
                foreach ($carrito as $i => $item_cargado) {
                    if ($i !== $idx && (int)($item_cargado["id_producto"] ?? 0) === $id_producto)
                        $consumo += Venta::calcular_consumo_stock((float)($item_cargado["cantidad"] ?? 0), $factor);
                }
                $disp = (float)$stock["cantidad"];
                if ($consumo > $disp + 0.0000001) {
                    flash_error("Stock insuficiente. Disponible: " . $disp);
                    redirigir("index.php?c=ventas&a=nueva");
                }
            }

            $carrito[$idx]["cantidad"] = $cantidad;
            $carrito[$idx]["precio_unit"] = $precio_unit;
            $carrito[$idx]["descuento"] = $descuento;
            $this->guardar_carrito($carrito);
            flash_ok("Item actualizado.");
            redirigir("index.php?c=ventas&a=nueva");
        }
    }

    public function editar_item(): void {
        if ($this->permiso()) {
            $idx = (int)obtener_get("idx", -1);
            $carrito = $this->obtener_carrito();
            if ($idx < 0 || !isset($carrito[$idx])) {
                flash_error("Item invalido.");
                redirigir("index.php?c=ventas&a=nueva");
            }

            $item = $carrito[$idx];
            unset($carrito[$idx]);
            $carrito = array_values($carrito);
            $this->guardar_carrito($carrito);

            $form = $this->obtener_form_venta();
            $form["id_producto"] = (string)($item["id_producto"] ?? "");
            $form["cantidad"] = (float)($item["cantidad"] ?? 1);
            $form["descuento"] = (float)($item["descuento"] ?? 0);
            $form["precio_unit"] = numero_para_input($item["precio_unit"] ?? 0, 2);
            $form["buscar_producto"] = (string)($item["nombre"] ?? "");
            $this->guardar_form_venta($form);

            flash_ok("Item listo para modificar. Ajusta los campos y agregalo de nuevo.");
            redirigir("index.php?c=ventas&a=nueva");
        }
    }

    public function vaciar(): void {
        if ($this->permiso()) {
            $this->vaciar_carrito_interno();
            flash_ok("Carrito vaciado.");
            redirigir("index.php?c=ventas&a=nueva");
        }
    }

    public function confirmar(): void {
        if ($this->permiso()) {
            $error = "";
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $csrf = obtener_post("csrf", "");
                if (!csrf_valido($csrf))
                    $error = "Token inválido. Recargá la página.";
                else {
                    iniciar_sesion();
                    $id_usuario = (int)($_SESSION["usuario_logueado"]["id"] ?? 0);
                    $id_cliente = (int)obtener_post("id_cliente", 1);
                    $buscar_cliente = trim((string)obtener_post("buscar_cliente", ""));
                    $tipo_comprobante = (int)obtener_post("tipo_comprobante", 98);
                    $forma_pago = strtolower(trim((string)obtener_post("forma_pago", "contado")));
                    $imprimir_ticket = (int)obtener_post("imprimir_ticket", 0) === 1;
                    $cc_cuotas = max(1, (int)obtener_post("cc_cuotas", 1));
                    $cc_vencimientos = $_POST["cc_vencimientos"] ?? [];
                    if (!is_array($cc_vencimientos))
                        $cc_vencimientos = [];
                    $tipos_disponibles = FacturaFiscal::tipos_comprobante();
                    if (!isset($tipos_disponibles[$tipo_comprobante]))
                        $tipo_comprobante = 98;
                    $tipo_info = FacturaFiscal::tipo_comprobante($tipo_comprobante);
                    if ($id_cliente <= 0)
                        $id_cliente = 1;
                    $total_carrito = $this->calcular_total_carrito($this->obtener_carrito());
                    if ($error === "" && $forma_pago === "saldo_favor" && (string)$tipo_info["operacion"] !== "presupuesto") {
                        $saldo_favor = CuentaCorriente::saldo_favor_cliente($id_cliente);
                        if ($total_carrito <= 0)
                            $error = "El carrito esta vacio.";
                        else if ($saldo_favor + 0.00001 < $total_carrito)
                            $error = "El saldo a favor del cliente es " . moneda_para_mostrar($saldo_favor) . " y no alcanza para pagar " . moneda_para_mostrar($total_carrito) . ".";
                    }
                    if (in_array((string)$tipo_info["operacion"], ["nota_credito", "nota_debito", "nota_credito_exportacion", "nota_debito_exportacion"], true)) {
                        $error = "Las notas de credito/debito deben referenciar un comprobante autorizado. Falta cargar el modulo de comprobante asociado.";
                    } else if ((string)$tipo_info["operacion"] === "exportacion") {
                        $error = "Factura E requiere datos de exportacion (pais, CUIT pais, moneda, incoterms y datos aduaneros si corresponden). Falta cargar el modulo de exportacion.";
                    } else if ((string)$tipo_info["operacion"] !== "presupuesto") {
                        $cliente_factura = Cliente::buscar_por_id($id_cliente);
                        if ($cliente_factura !== null) {
                            $error = "Para Factura A seleccioná un cliente con datos fiscales.";
                            $error = FacturaFiscal::validar_cliente_para_comprobante($tipo_comprobante, $cliente_factura);
                        }
                    }
                    if ($error === "" && (string)$tipo_info["operacion"] === "presupuesto") {
                        $carrito = $this->obtener_carrito();
                        $r = Presupuesto::confirmar($id_cliente, $id_usuario, $carrito);
                        if ($r["ok"] === true) {
                            $id_presupuesto = (int)$r["id_presupuesto"];
                            $this->vaciar_carrito_interno();
                            $this->guardar_form_venta([]);
                            $ok_pdf = $this->generar_pdf_presupuesto($id_presupuesto);
                            if ($ok_pdf)
                                flash_ok("Presupuesto generado. No descuenta stock ni se envia a ARCA.");
                            else
                                flash_ok("Presupuesto generado. No se pudo generar PDF: ver logs.");
                            if ($imprimir_ticket) {
                                redirigir("index.php?c=ventas&a=presupuesto_ticket&auto_print=1&id=" . $id_presupuesto);
                                return;
                            }
                            redirigir("index.php?c=ventas&a=nueva");
                            return;
                        } else
                            $error = (string)$r["error"];
                    }
                    if ($error === "" && (string)$tipo_info["operacion"] !== "presupuesto") {
                        $carrito = $this->obtener_carrito();
                        $r = Venta::confirmar_venta($id_cliente, $id_usuario, $carrito);
                        if ($r["ok"] === true) {
                            $id_venta = (int)$r["id_venta"];
                            $this->vaciar_carrito_interno();
                            $this->guardar_form_venta([]);
                            $es_fiscal = (($tipo_info["fiscal"] ?? true) === true);
                            $ok_fiscal = true;
                            if ($es_fiscal)
                                $ok_fiscal = FacturaFiscal::crear_pendiente_para_venta($id_venta, (string)$tipo_info["operacion"], $tipo_comprobante);
                            if ($forma_pago === "cuenta_corriente") {
                                $total_cc = $this->calcular_total_carrito($carrito);
                                $concepto_cc = "Venta #" . $id_venta;
                                $primer_vto = trim((string)($cc_vencimientos[0] ?? date("Y-m-d")));
                                $ok_cc = CuentaCorriente::crear($id_cliente, $concepto_cc, $total_cc, $cc_cuotas, $primer_vto, $id_venta, $cc_vencimientos);
                                if (!$ok_cc)
                                    registrar_log("Cuenta corriente venta", "No se pudo crear cuenta para venta $id_venta");
                            } else if ($forma_pago === "saldo_favor") {
                                $total_saldo = $this->calcular_total_carrito($carrito);
                                $ok_saldo = CuentaCorriente::aplicar_saldo_favor($id_cliente, $id_venta, $total_saldo);
                                if (!$ok_saldo)
                                    registrar_log("Saldo a favor venta", "No se pudo aplicar saldo a favor para venta $id_venta");
                            }
                            $ok_pdf = $this->generar_pdf_comprobante($id_venta, $tipo_comprobante);
                            if ($ok_pdf && $ok_fiscal && $es_fiscal)
                                flash_ok("Venta confirmada. PDF generado y factura fiscal en cola.");
                            else if ($ok_pdf && !$es_fiscal)
                                flash_ok("Factura X generada. Descuenta stock y no se envia a AFIP.");
                            else if ($ok_pdf)
                                flash_ok("Venta confirmada. PDF generado. Revisar cola fiscal.");
                            else
                                flash_ok("Venta confirmada. Revisar PDF y cola fiscal en logs.");
                            if ($imprimir_ticket) {
                                redirigir("index.php?c=ventas&a=ticket&auto_print=1&id=" . $id_venta);
                                return;
                            }
                            redirigir("index.php?c=ventas&a=lista");
                            return;
                        } else
                            $error = (string)$r["error"];
                    }
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                $this->guardar_form_venta([
                    "id_cliente" => $id_cliente ?? 1,
                    "buscar_cliente" => $buscar_cliente ?? "",
                    "id_producto" => "",
                    "cantidad" => 1,
                    "descuento" => 0,
                    "tipo_comprobante" => $tipo_comprobante ?? 98,
                    "buscar_producto" => ""
                ]);
                redirigir("index.php?c=ventas&a=nueva");
            }
        }
    }

    public function ticket(): void {
        if ($this->permiso()) {
            $id_venta = (int)obtener_get("id", 0);
            if ($id_venta <= 0) {
                flash_error("Venta invalida.");
                redirigir("index.php?c=ventas&a=lista");
            }

            $datos = $this->obtener_datos_comprobante($id_venta);
            if ($datos === null) {
                flash_error("Venta invalida.");
                redirigir("index.php?c=ventas&a=lista");
            }

            // Regenera el PDF para mantener el archivo viejo actualizado,
            // pero muestra la versión HTML con botones de impresión.
            $this->generar_pdf_comprobante($id_venta, (int)($datos["venta"]["tipo_comprobante"] ?? 98));
            $auto_print = (int)obtener_get("auto_print", 0) === 1;
            echo $this->html_comprobante($datos["venta"], $datos["items"], false, $auto_print);
        }
    }

    public function pdf(): void {
        if ($this->permiso()) {
            $id_venta = (int)obtener_get("id", 0);
            if ($id_venta <= 0) {
                flash_error("Venta invalida.");
                redirigir("index.php?c=ventas&a=lista");
            }
            $datos = $this->obtener_datos_comprobante($id_venta);
            if ($datos === null) {
                flash_error("Venta invalida.");
                redirigir("index.php?c=ventas&a=lista");
            }
            $ok = $this->generar_pdf_comprobante($id_venta, (int)($datos["venta"]["tipo_comprobante"] ?? 98));
            $base = realpath(__DIR__ . "/../..");
            $archivo = $base !== false ? $base . "/almacenamiento/pdf/venta_" . $id_venta . ".pdf" : "";
            if (!$ok || $archivo === "" || !is_file($archivo)) {
                flash_error("No se pudo generar el PDF.");
                redirigir("index.php?c=ventas&a=ticket&id=" . $id_venta);
            }
            header("Content-Type: application/pdf");
            header("Content-Disposition: attachment; filename=venta_" . $id_venta . ".pdf");
            header("Content-Length: " . filesize($archivo));
            readfile($archivo);
            return;
        }
    }

    public function presupuesto_pdf(): void {
        if ($this->permiso()) {
            $id_presupuesto = (int)obtener_get("id", 0);
            if ($id_presupuesto <= 0) {
                flash_error("Presupuesto invalido.");
                redirigir("index.php?c=ventas&a=nueva");
            }
            $presupuesto = Presupuesto::buscar($id_presupuesto);
            if (!$presupuesto) {
                flash_error("Presupuesto invalido.");
                redirigir("index.php?c=ventas&a=nueva");
            }
            $ok = $this->generar_pdf_presupuesto($id_presupuesto);
            $base = realpath(__DIR__ . "/../..");
            $archivo = $base !== false ? $base . "/almacenamiento/pdf/presupuesto_" . $id_presupuesto . ".pdf" : "";
            if (!$ok || $archivo === "" || !is_file($archivo)) {
                flash_error("No se pudo generar el PDF.");
                redirigir("index.php?c=ventas&a=presupuesto_ticket&id=" . $id_presupuesto);
            }
            header("Content-Type: application/pdf");
            header("Content-Disposition: attachment; filename=presupuesto_" . $id_presupuesto . ".pdf");
            header("Content-Length: " . filesize($archivo));
            readfile($archivo);
            return;
        }
    }

    public function presupuesto_ticket(): void {
        if ($this->permiso()) {
            $id_presupuesto = (int)obtener_get("id", 0);
            if ($id_presupuesto <= 0) {
                flash_error("Presupuesto invalido.");
                redirigir("index.php?c=ventas&a=nueva");
            }
            $presupuesto = Presupuesto::buscar($id_presupuesto);
            if (!$presupuesto) {
                flash_error("Presupuesto invalido.");
                redirigir("index.php?c=ventas&a=nueva");
            }
            $items = Presupuesto::obtener_detalle($id_presupuesto);
            $auto_print = (int)obtener_get("auto_print", 0) === 1;
            echo $this->html_presupuesto($presupuesto, $items, false, $auto_print);
        }
    }

    public function impresoras_json(): void {
        if ($this->permiso()) {
            $impresoras = [];
            $salida = @shell_exec('powershell -NoProfile -Command "Get-Printer | Select-Object -ExpandProperty Name"');
            if (is_string($salida) && trim($salida) !== "") {
                foreach (preg_split('/\r?\n/', $salida) as $linea) {
                    $nombre = trim($linea);
                    if ($nombre !== "")
                        $impresoras[] = $nombre;
                }
            }
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(["ok" => true, "impresoras" => array_values(array_unique($impresoras))], JSON_UNESCAPED_UNICODE);
        }
    }

    private function obtener_datos_comprobante(int $id_venta, int $tipo_comprobante_manual = 98): ?array {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return null;

        Cliente::asegurar_columnas_fiscales($pdo);
        $sql = "SELECT v.id, v.fecha, v.total, v.id_cliente,
                       c.nombre AS cliente_nombre, c.dni AS cliente_documento, c.tipo_documento, c.condicion_iva, c.direccion AS cliente_direccion,
                       u.usuario AS usuario_nombre,
                       COALESCE(f.tipo_comprobante, ?) AS tipo_comprobante, f.punto_venta, f.numero_comprobante, f.cae, f.cae_vencimiento, f.estado AS fiscal_estado, f.respuesta_json
                FROM ventas v
                INNER JOIN clientes c ON c.id = v.id_cliente
                INNER JOIN usuarios u ON u.id = v.id_usuario
                LEFT JOIN fiscal_comprobantes f ON f.id_venta = v.id
                WHERE v.id = ? LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([$tipo_comprobante_manual, $id_venta]);
        $venta = $st->fetch();
        if (!$venta)
            return null;

        return [
            "venta" => $venta,
            "items" => Venta::obtener_detalle($id_venta),
        ];
    }

    private function generar_pdf_presupuesto(int $id_presupuesto): bool {
        $ok = false;
        try {
            $base = __DIR__ . "/../../";
            $autoload = $base . "vendor/autoload.php";
            if (file_exists($autoload)) {
                require_once $autoload;
                $presupuesto = Presupuesto::buscar($id_presupuesto);
                if ($presupuesto) {
                    $items = Presupuesto::obtener_detalle($id_presupuesto);
                    $html = $this->html_presupuesto($presupuesto, $items);
                    $dompdf = new \Dompdf\Dompdf();
                    $dompdf->loadHtml($html, "UTF-8");
                    $dompdf->setPaper([0, 0, 226.77, 900], "portrait");
                    $dompdf->render();
                    $carpeta = $base . "almacenamiento/pdf";
                    if (!is_dir($carpeta))
                        @mkdir($carpeta, 0777, true);
                    $archivo = $carpeta . "/presupuesto_" . $id_presupuesto . ".pdf";
                    $ok = (bool)@file_put_contents($archivo, $dompdf->output());
                }
            }
        } catch (Throwable $e) {
            $ok = false;
            registrar_log("PDF Presupuesto", $e->getMessage());
        }
        return $ok;
    }

    private function generar_pdf_comprobante(int $id_venta, int $tipo_comprobante_manual = 98): bool {
        $ok = false;
        try {
            $base = __DIR__ . "/../../";
            $autoload = $base . "vendor/autoload.php";
            if (file_exists($autoload)) {
                require_once $autoload;
                $datos = $this->obtener_datos_comprobante($id_venta, $tipo_comprobante_manual);
                if ($datos !== null) {
                    $venta = $datos["venta"];
                    $items = $datos["items"];
                    $html = $this->html_comprobante($venta, $items, true); // genera el HTML para PDF
                    $dompdf = new \Dompdf\Dompdf(); // crea el generador de pdf
                    $dompdf->loadHtml($html, "UTF-8"); // paso el html a generar el pdf
                    $formato_impresion = $this->formato_impresion_ticket();
                    if ($formato_impresion === "a4")
                        $dompdf->setPaper("A4", "portrait");
                    else if ($formato_impresion === "58")
                        $dompdf->setPaper([0, 0, 164.41, 900], "portrait"); // 58 mm
                    else
                        $dompdf->setPaper([0, 0, 226.77, 900], "portrait"); // 80 mm
                    $dompdf->render(); // convierte a pdf interno, en memoria
                    $carpeta = $base . "almacenamiento/pdf";
                    if (!is_dir($carpeta))
                        @mkdir($carpeta, 0777, true);
                    $archivo = $carpeta . "/venta_" . $id_venta . ".pdf";
                    $bytes = $dompdf->output(); // obtiene el contenido del pdf en binario
                    $ok = (bool)@file_put_contents($archivo, $bytes); // guarda en la dirección indicada
                }
            } else
                registrar_log("PDF", "No existe vendor/autoload.php. Instalá dompdf con Composer.");
        } catch (Throwable $e) {
            $ok = false;
            registrar_log("PDF", $e->getMessage());
        }
        return $ok;
    }

    private function formato_impresion_ticket(): string {
        $config = ConfiguracionSistema::obtener();
        $formato = (string)($config["formato_impresion_ticket"] ?? "80");
        return in_array($formato, ["a4", "80", "58"], true) ? $formato : "80";
    }

    private function medidas_impresion_ticket(): array {
        $formato = $this->formato_impresion_ticket();
        if ($formato === "a4") {
            return [
                "page_size" => "A4 portrait",
                "page_margin" => "8mm",
                "ticket_width" => "190mm",
                "ticket_padding" => "0",
                "body_width" => "auto",
                "actions_width" => "190mm",
            ];
        }
        $ancho = $formato === "58" ? "58mm" : "80mm";
        return [
            "page_size" => $ancho . " auto",
            "page_margin" => "0",
            "ticket_width" => $ancho,
            "ticket_padding" => "3mm 3mm 4mm",
            "body_width" => "auto",
            "actions_width" => $ancho,
        ];
    }

    private function ticket_logo_html(array $empresa): string {
        $html = "";
        $logo_rel = trim((string)($empresa["logo_ticket"] ?? ""));
        if ($logo_rel !== "") {
            $logo_path = realpath(__DIR__ . "/../../" . $logo_rel);
            $base_path = realpath(__DIR__ . "/../../");
            if ($logo_path !== false && $base_path !== false && str_starts_with($logo_path, $base_path) && is_file($logo_path)) {
                $formato = $this->formato_impresion_ticket();
                $modo_termico = (string)($empresa["ticket_logo_termico"] ?? "1") === "1";
                $ruta_logo = $modo_termico ? procesar_logo_ticket_termico_hd($logo_path, $formato === "58" ? 384 : 576, true) : ruta_relativa_proyecto($logo_path);
                $archivo_logo = resolver_ruta_proyecto($ruta_logo);
                $ext = strtolower(pathinfo($archivo_logo, PATHINFO_EXTENSION));
                $mime = ["jpg" => "image/jpeg", "jpeg" => "image/jpeg", "png" => "image/png", "gif" => "image/gif", "webp" => "image/webp"][$ext] ?? "image/png";
                $bytes_logo = is_file($archivo_logo) ? @file_get_contents($archivo_logo) : false;
                if (is_string($bytes_logo) && $bytes_logo !== "")
                    $html = "<div class='center logo-wrap'><img class='logo' src='data:$mime;base64," . base64_encode($bytes_logo) . "'></div>";
            }
        }
        return $html;
    }

    private function html_comprobante(array $venta, array $items, bool $para_pdf = true, bool $auto_print = false): string {
        $id = (int)$venta["id"];
        $fecha = htmlspecialchars((string)$venta["fecha"]);
        $cliente = htmlspecialchars((string)$venta["cliente_nombre"]);
        $cliente_doc = htmlspecialchars(trim((string)($venta["tipo_documento"] ?? "") . " " . (string)($venta["cliente_documento"] ?? "")));
        $usuario = htmlspecialchars((string)$venta["usuario_nombre"]);
        $total = htmlspecialchars(moneda_para_mostrar($venta["total"] ?? 0));
        $tipo_comprobante = (int)($venta["tipo_comprobante"] ?? 98);
        $tipo_info = FacturaFiscal::tipo_comprobante($tipo_comprobante);
        $letra = htmlspecialchars((string)$tipo_info["letra"]);
        $es_factura_x = ($tipo_comprobante === 98);
        $titulo = htmlspecialchars($es_factura_x ? "Ticket" : (string)$tipo_info["texto"]);
        $es_fiscal = (($tipo_info["fiscal"] ?? true) === true);
        $config = ConfiguracionSistema::obtener_configuracion_fiscal();
        $empresa = $config["empresa"] ?? [];
        $comp_def = $config["comprobante_defecto"] ?? [];
        
        $nombre_comercio = htmlspecialchars((string)($empresa["nombre_comercio"] ?? "Comercio"));
        $razon = htmlspecialchars((string)($empresa["razon_social"] ?? "Comercio"));
        $cuit = htmlspecialchars((string)($empresa["cuit"] ?? ""));
        $domicilio = htmlspecialchars((string)($empresa["domicilio"] ?? ""));
        $telefonos = htmlspecialchars((string)($empresa["telefonos"] ?? ""));
        $email = htmlspecialchars((string)($empresa["email"] ?? ""));
        $pie_ticket = nl2br(htmlspecialchars((string)($empresa["texto_pie_ticket"] ?? "")));
        $logo_html = $this->ticket_logo_html($empresa);
        $ticket_imagen_completa = (string)($empresa["ticket_imagen_completa"] ?? "0") === "1";
        $ticket_fuente_raw = in_array((string)($empresa["ticket_fuente"] ?? "Arial"), ["Arial", "Verdana", "Courier New", "Tahoma"], true) ? (string)$empresa["ticket_fuente"] : "Arial";
        $ticket_fuente = $ticket_fuente_raw === "Courier New" ? "'Courier New'" : htmlspecialchars($ticket_fuente_raw);
        $ticket_tamano = max(10, min(18, (int)($empresa["ticket_tamano_fuente"] ?? 12)));
        
        $pv = (int)($venta["punto_venta"] ?? ($empresa["punto_venta"] ?? 1));
        $numero = (int)($venta["numero_comprobante"] ?? 0);
        $cae = htmlspecialchars((string)($venta["cae"] ?? ""));
        $cae_vto = htmlspecialchars((string)($venta["cae_vencimiento"] ?? ""));
        
        if ($es_factura_x)
            $numero_txt = "";
        else if (!$es_fiscal)
            $numero_txt = "INTERNO-" . str_pad((string)$id, 8, "0", STR_PAD_LEFT);
        else
            $numero_txt = $numero > 0 ? str_pad((string)$pv, 5, "0", STR_PAD_LEFT) . "-" . str_pad((string)$numero, 8, "0", STR_PAD_LEFT) : "PENDIENTE";
        
        // Construir filas de items
        $filas_html = "";
        foreach ($items as $it) {
            $p = htmlspecialchars((string)$it["producto_nombre"]);
            $cant = htmlspecialchars((string)$it["cantidad"]);
            $pu = htmlspecialchars(numero_precio_para_exportar($it["precio_unit"] ?? 0));
            $desc_raw = (float)($it["descuento"] ?? 0);
            $desc_fmt = (abs($desc_raw - round($desc_raw)) < 0.00001)
                ? (string)((int)round($desc_raw))
                : rtrim(rtrim(number_format($desc_raw, 2, ".", ""), "0"), ".");
            $desc = htmlspecialchars($desc_fmt);
            $sub = htmlspecialchars(numero_para_mostrar($it["subtotal"] ?? 0));
            $filas_html .= "<div class='item-row'><div class='item-desc'><strong>$p</strong><br><span class='item-detail'>$cant x $pu</span></div><div class='item-price'>$sub</div></div>";
            if ($desc_raw > 0)
                $filas_html .= "<div class='item-desc small'>Desc: $desc%</div>";
        }
        
        $medidas = $this->medidas_impresion_ticket();
        $acciones_html = $para_pdf ? "" : "<div class='actions'><button type='button' onclick='window.print()'>Imprimir</button><button type='button' onclick='window.close()'>Cerrar</button></div>";
        $auto_print_html = (!$para_pdf && $auto_print) ? "<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 250); });</script>" : "";
        
        $empresa_extra_html = "";
        if (!$ticket_imagen_completa) {
            if ($razon !== "" && $razon !== $nombre_comercio) $empresa_extra_html .= "<div class='center small'>$razon</div>";
            if ($cuit && !$es_factura_x) $empresa_extra_html .= "<div class='center small'>CUIT: $cuit</div>";
            if ($domicilio) $empresa_extra_html .= "<div class='center small'>$domicilio</div>";
            if ($telefonos) $empresa_extra_html .= "<div class='center small'>Tel: $telefonos</div>";
            if ($email) $empresa_extra_html .= "<div class='center small'>$email</div>";
        }
        $marca_html = $ticket_imagen_completa ? $logo_html : $logo_html . "<div class='center brand'>" . strtoupper($nombre_comercio) . "</div>" . $empresa_extra_html;
        
        $ticket_status = $es_fiscal && $cae === "" ? "<div class='center warning'>CAE PENDIENTE</div>" : "";
        if (!$es_fiscal) $ticket_status = "<div class='center warning'>DOCUMENTO INTERNO</div>";
        if ($es_factura_x) $ticket_status = "";
        $numero_html = $es_factura_x || $ticket_imagen_completa ? "" : "<div class='row'><span>Nro.</span><strong>$numero_txt</strong></div>";
        $cliente_doc_html = ($es_factura_x || $cliente_doc === "" || $ticket_imagen_completa) ? "" : "<div class='row'><span>Doc.</span><strong>$cliente_doc</strong></div>";
        $cabecera_documento_html = $ticket_imagen_completa ? "" : "
    <div class='line'></div>
    <div class='center title'>$titulo</div>
    <div class='line'></div>
    $numero_html
    <div class='row'><span>Fecha</span><strong>$fecha</strong></div>
    <div class='row'><span>Cliente</span><strong>$cliente</strong></div>
    $cliente_doc_html
    <div class='row'><span>Vendedor</span><strong>$usuario</strong></div>";
        
        return "<!doctype html>
<html lang='es'>
<head>
  <meta charset='utf-8'>
  <title>$titulo" . ($numero_txt !== "" ? " - $numero_txt" : "") . "</title>
  <style>
    @page { size: " . $medidas["page_size"] . "; margin: " . $medidas["page_margin"] . "; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #fff; font-family: $ticket_fuente, Arial, sans-serif; font-size: {$ticket_tamano}px; color: #000; }
    .ticket { width: " . $medidas["ticket_width"] . "; max-width: 100%; padding: " . $medidas["ticket_padding"] . "; margin: 0 auto; }
    .center { text-align: center; }
    .logo-wrap { margin: 0 0 4px; padding: 0; background: #fff; line-height: 0; }
    .logo { display: block; width: auto; max-width: 100%; max-height: 90px; object-fit: contain; margin: 0 auto; background: #fff; }
    .brand { font-weight: 800; font-size: 13px; line-height: 1.05; margin-bottom: 2px; }
    .title { font-weight: 800; font-size: 12px; margin: 5px 0; }
    .small { font-size: 9px; line-height: 1.2; }
    .nowrap { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .line { border-top: 1px dashed #000; margin: 4px 0; }
    .row { display: flex; justify-content: space-between; gap: 4px; font-size: 10px; line-height: 1.3; }
    .row span { flex: 0 0 auto; font-weight: 600; }
    .row strong { flex: 1 1 auto; text-align: right; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .item-row { display: flex; justify-content: space-between; gap: 4px; font-size: 10px; line-height: 1.25; }
    .item-desc { flex: 1 1 auto; }
    .item-detail { font-size: 9px; color: #666; display: block; }
    .item-price { flex: 0 0 auto; text-align: right; font-weight: 600; white-space: nowrap; }
    .item-desc.small { font-size: 9px; color: #666; }
    .block-title { font-weight: 800; font-size: 10px; margin-top: 4px; }
    .block-text { font-size: 9px; white-space: normal; overflow-wrap: anywhere; line-height: 1.25; }
    .total-row { display: flex; justify-content: space-between; gap: 4px; font-size: 11px; font-weight: 800; margin: 4px 0; }
    .total-row strong { text-align: right; }
    .warning { border: 1px solid #000; padding: 2px; font-weight: 600; font-size: 9px; }
    .actions { width: " . $medidas["actions_width"] . "; max-width: 100%; padding: 6px; display: flex; gap: 6px; margin: 0 auto 6px; }
    button, .actions a { border: 0; border-radius: 6px; padding: 6px 8px; background: #0e7490; color: white; font-weight: 600; cursor: pointer; font-size: 9px; text-decoration: none; display: inline-flex; align-items: center; }
    @media print { .actions { display: none; } body { width: " . $medidas["body_width"] . "; } .ticket { margin: 0 auto; } }
  </style>
</head>
<body>
  $acciones_html
  <div class='ticket'>
    $marca_html
    $cabecera_documento_html
    <div class='line'></div>
    " . ($ticket_imagen_completa ? "" : "<div class='block-title'>Detalle</div>") . "
    $filas_html
    <div class='line'></div>
    <div class='total-row'><span>Total</span><strong>$total</strong></div>
    $ticket_status
    " . ($pie_ticket !== "" ? "<div class='line'></div><div class='center small'>$pie_ticket</div>" : "") . "
  </div>
  $auto_print_html
</body>
</html>";
    }

    private function html_presupuesto(array $presupuesto, array $items, bool $para_pdf = true, bool $auto_print = false): string {
        $id = (int)$presupuesto["id"];
        $fecha = htmlspecialchars((string)$presupuesto["fecha"]);
        $cliente = htmlspecialchars((string)$presupuesto["cliente_nombre"]);
        $usuario = htmlspecialchars((string)$presupuesto["usuario_nombre"]);
        $total = htmlspecialchars(moneda_para_mostrar($presupuesto["total"] ?? 0));
        $config = ConfiguracionSistema::obtener_configuracion_fiscal();
        $empresa = $config["empresa"] ?? [];
        $nombre_comercio = htmlspecialchars((string)($empresa["nombre_comercio"] ?? ""));
        $razon = htmlspecialchars((string)($empresa["razon_social"] ?? ""));
        $cuit = htmlspecialchars((string)($empresa["cuit"] ?? ""));
        $domicilio = htmlspecialchars((string)($empresa["domicilio"] ?? ""));
        $telefonos = htmlspecialchars((string)($empresa["telefonos"] ?? ""));
        $pie_ticket = nl2br(htmlspecialchars((string)($empresa["texto_pie_ticket"] ?? "")));
        $logo_html = $this->ticket_logo_html($empresa);
        $ticket_imagen_completa = (string)($empresa["ticket_imagen_completa"] ?? "0") === "1";
        $ticket_fuente_raw = in_array((string)($empresa["ticket_fuente"] ?? "Arial"), ["Arial", "Verdana", "Courier New", "Tahoma"], true) ? (string)$empresa["ticket_fuente"] : "Arial";
        $ticket_fuente = $ticket_fuente_raw === "Courier New" ? "'Courier New'" : htmlspecialchars($ticket_fuente_raw);
        $ticket_tamano = max(10, min(18, (int)($empresa["ticket_tamano_fuente"] ?? 12)));
        $marca_presupuesto = $ticket_imagen_completa
            ? $logo_html
            : $logo_html . "<div class='center brand'>$nombre_comercio</div>" . ($razon !== "" && $razon !== $nombre_comercio ? "<div class='center'>$razon</div>" : "") . "<div class='center'>CUIT $cuit</div>"
                . ($domicilio !== "" ? "<div class='center'>$domicilio</div>" : "")
                . ($telefonos !== "" ? "<div class='center'>Tel: $telefonos</div>" : "");
        $filas = "";
        foreach ($items as $it) {
            $p = htmlspecialchars((string)$it["producto_nombre"]);
            $cant = htmlspecialchars((string)$it["cantidad"]);
            $pu = htmlspecialchars(numero_precio_para_exportar($it["precio_unit"] ?? 0));
            $desc = htmlspecialchars((string)$it["descuento"]);
            $sub = htmlspecialchars(numero_para_mostrar($it["subtotal"] ?? 0));
            $filas .= "<tr><td>$p<br><span>$cant x $pu Desc $desc%</span></td><td class='num'>$sub</td></tr>";
        }
        $medidas = $this->medidas_impresion_ticket();
        $acciones_html = $para_pdf ? "" : "<div class='actions'><button type='button' onclick='window.print()'>Imprimir</button><button type='button' onclick='window.close()'>Cerrar</button></div>";
        $auto_print_html = (!$para_pdf && $auto_print) ? "<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 250); });</script>" : "";
        return "<!doctype html>
            <html lang='es'><head><meta charset='utf-8'><style>
            @page { size: " . $medidas["page_size"] . "; margin: " . $medidas["page_margin"] . "; }
            body { font-family: $ticket_fuente, DejaVu Sans, sans-serif; font-size: {$ticket_tamano}px; color: #111; }
            .actions { width: " . $medidas["actions_width"] . "; max-width: 100%; padding: 6px; display: flex; gap: 6px; margin: 0 auto 6px; }
            .ticket { width: " . $medidas["ticket_width"] . "; max-width: 100%; padding: " . $medidas["ticket_padding"] . "; margin: 0 auto; }
            button { border: 0; border-radius: 6px; padding: 6px 8px; background: #0e7490; color: white; font-weight: 600; cursor: pointer; font-size: 9px; }
            .center { text-align: center; }
            .logo-wrap { margin: 0 0 4px; padding: 0; background: #fff; line-height: 0; }
            .logo { display: block; width: auto; max-width: 100%; max-height: 90px; object-fit: contain; margin: 0 auto; background: #fff; }
            .brand { font-size: 12px; font-weight: bold; }
            .marca { width: 34px; height: 34px; border: 2px solid #111; margin: 4px auto; text-align: center; font-size: 25px; font-weight: bold; line-height: 34px; }
            .sep { border-top: 1px dashed #111; margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; }
            td { padding: 3px 0; border-bottom: 1px dotted #999; vertical-align: top; }
            td span { font-size: 8px; color: #333; }
            .num { text-align: right; white-space: nowrap; }
            .total { display: flex; justify-content: space-between; font-size: 12px; font-weight: bold; }
            .legal { border: 1px solid #111; padding: 4px; text-align: center; font-weight: bold; }
            @media print { .actions { display: none; } body { width: " . $medidas["body_width"] . "; } .ticket { margin: 0 auto; } }
            </style></head><body>
            $acciones_html
            <div class='ticket'>
            $marca_presupuesto
            <div class='marca'>X</div>
            <div class='legal'>DOCUMENTO NO VALIDO COMO FACTURA</div>
            <div class='center'><b>PRESUPUESTO #$id</b></div>
            <div class='sep'></div>
            <div><b>Fecha:</b> $fecha</div>
            <div><b>Cliente:</b> $cliente</div>
            <div><b>Vendedor:</b> $usuario</div>
            <div class='sep'></div>
            <table><tbody>$filas</tbody></table>
            <div class='sep'></div>
            <div class='total'><span>TOTAL</span><b>$total</b></div>
            " . ($pie_ticket !== "" ? "<div class='sep'></div><div class='center'>$pie_ticket</div>" : "") . "
            </div>
            $auto_print_html
            </body></html>";
    }

    private function leyenda_iva_receptor(string $condicion_iva, int $id_cliente): string {
        $cond = trim($condicion_iva);
        if ($id_cliente === 1 || $cond === "Consumidor Final" || $cond === "")
            return "A CONSUMIDOR FINAL";
        if ($cond === "Responsable Inscripto")
            return "IVA RESPONSABLE INSCRIPTO";
        if ($cond === "Exento")
            return "IVA EXENTO";
        if ($cond === "Monotributista")
            return "RESPONSABLE MONOTRIBUTO";
        if ($cond === "No Responsable")
            return "NO RESPONSABLE IVA";
        return strtoupper($cond);
    }
}
