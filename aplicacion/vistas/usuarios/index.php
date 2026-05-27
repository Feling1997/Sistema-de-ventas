<?php
$texto_buscar = $texto_buscar ?? "";
$campo_buscar = $campo_buscar ?? "todos";
$metodo_buscar = $metodo_buscar ?? "contiene";
$campos_busqueda = $campos_busqueda ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Usuarios</h3>
  <a class="btn btn-primary" href="index.php?c=usuarios&a=nuevo">+ Nuevo</a>
</div>
<div class="card search-shell mb-3">
  <div class="card-body p-3">
    <form method="GET" action="index.php" class="row g-2 align-items-end" data-auto-submit-search="true" data-search-target="#usuariosResultados">
      <input type="hidden" name="c" value="usuarios">
      <input type="hidden" name="a" value="index">
      <input type="hidden" name="orden" value="<?= htmlspecialchars($orden_usuarios["campo"] ?? "usuario") ?>">
      <input type="hidden" name="direccion" value="<?= htmlspecialchars(strtolower($orden_usuarios["direccion"] ?? "ASC")) ?>">
      <div class="col-lg-5">
        <label class="form-label">Buscar usuario</label>
        <input type="text" class="form-control" name="buscar" value="<?= htmlspecialchars($texto_buscar) ?>" placeholder="Ej: usuario, rol o fecha">
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
        <a class="btn btn-outline-secondary" href="index.php?c=usuarios&a=index">Limpiar</a>
      </div>
    </form>
  </div>
</div>
<div id="usuariosResultados">
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table id="tablaUsuarios" class="table table-striped align-middle admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <?= orden_tabla_th("Usuario", "usuario", $orden_usuarios ?? [], "texto") ?>
            <th>Rol</th>
            <?= orden_tabla_th("Activo", "estado", $orden_usuarios ?? [], "texto") ?>
            <?= orden_tabla_th("Creado", "fecha", $orden_usuarios ?? [], "texto") ?>
            <th style="width: 180px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= (int)$u["id"] ?></td>
            <td><?= htmlspecialchars($u["usuario"]) ?></td>
            <td><?= htmlspecialchars($u["rol"]) ?></td>
            <td><?= ((int)$u["activo"] === 1) ? "Sí" : "No" ?></td>
            <td><?= date("d/m/Y", strtotime($u["creado_en"])) ?></td>
            <td>
              <a class="btn btn-sm btn-secondary" href="index.php?c=usuarios&a=editar&id=<?= (int)$u["id"] ?>">Editar</a>
              <a class="btn btn-sm btn-danger"
                 href="index.php?c=usuarios&a=eliminar&id=<?= (int)$u["id"] ?>"
                 onclick="return confirm('¿Eliminar usuario?');">
                 Eliminar
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (count($usuarios) === 0): ?>
          <tr><td colspan="6" class="text-center text-muted">Sin usuarios.</td></tr>
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
    const tabla = document.getElementById('tablaUsuarios');
    if (!tabla) ok = false;
    if (ok) {
      new DataTable('#tablaUsuarios', {
        searching: false,
        ordering: false,
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
