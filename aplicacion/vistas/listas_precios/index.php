<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <h3 class="mb-0">Listas de precios</h3>
  <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=inicio">Volver</a>
</div>

<div class="card form-shell mb-3">
  <div class="card-body p-4">
    <form method="POST" action="index.php?c=listas_precios&a=guardar" class="smart-form">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <div class="row g-2 align-items-end">
        <div class="col-md-6">
          <label class="form-label">Nueva lista</label>
          <input class="form-control form-control-lg" name="nombre" placeholder="Ej: Publico, Mayorista, Especial">
        </div>
        <div class="col-md-3">
          <label class="form-label">Estado</label>
          <select class="form-select form-select-lg" name="activo">
            <option value="1">Alta</option>
            <option value="0">Baja</option>
          </select>
        </div>
        <div class="col-md-3">
          <button class="btn btn-primary w-100">Agregar lista</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card list-shell">
  <div class="card-body p-4">
    <div class="table-responsive">
      <table class="table table-striped align-middle admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <?= orden_tabla_th("Nombre", "nombre", $orden_listas ?? [], "texto") ?>
            <?= orden_tabla_th("Estado", "estado", $orden_listas ?? [], "texto") ?>
            <th style="width: 280px;">Editar</th>
            <th style="width: 90px;">Eliminar</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($listas as $lista): ?>
            <?php $es_base = !empty($lista["es_lista_costo"]); ?>
            <tr>
              <td><?= (int)$lista["id"] ?></td>
              <td><?= htmlspecialchars($lista["nombre"] ?? "") ?></td>
              <td><?= (int)$lista["activo"] === 1 ? "Alta" : "Baja" ?></td>
              <td>
                <form method="POST" action="index.php?c=listas_precios&a=guardar" class="d-flex gap-2">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$lista["id"] ?>">
                  <input class="form-control form-control-sm" name="nombre" value="<?= htmlspecialchars($lista["nombre"] ?? "") ?>" <?= $es_base ? "readonly" : "" ?>>
                  <select class="form-select form-select-sm" name="activo">
                    <option value="1" <?= (int)$lista["activo"] === 1 ? "selected" : "" ?>>Alta</option>
                    <option value="0" <?= (int)$lista["activo"] === 0 ? "selected" : "" ?> <?= $es_base ? "disabled" : "" ?>>Baja</option>
                  </select>
                  <button class="btn btn-sm btn-secondary">Guardar</button>
                </form>
              </td>
              <td>
                <?php if ($es_base): ?>
                  <span class="text-muted small">Base</span>
                <?php else: ?>
                  <a class="btn btn-sm btn-danger" href="index.php?c=listas_precios&a=eliminar&id=<?= (int)$lista["id"] ?>" onclick="return confirm('Eliminar lista?');">Eliminar</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
