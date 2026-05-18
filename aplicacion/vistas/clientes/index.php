<?php
$texto_buscar = $texto_buscar ?? "";
$campo_buscar = $campo_buscar ?? "todos";
$metodo_buscar = $metodo_buscar ?? "contiene";
$campos_busqueda = $campos_busqueda ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Clientes</h3>
  <a class="btn btn-primary" href="index.php?c=clientes&a=nuevo">+ Nuevo</a>
</div>
<div class="card search-shell mb-3">
  <div class="card-body p-3">
    <form method="GET" action="index.php" class="row g-2 align-items-end" data-auto-submit-search="true" data-search-target="#clientesResultados">
      <input type="hidden" name="c" value="clientes">
      <input type="hidden" name="a" value="index">
      <div class="col-lg-5">
        <label class="form-label">Buscar cliente</label>
        <input type="text" class="form-control" name="buscar" value="<?= htmlspecialchars($texto_buscar) ?>" placeholder="Ej: nombre, DNI o teléfono">
      </div>
      <div class="col-md-3">
        <label class="form-label">Campo</label>
        <select class="form-select" name="campo">
          <option value="todos" <?= $campo_buscar === "todos" ? "selected" : "" ?>>Todos</option>
          <?php foreach ($campos_busqueda as $clave => $etiqueta): ?>
            <option value="<?= htmlspecialchars($clave) ?>" <?= $campo_buscar === $clave ? "selected" : "" ?>><?= htmlspecialchars($etiqueta) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Método</label>
        <select class="form-select" name="metodo">
          <option value="contiene" <?= $metodo_buscar === "contiene" ? "selected" : "" ?>>Contiene</option>
          <option value="exacto" <?= $metodo_buscar === "exacto" ? "selected" : "" ?>>Exacto</option>
          <option value="empieza" <?= $metodo_buscar === "empieza" ? "selected" : "" ?>>Empieza</option>
          <option value="termina" <?= $metodo_buscar === "termina" ? "selected" : "" ?>>Termina</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary flex-grow-1">Buscar</button>
        <a class="btn btn-outline-secondary" href="index.php?c=clientes&a=index">Limpiar</a>
      </div>
    </form>
  </div>
</div>
<div id="clientesResultados">
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table id="tablaClientes" class="table table-striped align-middle admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Documento</th>
            <th>IVA</th>
            <th>Lista</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Dirección</th>
            <th>Creado</th>
            <th style="width: 220px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($clientes as $c): ?>
          <?php $id = (int)$c["id"]; ?>
          <tr>
            <td><?= $id ?></td>
            <td><?= htmlspecialchars($c["nombre"] ?? "") ?></td>
            <td><?= htmlspecialchars(trim((string)($c["tipo_documento"] ?? "DNI") . " " . (string)($c["dni"] ?? ""))) ?></td>
            <td><?= htmlspecialchars($c["condicion_iva"] ?? "") ?></td>
            <td><?= htmlspecialchars($c["lista_precio_nombre"] ?? "") ?></td>
            <td><?= htmlspecialchars($c["email"] ?? "") ?></td>
            <td><?= htmlspecialchars($c["telefono"] ?? "") ?></td>
            <td><?= htmlspecialchars($c["direccion"] ?? "") ?></td>
            <td><?= htmlspecialchars($c["creado_en"] ?? "") ?></td>
            <td>
              <?php if ($id === 1): ?>
                <span class="badge text-bg-secondary">Fijo (Consumidor Final)</span>
              <?php else: ?>
                <a class="btn btn-sm btn-secondary" href="index.php?c=clientes&a=editar&id=<?= $id ?>">Editar</a>
                <a class="btn btn-sm btn-danger"
                   href="index.php?c=clientes&a=eliminar&id=<?= $id ?>"
                   onclick="return confirm('¿Eliminar cliente?');">
                   Eliminar
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (count($clientes) === 0): ?>
          <tr><td colspan="10" class="text-center text-muted">Sin clientes.</td></tr>
        <?php endif; ?>
        </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    let ok = true;
    const tabla = document.getElementById('tablaClientes');
    if (!tabla) ok = false;
    if (ok) {
      new DataTable('#tablaClientes', {
        searching: false,
        language: {
          search: "Buscar:",
          lengthMenu: "Mostrar _MENU_",
          info: "Mostrando _START_ a _END_ de _TOTAL_",
          infoEmpty: "Sin datos",
          zeroRecords: "No se encontraron resultados",
          paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
        }
      });
    }
  });
})();
</script>
