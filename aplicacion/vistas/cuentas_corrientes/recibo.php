<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div>
    <h3 class="mb-1">Generar recibo</h3>
    <div class="text-muted small"><?= htmlspecialchars((string)$cuenta["cliente_nombre"]) ?> - Saldo: <?= htmlspecialchars(moneda_para_mostrar($cuenta["saldo"] ?? 0)) ?></div>
  </div>
  <a class="btn btn-outline-secondary" href="index.php?c=cuentas_corrientes&a=index">Volver</a>
</div>

<div class="card form-shell mb-3">
  <div class="card-body p-4">
    <form method="POST" action="index.php?c=cuentas_corrientes&a=generar_recibo" class="smart-form" id="cc-recibo-form" target="_blank">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="hidden" name="id_cuenta" value="<?= (int)$cuenta["id"] ?>">
      <input type="hidden" name="imprimir" value="0" id="cc-recibo-imprimir">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Monto a recibir</label>
          <input type="number" step="0.01" min="0.01" max="<?= htmlspecialchars(numero_para_input($cuenta["saldo"] ?? 0, 2)) ?>" class="form-control" name="monto" value="<?= htmlspecialchars(numero_para_input($cuenta["saldo"] ?? 0, 2)) ?>" required>
          <div class="form-text">Maximo: <?= htmlspecialchars(moneda_para_mostrar($cuenta["saldo"] ?? 0)) ?></div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Forma de pago</label>
          <select class="form-select" name="forma_pago">
            <option value="contado">Contado</option>
            <option value="transferencia">Transferencia</option>
            <option value="tarjeta">Tarjeta</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Observacion</label>
          <input class="form-control" name="observacion" placeholder="Entrega parcial, saldo total, etc.">
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-primary w-100" type="submit" data-imprimir="0">Generar</button>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100" type="submit" data-imprimir="1">Generar e imprimir</button>
        </div>
      </div>

      <div class="mt-3">
        <label class="form-label">Aplicar a cuotas</label>
        <div class="table-responsive">
          <table class="table table-striped align-middle admin-table">
            <thead><tr><th></th><th>Cuota</th><th>Vence</th><th>Monto</th><th>Pagado</th><th>Pendiente</th></tr></thead>
            <tbody>
              <?php foreach ($cuotas as $q): ?>
                <tr>
                  <td><input type="checkbox" name="cuotas[]" value="<?= (int)$q["id"] ?>" checked></td>
                  <td><?= (int)$q["numero"] ?></td>
                  <td><?= htmlspecialchars((string)$q["vencimiento"]) ?></td>
                  <td><?= htmlspecialchars(moneda_para_mostrar($q["monto"] ?? 0)) ?></td>
                  <td><?= htmlspecialchars(moneda_para_mostrar($q["pagado"] ?? 0)) ?></td>
                  <td><strong><?= htmlspecialchars(moneda_para_mostrar($q["pendiente"] ?? 0)) ?></strong></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const input = document.getElementById("cc-recibo-imprimir");
  const form = document.getElementById("cc-recibo-form");
  document.querySelectorAll("[data-imprimir]").forEach(function (boton) {
    boton.addEventListener("click", function () {
      if (input)
        input.value = boton.dataset.imprimir || "0";
      setTimeout(function () {
        window.location.href = "index.php?c=cuentas_corrientes&a=index";
      }, 600);
    });
  });
});
</script>
