<?php
$viendo_productos = false;
if (isset($productos) && is_array($productos) && isset($s) && is_array($s))
    $viendo_productos = true;
$texto_buscar = $texto_buscar ?? "";
$campo_buscar = $campo_buscar ?? "todos";
$metodo_buscar = $metodo_buscar ?? "contiene";
$campos_busqueda = $campos_busqueda ?? [];
$listas_precios = $listas_precios ?? [];
$alertas_stock_bajo = $alertas_stock_bajo ?? [];
$resumen_alertas_stock = $resumen_alertas_stock ?? ["total" => 0, "pendientes" => 0, "leidas" => 0];
$filtro_alertas_stock = $filtro_alertas_stock ?? "bajo";
$mostrar_alertas_leidas = $mostrar_alertas_leidas ?? false;
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
        <input type="hidden" name="orden" value="<?= htmlspecialchars($orden_productos_stock["campo"] ?? "nombre") ?>">
        <input type="hidden" name="direccion" value="<?= htmlspecialchars(strtolower($orden_productos_stock["direccion"] ?? "ASC")) ?>">
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
              <?= orden_tabla_th("Nombre", "nombre", $orden_productos_stock ?? [], "texto") ?>
              <?= orden_tabla_th("Codigo barras", "codigo_barras", $orden_productos_stock ?? [], "texto") ?>
              <th>Factor</th>
              <th>Ganancia %</th>
              <?= orden_tabla_th("Precio final", "precio", $orden_productos_stock ?? [], "numero") ?>
              <?= orden_tabla_th("Estado", "estado", $orden_productos_stock ?? [], "texto") ?>
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
  <div class="stock-alert-panel mb-3">
    <div class="stock-alert-panel-header">
      <div>
        <h5 class="mb-1"><i class="bi bi-exclamation-circle-fill"></i> Productos con stock bajo</h5>
        <div class="text-muted small">
          Pendientes: <?= (int)($resumen_alertas_stock["pendientes"] ?? 0) ?> | Leidos: <?= (int)($resumen_alertas_stock["leidas"] ?? 0) ?> | Total: <?= (int)($resumen_alertas_stock["total"] ?? 0) ?>
        </div>
      </div>
      <form method="GET" action="index.php" class="stock-alert-filters">
        <input type="hidden" name="c" value="stock">
        <input type="hidden" name="a" value="index">
        <label class="form-check">
          <input class="form-check-input" type="radio" name="filtro_alertas_stock" value="bajo" <?= $filtro_alertas_stock === "bajo" ? "checked" : "" ?> onchange="this.form.submit()">
          <span class="form-check-label">Solo stock bajo</span>
        </label>
        <label class="form-check">
          <input class="form-check-input" type="radio" name="filtro_alertas_stock" value="criticos" <?= $filtro_alertas_stock === "criticos" ? "checked" : "" ?> onchange="this.form.submit()">
          <span class="form-check-label">Solo criticos</span>
        </label>
        <label class="form-check">
          <input class="form-check-input" type="checkbox" name="mostrar_alertas_leidas" value="1" <?= $mostrar_alertas_leidas ? "checked" : "" ?> onchange="this.form.submit()">
          <span class="form-check-label">Mostrar leidos</span>
        </label>
      </form>
    </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0 stock-alert-table">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Stock actual</th>
            <th>Stock minimo</th>
            <th>Estado</th>
            <th>Ultimo movimiento</th>
            <th style="width: 170px;">Lectura</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($alertas_stock_bajo as $alerta): ?>
          <?php
            $cantidad_alerta = (float)($alerta["cantidad"] ?? 0);
            $dec_alerta = (int)($alerta["unidad_decimales"] ?? 3);
            $estado_alerta = $cantidad_alerta <= 0.000001 ? "Critico" : "Bajo";
            $estado_clase = $cantidad_alerta <= 0.000001 ? "critical" : "low";
            $pendiente_alerta = (int)($alerta["pendiente"] ?? 0) === 1;
            $fecha_mov = trim((string)($alerta["ultimo_movimiento"] ?? ""));
          ?>
          <tr class="<?= $pendiente_alerta ? "stock-alert-row-pending" : "stock-alert-row-read" ?>">
            <td>
              <strong><?= htmlspecialchars((string)($alerta["producto"] ?? "")) ?></strong>
              <div class="text-muted small"><?= htmlspecialchars((string)($alerta["stock_nombre"] ?? "")) ?></div>
            </td>
            <td><?= htmlspecialchars(stock_para_mostrar($alerta["cantidad"] ?? 0, $dec_alerta)) ?> <?= htmlspecialchars((string)($alerta["unidad"] ?? "")) ?></td>
            <td><?= htmlspecialchars(numero_para_mostrar($alerta["stock_minimo"] ?? 0, $dec_alerta)) ?> <?= htmlspecialchars((string)($alerta["unidad"] ?? "")) ?></td>
            <td><span class="stock-alert-state <?= $estado_clase ?>"><?= $estado_alerta === "Critico" ? "Critico" : "Bajo" ?></span></td>
            <td><?= $fecha_mov !== "" ? htmlspecialchars(date("d/m/Y H:i", strtotime($fecha_mov))) : "Sin movimientos" ?></td>
            <td>
              <?php if ($pendiente_alerta): ?>
                <form method="POST" action="index.php?c=stock&a=marcar_alerta_leida" class="m-0">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="id_producto" value="<?= (int)($alerta["id_producto"] ?? 0) ?>">
                  <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-check-circle-fill"></i> Marcar como leido</button>
                </form>
              <?php else: ?>
                <span class="badge bg-secondary">Leido</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (count($alertas_stock_bajo) === 0): ?>
          <tr><td colspan="6" class="text-center text-muted">No hay alertas para los filtros seleccionados.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card search-shell mb-3">
    <div class="card-body p-3">
      <form method="GET" action="index.php" class="row g-2 align-items-end" data-auto-submit-search="true" data-search-target="#stockResultados">
        <input type="hidden" name="c" value="stock">
        <input type="hidden" name="a" value="index">
        <input type="hidden" name="orden" value="<?= htmlspecialchars($orden_stock["campo"] ?? "nombre") ?>">
        <input type="hidden" name="direccion" value="<?= htmlspecialchars(strtolower($orden_stock["direccion"] ?? "ASC")) ?>">
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
              <?= orden_tabla_th("Nombre", "nombre", $orden_stock ?? [], "texto") ?>
              <?= orden_tabla_th("Cantidad", "stock", $orden_stock ?? [], "numero") ?>
              <th>Min.</th>
              <th>Max.</th>
              <th>Unidad</th>
              <th>Tipo</th>
              <?= orden_tabla_th("Precio costo", "precio", $orden_stock ?? [], "numero") ?>
              <th style="width: 170px;">Agregar stock</th>
              <th style="width: 190px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($items as $fila): ?>
            <?php
              $decimales_unidad = (int)($fila["unidad_decimales"] ?? 3);
              $stock_cantidad_num = parsear_numero_form($fila["cantidad"] ?? 0, 0);
              $stock_minimo_num = parsear_numero_form($fila["stock_minimo"] ?? 0, 0);
              $stock_bajo_fila = (int)($fila["activo"] ?? 0) === 1 && $stock_cantidad_num <= $stock_minimo_num;
              $stock_critico_fila = $stock_bajo_fila && $stock_cantidad_num <= 0.000001;
            ?>
            <tr class="<?= $stock_bajo_fila ? "stock-main-alert-row" : "" ?>">
              <td><?= (int)$fila["id"] ?></td>
              <td>
                <?php if ($stock_bajo_fila): ?>
                  <i class="bi bi-exclamation-circle-fill text-danger me-1"></i>
                <?php endif; ?>
                <strong><?= htmlspecialchars($fila["nombre"] ?? "") ?></strong>
                <?php if ($stock_bajo_fila): ?>
                  <span class="badge badge-stock-alerta-tabla ms-2"><?= $stock_critico_fila ? "Critico" : "Bajo" ?></span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars(stock_para_mostrar($fila["cantidad"] ?? 0, $decimales_unidad)) ?></td>
              <td><?= htmlspecialchars(numero_para_mostrar($fila["stock_minimo"] ?? "0", $decimales_unidad)) ?></td>
              <td><?= htmlspecialchars(numero_para_mostrar($fila["stock_maximo"] ?? "0", $decimales_unidad)) ?></td>
              <td><?= htmlspecialchars($fila["unidad"] ?? "") ?></td>
              <td><span class="badge bg-<?= (($fila["tipo_stock"] ?? "general") === "general") ? "primary" : "secondary" ?>"><?= (($fila["tipo_stock"] ?? "general") === "general") ? "General" : "Propio" ?></span></td>
              <td>
                <?= htmlspecialchars(precio_para_mostrar($fila["precio_costo"] ?? 0)) ?>
                <?php if (($fila["moneda_costo"] ?? "ARS") === "USD"): ?>
                  <div class="small text-muted">USD <?= htmlspecialchars(numero_para_mostrar($fila["costo_origen"] ?? 0, 2)) ?></div>
                <?php endif; ?>
              </td>
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
            <tr><td colspan="10" class="text-center text-muted">Sin stock.</td></tr>
          <?php endif; ?>
          </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php
    $toast_alerta_stock = null;
    foreach ($alertas_stock_bajo as $alerta_toast) {
        if ((int)($alerta_toast["pendiente"] ?? 0) === 1) {
            $toast_alerta_stock = $alerta_toast;
            break;
        }
    }
  ?>
  <?php if ($toast_alerta_stock !== null): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3">
      <div class="toast stock-low-toast" role="status" aria-live="polite" aria-atomic="true" data-stock-low-toast>
        <div class="toast-header">
          <i class="bi bi-exclamation-circle-fill text-danger me-2"></i>
          <strong class="me-auto">Stock bajo</strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
        <div class="toast-body">
          <?= htmlspecialchars((string)($toast_alerta_stock["producto"] ?? "")) ?>
        </div>
      </div>
    </div>
    <script>
    (function () {
      document.addEventListener('DOMContentLoaded', function () {
        const toastEl = document.querySelector('[data-stock-low-toast]');
        if (toastEl && window.bootstrap)
          bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 5000 }).show();
      });
    })();
    </script>
  <?php endif; ?>
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
