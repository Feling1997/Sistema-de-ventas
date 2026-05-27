<?php
$resultado = $resultado ?? [];
$resumen = $resultado["resumen"] ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div class="d-flex align-items-center gap-2">
    <span class="section-heading-icon">📥</span>
    <h3 class="mb-1">Resultado de importacion</h3>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-primary" href="index.php?c=importacion&a=index">Nueva importacion</a>
    <a class="btn btn-outline-secondary" href="index.php?c=productos&a=index">Productos</a>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-2"><div class="card"><div class="card-body"><div class="text-muted">Nuevos</div><strong><?= (int)($resumen["nuevos"] ?? 0) ?></strong></div></div></div>
  <div class="col-md-2"><div class="card"><div class="card-body"><div class="text-muted">Actualizados</div><strong><?= (int)($resumen["actualizados"] ?? 0) ?></strong></div></div></div>
  <div class="col-md-2"><div class="card"><div class="card-body"><div class="text-muted">Omitidos</div><strong><?= (int)($resumen["omitidos"] ?? 0) ?></strong></div></div></div>
  <div class="col-md-2"><div class="card"><div class="card-body"><div class="text-muted">Advertencias</div><strong><?= (int)($resumen["advertencias"] ?? 0) ?></strong></div></div></div>
  <div class="col-md-2"><div class="card"><div class="card-body"><div class="text-muted">Errores</div><strong><?= (int)($resumen["errores"] ?? 0) ?></strong></div></div></div>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="card-title">Detalle</h5>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead><tr><th>Fila</th><th>Codigo</th><th>Nombre</th><th>Accion</th><th>Detalle</th></tr></thead>
        <tbody>
          <?php foreach (($resultado["filas"] ?? []) as $fila): ?>
            <tr>
              <td><?= (int)($fila["fila"] ?? 0) ?></td>
              <td><?= htmlspecialchars((string)($fila["codigo"] ?? "")) ?></td>
              <td><?= htmlspecialchars((string)($fila["nombre"] ?? "")) ?></td>
              <td><?= htmlspecialchars((string)($fila["accion"] ?? "")) ?></td>
              <td><?= htmlspecialchars(implode("; ", $fila["detalle"] ?? [])) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php foreach (array_merge($resultado["errores"] ?? [], $resultado["advertencias"] ?? []) as $msg): ?>
            <tr>
              <td><?= (int)($msg["fila"] ?? 0) ?></td>
              <td></td>
              <td></td>
              <td>Validacion</td>
              <td><?= htmlspecialchars((string)($msg["mensaje"] ?? "")) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($resultado["filas"] ?? []) === 0 && count($resultado["errores"] ?? []) === 0 && count($resultado["advertencias"] ?? []) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted">No hay detalle para mostrar.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
