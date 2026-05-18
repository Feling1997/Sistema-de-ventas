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
$stock_precio_costo = numero_para_input($p["stock_precio_costo"] ?? "0", 2);
$agregar_stock = numero_para_input($p["agregar_stock"] ?? "0", 4);
$listas_precios = $listas_precios ?? [];
$precios_producto = $precios_producto ?? [];
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
          <label class="form-label">Código del producto</label>
          <input class="form-control form-control-lg" name="cod_barras" value="<?= htmlspecialchars($cod_barras) ?>" placeholder="Opcional. Si lo dejás vacío, se genera solo">
          <div class="form-text">Solo hace falta si usás lector o querés un código manual.</div>
        </div>
      </div>
      <div class="mt-3">
        <label class="form-label">Tipo de stock</label>
        <div class="d-flex flex-wrap gap-3 mb-2">
          <label class="form-check">
            <input class="form-check-input" type="radio" name="usa_stock_general" value="0" <?= !$usa_stock_general ? "checked" : "" ?> <?= $stock_fijo ? "disabled" : "" ?>>
            <span class="form-check-label">Stock propio automatico</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="radio" name="usa_stock_general" value="1" <?= $usa_stock_general ? "checked" : "" ?> <?= $stock_fijo ? "disabled" : "" ?>>
            <span class="form-check-label">Usar stock general</span>
          </label>
        </div>
        <?php if ($stock_fijo): ?>
          <input type="hidden" name="usa_stock_general" value="1">
        <?php endif; ?>
        <div id="stockGeneralBox">
          <?php if (!$stock_fijo): ?>
            <input type="text" class="form-control form-control-lg" id="buscarStockProducto" placeholder="Escribi para buscar stock...">
            <div class="stock-live-list" id="stockResultadosLista"></div>
          <?php endif; ?>
          <select class="form-select form-select-lg stock-select-native" name="id_stock" <?= $stock_fijo ? "disabled" : "" ?>>
            <option value="" <?= $id_stock === null ? "selected" : "" ?>>Seleccionar stock general</option>
            <?php foreach ($stocks as $s): ?>
              <?php $sid = (int)$s["id"]; ?>
              <option value="<?= $sid ?>" data-costo="<?= htmlspecialchars(numero_para_input($s["precio_costo"] ?? "0", 4)) ?>" data-cantidad="<?= htmlspecialchars(numero_para_input($s["cantidad"] ?? "0", 4)) ?>" data-unidad="<?= htmlspecialchars((string)($s["unidad"] ?? "u")) ?>" <?= ($id_stock !== null && $id_stock === $sid) ? "selected" : "" ?>>
                #<?= $sid ?> - <?= htmlspecialchars($s["nombre"]) ?> (<?= htmlspecialchars($s["unidad"]) ?>) cantidad: <?= htmlspecialchars(stock_para_mostrar($s["cantidad"] ?? 0, 4)) ?> costo: <?= htmlspecialchars(precio_para_mostrar($s["precio_costo"] ?? 0)) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if ($stock_fijo): ?>
            <input type="hidden" name="id_stock" value="<?= (int)$id_stock ?>">
          <?php endif; ?>
        </div>
      </div>
      <div class="row g-2 mt-1">
        <div class="col-sm-6 col-lg-2">
          <label class="form-label">Cantidad actual</label>
          <input type="number" step="0.0001" min="0" class="form-control form-control-lg" name="cantidad_stock" value="<?= htmlspecialchars($stock_cantidad) ?>">
          <div class="form-text">Edita la cantidad total del stock asociado.</div>
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label">Agregar stock</label>
          <input type="number" step="0.0001" min="0" class="form-control form-control-lg" name="agregar_stock" value="<?= htmlspecialchars($agregar_stock) ?>">
          <div class="form-text">Si hay 50 y cargas 50, queda en 100.</div>
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label">Unidad</label>
          <input class="form-control form-control-lg" name="unidad_stock" value="<?= htmlspecialchars($stock_unidad) ?>" placeholder="u">
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label">Costo del stock</label>
          <input type="number" step="0.01" min="0" class="form-control form-control-lg" name="precio_costo" value="<?= htmlspecialchars($stock_precio_costo) ?>">
        </div>
        <div class="col-sm-6 col-lg-2 factor-conversion-row">
          <label class="form-label">Uso de stock por cada venta</label>
          <input type="number" step="0.0001" class="form-control form-control-lg" name="factor_conversion" value="<?= htmlspecialchars($factor_conversion) ?>">
          <div class="form-text">Ejemplo: si vendés 1 unidad y consumís 50 del stock, poné 50.</div>
        </div>
        <?php if (count($listas_precios) === 0): ?>
          <div class="col-md-6">
            <label class="form-label">Ganancia (%)</label>
            <input type="number" step="0.01" class="form-control form-control-lg" name="ganancia" value="<?= htmlspecialchars($ganancia) ?>">
            <div class="form-text">Si no querés complicarte, dejá 0 y ajustalo después.</div>
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
          <div class="form-text">Se mostrarán todas las listas activas automáticamente. Si agregás otra lista, recargá el formulario.</div>
          <div class="price-list-grid">
            <?php foreach ($listas_precios as $lista): ?>
              <?php
                $id_lista = (int)$lista["id"];
                $es_lista_costo = ListaPrecio::es_lista_costo($lista);
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
        <button class="btn btn-primary btn-lg px-4"><?= htmlspecialchars($texto_btn) ?></button>
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
  const factor = document.querySelector('input[name="factor_conversion"]');
  const ganancia = document.querySelector('input[name="ganancia"]');
  const precioManual = document.querySelector('input[name="precio_final_manual"]');
  const precioCosto = document.querySelector('input[name="precio_costo"]');
  const preview = document.getElementById('precioFinalPreview');
  if (!select)
    return;
  const opcionesStock = Array.from(select.options).filter(function (op) { return op.value; });

  function numeroSeguro(valor) {
    const normalizado = String(valor || '').replace(/\s/g, '').replace(',', '.');
    const numero = parseFloat(normalizado);
    return Number.isFinite(numero) ? numero : 0;
  }

  function actualizarPreview() {
    if (!preview || !select)
      return;
    const usaGeneral = document.querySelector('input[name="usa_stock_general"]:checked')?.value === '1';
    const op = select.options[select.selectedIndex];
    const costo = usaGeneral ? (op ? numeroSeguro(op.getAttribute('data-costo')) : 0) : (precioCosto ? numeroSeguro(precioCosto.value) : 0);
    const factorValor = factor ? numeroSeguro(factor.value) : 0;
    const gananciaValor = ganancia ? numeroSeguro(ganancia.value) : 0;
    let precio = (costo * factorValor) * (1 + (gananciaValor / 100));
    if (precio <= 0 && precioManual)
      precio = numeroSeguro(precioManual.value);
    preview.textContent = precio > 0 ? '$ ' + precio.toLocaleString('es-PY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : 'SIN PRECIO';
  }

  const factorRow = document.querySelector('.factor-conversion-row');

  function alternarTipoStock() {
    const usaGeneral = document.querySelector('input[name="usa_stock_general"]:checked')?.value === '1';
    if (stockGeneralBox)
      stockGeneralBox.style.display = usaGeneral ? '' : 'none';
    if (factorRow) {
      factorRow.style.display = usaGeneral ? '' : 'none';
      if (!usaGeneral && factor)
        factor.value = 1;
    }
    actualizarPreview();
  }

  function seleccionarStock(valor) {
    select.value = valor;
    const op = select.options[select.selectedIndex];
    if (input && op)
      input.value = (op.textContent || '').trim();
    if (lista)
      lista.innerHTML = '';
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
    const opInicial = select.options[select.selectedIndex];
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
  document.querySelectorAll('.price-list-row').forEach(function(row) {
    const pct = row.querySelector('.price-percent');
    const val = row.querySelector('.price-value');
    const esCosto = row.getAttribute('data-lista-costo') === '1';
    const esPublico = row.getAttribute('data-lista-publico') === '1';
    function costoBase() {
      const usaGeneral = document.querySelector('input[name="usa_stock_general"]:checked')?.value === '1';
      const op = select ? select.options[select.selectedIndex] : null;
      const costoStock = usaGeneral ? (op ? numeroSeguro(op.getAttribute('data-costo')) : 0) : (precioCosto ? numeroSeguro(precioCosto.value) : 0);
      return costoStock;
    }
    function actualizarFilaBase() {
      if (!pct || !val)
        return;
      const costo = costoBase();
      if (esCosto) {
        pct.value = '0';
        val.value = costo > 0 ? costo.toFixed(2) : '0';
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
      [select, factor, precioCosto, precioManual].forEach(function(el) {
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
  alternarTipoStock();
  actualizarPreview();
})();
</script>
