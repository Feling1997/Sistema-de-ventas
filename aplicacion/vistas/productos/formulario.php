<?php
$es_editar = false;
$accion = "index.php?c=productos&a=crear";
$titulo = "Nuevo producto";
$texto_btn = "Crear";
if (isset($modo) && $modo === "editar") {
    $es_editar = true;
    $accion = "index.php?c=productos&a=actualizar";
    $titulo = "Editar producto";
    $texto_btn = "Guardar cambios";
}
$id = (int)($p["id"] ?? 0);
$nombre = (string)($p["nombre"] ?? "");
$cod_barras = (string)($p["cod_barras"] ?? "");
$id_stock = $p["id_stock"] ?? null;
if ($id_stock !== null)
    $id_stock = (int)$id_stock;
$stock_fijo = false;
$id_stock_get = (int)($_GET["id_stock"] ?? 0);
if ($id_stock_get > 0) {
    $stock_fijo = true;
    $id_stock = $id_stock_get;
}
$factor_conversion = numero_para_input($p["factor_conversion"] ?? "1", 4);
$ganancia = numero_para_input($p["ganancia"] ?? "0", 2);
$precio_final_num = numero_para_input($p["precio_final"] ?? "0", 2);
$precio_final = precio_para_mostrar($p["precio_final"] ?? "0");
$activo = (int)($p["activo"] ?? 1);
$usa_stock_general = (int)($p["usa_stock_general"] ?? 0) === 1;
$stock_unidad = (string)($p["stock_unidad"] ?? "u");
$stock_cantidad = numero_para_input($p["stock_cantidad"] ?? "0", 4);
$stock_moneda_costo = strtoupper((string)($p["stock_moneda_costo"] ?? "ARS")) === "USD" ? "USD" : "ARS";
$stock_precio_costo = numero_para_input($p["stock_costo_origen"] ?? $p["stock_precio_costo"] ?? "0", 2);
$stock_minimo = numero_para_input($p["stock_minimo"] ?? "0", 4);
$stock_maximo = numero_para_input($p["stock_maximo"] ?? "0", 4);
$listas_precios = $listas_precios ?? [];
$precios_producto = $precios_producto ?? [];
$stocks = $stocks ?? [];
$unidades_medida = $unidades_medida ?? [];
$unidad_abbr_label = static function(string $abbr): string {
    $abbr = trim($abbr);
    return $abbr !== "" ? ucfirst($abbr) : "";
};
$unidad_option_label = static function(array $u) use ($unidad_abbr_label): string {
    $nombre = trim((string)($u["nombre"] ?? ""));
    $abbr = trim((string)($u["abreviatura"] ?? ""));
    $nombre_label = $nombre !== "" ? ucfirst($nombre) : $unidad_abbr_label($abbr);
    if ($nombre_label !== "" && $abbr !== "" && strtolower($nombre_label) !== strtolower($abbr))
        return $nombre_label . " (" . $abbr . ")";
    return $nombre_label !== "" ? $nombre_label : $unidad_abbr_label($abbr);
};
$precio_costo_visible = number_format(parsear_numero_form($stock_precio_costo, 0), 2, ",", "");
$stock_seleccionado = null;
foreach ($stocks as $stock_item) {
    if ($id_stock !== null && (int)$stock_item["id"] === $id_stock) {
        $stock_seleccionado = $stock_item;
        break;
    }
}
$stock_info_cantidad = $stock_seleccionado !== null ? stock_para_mostrar($stock_seleccionado["cantidad"] ?? 0, 4) : "0";
$stock_info_costo = $stock_seleccionado !== null ? precio_para_mostrar($stock_seleccionado["precio_costo"] ?? 0) : "$ 0";
$stock_info_unidad = $stock_seleccionado !== null ? (string)($stock_seleccionado["unidad"] ?? "u") : "u";
$stock_info_decimales = $stock_seleccionado !== null ? (int)($stock_seleccionado["unidad_decimales"] ?? 3) : 3;
$stock_info_nombre = $stock_seleccionado !== null ? ("#" . (int)$stock_seleccionado["id"] . " - " . (string)$stock_seleccionado["nombre"] . " (" . $stock_info_unidad . ")") : "Selecciona un stock general";
?>
<div class="d-flex justify-content-between align-items-center mb-2 section-heading product-form-heading">
  <div>
    <h3 class="mb-1"><?= htmlspecialchars($titulo) ?></h3>
  </div>
  <a class="btn btn-outline-secondary" href="index.php?c=productos&a=index">Volver</a>
</div>
<div class="card form-shell product-form-shell">
  <div class="card-body p-4">
    <form method="POST" action="<?= htmlspecialchars($accion) ?>" class="smart-form product-form">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <?php if ($es_editar): ?>
        <input type="hidden" name="id" value="<?= $id ?>">
      <?php endif; ?>
      <div class="row g-2">
        <div class="col-md-6">
          <label class="form-label">Nombre *</label>
          <input class="form-control form-control-lg" name="nombre" value="<?= htmlspecialchars($nombre) ?>" placeholder="Ej: Gaseosa 500 ml" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Codigo del producto</label>
          <input class="form-control form-control-lg" name="cod_barras" value="<?= htmlspecialchars($cod_barras) ?>" placeholder="Opcional. Si lo dejas vacio, se genera solo">
          <div class="form-text">Solo hace falta si usas lector o queres un codigo manual.</div>
        </div>
      </div>

      <div class="mt-3">
        <label class="form-label">Tipo de stock</label>
        <div class="stock-mode-grid mb-2">
          <label class="stock-mode-card">
            <input class="form-check-input" type="radio" name="usa_stock_general" value="0" <?= !$usa_stock_general ? "checked" : "" ?> <?= $stock_fijo ? "disabled" : "" ?>>
            <span class="stock-mode-icon"><i class="bi bi-box-seam-fill"></i></span>
            <span>
              <strong>Stock propio</strong>
              <small>El producto maneja su propio stock.</small>
            </span>
          </label>
          <label class="stock-mode-card">
            <input class="form-check-input" type="radio" name="usa_stock_general" value="1" <?= $usa_stock_general ? "checked" : "" ?> <?= $stock_fijo ? "disabled" : "" ?>>
            <span class="stock-mode-icon"><i class="bi bi-link-45deg"></i></span>
            <span>
              <strong>Asociar stock general</strong>
              <small>El producto consume de un stock principal.</small>
            </span>
          </label>
        </div>
        <?php if ($stock_fijo): ?>
          <input type="hidden" name="usa_stock_general" value="1">
        <?php endif; ?>
      </div>

      <div class="stock-own-fields row g-2 mt-1">
        <div class="col-12">
          <div class="stock-section-title"><i class="bi bi-box-seam-fill"></i> Stock propio del producto</div>
          <div class="stock-example">Ejemplo: Coca Cola 2L maneja sus propias unidades.</div>
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label">Stock</label>
          <input type="number" step="0.0001" min="0" class="form-control form-control-lg" name="cantidad_stock" value="<?= htmlspecialchars($stock_cantidad) ?>">
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label">Stock minimo</label>
          <input type="number" step="0.0001" min="0" class="form-control form-control-lg" name="stock_minimo" value="<?= htmlspecialchars($stock_minimo) ?>">
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label">Stock maximo</label>
          <input type="number" step="0.0001" min="0" class="form-control form-control-lg" name="stock_maximo" value="<?= htmlspecialchars($stock_maximo) ?>">
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label">Unidad</label>
          <select class="form-select form-select-lg" name="unidad_stock" data-unidad-select>
            <?php $unidad_en_lista = false; ?>
            <?php foreach ($unidades_medida as $u): ?>
              <?php $abbr = (string)($u["abreviatura"] ?? ""); ?>
              <?php $unidad_label = $unidad_option_label($u); ?>
              <?php $unidad_short_label = $unidad_abbr_label($abbr); ?>
              <?php if ($stock_unidad === $abbr) $unidad_en_lista = true; ?>
              <option value="<?= htmlspecialchars($abbr) ?>" data-decimales="<?= (int)($u["decimales"] ?? 0) ?>" data-full-label="<?= htmlspecialchars($unidad_label) ?>" data-short-label="<?= htmlspecialchars($unidad_short_label) ?>" <?= $stock_unidad === $abbr ? "selected" : "" ?>>
                <?= htmlspecialchars($unidad_label) ?>
              </option>
            <?php endforeach; ?>
            <?php if (!$unidad_en_lista && $stock_unidad !== ""): ?>
              <?php $unidad_fallback_label = $unidad_abbr_label($stock_unidad); ?>
              <option value="<?= htmlspecialchars($stock_unidad) ?>" data-full-label="<?= htmlspecialchars($unidad_fallback_label) ?>" data-short-label="<?= htmlspecialchars($unidad_fallback_label) ?>" selected><?= htmlspecialchars($unidad_fallback_label) ?></option>
            <?php endif; ?>
            <option value="" disabled>----------------</option>
            <option value="__otra_unidad__">Otro...</option>
          </select>
          <input class="form-control form-control-lg mt-2 d-none" name="nueva_unidad_simple" data-nueva-unidad-simple placeholder="Nueva unidad, ej: maple">
          <div class="form-help-visible d-none" data-unidad-feedback></div>
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label">Moneda costo</label>
          <select class="form-select form-select-lg" name="moneda_costo">
            <option value="ARS" <?= $stock_moneda_costo === "ARS" ? "selected" : "" ?>>Pesos</option>
            <option value="USD" <?= $stock_moneda_costo === "USD" ? "selected" : "" ?>>Dolares</option>
          </select>
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label">Costo origen</label>
          <input type="text" inputmode="decimal" class="form-control form-control-lg money-input" name="precio_costo" value="<?= htmlspecialchars($precio_costo_visible) ?>" placeholder="1500,00">
          <div class="form-text">USD actual: <?= htmlspecialchars(precio_para_mostrar(Stock::cotizacion_dolar())) ?></div>
        </div>
      </div>

      <div class="stock-general-fields mt-2" id="stockGeneralBox">
        <div class="stock-section-title"><i class="bi bi-link-45deg"></i> Asociado a stock general</div>
        <div class="stock-example">Ejemplo: Yerba 500gr consume 0.5 Kg de Yerba stock general.</div>
        <div class="row g-2 mt-1">
          <div class="col-lg-6">
            <label class="form-label">Stock general asociado</label>
            <?php if (!$stock_fijo): ?>
              <input type="text" class="form-control form-control-lg" id="buscarStockProducto" placeholder="Buscar stock...">
              <div class="stock-live-list" id="stockResultadosLista"></div>
            <?php endif; ?>
            <select class="form-select form-select-lg stock-select-native" name="id_stock" data-stock-fixed="<?= $stock_fijo ? "1" : "0" ?>" <?= $stock_fijo ? "disabled" : "" ?>>
              <option value="" <?= $id_stock === null ? "selected" : "" ?>>Seleccionar stock general</option>
              <?php foreach ($stocks as $s): ?>
                <?php $sid = (int)$s["id"]; ?>
                <option value="<?= $sid ?>" data-costo="<?= htmlspecialchars(numero_para_input($s["precio_costo"] ?? "0", 4)) ?>" data-cantidad="<?= htmlspecialchars(numero_para_input($s["cantidad"] ?? "0", 4)) ?>" data-unidad="<?= htmlspecialchars((string)($s["unidad"] ?? "u")) ?>" data-decimales="<?= (int)($s["unidad_decimales"] ?? 3) ?>" data-nombre="<?= htmlspecialchars((string)($s["nombre"] ?? "")) ?>" <?= ($id_stock !== null && $id_stock === $sid) ? "selected" : "" ?>>
                  #<?= $sid ?> - <?= htmlspecialchars($s["nombre"]) ?> (<?= htmlspecialchars($s["unidad"]) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <?php if ($stock_fijo): ?>
              <input type="hidden" name="id_stock" value="<?= (int)$id_stock ?>">
            <?php endif; ?>
          </div>
          <div class="col-lg-6">
            <div class="stock-readonly-card">
              <div>
                <span>Stock disponible</span>
                <strong id="stockInfoCantidad"><?= htmlspecialchars(stock_para_mostrar($stock_seleccionado["cantidad"] ?? 0, $stock_info_decimales)) ?> <?= htmlspecialchars($stock_info_unidad) ?></strong>
              </div>
              <div>
                <span>Costo actual</span>
                <strong id="stockInfoCosto"><?= htmlspecialchars($stock_info_costo) ?></strong>
              </div>
              <div>
                <span>Unidad</span>
                <strong id="stockInfoUnidad"><?= htmlspecialchars($stock_info_unidad) ?></strong>
              </div>
              <div class="stock-selected-line" id="stockInfoNombre"><?= htmlspecialchars($stock_info_nombre) ?></div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3 factor-conversion-row">
            <label class="form-label"><i class="bi bi-sliders"></i> Consumo por venta</label>
            <div class="input-group input-group-lg">
              <input type="number" step="any" min="0.0001" class="form-control" name="consumo_por_venta" value="<?= htmlspecialchars($factor_conversion) ?>">
              <span class="input-group-text" id="factorUnidadLabel"><?= htmlspecialchars($stock_info_unidad) ?></span>
            </div>
            <div class="form-help-visible">Cada venta de este producto descontara esta cantidad del stock general.</div>
          </div>
        </div>
      </div>

      <div class="row g-2 mt-1">
        <?php if (count($listas_precios) === 0): ?>
          <div class="col-md-6">
            <label class="form-label">Ganancia (%)</label>
            <input type="number" step="0.01" class="form-control form-control-lg" name="ganancia" value="<?= htmlspecialchars($ganancia) ?>">
            <div class="form-text">Si no queres complicarte, deja 0 y ajustalo despues.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Precio</label>
            <input type="number" step="0.01" min="0" class="form-control form-control-lg" name="precio_final_manual" value="<?= htmlspecialchars($precio_final_num) ?>" placeholder="Precio">
            <div class="form-text">Se usa cuando el producto no tiene stock asociado.</div>
          </div>
        <?php else: ?>
          <input type="hidden" name="ganancia" value="<?= htmlspecialchars($ganancia) ?>">
          <input type="hidden" name="precio_final_manual" value="<?= htmlspecialchars($precio_final_num) ?>">
        <?php endif; ?>
      </div>

      <?php if (count($listas_precios) > 0): ?>
        <div class="mt-3">
          <label class="form-label">Listas de precios</label>
          <div class="form-text">Se mostraran todas las listas activas automaticamente. Si agregas otra lista, recarga el formulario.</div>
          <div class="price-list-grid">
            <?php foreach ($listas_precios as $lista): ?>
              <?php
                $id_lista = (int)$lista["id"];
                $es_lista_costo = ListaPrecio::es_lista_costo($lista);
                if ($es_lista_costo)
                  continue;
                $es_lista_publico = ListaPrecio::es_lista_publico($lista);
                $precio_lista_valor = (float)($precios_producto[$id_lista]["precio"] ?? 0);
                if ($precio_lista_valor <= 0 && $es_lista_costo)
                  $precio_lista_valor = (float)($p["stock_precio_costo"] ?? 0);
                $precio_lista = numero_para_input($precio_lista_valor, 2);
                $porcentaje_lista = $es_lista_costo ? "0" : numero_para_input($precios_producto[$id_lista]["porcentaje"] ?? 0, 2);
              ?>
              <div class="price-list-row" data-lista-costo="<?= $es_lista_costo ? "1" : "0" ?>" data-lista-publico="<?= $es_lista_publico ? "1" : "0" ?>">
                <div class="price-list-name">
                  <strong><?= htmlspecialchars($lista["nombre"] ?? "") ?></strong>
                </div>
                <div class="price-list-field-grid">
                  <div class="price-list-field">
                    <label>Precio</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-lg price-value" name="precio_lista[<?= $id_lista ?>]" value="<?= htmlspecialchars($precio_lista) ?>" placeholder="Precio" <?= $es_lista_costo ? "readonly" : "" ?>>
                  </div>
                  <div class="price-list-field">
                    <label>%</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-lg price-percent" name="porcentaje_lista[<?= $id_lista ?>]" value="<?= htmlspecialchars($porcentaje_lista) ?>" placeholder="Porcentaje" <?= $es_lista_costo ? "readonly" : "" ?>>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="product-summary mt-4 mb-4">
        <?php if (count($listas_precios) === 0): ?>
          <div class="product-summary-item">
            <span class="product-summary-label">Precio</span>
            <strong id="precioFinalPreview"><?= htmlspecialchars($precio_final) ?></strong>
          </div>
        <?php else: ?>
          <strong id="precioFinalPreview" class="d-none"><?= htmlspecialchars($precio_final) ?></strong>
        <?php endif; ?>
        <div class="product-summary-item">
          <span class="product-summary-label">Estado</span>
          <?php if ($es_editar): ?>
            <select class="form-select form-select-lg" name="activo">
              <option value="1" <?= ($activo === 1) ? "selected" : "" ?>>Alta</option>
              <option value="0" <?= ($activo === 0) ? "selected" : "" ?>>Baja</option>
            </select>
          <?php else: ?>
            <input type="hidden" name="activo" value="1">
            <strong>Alta</strong>
          <?php endif; ?>
        </div>
      </div>
      <div class="form-actions">
        <a class="btn btn-outline-secondary" href="index.php?c=productos&a=index">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg px-4"><?= htmlspecialchars($texto_btn) ?></button>
      </div>
    </form>
  </div>
</div>
<script>
(function () {
  const input = document.getElementById('buscarStockProducto');
  const select = document.querySelector('select[name="id_stock"]');
  const lista = document.getElementById('stockResultadosLista');
  const radiosStock = document.querySelectorAll('input[name="usa_stock_general"]');
  const stockGeneralBox = document.getElementById('stockGeneralBox');
  const stockOwnBox = document.querySelector('.stock-own-fields');
  const factor = document.querySelector('input[name="consumo_por_venta"], input[name="factor_conversion"]');
  const ganancia = document.querySelector('input[name="ganancia"]');
  const precioManual = document.querySelector('input[name="precio_final_manual"]');
  const precioCosto = document.querySelector('input[name="precio_costo"]');
  const monedaCosto = document.querySelector('select[name="moneda_costo"]');
  const cotizacionDolar = <?= json_encode(Stock::cotizacion_dolar()) ?>;
  const preview = document.getElementById('precioFinalPreview');
  const stockInfoCantidad = document.getElementById('stockInfoCantidad');
  const stockInfoCosto = document.getElementById('stockInfoCosto');
  const stockInfoUnidad = document.getElementById('stockInfoUnidad');
  const stockInfoNombre = document.getElementById('stockInfoNombre');
  const factorUnidadLabel = document.getElementById('factorUnidadLabel');
  const unidadSelect = document.querySelector('[data-unidad-select]');
  if (!select)
    return;
  const opcionesStock = Array.from(select.options).filter(function (op) { return op.value; });

  function numeroSeguro(valor) {
    const normalizado = String(valor || '').replace(/\s/g, '').replace(',', '.');
    const numero = parseFloat(normalizado);
    return Number.isFinite(numero) ? numero : 0;
  }

  function dinero(valor) {
    const numero = numeroSeguro(valor);
    return numero > 0 ? '$ ' + numero.toLocaleString('es-PY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '$ 0';
  }

  function stockTexto(valor, decimales) {
    const numero = numeroSeguro(valor);
    const dec = Math.max(0, Math.min(4, parseInt(decimales || 3, 10) || 0));
    return numero.toLocaleString('es-PY', { minimumFractionDigits: 0, maximumFractionDigits: dec });
  }

  function usaStockGeneral() {
    return document.querySelector('input[name="usa_stock_general"]:checked')?.value === '1';
  }

  function opcionSeleccionada() {
    return select.options[select.selectedIndex] || null;
  }

  function actualizarInfoStock() {
    const op = opcionSeleccionada();
    const unidad = op && op.value ? (op.getAttribute('data-unidad') || 'u') : 'u';
    const decimales = op && op.value ? (op.getAttribute('data-decimales') || '3') : '3';
    const cantidad = op && op.value ? op.getAttribute('data-cantidad') : '0';
    const costo = op && op.value ? op.getAttribute('data-costo') : '0';
    const nombre = op && op.value ? (op.textContent || '').trim() : 'Selecciona un stock general';
    if (stockInfoCantidad)
      stockInfoCantidad.textContent = stockTexto(cantidad, decimales) + ' ' + unidad;
    if (stockInfoCosto)
      stockInfoCosto.textContent = dinero(costo);
    if (stockInfoUnidad)
      stockInfoUnidad.textContent = unidad;
    if (stockInfoNombre)
      stockInfoNombre.textContent = nombre;
    if (factorUnidadLabel)
      factorUnidadLabel.textContent = unidad;
  }

  function actualizarPreview() {
    if (!preview || !select)
      return;
    const op = opcionSeleccionada();
    let costo = usaStockGeneral() ? (op ? numeroSeguro(op.getAttribute('data-costo')) : 0) : (precioCosto ? numeroSeguro(precioCosto.value) : 0);
    if (!usaStockGeneral() && monedaCosto && monedaCosto.value === 'USD')
      costo *= numeroSeguro(cotizacionDolar);
    const factorValor = usaStockGeneral() ? (factor ? numeroSeguro(factor.value) : 0) : 1;
    const gananciaValor = ganancia ? numeroSeguro(ganancia.value) : 0;
    let precio = (costo * factorValor) * (1 + (gananciaValor / 100));
    if (precio <= 0 && precioManual)
      precio = numeroSeguro(precioManual.value);
    preview.textContent = precio > 0 ? '$ ' + precio.toLocaleString('es-PY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : 'SIN PRECIO';
  }

  function alternarTipoStock() {
    const usaGeneral = usaStockGeneral();
    if (stockGeneralBox)
      stockGeneralBox.style.display = usaGeneral ? '' : 'none';
    if (stockOwnBox)
      stockOwnBox.style.display = usaGeneral ? 'none' : '';
    if (stockGeneralBox) {
      stockGeneralBox.querySelectorAll('input, select, textarea, button').forEach(function(el) {
        if (el.type !== 'hidden' && el.dataset.stockFixed !== '1')
          el.disabled = !usaGeneral;
      });
    }
    if (stockOwnBox) {
      stockOwnBox.querySelectorAll('input, select, textarea, button').forEach(function(el) {
        if (el.type !== 'hidden')
          el.disabled = usaGeneral;
      });
    }
    document.querySelectorAll('.stock-mode-card').forEach(function(card) {
      const radio = card.querySelector('input[type="radio"]');
      card.classList.toggle('is-selected', !!radio && radio.checked);
    });
    if (!usaGeneral && factor)
      factor.value = 1;
    actualizarInfoStock();
    actualizarPreview();
  }

  function configurarNuevaUnidad() {
    if (!unidadSelect)
      return;
    const feedback = document.querySelector('[data-unidad-feedback]');
    const inputOtro = document.querySelector('[data-nueva-unidad-simple]');
    function capitalizarUnidad(texto) {
      texto = String(texto || '').trim();
      return texto ? texto.charAt(0).toUpperCase() + texto.slice(1) : '';
    }
    function opcionUnidadNormal(op) {
      return op && op.value && op.value !== '__otra_unidad__' && !op.disabled;
    }
    function mostrarOpcionesUnidadCompletas() {
      Array.from(unidadSelect.options).forEach(function(op) {
        if (opcionUnidadNormal(op))
          op.textContent = op.dataset.fullLabel || capitalizarUnidad(op.value);
      });
    }
    function mostrarUnidadSeleccionadaCorta() {
      mostrarOpcionesUnidadCompletas();
      const op = unidadSelect.options[unidadSelect.selectedIndex] || null;
      if (opcionUnidadNormal(op))
        op.textContent = op.dataset.shortLabel || capitalizarUnidad(op.value);
    }
    function alternarOtro() {
      const esOtra = unidadSelect.value === '__otra_unidad__';
      if (inputOtro) {
        inputOtro.classList.toggle('d-none', !esOtra);
        inputOtro.disabled = !esOtra;
        if (esOtra)
          inputOtro.focus();
      }
      if (feedback) {
        feedback.textContent = esOtra ? 'Se guardara como nueva unidad al crear el producto.' : '';
        feedback.classList.toggle('d-none', !esOtra);
      }
    }
    unidadSelect.addEventListener('focus', function () {
      mostrarOpcionesUnidadCompletas();
    });
    unidadSelect.addEventListener('mousedown', mostrarOpcionesUnidadCompletas);
    unidadSelect.addEventListener('click', mostrarOpcionesUnidadCompletas);
    unidadSelect.addEventListener('blur', mostrarUnidadSeleccionadaCorta);
    unidadSelect.addEventListener('change', function () {
      alternarOtro();
      if (unidadSelect.value !== '__otra_unidad__')
        window.setTimeout(mostrarUnidadSeleccionadaCorta, 0);
    });
    const formProducto = unidadSelect.closest('form');
    if (formProducto) {
      formProducto.addEventListener('submit', function() {
        if (inputOtro && unidadSelect.value === '__otra_unidad__')
          inputOtro.value = capitalizarUnidad(inputOtro.value);
      });
    }
    alternarOtro();
    mostrarUnidadSeleccionadaCorta();
  }

  function seleccionarStock(valor) {
    select.value = valor;
    const op = opcionSeleccionada();
    if (input && op)
      input.value = (op.textContent || '').trim();
    if (lista)
      lista.innerHTML = '';
    actualizarInfoStock();
    actualizarPreview();
  }

  function renderStocks(filtro) {
    if (!lista)
      return;
    const texto = String(filtro || '').toLowerCase().trim();
    const resultados = opcionesStock.filter(function (op) {
      return texto === '' || (op.textContent || '').toLowerCase().includes(texto);
    }).slice(0, 8);
    lista.innerHTML = '';
    resultados.forEach(function (op) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'stock-live-option' + (op.value === select.value ? ' is-selected' : '');
      btn.dataset.value = op.value;
      btn.innerHTML = '<span>' + (op.textContent || '').trim() + '</span>';
      btn.addEventListener('click', function () {
        seleccionarStock(op.value);
      });
      lista.appendChild(btn);
    });
    if (resultados.length === 0) {
      const vacio = document.createElement('div');
      vacio.className = 'stock-live-empty';
      vacio.textContent = 'Sin resultados';
      lista.appendChild(vacio);
    }
  }

  if (input) {
    const opInicial = opcionSeleccionada();
    if (opInicial && opInicial.value)
      input.value = (opInicial.textContent || '').trim();
    input.addEventListener('input', function () {
      renderStocks(input.value);
    });
    input.addEventListener('focus', function () {
      renderStocks(input.value);
    });
  }
  select.addEventListener('change', function () {
    actualizarInfoStock();
    actualizarPreview();
  });
  if (factor)
    factor.addEventListener('input', actualizarPreview);
  if (ganancia)
    ganancia.addEventListener('input', actualizarPreview);
  if (precioManual)
    precioManual.addEventListener('input', actualizarPreview);
  if (precioCosto)
    precioCosto.addEventListener('input', actualizarPreview);
  if (monedaCosto)
    monedaCosto.addEventListener('change', actualizarPreview);
  document.querySelectorAll('.price-list-row').forEach(function(row) {
    const pct = row.querySelector('.price-percent');
    const val = row.querySelector('.price-value');
    const esCosto = row.getAttribute('data-lista-costo') === '1';
    function costoBase() {
      const op = opcionSeleccionada();
      let costo = usaStockGeneral() ? (op ? numeroSeguro(op.getAttribute('data-costo')) : 0) : (precioCosto ? numeroSeguro(precioCosto.value) : 0);
      if (!usaStockGeneral() && monedaCosto && monedaCosto.value === 'USD')
        costo *= numeroSeguro(cotizacionDolar);
      return costo;
    }
    function actualizarFilaBase() {
      if (!pct || !val)
        return;
      const costo = costoBase();
      if (esCosto) {
        pct.value = '0';
        val.value = costo > 0 ? costo.toFixed(2) : '0';
        return;
      }
      const porcentaje = numeroSeguro(pct.value);
      if (costo > 0 && porcentaje >= 0) {
        val.value = (costo * (1 + (porcentaje / 100))).toFixed(2);
      }
    }
    if (pct && val) {
      pct.addEventListener('input', function() {
        if (esCosto) {
          actualizarFilaBase();
          return;
        }
        const costo = costoBase();
        if (costo > 0)
          val.value = (costo * (1 + (numeroSeguro(pct.value) / 100))).toFixed(2);
      });
      val.addEventListener('input', function() {
        if (esCosto) {
          actualizarFilaBase();
          return;
        }
        const costo = costoBase();
        const precio = numeroSeguro(val.value);
        if (costo > 0 && precio > 0)
          pct.value = (((precio / costo) - 1) * 100).toFixed(2);
      });
      [select, factor, precioCosto, precioManual, monedaCosto].forEach(function(el) {
        if (el)
          el.addEventListener('input', actualizarFilaBase);
        if (el)
          el.addEventListener('change', actualizarFilaBase);
      });
      actualizarFilaBase();
    }
  });
  radiosStock.forEach(function (radio) {
    radio.addEventListener('change', alternarTipoStock);
  });
  configurarNuevaUnidad();
  alternarTipoStock();
  actualizarInfoStock();
  actualizarPreview();
})();
</script>
