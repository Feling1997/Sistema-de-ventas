<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div>
    <h3 class="mb-1">Faltantes y pedidos</h3>
    <div class="text-muted small">Pedido sugerido segun stock minimo y maximo.</div>
  </div>
  <a class="btn btn-outline-secondary" href="index.php?c=stock&a=index">Volver</a>
</div>

<div class="card search-shell mb-3">
  <div class="card-body p-3">
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn <?= $solo_minimo ? "btn-primary" : "btn-outline-secondary" ?>" href="index.php?c=stock&a=faltantes&solo_minimo=1">Solo bajo minimo</a>
      <a class="btn <?= !$solo_minimo ? "btn-primary" : "btn-outline-secondary" ?>" href="index.php?c=stock&a=faltantes&solo_minimo=0">Todos los activos</a>
    </div>
  </div>
</div>

<div class="card list-shell">
  <div class="card-body p-4">
    <div class="table-responsive">
      <table class="table table-striped align-middle admin-table">
        <thead>
          <tr>
            <th>Stock</th>
            <th>Cantidad</th>
            <th>Minimo</th>
            <th>Maximo</th>
            <th>Unidad</th>
            <th>Sugerido a pedir</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= htmlspecialchars((string)$it["nombre"]) ?></td>
              <td><?= htmlspecialchars(stock_para_mostrar($it["cantidad"] ?? 0, 3)) ?></td>
              <td><?= htmlspecialchars(numero_para_mostrar($it["stock_minimo"] ?? 0, 3)) ?></td>
              <td><?= htmlspecialchars(numero_para_mostrar($it["stock_maximo"] ?? 0, 3)) ?></td>
              <td><?= htmlspecialchars((string)$it["unidad"]) ?></td>
              <td><strong><?= htmlspecialchars(numero_para_mostrar($it["cantidad_sugerida"] ?? 0, 3)) ?></strong></td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($items) === 0): ?>
            <tr><td colspan="6" class="text-center text-muted">No hay faltantes.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
