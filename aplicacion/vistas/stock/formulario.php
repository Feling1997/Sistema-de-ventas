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
$cantidad = (string)($s["cantidad"] ?? "0");
$stock_minimo = (string)($s["stock_minimo"] ?? "0");
$stock_maximo = (string)($s["stock_maximo"] ?? "0");
$precio_costo = (string)($s["precio_costo"] ?? "0");
$activo = (int)($s["activo"] ?? 1);
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
          <input class="form-control form-control-lg" name="unidad" value="<?= htmlspecialchars($unidad) ?>" placeholder="Ej: u / kg / lt">
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
        <div class="col-md-6">
          <label class="form-label">Precio costo</label>
          <input type="number" step="0.01" class="form-control form-control-lg" name="precio_costo" value="<?= htmlspecialchars($precio_costo) ?>">
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
        <button class="btn btn-primary btn-lg px-4"><?= htmlspecialchars($texto_btn) ?></button>
      </div>
    </form>
  </div>
</div>
