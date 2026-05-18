<?php
$fecha_desde = $fecha_desde ?? "";
$fecha_hasta = $fecha_hasta ?? "";
$texto_buscar = $texto_buscar ?? "";
$campo_buscar = $campo_buscar ?? "todos";
$metodo_buscar = $metodo_buscar ?? "contiene";
$campos_busqueda = $campos_busqueda ?? [];
$resumen_periodo = $resumen_periodo ?? ["cantidad_ventas" => 0, "total_vendido" => 0, "ganancia" => 0];
$estados_fiscales = $estados_fiscales ?? [];
$detalles_ventas = $detalles_ventas ?? [];
$ventas_detalle = [];
foreach ($ventas as $venta_detalle_item) {
  $venta_id = (int)($venta_detalle_item["id"] ?? 0);
  $fiscal_item = $estados_fiscales[$venta_id] ?? null;
  $estado_fiscal = $fiscal_item ? (string)($fiscal_item["estado"] ?? "") : "SIN COLA";
  $extra_fiscal = "";
  if ($fiscal_item && !empty($fiscal_item["cae"]))
    $extra_fiscal = "CAE " . (string)$fiscal_item["cae"];
  else if ($fiscal_item && !empty($fiscal_item["ultimo_error"]))
    $extra_fiscal = (string)$fiscal_item["ultimo_error"];
  $items = [];
  foreach (($detalles_ventas[$venta_id] ?? []) as $detalle_item) {
    $items[] = [
      "producto" => (string)($detalle_item["producto_nombre"] ?? ""),
      "cantidad" => (float)($detalle_item["cantidad"] ?? 0),
      "precio" => precio_para_mostrar($detalle_item["precio_unit"] ?? 0),
      "descuento" => (float)($detalle_item["descuento"] ?? 0),
      "subtotal" => moneda_para_mostrar($detalle_item["subtotal"] ?? 0)
    ];
  }
  $ventas_detalle[$venta_id] = [
    "id" => $venta_id,
    "fecha" => (string)($venta_detalle_item["fecha"] ?? ""),
    "cliente" => (string)($venta_detalle_item["cliente_nombre"] ?? ""),
    "vendedor" => (string)($venta_detalle_item["usuario_nombre"] ?? ""),
    "total" => moneda_para_mostrar($venta_detalle_item["total"] ?? 0),
    "fiscal" => $estado_fiscal,
    "fiscal_extra" => $extra_fiscal,
    "ticket_url" => "index.php?c=ventas&a=ticket&id=" . $venta_id,
    "items" => $items
  ];
}
?>
<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div>
    <h3 class="mb-0">Ventas</h3>
  </div>
  <a class="btn btn-primary" href="index.php?c=ventas&a=nueva">Nueva venta</a>
</div>

<div class="card search-shell mb-3">
  <div class="card-body p-3">
    <form method="get" action="index.php" class="row g-2 align-items-end" data-auto-submit-search="true" data-search-target="#ventasResultados">
      <input type="hidden" name="c" value="ventas">
      <input type="hidden" name="a" value="lista">
      <div class="col-lg-2 col-md-3">
        <label for="fecha_desde" class="form-label">Desde</label>
        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>">
      </div>
      <div class="col-lg-2 col-md-3">
        <label for="fecha_hasta" class="form-label">Hasta</label>
        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>">
      </div>
      <div class="col-lg-3 col-md-6">
        <label class="form-label">Buscar</label>
        <input type="text" class="form-control" name="buscar" value="<?= htmlspecialchars($texto_buscar) ?>" placeholder="Cliente, fecha o total">
      </div>
      <div class="col-lg-2 col-md-3">
        <label class="form-label">Campo</label>
        <select class="form-select" name="campo">
          <option value="todos" <?= $campo_buscar === "todos" ? "selected" : "" ?>>Todos</option>
          <?php foreach ($campos_busqueda as $clave => $etiqueta): ?>
            <option value="<?= htmlspecialchars($clave) ?>" <?= $campo_buscar === $clave ? "selected" : "" ?>><?= htmlspecialchars($etiqueta) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-lg-2 col-md-3">
        <label class="form-label">Método</label>
        <select class="form-select" name="metodo">
          <option value="contiene" <?= $metodo_buscar === "contiene" ? "selected" : "" ?>>Contiene</option>
          <option value="exacto" <?= $metodo_buscar === "exacto" ? "selected" : "" ?>>Exacto</option>
          <option value="empieza" <?= $metodo_buscar === "empieza" ? "selected" : "" ?>>Empieza</option>
          <option value="termina" <?= $metodo_buscar === "termina" ? "selected" : "" ?>>Termina</option>
        </select>
      </div>
      <div class="col-lg-auto col-md-6">
        <div class="d-flex gap-2 ventas-lista-acciones">
          <button type="submit" class="btn btn-primary">Filtrar</button>
          <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=lista">Limpiar</a>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="ventasResultados">
  <div class="row g-2 mb-3">
    <div class="col-md-4">
      <div class="card list-shell h-100">
        <div class="card-body p-3">
          <div class="text-muted small">Ventas</div>
          <div class="fs-5 fw-bold"><?= (int)$resumen_periodo["cantidad_ventas"] ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card list-shell h-100">
        <div class="card-body p-3">
          <div class="text-muted small">Total</div>
          <div class="fs-5 fw-bold"><?= htmlspecialchars(moneda_para_mostrar($resumen_periodo["total_vendido"] ?? 0)) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card list-shell h-100">
        <div class="card-body p-3">
          <div class="text-muted small">Ganancia</div>
          <div class="fs-5 fw-bold"><?= htmlspecialchars(moneda_para_mostrar($resumen_periodo["ganancia"] ?? 0)) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="panel ventas-detail-panel">
    <div class="layout-consulta ventas-layout-consulta">
      <div class="table-wrap">
        <table class="ventas-select-table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Cliente</th>
              <th style="text-align:right;">Total</th>
              <th>Fiscal</th>
              <th>Ticket</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($ventas as $v): ?>
            <?php $id = (int)$v["id"]; ?>
            <tr data-venta-id="<?= $id ?>">
              <td><?= htmlspecialchars((string)$v["fecha"]) ?></td>
              <td><?= htmlspecialchars((string)$v["cliente_nombre"]) ?></td>
              <td style="text-align:right;"><?= htmlspecialchars(moneda_para_mostrar($v["total"] ?? 0)) ?></td>
              <td>
                <?php
                  $fiscal = $estados_fiscales[$id] ?? null;
                  $estado = $fiscal ? (string)$fiscal["estado"] : "SIN COLA";
                  $clase = "secondary";
                  if ($estado === "APROBADO") { $clase = "success"; }
                  else if ($estado === "PENDIENTE" || $estado === "EN_PROCESO") { $clase = "warning text-dark"; }
                  else if ($estado === "RECHAZADO" || $estado === "ERROR") { $clase = "danger"; }
                  $extra = "";
                  if ($fiscal && !empty($fiscal["cae"]))
                    $extra = " CAE " . (string)$fiscal["cae"];
                  else if ($fiscal && !empty($fiscal["ultimo_error"]))
                    $extra = " - " . (string)$fiscal["ultimo_error"];
                ?>
                <span class="badge bg-<?= htmlspecialchars($clase) ?>" title="<?= htmlspecialchars($extra) ?>">
                  <?= htmlspecialchars($estado) ?>
                </span>
              </td>
              <td>
                <?php $ruta = "index.php?c=ventas&a=ticket&id=" . $id; ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($ruta) ?>" target="_blank" rel="noopener">Ver ticket</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($ventas) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted">Sin ventas.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <aside class="panel detail ventas-ticket-detail" id="ventasDetalle">Seleccione una venta para ver el detalle.</aside>
    </div>
  </div>
  <script type="application/json" id="ventasDetalleDatos"><?= json_encode($ventas_detalle, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
</div>
