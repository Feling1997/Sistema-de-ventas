<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div>
    <h3 class="mb-1">Cuenta corriente</h3>
    <div class="text-muted small">Avisos de cuotas vencidas o por vencer en los proximos 7 dias.</div>
  </div>
  <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=inicio">Volver</a>
</div>

<div class="card list-shell mb-3">
  <div class="card-body p-4">
    <h5 class="mb-3">Alertas</h5>
    <div class="table-responsive">
      <table class="table table-striped align-middle admin-table">
        <thead><tr><th>Cliente</th><th>Concepto</th><th>Cuota</th><th>Vence</th><th>Monto</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($alertas as $q): ?>
            <tr>
              <td><?= htmlspecialchars((string)$q["cliente_nombre"]) ?></td>
              <td><?= htmlspecialchars((string)$q["concepto"]) ?></td>
              <td><?= (int)$q["numero"] ?></td>
              <td><?= htmlspecialchars((string)$q["vencimiento"]) ?></td>
              <td><?= htmlspecialchars(moneda_para_mostrar($q["monto"] ?? 0)) ?></td>
              <td><a class="btn btn-sm btn-success" href="index.php?c=cuentas_corrientes&a=pagar_cuota&id=<?= (int)$q["id"] ?>">Pagar</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($alertas) === 0): ?>
            <tr><td colspan="6" class="text-center text-muted">Sin cuotas vencidas ni proximas.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card list-shell">
  <div class="card-body p-4">
    <h5 class="mb-3">Deudores</h5>
    <div class="table-responsive">
      <table class="table table-striped align-middle admin-table">
        <thead><tr><th>ID</th><th>Cliente</th><th>Concepto</th><th>Total</th><th>Saldo</th><th>Proximo venc.</th><th>Vencidas</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($cuentas as $cc): ?>
            <tr>
              <td><?= (int)$cc["id"] ?></td>
              <td><?= htmlspecialchars((string)$cc["cliente_nombre"]) ?></td>
              <td><?= htmlspecialchars((string)$cc["concepto"]) ?></td>
              <td><?= htmlspecialchars(moneda_para_mostrar($cc["total"] ?? 0)) ?></td>
              <td><?= htmlspecialchars(moneda_para_mostrar($cc["saldo"] ?? 0)) ?></td>
              <td><?= htmlspecialchars((string)($cc["proximo_vencimiento"] ?? "")) ?></td>
              <td><?= (int)($cc["vencidas"] ?? 0) ?></td>
              <td><?= htmlspecialchars((string)$cc["estado"]) ?></td>
              <td>
                <div class="d-flex gap-2 flex-wrap">
                  <a class="btn btn-sm btn-primary" href="index.php?c=cuentas_corrientes&a=recibo&id=<?= (int)$cc["id"] ?>">Generar recibo</a>
                  <a class="btn btn-sm btn-outline-danger" href="index.php?c=cuentas_corrientes&a=cancelar&id=<?= (int)$cc["id"] ?>" onclick="return confirm('Cancelar esta cuenta corriente?');">Cancelar</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($cuentas) === 0): ?>
            <tr><td colspan="9" class="text-center text-muted">Sin deudores.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
