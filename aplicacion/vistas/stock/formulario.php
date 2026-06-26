<?php
$es_editar = false;
$accion = "index.php?c=stock&a=crear";
$titulo = "Nuevo stock";
$texto_btn = "Crear";
if (isset($modo) && $modo === "editar") {
    $es_editar = true;
    $accion = "index.php?c=stock&a=actualizar";
    $titulo = "Editar stock";
    $texto_btn = "Guardar cambios";
}
$id = (int)($s["id"] ?? 0);
$nombre = (string)($s["nombre"] ?? "");
$unidad = (string)($s["unidad"] ?? "u");
$tipo_stock = (string)($s["tipo_stock"] ?? "general");
$cantidad = (string)($s["cantidad"] ?? "0");
$stock_minimo = (string)($s["stock_minimo"] ?? "0");
$stock_maximo = (string)($s["stock_maximo"] ?? "0");
$moneda_costo = strtoupper((string)($s["moneda_costo"] ?? "ARS")) === "USD" ? "USD" : "ARS";
$precio_costo = (string)($s["costo_origen"] ?? $s["precio_costo"] ?? "0");
$activo = (int)($s["activo"] ?? 1);
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
$precio_costo_visible = number_format(parsear_numero_form($precio_costo, 0), 2, ",", "");
?>
<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div>
    <h3 class="mb-1"><?= htmlspecialchars($titulo) ?></h3>
  </div>
  <a class="btn btn-outline-secondary" href="index.php?c=stock&a=index">Volver</a>
</div>
<div class="card form-shell">
  <div class="card-body p-4">
    <form method="POST" action="<?= htmlspecialchars($accion) ?>" class="smart-form">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <?php if ($es_editar): ?>
        <input type="hidden" name="id" value="<?= $id ?>">
      <?php endif; ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre *</label>
          <input class="form-control form-control-lg" name="nombre" value="<?= htmlspecialchars($nombre) ?>" placeholder="Ej: Azúcar">
        </div>
        <div class="col-md-6">
          <label class="form-label">Unidad</label>
          <select class="form-select form-select-lg" name="unidad" data-unidad-select>
            <?php $unidad_en_lista = false; ?>
            <?php foreach ($unidades_medida as $u): ?>
              <?php $abbr = (string)($u["abreviatura"] ?? ""); ?>
              <?php $unidad_label = $unidad_option_label($u); ?>
              <?php if ($unidad === $abbr) $unidad_en_lista = true; ?>
              <option value="<?= htmlspecialchars($abbr) ?>" data-decimales="<?= (int)($u["decimales"] ?? 0) ?>" <?= $unidad === $abbr ? "selected" : "" ?>>
                <?= htmlspecialchars($unidad_label) ?>
              </option>
            <?php endforeach; ?>
            <?php if (!$unidad_en_lista && $unidad !== ""): ?>
              <option value="<?= htmlspecialchars($unidad) ?>" selected><?= htmlspecialchars($unidad_abbr_label($unidad)) ?></option>
            <?php endif; ?>
            <option value="" disabled>----------------</option>
            <option value="__otra_unidad__">Otro...</option>
          </select>
          <input class="form-control form-control-lg mt-2 d-none" name="nueva_unidad_simple" data-nueva-unidad-simple placeholder="Nueva unidad, ej: bolsa">
        </div>
        <div class="col-md-6">
          <label class="form-label">Tipo de stock</label>
          <select class="form-select form-select-lg" name="tipo_stock">
            <option value="general" <?= $tipo_stock === "general" ? "selected" : "" ?>>General / materia prima</option>
            <option value="propio" <?= $tipo_stock === "propio" ? "selected" : "" ?>>Propio / producto independiente</option>
          </select>
          <div class="form-text">Solo el stock general aparece para asociar en Productos.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Cantidad</label>
          <input type="number" step="0.001" class="form-control form-control-lg" name="cantidad" value="<?= htmlspecialchars($cantidad) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Stock minimo</label>
          <input type="number" step="0.001" min="0" class="form-control form-control-lg" name="stock_minimo" value="<?= htmlspecialchars($stock_minimo) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Stock maximo</label>
          <input type="number" step="0.001" min="0" class="form-control form-control-lg" name="stock_maximo" value="<?= htmlspecialchars($stock_maximo) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Moneda del costo</label>
          <select class="form-select form-select-lg" name="moneda_costo">
            <option value="ARS" <?= $moneda_costo === "ARS" ? "selected" : "" ?>>Pesos (ARS)</option>
            <option value="USD" <?= $moneda_costo === "USD" ? "selected" : "" ?>>Dolares (USD)</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Costo en <?= $moneda_costo === "USD" ? "dolares" : "pesos" ?></label>
          <input type="text" inputmode="decimal" class="form-control form-control-lg money-input" name="precio_costo" value="<?= htmlspecialchars($precio_costo_visible) ?>" placeholder="1500,00">
          <div class="form-text">Cotizacion actual: <?= htmlspecialchars(precio_para_mostrar($cotizacion_dolar_stock ?? 1)) ?> por USD.</div>
        </div>
      </div>
      <div class="mt-4 mb-4">
        <label class="form-label">Activo</label>
        <select class="form-select form-select-lg" name="activo">
          <option value="1" <?= ($activo === 1) ? "selected" : "" ?>>Sí</option>
          <option value="0" <?= ($activo === 0) ? "selected" : "" ?>>No</option>
        </select>
      </div>
      <div class="form-actions">
        <a class="btn btn-outline-secondary" href="index.php?c=stock&a=index">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg px-4"><?= htmlspecialchars($texto_btn) ?></button>
      </div>
    </form>
  </div>
</div>
<script>
(function () {
  const select = document.querySelector('[data-unidad-select]');
  const inputOtro = document.querySelector('[data-nueva-unidad-simple]');
  if (!select || !inputOtro) return;
  function capitalizar(texto) {
    texto = String(texto || '').trim();
    return texto ? texto.charAt(0).toUpperCase() + texto.slice(1) : '';
  }
  function alternarOtro() {
    const esOtra = select.value === '__otra_unidad__';
    inputOtro.classList.toggle('d-none', !esOtra);
    inputOtro.disabled = !esOtra;
    if (esOtra)
      inputOtro.focus();
  }
  select.addEventListener('change', alternarOtro);
  const form = select.closest('form');
  if (form) {
    form.addEventListener('submit', function () {
      if (select.value === '__otra_unidad__')
        inputOtro.value = capitalizar(inputOtro.value);
    });
  }
  alternarOtro();
})();
</script>
