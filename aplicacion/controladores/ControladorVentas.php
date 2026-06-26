<?php
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

    private function obtener_form_venta(): array {
        global $container;
        $obtenerListaPrecioPredeterminada = $container->get(\Ventas\ListasPrecios\Application\ObtenerListaPrecioPredeterminada::class);
        $id_lista_precio_predeterminada = $obtenerListaPrecioPredeterminada->ejecutar();
        $datos = [
            "id_cliente" => 1,
            "buscar_cliente" => "",
            "id_producto" => "",
            "cantidad" => 1,
            "descuento" => 0,
            "precio_unit" => "",
            "tipo_comprobante" => 98,
            "buscar_producto" => "",
            "id_lista_precio" => $id_lista_precio_predeterminada
        ];
        $flash = obtener_form_data("ventas_form");
        if ($flash !== [])
            $datos = array_merge($datos, $flash);
        return $datos;
    }

    private function guardar_form_venta(array $datos): void {
        global $container;
        $obtenerListaPrecioPredeterminada = $container->get(\Ventas\ListasPrecios\Application\ObtenerListaPrecioPredeterminada::class);
        $id_lista_precio_predeterminada = $obtenerListaPrecioPredeterminada->ejecutar();
        flash_form_data("ventas_form", [
            "id_cliente" => (int)($datos["id_cliente"] ?? 1),
            "buscar_cliente" => (string)($datos["buscar_cliente"] ?? ""),
            "id_producto" => (string)($datos["id_producto"] ?? ""),
            "cantidad" => $datos["cantidad"] ?? 1,
            "descuento" => $datos["descuento"] ?? 0,
            "precio_unit" => (string)($datos["precio_unit"] ?? ""),
            "tipo_comprobante" => (int)($datos["tipo_comprobante"] ?? 98),
            "buscar_producto" => (string)($datos["buscar_producto"] ?? ""),
            "id_lista_precio" => (int)($datos["id_lista_precio"] ?? $id_lista_precio_predeterminada)
        ]);
    }

    public function lista(): void {
        if ($this->permiso()) {
            global $container;
            $listarVentasPeriodo = $container->get(\Ventas\Aplicacion\Ventas\CasosUso\ListarVentasPeriodo::class);
            $obtenerResumenVentasPeriodo = $container->get(\Ventas\Aplicacion\Ventas\CasosUso\ObtenerResumenVentasPeriodo::class);
            $obtenerEstadosFiscalesVentas = $container->get(\Ventas\Aplicacion\Ventas\CasosUso\ObtenerEstadosFiscalesVentas::class);
            $obtenerDetallesVentas = $container->get(\Ventas\Aplicacion\Ventas\CasosUso\ObtenerDetallesVentas::class);
            $fecha_desde = trim((string)obtener_get("fecha_desde", ""));
            $fecha_hasta = trim((string)obtener_get("fecha_hasta", ""));
            $texto_buscar = trim((string)obtener_get("buscar", ""));
            $campo_buscar = trim((string)obtener_get("campo", "todos"));
            $metodo_buscar = trim((string)obtener_get("metodo", "contiene"));
            $orden_ventas = orden_parametros([
                "fecha" => "fecha",
                "cliente" => "cliente",
                "nombre" => "nombre",
                "precio" => "precio",
                "total" => "total"
            ], "fecha", "DESC");
            $ventas = $listarVentasPeriodo->ejecutar($fecha_desde, $fecha_hasta, (string)$orden_ventas["campo"], (string)$orden_ventas["direccion"]);
            $campos_busqueda = [
                "id" => "ID",
                "fecha" => "Fecha",
                "cliente_nombre" => "Cliente",
                "usuario_nombre" => "Vendedor",
                "total" => "Total"
            ];
            $ventas = filtrar_registros_busqueda($ventas, $texto_buscar, $campo_buscar, $campos_busqueda, $metodo_buscar);
            $ids_venta_filtradas = array_map(fn($venta) => (int)($venta["id"] ?? 0), $ventas);
            $resumen_periodo = $obtenerResumenVentasPeriodo->ejecutar($ventas);
            $estados_fiscales = $obtenerEstadosFiscalesVentas->ejecutar($ids_venta_filtradas);
            $detalles_ventas = $obtenerDetallesVentas->ejecutar($ids_venta_filtradas);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/ventas/lista.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function inicio(): void {
        if ($this->permiso()) {
            global $container;
            $obtenerInicioVentas = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerInicioVentas::class);
            $datosInicio = $obtenerInicioVentas->ejecutar();
            $modulos = $datosInicio["modulos"];
            $body_class = (string)$datosInicio["body_class"];
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/ventas/inicio.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }



    public function guardar_menu(): void {
        if ($this->permiso()) {
            $volver = (string)obtener_post("volver", "index.php?c=ventas&a=inicio");
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                flash_error("Acceso invalido.");
                redirigir($volver);
            }
            $csrf = obtener_post("csrf", "");
            if (!csrf_valido($csrf)) {
                flash_error("Token invalido. Recarga la pagina.");
                redirigir($volver);
            }
            global $container;
            $guardarMenuVentas = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\GuardarMenuVentas::class);
            $seleccion = obtener_post("modulos_menu", []);
            if (!is_array($seleccion))
                $seleccion = [];
            $ok = $guardarMenuVentas->ejecutar($seleccion);
            if ($ok)
                flash_ok("Barra superior actualizada.");
            else
                flash_error("No se pudo guardar la barra superior.");
            redirigir($volver);
        }
    }



    public function nueva(): void {
        if ($this->permiso()) {
            global $container;
            if (!$container->has(\Ventas\Configuracion\Application\ObtenerConfiguracionBalanza::class)) {
                \Ventas\Configuracion\Infrastructure\RegistroConfiguracion::registrar($container);
            }
            $listarClientesVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\ListarClientesVenta::class);
            $listarListasPrecios = $container->get(\Ventas\ListasPrecios\Application\ListarListasPrecios::class);
            $obtenerCarritoVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerCarritoVenta::class);
            $calcularTotalCarritoVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\CalcularTotalCarritoVenta::class);
            $obtenerFormularioVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerFormularioVenta::class);
            $obtenerSaldosFavorClientes = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerSaldosFavorClientes::class);
            $obtenerConfiguracionBalanza = $container->get(\Ventas\Configuracion\Application\ObtenerConfiguracionBalanza::class);
            $obtenerTiposComprobanteVenta = $container->get(\Ventas\Ventas\Application\ObtenerTiposComprobanteVenta::class);
            $clientes = $listarClientesVenta->ejecutar();
            $productos = [];
            $listas_precios = [];
            foreach ($listarListasPrecios->ejecutar() as $lista_precio_dominio) {
                $listas_precios[] = [
                    "id" => $lista_precio_dominio->id(),
                    "nombre" => $lista_precio_dominio->nombre(),
                    "activo" => $lista_precio_dominio->activo() ? 1 : 0,
                    "creado_en" => $lista_precio_dominio->creadoEn(),
                ];
            }
            $carrito = $obtenerCarritoVenta->ejecutar();
            $carrito_vista = [];
            foreach ($carrito as $indice => $item) {
                $cantidad = max(0.0, (float)($item["cantidad"] ?? 0));
                $precio_unit = max(0.0, (float)($item["precio_unit"] ?? 0));
                $descuento = max(0.0, min(100.0, (float)($item["descuento"] ?? 0)));
                $bruto = $cantidad * $precio_unit;
                $subtotal = max(0.0, $bruto - (($bruto * $descuento) / 100));
                $item["subtotal"] = $subtotal;
                $carrito_vista[$indice] = $item;
            }
            $total = $calcularTotalCarritoVenta->ejecutar($carrito);
            $form_venta = $obtenerFormularioVenta->ejecutar();
            $saldos_favor_clientes = $obtenerSaldosFavorClientes->ejecutar();
            $configuracion_balanza_modular = $obtenerConfiguracionBalanza->ejecutar();
            $configuracion_balanza = [
                "modo" => (string)($configuracion_balanza_modular["modo"] ?? "auto"),
                "pluDigitos" => max(1, min(8, (int)($configuracion_balanza_modular["plu_digitos"] ?? 5))),
                "valorDecimales" => max(0, min(4, (int)($configuracion_balanza_modular["valor_decimales"] ?? 3))),
                "importeDecimales" => max(0, min(4, (int)($configuracion_balanza_modular["importe_decimales"] ?? 2))),
                "prefijosCantidad" => array_values(array_filter(array_map("trim", $configuracion_balanza_modular["prefijos_cantidad"] ?? ["20", "21", "23", "25", "27", "29"]))),
                "prefijosImporte" => array_values(array_filter(array_map("trim", $configuracion_balanza_modular["prefijos_importe"] ?? ["22", "24", "26", "28"]))),
            ];
            $tipos_comprobante = $obtenerTiposComprobanteVenta->ejecutar();
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/ventas/nueva.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function buscar_productos_json(): void {
        if ($this->permiso()) {
            global $container;
            $obtenerListaPrecioPredeterminada = $container->get(\Ventas\ListasPrecios\Application\ObtenerListaPrecioPredeterminada::class);
            $buscarProductosParaVenta = $container->get(\Ventas\Productos\Application\BuscarProductosParaVenta::class);
            $texto = trim((string)obtener_get("q", ""));
            $modo = trim((string)obtener_get("modo", "general"));
            $id_lista_precio = (int)obtener_get("id_lista_precio", $obtenerListaPrecioPredeterminada->ejecutar());
            if ($id_lista_precio <= 0)
                $id_lista_precio = $obtenerListaPrecioPredeterminada->ejecutar();
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode([
                "ok" => true,
                "productos" => $buscarProductosParaVenta->ejecutar($texto, $modo, $id_lista_precio, 30),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }



    public function aparte(): void {
        if ($this->permiso()) {
            redirigir("index.php?c=ventas&a=panel");
        }
    }

    public function panel(): void {
        if ($this->permiso()) {
            global $container;
            $obtenerPanelVentas = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerPanelVentas::class);
            $datosPanel = $obtenerPanelVentas->ejecutar();
            $modulos = $datosPanel["modulos"];
            $body_class = (string)$datosPanel["body_class"];
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            ?>
            <div class="module-shell">
              <div class="module-head">
                <div>
                  <h3 class="mb-1">Ventas</h3>
                  <div class="text-muted small">Panel principal de acceso rapido a los modulos de ventas.</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <a class="btn btn-outline-secondary" href="/Sistema-de-ventas/laravel/public/reparaciones">Ir a Reparaciones</a>
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
            $datos_form = [];
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $csrf = obtener_post("csrf", "");
                if (!csrf_valido($csrf))
                    $error = "Token invalido. Recarga la pagina.";
                else {
                    global $container;
                    $obtenerFormularioVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerFormularioVenta::class);
                    $agregarItemCarritoVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\AgregarItemCarritoVenta::class);
                    $calcularTotalCarritoVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\CalcularTotalCarritoVenta::class);
                    $guardarFormularioVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\GuardarFormularioVenta::class);
                    $renderizarCarritoVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\RenderizarCarritoVenta::class);
                    $formularioActual = $obtenerFormularioVenta->ejecutar();
                    $id_lista_precio = (int)obtener_post("id_lista_precio", (int)($formularioActual["id_lista_precio"] ?? 1));
                    $datos_form = [
                        "id_cliente" => (int)obtener_post("id_cliente", 1),
                        "buscar_cliente" => trim((string)obtener_post("buscar_cliente", "")),
                        "id_producto" => (string)obtener_post("id_producto", ""),
                        "cantidad" => (float)obtener_post("cantidad", 1),
                        "descuento" => (float)obtener_post("descuento", 0),
                        "precio_unit" => trim((string)obtener_post("precio_unit", "")),
                        "tipo_comprobante" => (int)obtener_post("tipo_comprobante", 98),
                        "buscar_producto" => trim((string)obtener_post("buscar_producto", "")),
                        "id_lista_precio" => $id_lista_precio
                    ];
                    $resultado = $agregarItemCarritoVenta->ejecutar(
                        (int)obtener_post("id_producto", 0),
                        (float)obtener_post("cantidad", 0),
                        (float)obtener_post("descuento", 0),
                        trim((string)obtener_post("precio_unit", "")),
                        parsear_numero_form(trim((string)obtener_post("precio_unit", "")), 0),
                        (int)obtener_post("aplicar_lista_existente", 0) === 1,
                        $datos_form["buscar_producto"],
                        (int)$datos_form["id_lista_precio"]
                    );

                    if ($resultado["ok"] === true) {
                        $carrito = $resultado["carrito"];
                        $guardarFormularioVenta->ejecutar([
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
                        $total = $calcularTotalCarritoVenta->ejecutar($carrito);
                        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                        if ($isAjax) {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode([
                                "success" => true,
                                "carrito_html" => $renderizarCarritoVenta->ejecutar($carrito),
                                "total" => moneda_para_mostrar($total),
                                "items" => count($carrito)
                            ]);
                        } else {
                            flash_ok("Producto agregado al carrito.");
                            redirigir("index.php?c=ventas&a=nueva");
                        }
                    } else {
                        $error = (string)$resultado["error"];
                    }
                }
            } else
                $error = "Acceso invalido.";
            if ($error !== "") {
                $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(["success" => false, "error" => $error]);
                } else {
                    flash_error($error);
                    global $container;
                    $guardarFormularioVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\GuardarFormularioVenta::class);
                    $guardarFormularioVenta->ejecutar($datos_form);
                    redirigir("index.php?c=ventas&a=nueva");
                }
            }
        }
    }

    public function aplicar_lista(): void {
        if ($this->permiso()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=ventas&a=nueva");
            }
            global $container;
            $obtenerFormularioVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerFormularioVenta::class);
            $aplicarListaPrecioCarritoVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\AplicarListaPrecioCarritoVenta::class);
            $guardarFormularioVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\GuardarFormularioVenta::class);
            $formularioActual = $obtenerFormularioVenta->ejecutar();
            $id_lista_precio = (int)obtener_post("id_lista_precio", (int)($formularioActual["id_lista_precio"] ?? 1));
            $carrito_actualizado = $aplicarListaPrecioCarritoVenta->ejecutar($id_lista_precio);
            if (count($carrito_actualizado) === 0) {
                flash_error("No hay productos cargados para aplicar la lista.");
            } else {
                flash_ok("Lista de precios aplicada a los productos cargados.");
            }
            $guardarFormularioVenta->ejecutar([
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
            global $container;
            $quitarItemCarritoVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\QuitarItemCarritoVenta::class);
            $quitarItemCarritoVenta->ejecutar((int)obtener_get("idx", -1), (int)obtener_get("id_producto", 0));
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
            global $container;
            $actualizarItemCarritoVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\ActualizarItemCarritoVenta::class);
            $resultado = $actualizarItemCarritoVenta->ejecutar(
                (int)obtener_post("idx", -1),
                parsear_numero_form(obtener_post("cantidad", 0), 0),
                parsear_numero_form(obtener_post("precio_unit", 0), 0),
                parsear_numero_form(obtener_post("descuento", 0), 0)
            );
            if ($resultado["ok"] === true) {
                flash_ok("Item actualizado.");
            } else {
                flash_error((string)$resultado["error"]);
            }
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
            global $container;
            $vaciarCarritoVenta = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\VaciarCarritoVenta::class);
            $vaciarCarritoVenta->ejecutar();
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
                    global $container;
                    $confirmarVenta = $container->get(\Ventas\Aplicacion\Ventas\CasosUso\ConfirmarVenta::class);
                    $obtenerUsuarioActual = $container->get(\Ventas\Aplicacion\Ventas\NuevaVenta\ObtenerUsuarioActual::class);
                    $generarPdfComprobanteVenta = $container->get(\Ventas\Aplicacion\Ventas\CasosUso\GenerarPdfComprobanteVenta::class);
                    $cc_vencimientos = $_POST["cc_vencimientos"] ?? [];
                    if (!is_array($cc_vencimientos))
                        $cc_vencimientos = [];
                    $usuario_actual = $obtenerUsuarioActual->ejecutar();

                    $resultado = $confirmarVenta->ejecutar([
                        "id_usuario" => (int)($usuario_actual["id"] ?? 0),
                        "id_cliente" => (int)obtener_post("id_cliente", 1),
                        "buscar_cliente" => trim((string)obtener_post("buscar_cliente", "")),
                        "tipo_comprobante" => (int)obtener_post("tipo_comprobante", 98),
                        "forma_pago" => strtolower(trim((string)obtener_post("forma_pago", "contado"))),
                        "imprimir_ticket" => (int)obtener_post("imprimir_ticket", 0) === 1,
                        "cc_cuotas" => max(1, (int)obtener_post("cc_cuotas", 1)),
                        "cc_vencimientos" => $cc_vencimientos,
                    ]);

                    if ($resultado["ok"] === true) {
                        $ok_pdf = false;
                        if (($resultado["generar_pdf"] ?? false) === true) {
                            $resultado_pdf = $generarPdfComprobanteVenta->ejecutar((int)$resultado["id_venta"]);
                            $ok_pdf = ($resultado_pdf["ok"] ?? false) === true;
                        }
                        if ($ok_pdf || ($resultado["generar_pdf"] ?? false) === false) {
                            flash_ok((string)$resultado["mensaje"]);
                        } else {
                            flash_ok("Venta confirmada. Revisar PDF y cola fiscal en logs.");
                        }
                        redirigir((string)$resultado["redirigir"]);
                    } else {
                        $error = (string)$resultado["error"];
                    }
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
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

            global $container;
            $renderizarTicketVenta = $container->get(\Ventas\Aplicacion\Ventas\CasosUso\RenderizarTicketVenta::class);
            $auto_print = (int)obtener_get("auto_print", 0) === 1;
            $resultado = $renderizarTicketVenta->ejecutar($id_venta, $auto_print);

            if (($resultado["ok"] ?? false) !== true) {
                flash_error("Venta invalida.");
                redirigir("index.php?c=ventas&a=lista");
            } else {
                echo (string)$resultado["html"];
            }
        }
    }

    public function pdf(): void {
        if ($this->permiso()) {
            $id_venta = (int)obtener_get("id", 0);
            if ($id_venta <= 0) {
                flash_error("Venta invalida.");
                redirigir("index.php?c=ventas&a=lista");
            }

            global $container;
            $generarPdfComprobanteVenta = $container->get(\Ventas\Aplicacion\Ventas\CasosUso\GenerarPdfComprobanteVenta::class);
            $obtenerArchivoPdfVenta = $container->get(\Ventas\Aplicacion\Ventas\CasosUso\ObtenerArchivoPdfVenta::class);
            $resultadoPdf = $generarPdfComprobanteVenta->ejecutar($id_venta);
            $archivoPdf = $obtenerArchivoPdfVenta->ejecutar($id_venta);

            if (($resultadoPdf["ok"] ?? false) !== true || ($archivoPdf["ok"] ?? false) !== true) {
                flash_error("No se pudo generar el PDF.");
                redirigir("index.php?c=ventas&a=ticket&id=" . $id_venta);
            } else {
                header("Content-Type: application/pdf");
                header("Content-Disposition: attachment; filename=" . (string)$archivoPdf["nombre"]);
                header("Content-Length: " . (int)$archivoPdf["tamano"]);
                echo (string)$archivoPdf["contenido"];
            }
        }
    }

    public function presupuesto_pdf(): void {
        if ($this->permiso()) {
            $id_presupuesto = (int)obtener_get("id", 0);
            if ($id_presupuesto <= 0) {
                flash_error("Presupuesto invalido.");
                redirigir("index.php?c=ventas&a=nueva");
            }

            global $container;
            $generarPdfPresupuesto = $container->get(\Ventas\Aplicacion\Presupuestos\CasosUso\GenerarPdfPresupuesto::class);
            $obtenerArchivoPdfPresupuesto = $container->get(\Ventas\Aplicacion\Presupuestos\CasosUso\ObtenerArchivoPdfPresupuesto::class);
            $resultadoPdf = $generarPdfPresupuesto->ejecutar($id_presupuesto);
            $archivoPdf = $obtenerArchivoPdfPresupuesto->ejecutar($id_presupuesto);

            if (($resultadoPdf["ok"] ?? false) !== true || ($archivoPdf["ok"] ?? false) !== true) {
                flash_error("No se pudo generar el PDF.");
                redirigir("index.php?c=ventas&a=presupuesto_ticket&id=" . $id_presupuesto);
            } else {
                header("Content-Type: application/pdf");
                header("Content-Disposition: attachment; filename=" . (string)$archivoPdf["nombre"]);
                header("Content-Length: " . (int)$archivoPdf["tamano"]);
                echo (string)$archivoPdf["contenido"];
            }
        }
    }

    public function presupuesto_ticket(): void {
        if ($this->permiso()) {
            $id_presupuesto = (int)obtener_get("id", 0);
            if ($id_presupuesto <= 0) {
                flash_error("Presupuesto invalido.");
                redirigir("index.php?c=ventas&a=nueva");
            }

            global $container;
            $renderizarTicketPresupuesto = $container->get(\Ventas\Aplicacion\Presupuestos\CasosUso\RenderizarTicketPresupuesto::class);
            $auto_print = (int)obtener_get("auto_print", 0) === 1;
            $resultado = $renderizarTicketPresupuesto->ejecutar($id_presupuesto, $auto_print);

            if (($resultado["ok"] ?? false) !== true) {
                flash_error("Presupuesto invalido.");
                redirigir("index.php?c=ventas&a=nueva");
            } else {
                echo (string)$resultado["html"];
            }
        }
    }

    public function impresoras_json(): void
    {
        if ($this->permiso()) {
            global $container;
            $listarImpresoras = $container->get(\Ventas\Impresoras\Application\ListarImpresoras::class);
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(["ok" => true, "impresoras" => $listarImpresoras->ejecutar()], JSON_UNESCAPED_UNICODE);
        }
    }

}
