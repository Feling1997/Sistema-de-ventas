<?php
$buscar = $buscar ?? "";
$estado = $estado ?? "todos";
$orden = $orden ?? "vencimiento";
$vencidas = $vencidas ?? [];
$proximas = $proximas ?? [];
$resumen = $resumen ?? [];
$recibos = $recibos ?? [];
$saldos_favor = $saldos_favor ?? [];
$orden_cuentas = $orden_cuentas ?? ["campo" => "vencimiento", "direccion" => "ASC", "defecto_campo" => "vencimiento", "defecto_direccion" => "ASC"];

$fila_cuota = function (array $q, bool $vencida): void { ?>
  <tr class="<?= $vencida ? "cc-row-overdue" : "" ?>">
    <td>
      <strong><?= htmlspecialchars((string)$q["cliente_nombre"]) ?></strong>
      <div class="text-muted small"><?= htmlspecialchars((string)$q["concepto"]) ?></div>
    </td>
    <td class="text-center"><?= (int)$q["numero"] ?></td>
    <td>
      <span class="badge <?= $vencida ? "bg-danger" : "bg-warning" ?>">
        <?= htmlspecialchars((string)$q["vencimiento"]) ?>
      </span>
    </td>
    <td class="text-end"><?= htmlspecialchars(moneda_para_mostrar($q["monto"] ?? 0)) ?></td>
    <td class="text-end"><strong><?= htmlspecialchars(moneda_para_mostrar($q["pendiente"] ?? 0)) ?></strong></td>
    <td>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-sm btn-primary" href="index.php?c=cuentas_corrientes&a=recibo&id=<?= (int)$q["id_cuenta"] ?>">Generar recibo</a>
        <a class="btn btn-sm btn-success" href="index.php?c=cuentas_corrientes&a=pagar_cuota&id=<?= (int)$q["id"] ?>">Pagar cuota</a>
      </div>
    </td>
  </tr>
<?php };
?>

<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div>
    <h3 class="mb-1">Cuenta corriente</h3>
    <div class="text-muted small">Primero vencidos, despues proximos vencimientos y al final resumen de movimientos.</div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-primary" href="index.php?c=cuentas_corrientes&a=anticipo">Recibo de anticipo</a>
    <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=inicio">Volver</a>
  </div>
</div>

<div class="card form-shell mb-3">
  <div class="card-body p-4">
    <form method="GET" action="index.php" class="row g-2 align-items-end">
      <input type="hidden" name="c" value="cuentas_corrientes">
      <input type="hidden" name="a" value="index">
      <input type="hidden" name="direccion" value="<?= htmlspecialchars(strtolower($orden_cuentas["direccion"] ?? "ASC")) ?>">
      <div class="col-md-4">
        <label class="form-label">Buscar</label>
        <input class="form-control" name="buscar" value="<?= htmlspecialchars((string)$buscar) ?>" placeholder="Cliente, venta o fecha">
      </div>
      <div class="col-md-3">
        <label class="form-label">Mostrar</label>
        <select class="form-select" name="estado">
          <option value="todos" <?= $estado === "todos" ? "selected" : "" ?>>Todos</option>
          <option value="vencidos" <?= $estado === "vencidos" ? "selected" : "" ?>>Solo vencidos</option>
          <option value="proximos" <?= $estado === "proximos" ? "selected" : "" ?>>Solo proximos</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Ordenar por</label>
        <select class="form-select" name="orden">
          <option value="vencimiento" <?= $orden === "vencimiento" ? "selected" : "" ?>>Vencimiento</option>
          <option value="estado" <?= $orden === "estado" ? "selected" : "" ?>>Vencidos primero</option>
          <option value="cliente" <?= $orden === "cliente" ? "selected" : "" ?>>Cliente</option>
          <option value="saldo" <?= $orden === "saldo" ? "selected" : "" ?>>Mayor saldo</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary flex-fill">Filtrar</button>
        <a class="btn btn-outline-secondary" href="index.php?c=cuentas_corrientes&a=index">Limpiar</a>
      </div>
    </form>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="metric-card"><div class="metric-label">Cuentas abiertas</div><div class="metric-value"><?= (int)($resumen["cuentas"] ?? 0) ?></div></div></div>
  <div class="col-md-3"><div class="metric-card"><div class="metric-label">Saldo pendiente</div><div class="metric-value"><?= htmlspecialchars(moneda_para_mostrar($resumen["saldo"] ?? 0)) ?></div></div></div>
  <div class="col-md-3"><div class="metric-card"><div class="metric-label">Cuotas vencidas</div><div class="metric-value text-danger"><?= (int)($resumen["vencidas"] ?? 0) ?></div></div></div>
  <div class="col-md-3"><div class="metric-card"><div class="metric-label">Saldo a favor</div><div class="metric-value text-success"><?= htmlspecialchars(moneda_para_mostrar(array_sum($saldos_favor))) ?></div></div></div>
</div>

<div class="card list-shell mb-3">
  <div class="card-body p-4">
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
      <h5 class="mb-0 text-danger">Vencidos</h5>
      <?php if (count($vencidas) > 0): ?>
        <form method="POST" action="index.php?c=cuentas_corrientes&a=marcar_alertas_leidas" class="m-0">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <button class="btn btn-sm btn-outline-secondary" type="submit">Marcar aviso como leido</button>
        </form>
      <?php endif; ?>
    </div>
    <div class="table-responsive">
      <table class="table align-middle admin-table">
        <thead><tr><?= orden_tabla_th("Cliente / concepto", "cliente", $orden_cuentas, "texto") ?><th class="text-center">Cuota</th><?= orden_tabla_th("Vence", "fecha", $orden_cuentas, "texto") ?><?= orden_tabla_th("Monto", "monto", $orden_cuentas, "numero") ?><?= orden_tabla_th("Pendiente", "saldo", $orden_cuentas, "numero") ?><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($vencidas as $q) $fila_cuota($q, true); ?>
          <?php if (count($vencidas) === 0): ?>
            <tr><td colspan="6" class="text-center text-muted">Sin cuotas vencidas.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card list-shell mb-3">
  <div class="card-body p-4">
    <h5 class="mb-3">Proximos vencimientos</h5>
    <div class="table-responsive">
      <table class="table table-striped align-middle admin-table">
        <thead><tr><?= orden_tabla_th("Cliente / concepto", "cliente", $orden_cuentas, "texto") ?><th class="text-center">Cuota</th><?= orden_tabla_th("Vence", "fecha", $orden_cuentas, "texto") ?><?= orden_tabla_th("Monto", "monto", $orden_cuentas, "numero") ?><?= orden_tabla_th("Pendiente", "saldo", $orden_cuentas, "numero") ?><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($proximas as $q) $fila_cuota($q, false); ?>
          <?php if (count($proximas) === 0): ?>
            <tr><td colspan="6" class="text-center text-muted">Sin proximos vencimientos.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card list-shell">
  <div class="card-body p-4">
    <h5 class="mb-3">Recibos y movimientos</h5>
    <div class="table-responsive">
      <table class="table table-striped align-middle admin-table">
        <thead><tr><th>Fecha</th><th>Cliente</th><th>Concepto</th><th>Forma</th><th class="text-end">Monto</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($recibos as $r): ?>
            <tr>
              <td><?= htmlspecialchars((string)$r["fecha"]) ?></td>
              <td><?= htmlspecialchars((string)$r["cliente_nombre"]) ?></td>
              <td><?= htmlspecialchars((string)$r["concepto"]) ?></td>
              <td><?= htmlspecialchars((string)$r["forma_pago"]) ?></td>
              <td class="text-end"><?= htmlspecialchars(moneda_para_mostrar($r["monto"] ?? 0)) ?></td>
              <td><a class="btn btn-sm btn-outline-primary" href="index.php?c=cuentas_corrientes&a=ver_recibo&id=<?= (int)$r["id"] ?>" target="_blank">Ver recibo</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($recibos) === 0): ?>
            <tr><td colspan="6" class="text-center text-muted">Todavia no hay recibos generados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
