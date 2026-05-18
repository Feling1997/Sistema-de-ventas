<?php
$listas = $listas ?? [];
$stocks = $stocks ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div>
    <h3 class="mb-1">Exportaciones</h3>
    <div class="text-muted small">Reportes descargables sin datos personales sensibles.</div>
  </div>
  <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=inicio">Volver</a>
</div>

<div class="card form-shell mb-3">
  <div class="card-body p-4">
    <h4 class="mb-1">Stock y pedidos</h4>
    <div class="text-muted small mb-3">Existencias, faltantes y pedido sugerido.</div>
    <form method="GET" action="index.php" class="row g-2 align-items-end" data-save-picker="true" data-export-name="stock_pedidos">
      <input type="hidden" name="c" value="exportaciones">
      <input type="hidden" name="a" value="ir">
      <div class="col-md-5">
        <label class="form-label">Reporte</label>
        <select class="form-select" name="reporte">
          <option value="stock_actual">Stock actual</option>
          <option value="pedido_minimo">Faltantes / pedido minimo</option>
          <option value="faltantes_completo">Faltantes completo</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Formato</label>
        <select class="form-select" name="formato">
          <option value="html">Ver / imprimir</option>
          <option value="pdf">PDF</option>
          <option value="xls">Excel</option>
          <option value="csv">CSV</option>
        </select>
      </div>
      <div class="col-md-3">
        <button class="btn btn-outline-primary w-100">Exportar</button>
      </div>
    </form>
  </div>
</div>

<div class="card form-shell mb-3">
  <div class="card-body p-4">
    <h4 class="mb-1">Articulos</h4>
    <div class="text-muted small mb-3">Catalogo de productos con lista de precios seleccionada.</div>
    <form method="GET" action="index.php" class="row g-2 align-items-end" data-save-picker="true" data-export-name="articulos">
      <input type="hidden" name="c" value="exportaciones">
      <input type="hidden" name="a" value="ir">
      <input type="hidden" name="reporte" value="articulos">
      <div class="col-md-3">
        <label class="form-label">Alcance</label>
        <select class="form-select" name="alcance">
          <option value="alta">Solo dados de alta</option>
          <option value="todos">Todos los articulos</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Lista de precios</label>
        <select class="form-select" name="id_lista_precio">
          <option value="0">Precio publico</option>
          <?php foreach ($listas as $lista): ?>
            <option value="<?= (int)$lista["id"] ?>"><?= htmlspecialchars((string)($lista["nombre"] ?? "")) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Formato</label>
        <select class="form-select" name="formato">
          <option value="html">Ver / imprimir</option>
          <option value="pdf">PDF</option>
          <option value="xls">Excel</option>
          <option value="csv">CSV</option>
        </select>
      </div>
      <div class="col-md-3">
        <button class="btn btn-outline-primary w-100">Exportar</button>
      </div>
    </form>
  </div>
</div>

<div class="card form-shell mb-3">
  <div class="card-body p-4">
    <h4 class="mb-1">Articulos por stock</h4>
    <div class="text-muted small mb-3">Productos asociados a un stock especifico.</div>
    <form method="GET" action="index.php" class="row g-2 align-items-end" data-save-picker="true" data-export-name="articulos_stock">
      <input type="hidden" name="c" value="exportaciones">
      <input type="hidden" name="a" value="ir">
      <input type="hidden" name="reporte" value="productos_stock">
      <div class="col-md-3">
        <label class="form-label">Stock</label>
        <select class="form-select" name="id_stock">
          <?php foreach ($stocks as $stock): ?>
            <option value="<?= (int)$stock["id"] ?>"><?= htmlspecialchars((string)($stock["nombre"] ?? "")) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Lista de precios</label>
        <select class="form-select" name="id_lista_precio">
          <option value="0">Precio publico</option>
          <?php foreach ($listas as $lista): ?>
            <option value="<?= (int)$lista["id"] ?>"><?= htmlspecialchars((string)($lista["nombre"] ?? "")) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Formato</label>
        <select class="form-select" name="formato">
          <option value="html">Ver / imprimir</option>
          <option value="pdf">PDF</option>
          <option value="xls">Excel</option>
          <option value="csv">CSV</option>
        </select>
      </div>
      <div class="col-md-3">
        <button class="btn btn-outline-primary w-100">Exportar</button>
      </div>
    </form>
  </div>
</div>

<div class="card form-shell mb-3">
  <div class="card-body p-4">
    <h4 class="mb-1">Listas de precios</h4>
    <div class="text-muted small mb-3">Lista general o una lista especifica.</div>
    <form method="GET" action="index.php" class="row g-2 align-items-end" data-save-picker="true" data-export-name="lista_precios">
      <input type="hidden" name="c" value="exportaciones">
      <input type="hidden" name="a" value="ir">
      <input type="hidden" name="reporte" value="lista_precios">
      <div class="col-md-5">
        <label class="form-label">Lista</label>
        <select class="form-select" name="id_lista_precio">
          <option value="0">Lista general</option>
          <?php foreach ($listas as $lista): ?>
            <option value="<?= (int)$lista["id"] ?>"><?= htmlspecialchars((string)($lista["nombre"] ?? "")) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Formato</label>
        <select class="form-select" name="formato">
          <option value="html">Ver / imprimir</option>
          <option value="pdf">PDF</option>
          <option value="xls">Excel</option>
          <option value="csv">CSV</option>
        </select>
      </div>
      <div class="col-md-3">
        <button class="btn btn-outline-primary w-100">Exportar</button>
      </div>
    </form>
  </div>
</div>

<div class="card form-shell mb-3">
  <div class="card-body p-4">
    <h4 class="mb-1">Estadisticas de ventas</h4>
    <div class="text-muted small mb-3">Analisis sin datos de clientes: rentabilidad, articulos, periodos y tickets.</div>
    <form method="GET" action="index.php" class="row g-2 align-items-end" data-save-picker="true" data-export-name="estadisticas">
      <input type="hidden" name="c" value="exportaciones">
      <input type="hidden" name="a" value="estadisticas">
      <div class="col-md-4">
        <label class="form-label">Reporte</label>
        <select class="form-select" name="tipo">
          <option value="resumen">Resumen general</option>
          <option value="articulos_detalle">Ventas por articulos detallado</option>
          <option value="diarias">Ventas por dia</option>
          <option value="mensuales">Ventas por mes</option>
          <option value="productos">Productos vendidos</option>
          <option value="stock_ventas">Stock vendido vs existencia</option>
          <option value="tickets">Tickets por rango de importe</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Desde</label>
        <input type="date" class="form-control" name="fecha_desde">
      </div>
      <div class="col-md-2">
        <label class="form-label">Hasta</label>
        <input type="date" class="form-control" name="fecha_hasta">
      </div>
      <div class="col-md-2">
        <label class="form-label">Formato</label>
        <select class="form-select" name="formato">
          <option value="html">Ver / imprimir</option>
          <option value="pdf">PDF</option>
          <option value="xls">Excel</option>
          <option value="csv">CSV</option>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-primary w-100">Exportar</button>
      </div>
    </form>
  </div>
</div>
