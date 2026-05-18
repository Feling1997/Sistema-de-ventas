<?php
$texto_buscar = $texto_buscar ?? "";
$campo_buscar = $campo_buscar ?? "todos";
$metodo_buscar = $metodo_buscar ?? "contiene";
$campos_busqueda = $campos_busqueda ?? [];
$listas_precios = $listas_precios ?? [];
$id_lista_precio_actual = isset($id_lista_precio_actual) ? (int)$id_lista_precio_actual : ListaPrecio::id_predeterminada();
if ($id_lista_precio_actual <= 0)
  $id_lista_precio_actual = ListaPrecio::id_predeterminada();
?>
<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div class="d-flex align-items-center gap-2">
    <span class="section-heading-icon">📦</span>
    <h3 class="mb-1">Productos</h3>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-primary" href="index.php?c=productos&a=nuevo">+ Nuevo</a>
  </div>
</div>
<div class="card search-shell mb-3">
  <div class="card-body p-3">
    <form method="GET" action="index.php" class="row g-2 align-items-end" data-auto-submit-search="true" data-search-target="#productosResultados">
      <input type="hidden" name="c" value="productos">
      <input type="hidden" name="a" value="index">
      <div class="col-md-4 col-lg-5">
        <label class="form-label">Buscar</label>
        <input type="text" class="form-control" name="buscar" value="<?= htmlspecialchars($texto_buscar) ?>" placeholder="Ej: nombre, código o precio">
      </div>
      <div class="col-md-2 col-lg-2">
        <label class="form-label">Campo</label>
        <select class="form-select" name="campo">
          <option value="todos" <?= $campo_buscar === "todos" ? "selected" : "" ?>>Todos</option>
          <?php foreach ($campos_busqueda as $clave => $etiqueta): ?>
            <option value="<?= htmlspecialchars($clave) ?>" <?= $campo_buscar === $clave ? "selected" : "" ?>><?= htmlspecialchars($etiqueta) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 col-lg-2">
        <label class="form-label">Lista de precios</label>
        <select class="form-select" name="id_lista_precio">
          <?php foreach ($listas_precios as $lista): ?>
            <?php $lid = (int)$lista["id"]; ?>
            <option value="<?= $lid ?>" <?= $id_lista_precio_actual === $lid ? "selected" : "" ?>><?= htmlspecialchars($lista["nombre"] ?? "") ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4 col-lg-3">
        <label class="form-label">Método</label>
        <div class="d-flex gap-2 align-items-end">
          <select class="form-select flex-grow-1" name="metodo">
            <option value="contiene" <?= $metodo_buscar === "contiene" ? "selected" : "" ?>>Contiene</option>
            <option value="exacto" <?= $metodo_buscar === "exacto" ? "selected" : "" ?>>Exacto</option>
            <option value="empieza" <?= $metodo_buscar === "empieza" ? "selected" : "" ?>>Empieza</option>
            <option value="termina" <?= $metodo_buscar === "termina" ? "selected" : "" ?>>Termina</option>
          </select>
          <a class="btn btn-outline-secondary" href="index.php?c=productos&a=index">Limpiar</a>
        </div>
      </div>
    </form>
  </div>
</div>
<div id="productosResultados">
  <div class="card list-shell">
    <div class="card-body p-4">
      <div class="table-responsive">
        <table id="tablaProductos" class="table table-striped align-middle admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Código barras</th>
            <th>Stock</th>
            <th>Cantidad</th>
            <th>Factor</th>
            <th>%</th>
            <th>Precio</th>
            <th>Estado</th>
            <th style="width: 170px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($productos as $p): ?>
          <tr>
            <td><?= (int)$p["id"] ?></td>
            <td><?= htmlspecialchars($p["nombre"] ?? "") ?></td>
            <td><?= htmlspecialchars($p["cod_barras"] ?? "") ?></td>
            <td><?= htmlspecialchars($p["stock_nombre"] ?? "") ?></td>
            <td><?= htmlspecialchars(stock_para_mostrar($p["stock_cantidad"] ?? 0, 4)) ?></td>
            <td><?= htmlspecialchars(numero_para_mostrar($p["factor_conversion"] ?? 0, 4)) ?></td>
            <?php $precio_lista = ListaPrecio::precio_producto_cargado((int)$p["id"], $id_lista_precio_actual); ?>
            <td><?= htmlspecialchars($precio_lista !== null && $precio_lista["precio"] > 0 ? numero_para_mostrar($precio_lista["porcentaje"], 2) : "SIN PRECIO") ?></td>
            <td><?= htmlspecialchars($precio_lista !== null && $precio_lista["precio"] > 0 ? precio_para_mostrar($precio_lista["precio"]) : "SIN PRECIO") ?></td>
            <td><?= ((int)$p["activo"] === 1) ? "Alta" : "Baja" ?></td>
            <td>
              <div class="acciones-grid acciones-grid-compactas">
                <a class="btn btn-sm btn-secondary accion-btn" href="index.php?c=productos&a=editar&id=<?= (int)$p["id"] ?>">Editar</a>
                <a class="btn btn-sm btn-danger accion-btn"
                   href="index.php?c=productos&a=eliminar&id=<?= (int)$p["id"] ?>"
                   onclick="return confirm('¿Eliminar producto?');">
                   Eliminar
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (count($productos) === 0): ?>
          <tr><td colspan="10" class="text-center text-muted">Sin productos.</td></tr>
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
    const tabla = document.getElementById('tablaProductos');
    if (!tabla) ok = false;
    if (ok) {
      new DataTable('#tablaProductos', {
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
