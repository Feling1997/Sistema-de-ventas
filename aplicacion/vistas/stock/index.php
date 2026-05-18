<?php
$viendo_productos = false;
if (isset($productos) && is_array($productos) && isset($s) && is_array($s))
    $viendo_productos = true;
$texto_buscar = $texto_buscar ?? "";
$campo_buscar = $campo_buscar ?? "todos";
$metodo_buscar = $metodo_buscar ?? "contiene";
$campos_busqueda = $campos_busqueda ?? [];
$listas_precios = $listas_precios ?? [];
?>
<?php if ($viendo_productos): ?>
  <?php
    $id_stock = (int)($s["id"] ?? 0);
    $nombre_stock = (string)($s["nombre"] ?? "");
    $unidad = (string)($s["unidad"] ?? "");
    $cantidad = (string)($s["cantidad"] ?? "");
  ?>
  <div class="d-flex justify-content-between align-items-center mb-3 section-heading">
    <div>
      <h3 class="mb-1">Productos asociados al stock</h3>
      <div class="text-muted small">
        Stock #<?= $id_stock ?> - <?= htmlspecialchars($nombre_stock) ?> (<?= htmlspecialchars($unidad) ?>) | Cantidad: <?= htmlspecialchars(stock_para_mostrar($cantidad, 3)) ?>
      </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-outline-secondary" href="index.php?c=stock&a=index">Volver</a>
      <a class="btn btn-success" href="index.php?c=productos&a=nuevo&id_stock=<?= $id_stock ?>">
        + Crear producto
      </a>
    </div>
  </div>
  <div class="card search-shell mb-3">
    <div class="card-body p-3">
      <form method="GET" action="index.php" class="row g-2 align-items-end" data-auto-submit-search="true" data-search-target="#stockProductosResultados">
        <input type="hidden" name="c" value="stock">
        <input type="hidden" name="a" value="productos">
        <input type="hidden" name="id" value="<?= $id_stock ?>">
        <div class="col-lg-5">
          <label class="form-label">Buscar producto asociado</label>
          <input type="text" class="form-control" name="buscar" value="<?= htmlspecialchars($texto_buscar) ?>" placeholder="Ej: nombre, código o precio">
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
          <a class="btn btn-outline-secondary" href="index.php?c=stock&a=productos&id=<?= $id_stock ?>">Limpiar</a>
        </div>
      </form>
    </div>
  </div>
  <div id="stockProductosResultados">
    <div class="card list-shell">
      <div class="card-body p-4">
        <div class="table-responsive">
          <table id="tablaStock" class="table table-striped align-middle admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Código barras</th>
              <th>Factor</th>
              <th>Ganancia %</th>
              <th>Precio final</th>
              <th>Estado</th>
              <th style="width: 140px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($productos as $p): ?>
            <tr>
              <td><?= (int)$p["id"] ?></td>
              <td><?= htmlspecialchars((string)($p["nombre"] ?? "")) ?></td>
              <td><?= htmlspecialchars((string)($p["cod_barras"] ?? "")) ?></td>
              <td><?= htmlspecialchars(numero_para_mostrar($p["factor_conversion"] ?? 0, 4)) ?></td>
              <td><?= htmlspecialchars((string)($p["ganancia"] ?? "")) ?></td>
              <td><?= htmlspecialchars(precio_para_mostrar($p["precio_final"] ?? 0)) ?></td>
              <td><?= ((int)($p["activo"] ?? 0) === 1) ? "Alta" : "Baja" ?></td>
              <td>
                <a class="btn btn-sm btn-secondary"
                   href="index.php?c=productos&a=editar&id=<?= (int)$p["id"] ?>">
                   Editar
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($productos) === 0): ?>
            <tr><td colspan="8" class="text-center text-muted">No hay productos asociados a este stock.</td></tr>
          <?php endif; ?>
          </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="d-flex justify-content-between align-items-center mb-3 section-heading">
    <div>
      <h3 class="mb-1">Stock</h3>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-outline-secondary" href="index.php?c=stock&a=faltantes">Faltantes</a>
      <a class="btn btn-primary" href="index.php?c=stock&a=nuevo">+ Nuevo</a>
    </div>
  </div>
  <div class="card search-shell mb-3">
    <div class="card-body p-3">
      <form method="GET" action="index.php" class="row g-2 align-items-end" data-auto-submit-search="true" data-search-target="#stockResultados">
        <input type="hidden" name="c" value="stock">
        <input type="hidden" name="a" value="index">
        <div class="col-lg-5">
          <label class="form-label">Buscar stock</label>
          <input type="text" class="form-control" name="buscar" value="<?= htmlspecialchars($texto_buscar) ?>" placeholder="Ej: nombre, unidad o precio">
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
          <a class="btn btn-outline-secondary" href="index.php?c=stock&a=index">Limpiar</a>
        </div>
      </form>
    </div>
  </div>
  <div id="stockResultados">
    <div class="card list-shell">
      <div class="card-body p-4">
        <div class="table-responsive">
          <table id="tablaStockProductos" class="table table-striped align-middle admin-table stock-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Cantidad</th>
              <th>Min.</th>
              <th>Max.</th>
              <th>Unidad</th>
              <th>Precio costo</th>
              <th style="width: 170px;">Agregar stock</th>
              <th style="width: 190px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($items as $fila): ?>
            <tr>
              <td><?= (int)$fila["id"] ?></td>
              <td><?= htmlspecialchars($fila["nombre"] ?? "") ?></td>
              <td><?= htmlspecialchars(stock_para_mostrar($fila["cantidad"] ?? 0, 3)) ?></td>
              <td><?= htmlspecialchars($fila["stock_minimo"] ?? "0") ?></td>
              <td><?= htmlspecialchars($fila["stock_maximo"] ?? "0") ?></td>
              <td><?= htmlspecialchars($fila["unidad"] ?? "") ?></td>
              <td><?= htmlspecialchars(precio_para_mostrar($fila["precio_costo"] ?? 0)) ?></td>
              <td>
                <form method="POST" action="index.php?c=stock&a=agregar" class="stock-add-form">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$fila["id"] ?>">
                  <input type="number" step="0.001" min="0.001" name="cantidad_agregar" class="form-control form-control-sm" placeholder="50" required>
                  <button class="btn btn-sm btn-success">Agregar</button>
                </form>
              </td>
              <td>
                <div class="acciones-grid acciones-grid-compactas acciones-stock">
                  <a class="btn btn-sm btn-secondary accion-btn"
                    href="index.php?c=stock&a=editar&id=<?= (int)$fila["id"] ?>">
                    Editar
                  </a>
                  <a class="btn btn-sm btn-danger accion-btn"
                    href="index.php?c=stock&a=eliminar&id=<?= (int)$fila["id"] ?>"
                    onclick="return confirm('¿Eliminar stock?');">
                    Eliminar
                  </a>
                  <a class="btn btn-sm btn-outline-primary accion-btn accion-productos"
                    href="index.php?c=stock&a=productos&id=<?= (int)$fila["id"] ?>">
                    Productos
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($items) === 0): ?>
            <tr><td colspan="9" class="text-center text-muted">Sin stock.</td></tr>
          <?php endif; ?>
          </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    let ok = true;
    const tabla = document.getElementById('tablaStockProductos');
    if (!tabla) ok = false;
    if (ok) {
      new DataTable('#tablaStockProductos', {
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
