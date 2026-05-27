<?php
$form_venta = $form_venta ?? [];
$id_cliente_actual = (int)($form_venta["id_cliente"] ?? 1);
$buscar_cliente_actual = (string)($form_venta["buscar_cliente"] ?? "");
$id_producto_actual = (string)($form_venta["id_producto"] ?? "");
$cantidad_actual = (string)($form_venta["cantidad"] ?? "1");
$descuento_actual = (string)($form_venta["descuento"] ?? "0");
$precio_unit_actual = (string)($form_venta["precio_unit"] ?? "");
$tipo_comprobante_actual = (int)($form_venta["tipo_comprobante"] ?? 98);
$buscar_producto_actual = (string)($form_venta["buscar_producto"] ?? "");
$listas_precios = $listas_precios ?? [];
$config_sistema_ventas = ConfiguracionSistema::obtener();
$balanza_config = [
  "modo" => (string)($config_sistema_ventas["balanza_modo"] ?? "auto"),
  "pluDigitos" => max(1, min(8, (int)($config_sistema_ventas["balanza_plu_digitos"] ?? 5))),
  "valorDecimales" => max(0, min(4, (int)($config_sistema_ventas["balanza_valor_decimales"] ?? 3))),
  "importeDecimales" => max(0, min(4, (int)($config_sistema_ventas["balanza_importe_decimales"] ?? 2))),
  "prefijosCantidad" => array_values(array_filter(array_map("trim", explode(",", (string)($config_sistema_ventas["balanza_prefijos_cantidad"] ?? "20,21,23,25,27,29"))))),
  "prefijosImporte" => array_values(array_filter(array_map("trim", explode(",", (string)($config_sistema_ventas["balanza_prefijos_importe"] ?? "22,24,26,28"))))),
];
$id_lista_precio_actual = (int)($form_venta["id_lista_precio"] ?? 0);
if ($id_lista_precio_actual <= 0 && count($listas_precios) > 0)
  $id_lista_precio_actual = (int)$listas_precios[0]["id"];
$tipos_comprobante = FacturaFiscal::tipos_comprobante();
if (!isset($tipos_comprobante[$tipo_comprobante_actual]))
  $tipo_comprobante_actual = 98;
$tipo_comprobante_info = $tipos_comprobante[$tipo_comprobante_actual];
$cliente_actual_nombre = "Consumidor Final";
$clientes_json = [];
$saldos_favor_clientes = $saldos_favor_clientes ?? [];
foreach ($clientes as $cliente_item) {
  $cliente_texto = (string)($cliente_item["nombre"] ?? "");
  if ((int)($cliente_item["id"] ?? 0) === 1 && $cliente_texto === "") {
    $cliente_texto = "Consumidor Final";
  }
  $tipo_doc_cliente = (string)($cliente_item["tipo_documento"] ?? "DNI");
  if (!empty($cliente_item["dni"])) {
    $cliente_texto .= " - " . $tipo_doc_cliente . ": " . (string)$cliente_item["dni"];
  }
  $condicion_cliente = (string)($cliente_item["condicion_iva"] ?? "");
  $clientes_json[] = [
    "id" => (int)($cliente_item["id"] ?? 0),
    "texto" => $cliente_texto,
    "documento" => (string)($cliente_item["dni"] ?? ""),
    "tipo_documento" => $tipo_doc_cliente,
    "condicion_iva" => $condicion_cliente,
    "id_lista_precio" => (int)($cliente_item["id_lista_precio"] ?? 0),
    "saldo_favor" => (float)($saldos_favor_clientes[(int)($cliente_item["id"] ?? 0)] ?? 0)
  ];
  if ((int)($cliente_item["id"] ?? 0) === $id_cliente_actual) {
    $cliente_actual_nombre = $cliente_texto;
  }
}
?>
<div class="sales-workspace">
  <div class="card sales-summary-card sales-cart-card sales-sale-panel">
    <div class="card-body p-4">
      <div class="sales-sale-header">
        <div>
          <h3 class="mb-1">Nueva venta</h3>
        </div>
        <div class="sales-sale-header-actions">
          <span class="cart-counter"><?= count($carrito) ?> item<?= count($carrito) === 1 ? "" : "s" ?></span>
          <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=lista">Ir a lista</a>
          <a class="btn btn-primary" href="index.php?c=ventas&a=panel">Panel principal</a>
        </div>
      </div>

      <div class="sales-client-layout">
        <div class="invoice-type-card">
          <div class="invoice-type-letter" id="facturaLetra"><?= htmlspecialchars($tipo_comprobante_info["letra"]) ?></div>
          <div class="invoice-type-body">
            <label class="form-label mb-1" for="tipoComprobanteTop">Tipo de comprobante</label>
            <select class="form-select" id="tipoComprobanteTop">
              <?php foreach ($tipos_comprobante as $codigo => $info): ?>
                <option value="<?= (int)$codigo ?>"
                        data-letra="<?= htmlspecialchars($info["letra"]) ?>"
                        data-requisito="<?= htmlspecialchars($info["requisito"]) ?>"
                        <?= $tipo_comprobante_actual === (int)$codigo ? "selected" : "" ?>>
                  <?= htmlspecialchars($info["texto"]) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invoice-type-rule d-none" id="facturaRegla"><?= htmlspecialchars($tipo_comprobante_info["requisito"]) ?></div>
          </div>
        </div>
        <div class="sales-client-main">
          <div class="sales-client-head">
            <div>
              <label class="form-label mb-1" for="clienteSelectorInput">Cliente</label>
              <strong class="sales-client-title d-none" id="clienteActualTitulo"><?= htmlspecialchars($cliente_actual_nombre) ?></strong>
            </div>
          </div>
          <div class="sales-client-picker">
            <div class="sales-client-input-group">
              <input id="clienteSelectorInput" class="form-control form-control-lg" list="listaClientesAuto" placeholder="Escrib&iacute; nombre o DNI..." autocomplete="off" value="<?= htmlspecialchars($buscar_cliente_actual !== "" ? $buscar_cliente_actual : $cliente_actual_nombre) ?>">
            </div>
            <div class="sales-client-panel d-none" id="clientePanel">
              <div id="clientePanelSelect" class="sales-client-list" role="listbox" aria-label="Lista de clientes">
                <button type="button" class="sales-client-option <?= $id_cliente_actual === 1 ? "active" : "" ?>" data-id="1">Consumidor Final</button>
                <?php foreach ($clientes as $c): ?>
                  <?php if ((int)$c["id"] !== 1): ?>
                    <?php
                      $texto_doc = "";
                      if (!empty($c["dni"]))
                        $texto_doc = (string)($c["tipo_documento"] ?? "DNI") . ": " . (string)$c["dni"];
                    ?>
                    <button type="button" class="sales-client-option <?= $id_cliente_actual === (int)$c["id"] ? "active" : "" ?>" data-id="<?= (int)$c["id"] ?>">
                      <strong><?= htmlspecialchars($c["nombre"]) ?></strong>
                      <span><?= htmlspecialchars(trim($texto_doc . " " . (string)($c["condicion_iva"] ?? ""))) ?></span>
                    </button>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
            <input id="buscarCliente" type="hidden" value="<?= htmlspecialchars($buscar_cliente_actual) ?>">
            <select id="selectCliente" class="d-none" name="id_cliente_form" aria-label="Cliente">
              <option value="1" <?= $id_cliente_actual === 1 ? "selected" : "" ?>>Consumidor Final</option>
              <?php foreach ($clientes as $c): ?>
                <?php if ((int)$c["id"] !== 1): ?>
                  <option value="<?= (int)$c["id"] ?>" <?= $id_cliente_actual === (int)$c["id"] ? "selected" : "" ?>>
                    <?= htmlspecialchars($c["nombre"]) ?><?= !empty($c["dni"]) ? (" - DNI: " . htmlspecialchars($c["dni"])) : "" ?>
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
            <datalist id="listaClientesAuto">
              <?php foreach ($clientes_json as $cliente_auto): ?>
                <option value="<?= htmlspecialchars((string)$cliente_auto["texto"]) ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
        </div>
        <div class="sales-price-list-main">
          <form method="POST" action="index.php?c=ventas&a=aplicar_lista" id="formAplicarListaVenta" class="sales-price-list-form">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="id_cliente" id="idClienteHiddenLista" value="<?= $id_cliente_actual ?>">
            <input type="hidden" name="buscar_cliente" id="buscarClienteHiddenLista" value="<?= htmlspecialchars($buscar_cliente_actual) ?>">
            <input type="hidden" name="tipo_comprobante" id="tipoComprobanteLista" value="<?= $tipo_comprobante_actual ?>">
            <label class="form-label mb-1" for="selectListaPrecio">Lista de precios</label>
            <div class="sales-price-list-control">
              <select class="form-select" id="selectListaPrecio" name="id_lista_precio">
                <?php foreach ($listas_precios as $lista): ?>
                  <?php $lid = (int)$lista["id"]; ?>
                  <option value="<?= $lid ?>" <?= $id_lista_precio_actual === $lid ? "selected" : "" ?>><?= htmlspecialchars($lista["nombre"] ?? "") ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-outline-primary" type="submit" <?= count($carrito) === 0 ? "disabled" : "" ?>>Aplicar a cargados</button>
            </div>
          </form>
        </div>
        <div class="sales-total-panel">
          <div class="sales-total-box">
            <div class="sales-total-inline">
              <span class="sales-total-label">Total:</span>
              <strong class="sales-total-amount"><?= htmlspecialchars(moneda_para_mostrar($total)) ?></strong>
            </div>
          </div>
        </div>
      </div>

      <div class="sales-add-panel mb-3">
        <form method="POST" action="index.php?c=ventas&a=agregar" class="smart-form sales-add-form" id="formAgregarVenta">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="id_cliente" id="idClienteHiddenAgregar" value="<?= $id_cliente_actual ?>">
            <input type="hidden" name="buscar_cliente" id="buscarClienteHiddenAgregar" value="<?= htmlspecialchars($buscar_cliente_actual) ?>">
            <input type="hidden" name="tipo_comprobante" id="tipoComprobanteAgregar" value="<?= $tipo_comprobante_actual ?>">
            <input type="hidden" name="id_lista_precio" id="idListaPrecioAgregar" value="<?= $id_lista_precio_actual ?>">
            <div id="salesAgregarError" class="form-text text-danger d-none mb-2"></div>
            <div class="sales-add-grid">
              <div class="sales-add-item sales-add-search">
                <label class="form-label">Buscar producto</label>
                <div class="sales-search-mode-group">
                  <select class="form-select" id="modoBusquedaProducto" aria-label="Modo de busqueda de producto">
                    <option value="codigo">Codigo de barras</option>
                    <option value="general">Nombre / general</option>
                  </select>
                  <input id="buscarProducto" name="buscar_producto" class="form-control" placeholder="Codigo de barras..." autocomplete="off" value="<?= htmlspecialchars($buscar_producto_actual) ?>">
                </div>
                <div class="sales-product-panel d-none" id="productoPanel">
                  <div id="productoPanelSelect" class="sales-product-list" role="listbox" aria-label="Lista de productos">
                  </div>
                </div>
              </div>
              <div class="sales-add-item sales-product-select-hidden">
                <select class="form-select" id="selectProducto" name="id_producto" aria-label="Producto">
                  <option value="" selected disabled>Seleccion&aacute; un producto</option>
                </select>
              </div>
              <div class="sales-add-item">
                <label class="form-label">Cant.</label>
                <input type="number" step="0.001" class="form-control" name="cantidad" value="<?= htmlspecialchars($cantidad_actual) ?>" required>
              </div>
              <div class="sales-add-item">
                <label class="form-label">Precio</label>
                <input type="number" step="0.01" min="0" class="form-control" name="precio_unit" id="precioUnitVenta" placeholder="Precio" value="<?= htmlspecialchars($precio_unit_actual) ?>">
              </div>
              <div class="sales-add-item">
                <label class="form-label">Desc.</label>
                <div class="input-group">
                  <input type="number" step="0.01" min="0" max="100" class="form-control" name="descuento" value="<?= htmlspecialchars($descuento_actual) ?>">
                  <span class="input-group-text">%</span>
                </div>
              </div>
              <div class="sales-add-item sales-add-action">
                <button class="btn btn-primary w-100">Agregar</button>
              </div>
            </div>
        </form>
      </div>

      <div class="sales-lines-panel">
        <div class="table-responsive">
          <table class="table table-striped align-middle sales-table">
            <thead>
              <tr>
                <th>Producto</th>
                <th style="text-align:right;">Cant</th>
                <th style="text-align:right;">P.Unit</th>
                <th style="text-align:right;">Desc</th>
                <th style="text-align:right;">Sub</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($carrito as $idx => $it): ?>
              <?php $sub = Venta::calcular_subtotal((float)$it["cantidad"], (float)$it["precio_unit"], (float)$it["descuento"]); ?>
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
            <?php endforeach; ?>
            <?php if (count($carrito) === 0): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">Todav&iacute;a no hay productos cargados.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="sales-detail-footer">
          <div class="sales-footer-actions">
            <a class="btn btn-outline-danger" href="index.php?c=ventas&a=vaciar" onclick="return confirm('&iquest;Vaciar detalle?');">Vaciar</a>
            <form id="formConfirmarBottom" method="POST" action="index.php?c=ventas&a=confirmar" class="m-0">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input type="hidden" name="id_cliente" id="idClienteHiddenBottom" value="<?= $id_cliente_actual ?>">
              <input type="hidden" name="buscar_cliente" id="buscarClienteHiddenBottom" value="<?= htmlspecialchars($buscar_cliente_actual) ?>">
              <input type="hidden" name="tipo_comprobante" id="tipoComprobanteBottom" value="<?= $tipo_comprobante_actual ?>">
              <input type="hidden" name="forma_pago" id="formaPagoHidden" value="contado">
              <input type="hidden" name="imprimir_ticket" id="imprimirTicketHidden" value="0">
              <div id="ccVencimientosHidden"></div>
              <button type="button" class="btn btn-success w-100" id="btnConfirmarComprobante" <?= count($carrito) === 0 ? "disabled" : "" ?>>
                Confirmar comprobante
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="sales-payment-modal d-none" id="modalFormaPago" role="dialog" aria-modal="true" aria-labelledby="modalFormaPagoTitulo">
  <div class="sales-payment-dialog">
    <div class="sales-payment-head">
      <h4 id="modalFormaPagoTitulo">Forma de pago</h4>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="cerrarModalFormaPago">Cerrar</button>
    </div>
    <div class="sales-payment-body">
      <label class="form-label">Elegir forma de pago</label>
      <select class="form-select" id="formaPagoVenta">
        <option value="contado">Contado</option>
        <option value="transferencia">Transferencia</option>
        <option value="tarjeta">Tarjeta</option>
        <option value="saldo_favor">Saldo a favor</option>
        <option value="cuenta_corriente">Cuenta corriente</option>
      </select>
      <div class="form-text d-none" id="saldoFavorVentaInfo"></div>
      <div class="sales-cc-panel d-none" id="cuentaCorrienteVentaPanel">
        <label class="form-label">Cuotas y vencimientos</label>
        <input type="number" min="1" max="36" class="form-control" id="ccCuotasVenta" value="1">
        <div class="sales-cc-dates" id="ccVencimientosVenta"></div>
      </div>
      <div class="sales-printer-panel d-none" id="impresoraVentaPanel">
        <label class="form-label">Impresora</label>
        <select class="form-select" id="impresoraVentaSelect">
          <option value="">Cargando impresoras...</option>
        </select>
        <div class="form-text">El navegador abrira el dialogo de impresion. Elegi ahi la misma impresora si no queda seleccionada.</div>
      </div>
    </div>
    <div class="sales-payment-actions">
      <button type="button" class="btn btn-outline-secondary" id="guardarVentaSinImprimir">Solo guardar</button>
      <button type="button" class="btn btn-primary" id="guardarVentaImprimir">Guardar e imprimir ticket</button>
    </div>
  </div>
</div>
<script>
(function(){
  const inputCli = document.getElementById('buscarCliente');
  const clienteSelectorInput = document.getElementById('clienteSelectorInput');
  const clientePanel = document.getElementById('clientePanel');
  const clientePanelSelect = document.getElementById('clientePanelSelect');
  const selCli = document.getElementById('selectCliente');
  const hidBot = document.getElementById('idClienteHiddenBottom');
  const hidAdd = document.getElementById('idClienteHiddenAgregar');
  const buscarCliBottom = document.getElementById('buscarClienteHiddenBottom');
  const buscarCliAdd = document.getElementById('buscarClienteHiddenAgregar');
  const hidLista = document.getElementById('idClienteHiddenLista');
  const buscarCliLista = document.getElementById('buscarClienteHiddenLista');
  const clienteActualTitulo = document.getElementById('clienteActualTitulo');
  const cliOptionsHTML = selCli ? selCli.innerHTML : '';
  const cliPanelOptionsHTML = clientePanelSelect ? clientePanelSelect.innerHTML : '';
  const tipoComprobanteTop = document.getElementById('tipoComprobanteTop');
  const tipoComprobanteBottom = document.getElementById('tipoComprobanteBottom');
  const tipoComprobanteAgregar = document.getElementById('tipoComprobanteAgregar');
  const tipoComprobanteLista = document.getElementById('tipoComprobanteLista');
  const facturaLetra = document.getElementById('facturaLetra');
  const facturaRegla = document.getElementById('facturaRegla');
  const btnConfirmarComprobante = document.getElementById('btnConfirmarComprobante');
  const formaPagoVenta = document.getElementById('formaPagoVenta');
  const formaPagoHidden = document.getElementById('formaPagoHidden');
  const imprimirTicketHidden = document.getElementById('imprimirTicketHidden');
  const ccVencimientosHidden = document.getElementById('ccVencimientosHidden');
  const modalFormaPago = document.getElementById('modalFormaPago');
  const cerrarModalFormaPago = document.getElementById('cerrarModalFormaPago');
  const guardarVentaSinImprimir = document.getElementById('guardarVentaSinImprimir');
  const guardarVentaImprimir = document.getElementById('guardarVentaImprimir');
  const impresoraVentaPanel = document.getElementById('impresoraVentaPanel');
  const impresoraVentaSelect = document.getElementById('impresoraVentaSelect');
  const cuentaCorrienteVentaPanel = document.getElementById('cuentaCorrienteVentaPanel');
  const ccCuotasVenta = document.getElementById('ccCuotasVenta');
  const ccVencimientosVenta = document.getElementById('ccVencimientosVenta');
  const saldoFavorVentaInfo = document.getElementById('saldoFavorVentaInfo');
  const clientesData = <?= json_encode($clientes_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  const balanzaConfig = <?= json_encode($balanza_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  const inputProd = document.getElementById('buscarProducto');
  const modoBusquedaProducto = document.getElementById('modoBusquedaProducto');
  const selProd = document.getElementById('selectProducto');
  const productoPanel = document.getElementById('productoPanel');
  const productoPanelSelect = document.getElementById('productoPanelSelect');
  const selListaPrecio = document.getElementById('selectListaPrecio');
  const hidListaPrecioAgregar = document.getElementById('idListaPrecioAgregar');
  const formAplicarListaVenta = document.getElementById('formAplicarListaVenta');
  const precioUnitVenta = document.getElementById('precioUnitVenta');
  const formAgregarVenta = document.getElementById('formAgregarVenta');
  const salesAgregarError = document.getElementById('salesAgregarError');
  const cantidadVenta = formAgregarVenta ? formAgregarVenta.querySelector('input[name="cantidad"]') : null;
  const storageKey = 'ventas.nueva.form.v3';
  let enviandoProducto = false;
  let busquedaProductosTimer = null;
  let busquedaProductosSeq = 0;
  let scannerBuffer = '';
  let scannerTimer = null;
  let scannerUltimaTecla = 0;
  const scannerMaxIntervalo = 45;
  const scannerMinLargo = 2;
  const scannerEsperaFinal = 90;
  let preservarPrecioInicial = precioUnitVenta && precioUnitVenta.value !== '';

  function guardarEstadoLocal() {
    const estado = {
      cliente: selCli ? selCli.value : '1',
      buscarCliente: inputCli ? inputCli.value : ''
    };
    localStorage.setItem(storageKey, JSON.stringify(estado));
  }

  function cargarEstadoLocal() {
    try {
      const bruto = localStorage.getItem(storageKey);
      if (!bruto)
        return;
      const estado = JSON.parse(bruto);
      if (clienteSelectorInput && !clienteSelectorInput.value)
        clienteSelectorInput.value = estado.buscarCliente || '';
      if (inputCli)
        inputCli.value = estado.buscarCliente || '';
      if (selCli && estado.cliente)
        selCli.value = estado.cliente;
    } catch (e) {}
  }

  function syncTipoComprobante() {
    const valor = tipoComprobanteTop ? tipoComprobanteTop.value : '98';
    const opcion = tipoComprobanteTop ? tipoComprobanteTop.options[tipoComprobanteTop.selectedIndex] : null;
    if (tipoComprobanteBottom)
      tipoComprobanteBottom.value = valor;
    if (tipoComprobanteAgregar)
      tipoComprobanteAgregar.value = valor;
    if (tipoComprobanteLista)
      tipoComprobanteLista.value = valor;
    if (facturaLetra)
      facturaLetra.textContent = opcion ? (opcion.dataset.letra || 'X') : 'X';
    if (facturaRegla)
      facturaRegla.textContent = opcion ? (opcion.dataset.requisito || '') : '';
    if (btnConfirmarComprobante) {
      const texto = opcion ? (opcion.textContent || '').trim() : 'comprobante';
      btnConfirmarComprobante.textContent = texto === 'Presupuesto' ? 'Generar presupuesto' : 'Confirmar ' + texto;
    }
    guardarEstadoLocal();
  }

  function obtenerTextoClientePorId(id) {
    for (let i = 0; i < clientesData.length; i++) {
      if (String(clientesData[i].id) === String(id))
        return clientesData[i].texto;
    }
    return 'Consumidor Final';
  }

  function obtenerIdClientePorTexto(texto) {
    const textoNorm = (texto || '').toLowerCase().trim();
    if (textoNorm === '')
      return '';
    for (let i = 0; i < clientesData.length; i++) {
      if ((clientesData[i].texto || '').toLowerCase() === textoNorm)
        return String(clientesData[i].id);
    }
    return '';
  }

  function syncClienteHidden(){
    const valor = selCli && selCli.value ? selCli.value : '1';
    const texto = clienteSelectorInput ? clienteSelectorInput.value : '';
    const seleccionado = obtenerTextoClientePorId(valor);
    if (hidBot)
      hidBot.value = valor;
    if (hidAdd)
      hidAdd.value = valor;
    if (hidLista)
      hidLista.value = valor;
    if (buscarCliBottom)
      buscarCliBottom.value = texto;
    if (buscarCliAdd)
      buscarCliAdd.value = texto;
    if (buscarCliLista)
      buscarCliLista.value = texto;
    if (inputCli)
      inputCli.value = texto;
    if (clientePanelSelect) {
      clientePanelSelect.querySelectorAll('.sales-client-option').forEach(function(btn){
        btn.classList.toggle('active', String(btn.dataset.id || '') === String(valor));
      });
    }
    if (clienteActualTitulo)
      clienteActualTitulo.textContent = seleccionado || 'Consumidor Final';
    const cliente = clientesData.find(function(c){ return String(c.id) === String(valor); });
    if (cliente && cliente.id_lista_precio && selListaPrecio) {
      selListaPrecio.value = String(cliente.id_lista_precio);
      syncListaPrecio();
    }
    syncFormaPagoVenta();
    guardarEstadoLocal();
  }

  function precioProductoSeleccionado() {
    if (!selProd || !precioUnitVenta)
      return;
    const op = selProd.options[selProd.selectedIndex];
    if (!op || !op.value) {
      precioUnitVenta.value = '';
      return;
    }
    if (cantidadVenta) {
      const decimales = Math.max(0, Math.min(4, parseInt(op.getAttribute('data-decimales') || '3', 10) || 0));
      cantidadVenta.step = String(1 / Math.pow(10, decimales));
    }
    const idLista = selListaPrecio ? selListaPrecio.value : '';
    const bruto = op.getAttribute('data-precios') || '';
    let precio = 0;
    let precioListaEncontrado = false;
    bruto.split('|').forEach(function(par) {
      const partes = par.split(':');
      if (partes.length === 2 && partes[0] === idLista) {
        precio = parseFloat(partes[1]) || 0;
        precioListaEncontrado = precio > 0;
      }
    });
    precioUnitVenta.value = precioListaEncontrado && precio > 0 ? precio.toFixed(2) : '';
  }

  function syncListaPrecio() {
    if (hidListaPrecioAgregar && selListaPrecio)
      hidListaPrecioAgregar.value = selListaPrecio.value;
    if (!(preservarPrecioInicial && precioUnitVenta && precioUnitVenta.value !== ''))
      precioProductoSeleccionado();
    preservarPrecioInicial = false;
    guardarEstadoLocal();
  }

  function filtrarClientes(textoEntrada){
    if (!clientePanelSelect)
      return;
    const texto = (textoEntrada || '').toLowerCase().trim();
    const seleccionado = selCli ? selCli.value : '1';
    if (texto === '') {
      clientePanelSelect.innerHTML = cliPanelOptionsHTML;
    } else {
      const temp = document.createElement('div');
      temp.innerHTML = cliPanelOptionsHTML;
      const opciones = Array.from(temp.querySelectorAll('.sales-client-option'));
      clientePanelSelect.innerHTML = '';
      opciones.forEach(op => {
        const contenido = (op.textContent || '').toLowerCase();
        if (contenido.includes(texto))
          clientePanelSelect.appendChild(op);
      });
    }
    activarBotonCliente(seleccionado);
    enlazarBotonesCliente();
  }

  function aplicarClienteDesdeTexto() {
    if (!clienteSelectorInput || !selCli)
      return;
    const idExacto = obtenerIdClientePorTexto(clienteSelectorInput.value);
    if (idExacto !== '') {
      selCli.value = idExacto;
      syncClienteHidden();
      return true;
    }
    return false;
  }

  function seleccionarClienteDesdePanel() {
    if (!clientePanelSelect || !selCli)
      return;
    const activo = clientePanelSelect.querySelector('.sales-client-option.active') || clientePanelSelect.querySelector('.sales-client-option');
    const valor = activo ? (activo.dataset.id || '1') : '1';
    selCli.value = valor;
    if (clienteSelectorInput)
      clienteSelectorInput.value = obtenerTextoClientePorId(valor);
    syncClienteHidden();
  }

  function activarBotonCliente(valor) {
    if (!clientePanelSelect)
      return;
    clientePanelSelect.querySelectorAll('.sales-client-option').forEach(function(btn){
      btn.classList.toggle('active', String(btn.dataset.id || '') === String(valor));
    });
  }

  function enlazarBotonesCliente() {
    if (!clientePanelSelect)
      return;
    clientePanelSelect.querySelectorAll('.sales-client-option').forEach(function(btn){
      if (btn.dataset.bound === '1')
        return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', function(){
        activarBotonCliente(btn.dataset.id || '1');
        seleccionarClienteDesdePanel();
        if (clientePanel)
          clientePanel.classList.add('d-none');
      });
      btn.addEventListener('dblclick', function(){
        activarBotonCliente(btn.dataset.id || '1');
        seleccionarClienteDesdePanel();
        if (clientePanel)
          clientePanel.classList.add('d-none');
      });
    });
  }

  function datosEntradaProducto(valor) {
    const texto = (valor || '').trim();
    const partes = texto.match(/^(\d+(?:[.,]\d+)?)\s*\*\s*(.+)$/);
    if (!partes)
      return { cantidad: 1, codigo: texto };
    const cantidad = parseFloat(partes[1].replace(',', '.')) || 1;
    return { cantidad: cantidad > 0 ? cantidad : 1, codigo: partes[2].trim() };
  }

  function soloDigitos(valor) {
    return String(valor || '').replace(/\D+/g, '');
  }

  function normalizarPlu(valor) {
    return soloDigitos(valor).replace(/^0+/, '') || '0';
  }

  function precioListaDeOpcion(op) {
    if (!op)
      return 0;
    const idLista = selListaPrecio ? selListaPrecio.value : '';
    const bruto = op.getAttribute('data-precios') || '';
    let precio = 0;
    bruto.split('|').forEach(function(par) {
      const partes = par.split(':');
      if (partes.length === 2 && partes[0] === idLista)
        precio = parseFloat(partes[1]) || 0;
    });
    return precio;
  }

  function buscarOpcionPorCodigoOPlu(codigo) {
    if (!selProd || codigo === '')
      return null;
    const codigoNorm = normalizarPlu(codigo);
    const opciones = Array.from(selProd.querySelectorAll('option'));
    for (let i = 0; i < opciones.length; i++) {
      const op = opciones[i];
      if (!op.value)
        continue;
      const cb = (op.getAttribute('data-cb') || '').trim();
      if (cb === codigo || normalizarPlu(cb) === codigoNorm)
        return op;
    }
    return null;
  }

  function escapeHtml(valor) {
    const div = document.createElement('div');
    div.textContent = String(valor || '');
    return div.innerHTML;
  }

  function optionProductoHTML(p) {
    return '<option value="' + String(p.id) + '" data-cb="' + escapeHtml(p.cod_barras || '') + '" data-nombre="' + escapeHtml(p.nombre || '') + '" data-precios="' + escapeHtml(p.precios_lista || '') + '" data-unidad="' + escapeHtml(p.stock_unidad || 'u') + '" data-decimales="' + String(p.unidad_decimales || 3) + '">' +
      escapeHtml((p.nombre || '') + ' | ' + (p.precio_texto || 'SIN PRECIO') + (p.cod_barras ? ' | CB: ' + p.cod_barras : '')) +
      '</option>';
  }

  function botonProductoHTML(p) {
    return '<button type="button" class="sales-product-option" data-id="' + String(p.id) + '" data-cb="' + escapeHtml(p.cod_barras || '') + '">' +
      '<strong>' + escapeHtml(p.nombre || '') + '</strong>' +
      '<span>' + escapeHtml(p.precio_texto || 'SIN PRECIO') + (p.cod_barras ? ' | CB: ' + escapeHtml(p.cod_barras) : '') + '</span>' +
      '</button>';
  }

  function cargarProductosEnUI(productos, seleccionado) {
    const lista = Array.isArray(productos) ? productos : [];
    if (selProd) {
      selProd.innerHTML = '<option value="" selected disabled>Seleccioná un producto</option>' + lista.map(optionProductoHTML).join('');
      if (seleccionado)
        selProd.value = String(seleccionado);
    }
    if (productoPanelSelect) {
      productoPanelSelect.innerHTML = lista.length > 0 ? lista.map(botonProductoHTML).join('') : '<div class="text-muted small px-2 py-2">Sin resultados.</div>';
      activarBotonProducto(selProd ? selProd.value : '');
      enlazarBotonesProducto();
    }
  }

  async function buscarProductosServidor(textoEntrada) {
    const textoBase = textoEntrada !== undefined ? textoEntrada : (inputProd ? inputProd.value : '');
    const datos = datosEntradaProducto(textoBase);
    const texto = datos.codigo.trim();
    if (!selProd || texto === '') {
      cargarProductosEnUI([], '');
      precioProductoSeleccionado();
      return;
    }
    const soloCodigo = modoBusquedaProducto && modoBusquedaProducto.value === 'codigo';
    if (!soloCodigo && texto.length < 2) {
      cargarProductosEnUI([], '');
      return;
    }
    const seq = ++busquedaProductosSeq;
    const params = new URLSearchParams({
      c: 'ventas',
      a: 'buscar_productos_json',
      q: texto,
      modo: soloCodigo ? 'codigo' : 'general',
      id_lista_precio: selListaPrecio ? selListaPrecio.value : ''
    });
    try {
      const respuesta = await fetch('index.php?' + params.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await respuesta.json();
      if (seq !== busquedaProductosSeq)
        return;
      cargarProductosEnUI(data && Array.isArray(data.productos) ? data.productos : [], selProd.value);
      precioProductoSeleccionado();
    } catch (e) {
      if (productoPanelSelect)
        productoPanelSelect.innerHTML = '<div class="text-danger small px-2 py-2">No se pudo buscar.</div>';
    }
  }

  function programarBusquedaProductos(texto) {
    if (busquedaProductosTimer)
      clearTimeout(busquedaProductosTimer);
    busquedaProductosTimer = setTimeout(function(){
      buscarProductosServidor(texto);
    }, 160);
  }

  function interpretarCodigoBalanza(codigo) {
    const digitos = soloDigitos(codigo);
    if (digitos.length < 8)
      return null;
    const cuerpo = digitos.length >= 13 ? digitos.slice(0, 12) : digitos;
    const pluDigitos = Math.max(1, Math.min(8, parseInt(balanzaConfig.pluDigitos || 5, 10) || 5));
    const formatos = [
      [2, pluDigitos, 12 - 2 - pluDigitos],
      [1, pluDigitos, 12 - 1 - pluDigitos],
      [2, 5, 5],
      [2, 4, 6],
      [2, 6, 4],
      [2, 3, 7],
      [1, 5, 6]
    ];
    const prefijosImporte = Array.isArray(balanzaConfig.prefijosImporte) ? balanzaConfig.prefijosImporte : ['22', '24', '26', '28'];
    const prefijosCantidad = Array.isArray(balanzaConfig.prefijosCantidad) ? balanzaConfig.prefijosCantidad : ['20', '21', '23', '25', '27', '29'];
    const modoBalanza = ['auto', 'cantidad', 'importe'].includes(balanzaConfig.modo) ? balanzaConfig.modo : 'auto';
    const valorDecimales = Math.max(0, Math.min(4, parseInt(balanzaConfig.valorDecimales || 3, 10) || 0));
    const importeDecimales = Math.max(0, Math.min(4, parseInt(balanzaConfig.importeDecimales || 2, 10) || 0));
    let mejor = null;

    formatos.forEach(function(formato) {
      const prefLen = formato[0];
      const pluLen = formato[1];
      const valLen = formato[2];
      if (valLen <= 0 || cuerpo.length < prefLen + pluLen + valLen)
        return;
      const prefijo = cuerpo.slice(0, prefLen);
      const plu = cuerpo.slice(prefLen, prefLen + pluLen);
      const valor = cuerpo.slice(prefLen + pluLen, prefLen + pluLen + valLen);
      const op = buscarOpcionPorCodigoOPlu(plu);
      if (!op)
        return;
      const raw = parseInt(valor, 10) || 0;
      if (raw <= 0)
        return;
      const precio = precioListaDeOpcion(op);
      const candidatos = [];
      candidatos.push({ modo: 'cantidad', cantidad: raw / Math.pow(10, valorDecimales), precio: precio });
      if (precio > 0)
        candidatos.push({ modo: 'importe', cantidad: (raw / Math.pow(10, importeDecimales)) / precio, precio: precio });
      candidatos.forEach(function(candidato) {
        if (candidato.cantidad <= 0 || candidato.cantidad > 9999)
          return;
        let score = 10;
        const pref2 = prefijo.slice(0, 2);
        if (modoBalanza === candidato.modo)
          score += 100;
        else if (modoBalanza !== 'auto')
          return;
        if (candidato.modo === 'importe' && prefijosImporte.includes(pref2))
          score += 50;
        if (candidato.modo === 'cantidad' && prefijosCantidad.includes(pref2))
          score += 50;
        if (candidato.modo === 'cantidad' && mejor === null)
          score += 5;
        if (mejor === null || score > mejor.score) {
          mejor = {
            score: score,
            opcion: op,
            cantidad: candidato.cantidad,
            precio: candidato.precio
          };
        }
      });
    });
    return mejor;
  }

  function seleccionarCBExacto(valor){
    const datos = datosEntradaProducto(valor);
    const codigo = datos.codigo;
    if (!selProd || codigo === '')
      return false;
    let op = buscarOpcionPorCodigoOPlu(codigo);
    let cantidad = datos.cantidad;
    let precioBalanza = 0;
    if (!op) {
      const datosBalanza = interpretarCodigoBalanza(codigo);
      if (datosBalanza) {
        op = datosBalanza.opcion;
        cantidad = datosBalanza.cantidad;
        precioBalanza = datosBalanza.precio;
      }
    }
    if (!op)
      return false;
    if (!Array.from(selProd.options).some(function(opt){ return String(opt.value) === String(op.value); }))
      selProd.appendChild(op.cloneNode(true));
    selProd.value = op.value;
    if (cantidadVenta)
      cantidadVenta.value = Number(cantidad).toFixed(3).replace(/\.?0+$/, '');
    precioProductoSeleccionado();
    if (precioBalanza > 0 && precioUnitVenta)
      precioUnitVenta.value = precioBalanza.toFixed(2);
    preservarPrecioInicial = false;
    guardarEstadoLocal();
    return true;
  }

  function activarBotonProducto(valor) {
    if (!productoPanelSelect)
      return;
    productoPanelSelect.querySelectorAll('.sales-product-option').forEach(function(btn){
      btn.classList.toggle('active', String(btn.dataset.id || '') === String(valor));
    });
  }

  function seleccionarProductoPorId(valor) {
    if (!selProd || !valor)
      return false;
    selProd.value = String(valor);
    if (!selProd.value)
      return false;
    const op = selProd.options[selProd.selectedIndex];
    if (inputProd && op)
      inputProd.value = op.getAttribute('data-nombre') || (op.textContent || '').split('|')[0].trim();
    precioProductoSeleccionado();
    activarBotonProducto(valor);
    guardarEstadoLocal();
    return true;
  }

  function enlazarBotonesProducto() {
    if (!productoPanelSelect)
      return;
    productoPanelSelect.querySelectorAll('.sales-product-option').forEach(function(btn){
      if (btn.dataset.bound === '1')
        return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', function(){
        seleccionarProductoPorId(btn.dataset.id || '');
        if (productoPanel)
          productoPanel.classList.add('d-none');
      });
    });
  }

  function enviarProductoPorCodigo(valor) {
    if (enviandoProducto || !formAgregarVenta)
      return false;
    if (!seleccionarCBExacto(valor))
      selProd.value = '';
    syncClienteHidden();
    syncTipoComprobante();
    syncListaPrecio();
    if (formAgregarVenta.requestSubmit)
      formAgregarVenta.requestSubmit();
    else
      formAgregarVenta.submit();
    return true;
  }

  function limpiarScannerBuffer() {
    scannerBuffer = '';
    scannerUltimaTecla = 0;
    if (scannerTimer) {
      clearTimeout(scannerTimer);
      scannerTimer = null;
    }
  }

  function manejarTeclaScanner(e) {
    if (e.ctrlKey || e.altKey || e.metaKey)
      return false;
    if (e.key.length !== 1)
      return false;
    const ahora = Date.now();
    const intervalo = scannerUltimaTecla > 0 ? ahora - scannerUltimaTecla : 999;
    scannerUltimaTecla = ahora;
    scannerBuffer = intervalo <= scannerMaxIntervalo ? scannerBuffer + e.key : e.key;
    if (scannerTimer)
      clearTimeout(scannerTimer);
    scannerTimer = setTimeout(function(){
      const valorActual = inputProd ? inputProd.value.trim() : '';
      const buffer = scannerBuffer;
      limpiarScannerBuffer();
      if (buffer.length >= scannerMinLargo && buffer === valorActual)
        enviarProductoPorCodigo(valorActual);
    }, scannerEsperaFinal);
    return true;
  }

  function mostrarErrorAgregar(texto) {
    if (!salesAgregarError)
      return;
    if (!texto) {
      salesAgregarError.classList.add('d-none');
      salesAgregarError.textContent = '';
      return;
    }
    salesAgregarError.textContent = texto;
    salesAgregarError.classList.remove('d-none');
  }

  async function enviarProductoAjax(formData) {
    if (enviandoProducto)
      return;
    if (!formAgregarVenta)
      return;
    enviandoProducto = true;
    mostrarErrorAgregar('');
    try {
      const respuesta = await fetch(formAgregarVenta.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      const data = await respuesta.json();
      if (!data || data.success !== true) {
        mostrarErrorAgregar(data && data.error ? data.error : 'Error al agregar el producto.');
        return;
      }
      const tbody = document.querySelector('.sales-lines-panel table.sales-table tbody');
      if (tbody && typeof data.carrito_html === 'string') {
        tbody.innerHTML = data.carrito_html;
      }
      const totalAmount = document.querySelector('.sales-total-amount');
      if (totalAmount && typeof data.total === 'string') {
        totalAmount.textContent = data.total;
      }
      const btnConfirmarComprobante = document.getElementById('btnConfirmarComprobante');
      if (btnConfirmarComprobante) {
        btnConfirmarComprobante.disabled = data.items === 0;
      }
      if (selProd) {
        selProd.value = '';
      }
      if (inputProd) {
        inputProd.value = '';
        inputProd.focus();
      }
      if (precioUnitVenta) {
        precioUnitVenta.value = '';
      }
      if (cantidadVenta) {
        cantidadVenta.value = '1';
      }
      const descuentoInput = formAgregarVenta.querySelector('input[name="descuento"]');
      if (descuentoInput) {
        descuentoInput.value = '0';
      }
    } catch (error) {
      mostrarErrorAgregar('Error de conexión. Intentá nuevamente.');
      console.error(error);
    } finally {
      enviandoProducto = false;
    }
  }

  if (formAgregarVenta) {
    formAgregarVenta.addEventListener('submit', function(event) {
      event.preventDefault();
      const formData = new FormData(formAgregarVenta);
      enviarProductoAjax(formData);
    });
  }

  function filtrarProductos(textoEntrada){
    programarBusquedaProductos(textoEntrada);
  }

  cargarEstadoLocal();
  filtrarClientes(clienteSelectorInput ? clienteSelectorInput.value : '');
  if (clienteSelectorInput && !aplicarClienteDesdeTexto())
    clienteSelectorInput.value = obtenerTextoClientePorId(selCli ? selCli.value : '1');
  if (inputProd && inputProd.value)
    filtrarProductos();
  syncTipoComprobante();
  syncClienteHidden();
  syncListaPrecio();

  if (selCli)
    selCli.addEventListener('change', syncClienteHidden);
  if (clienteSelectorInput) {
    clienteSelectorInput.addEventListener('focus', function(){
      if (clientePanel) {
        filtrarClientes(clienteSelectorInput.value);
        clientePanel.classList.remove('d-none');
      }
    });
    clienteSelectorInput.addEventListener('click', function(){
      if (clientePanel) {
        filtrarClientes(clienteSelectorInput.value);
        clientePanel.classList.remove('d-none');
      }
    });
    clienteSelectorInput.addEventListener('input', function(){
      if (inputCli)
        inputCli.value = clienteSelectorInput.value;
      filtrarClientes(clienteSelectorInput.value);
      if (clientePanel)
        clientePanel.classList.remove('d-none');
      aplicarClienteDesdeTexto();
      syncClienteHidden();
    });
    clienteSelectorInput.addEventListener('change', function(){
      if (!aplicarClienteDesdeTexto() && selCli)
        clienteSelectorInput.value = obtenerTextoClientePorId(selCli.value);
      syncClienteHidden();
    });
    clienteSelectorInput.addEventListener('keydown', function(e){
      if (e.key === 'Enter') {
        e.preventDefault();
        if (!aplicarClienteDesdeTexto() && clientePanelSelect && clientePanelSelect.querySelectorAll('.sales-client-option').length === 1) {
          activarBotonCliente(clientePanelSelect.querySelector('.sales-client-option').dataset.id || '1');
          seleccionarClienteDesdePanel();
        }
      }
    });
  }
  enlazarBotonesCliente();
  if (tipoComprobanteTop)
    tipoComprobanteTop.addEventListener('change', syncTipoComprobante);
  if (inputProd && selProd) {
    if (modoBusquedaProducto) {
      modoBusquedaProducto.addEventListener('change', function(){
        inputProd.placeholder = modoBusquedaProducto.value === 'codigo' ? 'Codigo de barras...' : 'Nombre o codigo de barras...';
        filtrarProductos(inputProd.value);
        inputProd.focus();
      });
    }
    inputProd.addEventListener('focus', function(){
      filtrarProductos(inputProd.value);
      if (productoPanel)
        productoPanel.classList.remove('d-none');
    });
    inputProd.addEventListener('click', function(){
      filtrarProductos(inputProd.value);
      if (productoPanel)
        productoPanel.classList.remove('d-none');
    });
    inputProd.addEventListener('input', function(){
      const valor = inputProd.value.trim();
      if (productoPanel)
        productoPanel.classList.remove('d-none');
      filtrarProductos(valor);
    });
    inputProd.addEventListener('keydown', function(e){
      if (e.key !== 'Enter')
        manejarTeclaScanner(e);
      if (e.key === 'Enter') {
        e.preventDefault();
        limpiarScannerBuffer();
        const valor = inputProd.value.trim();
        const datosProducto = datosEntradaProducto(valor);
        const pareceCodigo = (modoBusquedaProducto && modoBusquedaProducto.value === 'codigo') || /^\d+$/.test(datosProducto.codigo.replace(/\s+/g, ''));
        if (!(pareceCodigo && enviarProductoPorCodigo(valor))) {
          if (productoPanelSelect && productoPanelSelect.querySelectorAll('.sales-product-option').length === 1) {
            seleccionarProductoPorId(productoPanelSelect.querySelector('.sales-product-option').dataset.id || '');
            if (productoPanel)
              productoPanel.classList.add('d-none');
          }
          filtrarProductos(valor);
        }
      }
    });
    selProd.addEventListener('change', function(){
      precioProductoSeleccionado();
      guardarEstadoLocal();
    });
  }
  enlazarBotonesProducto();
  if (selListaPrecio)
    selListaPrecio.addEventListener('change', syncListaPrecio);
  if (formAplicarListaVenta)
    formAplicarListaVenta.addEventListener('submit', function(){
      syncClienteHidden();
      syncTipoComprobante();
      syncListaPrecio();
    });
  function renderVencimientosVenta() {
    if (!ccCuotasVenta || !ccVencimientosVenta)
      return;
    const cantidad = Math.max(1, Math.min(36, parseInt(ccCuotasVenta.value || '1', 10) || 1));
    ccVencimientosVenta.innerHTML = '';
    const base = new Date();
    for (let i = 0; i < cantidad; i++) {
      const fecha = new Date(base.getFullYear(), base.getMonth() + i, base.getDate());
      const yyyy = fecha.getFullYear();
      const mm = String(fecha.getMonth() + 1).padStart(2, '0');
      const dd = String(fecha.getDate()).padStart(2, '0');
      const wrap = document.createElement('label');
      wrap.className = 'sales-cc-date';
      wrap.innerHTML = '<span>Vto. ' + (i + 1) + '</span><input type="date" class="form-control" name="cc_vencimientos[]" value="' + yyyy + '-' + mm + '-' + dd + '">';
      ccVencimientosVenta.appendChild(wrap);
    }
  }
  function syncFormaPagoVenta() {
    const esCc = formaPagoVenta && formaPagoVenta.value === 'cuenta_corriente';
    const esSaldoFavor = formaPagoVenta && formaPagoVenta.value === 'saldo_favor';
    if (cuentaCorrienteVentaPanel)
      cuentaCorrienteVentaPanel.classList.toggle('d-none', !esCc);
    if (esCc)
      renderVencimientosVenta();
    if (saldoFavorVentaInfo) {
      const cliente = clientesData.find(function(c){ return selCli && String(c.id) === String(selCli.value || '1'); });
      const saldo = cliente ? (parseFloat(cliente.saldo_favor || 0) || 0) : 0;
      saldoFavorVentaInfo.textContent = 'Disponible: $ ' + saldo.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      saldoFavorVentaInfo.classList.toggle('d-none', !esSaldoFavor);
    }
  }
  if (formaPagoVenta)
    formaPagoVenta.addEventListener('change', syncFormaPagoVenta);
  if (ccCuotasVenta)
    ccCuotasVenta.addEventListener('input', renderVencimientosVenta);
  syncFormaPagoVenta();
  function abrirModalFormaPago() {
    if (modalFormaPago)
      modalFormaPago.classList.remove('d-none');
    if (impresoraVentaPanel)
      impresoraVentaPanel.classList.add('d-none');
    syncFormaPagoVenta();
  }
  function cerrarFormaPago() {
    if (modalFormaPago)
      modalFormaPago.classList.add('d-none');
  }
  function copiarFormaPagoYEnviar(imprimir) {
    if (!formConfirmarBottom)
      return;
    if (formaPagoHidden && formaPagoVenta)
      formaPagoHidden.value = formaPagoVenta.value;
    if (imprimirTicketHidden)
      imprimirTicketHidden.value = imprimir ? '1' : '0';
    if (formConfirmarBottom)
      formConfirmarBottom.target = imprimir ? '_blank' : '_self';
    if (ccVencimientosHidden)
      ccVencimientosHidden.innerHTML = '';
    if (formaPagoVenta && formaPagoVenta.value === 'cuenta_corriente' && ccVencimientosHidden) {
      if (ccCuotasVenta) {
        const inputCuotas = document.createElement('input');
        inputCuotas.type = 'hidden';
        inputCuotas.name = 'cc_cuotas';
        inputCuotas.value = ccCuotasVenta.value || '1';
        ccVencimientosHidden.appendChild(inputCuotas);
      }
      if (ccVencimientosVenta) {
        ccVencimientosVenta.querySelectorAll('input[type="date"]').forEach(function(inp){
          const hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = 'cc_vencimientos[]';
          hidden.value = inp.value;
          ccVencimientosHidden.appendChild(hidden);
        });
      }
    }
    if (formConfirmarBottom.requestSubmit)
      formConfirmarBottom.requestSubmit();
    else
      formConfirmarBottom.submit();
    if (imprimir)
      setTimeout(function () {
        window.location.href = 'index.php?c=ventas&a=nueva';
      }, 700);
  }
  async function cargarImpresorasVenta() {
    if (!impresoraVentaPanel || !impresoraVentaSelect)
      return;
    impresoraVentaPanel.classList.remove('d-none');
    impresoraVentaSelect.innerHTML = '<option value="">Cargando impresoras...</option>';
    try {
      const resp = await fetch('index.php?c=ventas&a=impresoras_json');
      const data = await resp.json();
      const impresoras = data.impresoras || [];
      if (impresoras.length === 0) {
        impresoraVentaSelect.innerHTML = '<option value="">No se detectaron impresoras</option>';
        return;
      }
      impresoraVentaSelect.innerHTML = impresoras.map(function(nombre){
        return '<option value="' + nombre.replace(/"/g, '&quot;') + '">' + nombre + '</option>';
      }).join('');
    } catch (e) {
      impresoraVentaSelect.innerHTML = '<option value="">No se pudieron cargar impresoras</option>';
    }
  }
  if (btnConfirmarComprobante)
    btnConfirmarComprobante.addEventListener('click', abrirModalFormaPago);
  if (cerrarModalFormaPago)
    cerrarModalFormaPago.addEventListener('click', cerrarFormaPago);
  if (modalFormaPago)
    modalFormaPago.addEventListener('click', function(e){
      if (e.target === modalFormaPago)
        cerrarFormaPago();
    });
  if (guardarVentaSinImprimir)
    guardarVentaSinImprimir.addEventListener('click', function(){ copiarFormaPagoYEnviar(false); });
  if (guardarVentaImprimir)
    guardarVentaImprimir.addEventListener('click', function(){
      copiarFormaPagoYEnviar(true);
    });
  document.addEventListener('click', function(e){
    const objetivo = e.target;
    if (clientePanel && !clientePanel.classList.contains('d-none')) {
      if (!(clientePanel.contains(objetivo) || (clienteSelectorInput && clienteSelectorInput.contains(objetivo))))
        clientePanel.classList.add('d-none');
    }
    if (productoPanel && !productoPanel.classList.contains('d-none')) {
      if (!(productoPanel.contains(objetivo) || (inputProd && inputProd.contains(objetivo))))
        productoPanel.classList.add('d-none');
    }
  });
  document.querySelectorAll('#formAgregarVenta input, #formAgregarVenta select').forEach(function(el){
    if (el.name === 'cantidad' || el.name === 'descuento' || el.name === 'id_producto' || el.name === 'buscar_producto')
      return;
    el.addEventListener('change', guardarEstadoLocal);
    el.addEventListener('input', guardarEstadoLocal);
  });
})();
</script>
