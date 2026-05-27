<?php
$analisis = $analisis ?? null;
$hojas = $hojas ?? [];
$archivo_nombre = $archivo_nombre ?? "";
$hoja_seleccionada = isset($hoja_seleccionada) ? (int)$hoja_seleccionada : 0;
?>
<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div class="d-flex align-items-center gap-2">
    <span class="section-heading-icon">📥</span>
    <h3 class="mb-1">Productos &gt; Importar Excel</h3>
  </div>
  <a class="btn btn-outline-secondary" href="index.php?c=productos&a=index">Volver</a>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form method="POST" action="index.php?c=importacion&a=analizar" enctype="multipart/form-data" class="row g-3 align-items-end">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <div class="col-md-5">
        <label class="form-label">Archivo Excel</label>
        <input type="file" class="form-control" name="archivo" accept=".xlsx,.xls,.xlsm">
        <?php if ($archivo_nombre !== ""): ?>
          <div class="form-text">Archivo actual: <?= htmlspecialchars($archivo_nombre) ?></div>
        <?php endif; ?>
      </div>
      <div class="col-md-4">
        <label class="form-label">Hoja</label>
        <select class="form-select" name="hoja">
          <?php if (count($hojas) === 0): ?>
            <option value="0">Primera hoja</option>
          <?php else: ?>
            <?php foreach ($hojas as $idx => $nombre_hoja): ?>
              <option value="<?= (int)$idx ?>" <?= $hoja_seleccionada === (int)$idx ? "selected" : "" ?>><?= htmlspecialchars($nombre_hoja) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Analizar archivo</button>
      </div>
    </form>
  </div>
</div>

<?php if (is_array($analisis)): ?>
  <?php $resumen = $analisis["resumen"] ?? []; ?>
  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Productos nuevos</div><strong><?= (int)($resumen["nuevos"] ?? 0) ?></strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Productos actualizados</div><strong><?= (int)($resumen["actualizados"] ?? 0) ?></strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Productos omitidos</div><strong><?= (int)($resumen["omitidos"] ?? 0) ?></strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Advertencias / Errores</div><strong><?= (int)($resumen["advertencias"] ?? 0) ?> / <?= (int)($resumen["errores"] ?? 0) ?></strong></div></div></div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <h5 class="card-title">Columnas detectadas</h5>
      <div class="row g-2">
        <div class="col-md-4"><strong>Codigo:</strong> <?= htmlspecialchars((string)($analisis["columnas"]["codigo"] ?? "")) ?></div>
        <div class="col-md-4"><strong>Nombre:</strong> <?= htmlspecialchars((string)($analisis["columnas"]["nombre"] ?? "")) ?></div>
        <div class="col-md-4"><strong>Listas:</strong>
          <?php
          $nombres_listas = [];
          foreach (($analisis["columnas"]["listas"] ?? []) as $lista)
            $nombres_listas[] = (string)($lista["nombre"] ?? "");
          ?>
          <?= htmlspecialchars(count($nombres_listas) > 0 ? implode(", ", $nombres_listas) : "Ninguna") ?>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($analisis["advertencias"]) || !empty($analisis["errores"])): ?>
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="card-title">Detalle de validaciones</h5>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>Fila</th><th>Mensaje</th></tr></thead>
            <tbody>
              <?php foreach (array_merge($analisis["errores"] ?? [], $analisis["advertencias"] ?? []) as $msg): ?>
                <tr><td><?= (int)($msg["fila"] ?? 0) ?></td><td><?= htmlspecialchars((string)($msg["mensaje"] ?? "")) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="card-title mb-0">Vista previa</h5>
        <form method="POST" action="index.php?c=importacion&a=importar" class="m-0" onsubmit="return confirm('Confirmar importacion de productos?');">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="hidden" name="hoja" value="<?= (int)($analisis["hoja"] ?? 0) ?>">
          <button class="btn btn-success" type="submit" <?= !empty($analisis["errores"]) ? "disabled" : "" ?>>Importar</button>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead><tr><?= orden_tabla_th("Fila", "fecha", $orden_importacion ?? [], "numero") ?><?= orden_tabla_th("Codigo", "codigo", $orden_importacion ?? [], "texto") ?><?= orden_tabla_th("Nombre", "nombre", $orden_importacion ?? [], "texto") ?><?= orden_tabla_th("Accion", "estado", $orden_importacion ?? [], "texto") ?><th>Detalle</th></tr></thead>
          <tbody>
            <?php foreach (($analisis["preview"] ?? []) as $fila): ?>
              <?php
              $accion = (string)($fila["accion"] ?? "");
              $clase = "secondary";
              if ($accion === "Nuevo") $clase = "success";
              elseif ($accion === "Actualizar") $clase = "primary";
              elseif ($accion === "Ignorar") $clase = "danger";
              elseif ($accion === "Advertencia") $clase = "warning text-dark";
              ?>
              <tr>
                <td><?= (int)($fila["fila"] ?? 0) ?></td>
                <td><?= htmlspecialchars((string)($fila["codigo"] ?? "")) ?></td>
                <td><?= htmlspecialchars((string)($fila["nombre"] ?? "")) ?></td>
                <td><span class="badge bg-<?= $clase ?>"><?= htmlspecialchars($accion) ?></span></td>
                <td><?= htmlspecialchars(implode("; ", $fila["detalle"] ?? [])) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (count($analisis["preview"] ?? []) === 0): ?>
              <tr><td colspan="5" class="text-center text-muted">Sin filas para mostrar.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>
