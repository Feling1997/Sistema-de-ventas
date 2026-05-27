<?php
$listas = $listas ?? [];
$stocks = $stocks ?? [];

$opciones_formato = [
    "html" => "Ver / imprimir",
    "pdf" => "PDF",
    "xls" => "Excel",
];

$hay_listas_precios = count($listas) > 0;

$opciones_lista = function () use ($listas, $hay_listas_precios): void {
    if (!$hay_listas_precios) { ?>
      <option value="0" selected disabled>Sin lista de precios</option>
    <?php return;
    }
    foreach ($listas as $lista): ?>
      <option value="<?= (int)$lista["id"] ?>"><?= htmlspecialchars((string)($lista["nombre"] ?? "")) ?></option>
    <?php endforeach;
};
?>
<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div>
    <h3 class="mb-1">Exportaciones</h3>
    <div class="text-muted small">Elegis el reporte, ajustas solo lo necesario y descargas.</div>
  </div>
  <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=inicio">Volver</a>
</div>

<div class="card form-shell mb-3">
  <div class="card-body p-4">
    <form method="GET" action="index.php" class="row g-3 align-items-end" data-save-picker="true" data-export-name="exportaciones">
      <input type="hidden" name="c" value="exportaciones">
      <input type="hidden" name="a" value="ir">

      <div class="col-lg-6">
        <label class="form-label">Reporte</label>
        <select class="form-select" name="reporte" id="export-reporte">
          <optgroup label="Articulos y precios">
            <option value="productos_stock" data-fields="stock,lista_precio" data-requires-list="1" data-help="Articulos vinculados a un stock especifico.">Articulos por stock</option>
            <option value="balanza" data-fields="lista_precio" data-requires-list="1" data-help="Archivo limpio para Excel y balanza, sin titulos ni tablas.">Balanza PLU</option>
            <option value="articulos" data-fields="alcance,lista_precio" data-requires-list="1" data-help="Catalogo de articulos con la lista de precios seleccionada.">Catalogo de articulos</option>
            <option value="lista_precios" data-fields="lista_precio" data-requires-list="1" data-help="Exporta una lista de precios cargada.">Lista de precios</option>
          </optgroup>
          <optgroup label="Importacion y cambios">
            <option value="cambios_precios" data-fields="periodo,lista_opcional" data-help="Cambios de precios por fecha, lista o todas las listas.">Cambios de precios</option>
          </optgroup>
          <optgroup label="Stock y pedidos">
            <option value="faltantes_completo" data-help="Faltantes y pedido sugerido completo.">Faltantes completo</option>
            <option value="pedido_minimo" data-help="Solo stocks por debajo del minimo configurado.">Pedido minimo</option>
            <option value="stock_actual" data-help="Existencia actual de todos los stocks.">Stock actual</option>
          </optgroup>
          <optgroup label="Ventas">
            <option value="estadisticas_articulos_detalle" data-fields="periodo,orden_detalle,limite" data-help="Articulos ordenados por unidades vendidas, con rentabilidad, participacion y stock." selected>Articulos mas vendidos</option>
            <option value="estadisticas_productos" data-fields="periodo,orden_basico,limite" data-help="Resumen simple de productos vendidos por cantidad, ventas o importe.">Productos vendidos simple</option>
            <option value="estadisticas_resumen" data-fields="periodo" data-help="Totales principales del periodo.">Resumen general</option>
            <option value="estadisticas_stock_ventas" data-fields="periodo" data-help="Stock consumido comparado con existencia actual.">Stock vendido vs existencia</option>
            <option value="estadisticas_tickets" data-fields="periodo" data-help="Ventas por rango de importe.">Tickets por rango</option>
            <option value="estadisticas_diarias" data-fields="periodo" data-help="Ventas agrupadas por dia.">Ventas por dia</option>
            <option value="estadisticas_mensuales" data-fields="periodo" data-help="Ventas agrupadas por mes.">Ventas por mes</option>
          </optgroup>
        </select>
        <div class="form-text" id="export-help"></div>
      </div>

      <div class="col-md-3 col-lg-2">
        <label class="form-label">Formato</label>
        <select class="form-select" name="formato">
          <?php foreach ($opciones_formato as $valor => $etiqueta): ?>
            <option value="<?= htmlspecialchars($valor) ?>"><?= htmlspecialchars($etiqueta) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3 col-lg-2 export-field" data-field="alcance">
        <label class="form-label">Alcance</label>
        <select class="form-select" name="alcance">
          <option value="alta">Solo dados de alta</option>
          <option value="todos">Todos los articulos</option>
        </select>
      </div>

      <div class="col-md-4 col-lg-3 export-field" data-field="stock">
        <label class="form-label">Stock</label>
        <select class="form-select" name="id_stock">
          <?php foreach ($stocks as $stock): ?>
            <option value="<?= (int)$stock["id"] ?>"><?= htmlspecialchars((string)($stock["nombre"] ?? "")) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4 col-lg-3 export-field" data-field="lista_precio">
        <label class="form-label">Lista de precios</label>
        <select class="form-select" name="id_lista_precio">
          <?php $opciones_lista(); ?>
        </select>
      </div>

      <div class="col-md-4 col-lg-3 export-field" data-field="lista_opcional">
        <label class="form-label">Lista de precios</label>
        <select class="form-select" name="id_lista_precio">
          <option value="0">Todas las listas</option>
          <?php if ($hay_listas_precios): ?>
            <?php foreach ($listas as $lista): ?>
              <option value="<?= (int)$lista["id"] ?>"><?= htmlspecialchars((string)($lista["nombre"] ?? "")) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>

      <div class="col-md-3 col-lg-2 export-field" data-field="periodo">
        <label class="form-label">Desde</label>
        <input type="date" class="form-control" name="fecha_desde">
      </div>

      <div class="col-md-3 col-lg-2 export-field" data-field="periodo">
        <label class="form-label">Hasta</label>
        <input type="date" class="form-control" name="fecha_hasta">
      </div>

      <div class="col-md-3 col-lg-2 export-field" data-field="orden_basico">
        <label class="form-label">Orden</label>
        <select class="form-select" name="orden">
          <option value="cantidad">Mas vendidos</option>
          <option value="ventas">Mas ventas</option>
          <option value="total">Mayor importe</option>
          <option value="nombre">Nombre</option>
        </select>
      </div>

      <div class="col-md-3 col-lg-2 export-field" data-field="orden_detalle">
        <label class="form-label">Orden</label>
        <select class="form-select" name="orden">
          <option value="cantidad">Mas vendidos</option>
          <option value="ventas">Mas ventas</option>
          <option value="total">Mayor importe</option>
          <option value="ganancia">Mayor ganancia</option>
          <option value="margen">Mayor margen</option>
          <option value="stock_menor">Menor stock</option>
          <option value="stock_mayor">Mayor stock</option>
          <option value="nombre">Nombre</option>
        </select>
      </div>

      <div class="col-md-3 col-lg-2 export-field" data-field="limite">
        <label class="form-label">Mostrar</label>
        <select class="form-select" name="limite">
          <option value="0">Todos</option>
          <option value="10">Top 10</option>
          <option value="20">Top 20</option>
          <option value="50">Top 50</option>
          <option value="100">Top 100</option>
          <option value="200">Top 200</option>
        </select>
      </div>

      <div class="col-md-3 col-lg-2 ms-lg-auto">
        <button class="btn btn-outline-primary w-100">Exportar</button>
      </div>
    </form>
  </div>
</div>

<div class="card form-shell mb-3">
  <div class="card-body p-4">
    <h4 class="mb-1">Importar listas de precios desde Excel</h4>
    <div class="text-muted small mb-3">Guarda el Excel como CSV y subilo aca. Busca el producto por Codigo/PLU o Nombre/Descripcion y carga solo las columnas que coincidan con listas ya creadas, sin importar mayusculas o minusculas: Publico, Mayorista, Costo, etc.</div>
    <form method="POST" action="index.php?c=exportaciones&a=importar_articulos_excel" enctype="multipart/form-data" class="row g-3 align-items-end">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <div class="col-md-7">
        <label class="form-label">Archivo Excel o CSV</label>
        <input type="file" class="form-control" name="archivo_articulos" accept=".csv,text/csv,.txt,.xls,.xlsx,.xlsm">
      </div>
      <div class="col-md-7">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="importar_disponibles" value="1" id="importarDisponibles" checked>
          <label class="form-check-label" for="importarDisponibles">Importar igual las listas que coinciden aunque haya columnas sin lista cargada</label>
        </div>
      </div>
      <div class="col-md-7">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="crear_productos" value="1" id="crearProductosImportacion">
          <label class="form-check-label" for="crearProductosImportacion">Crear productos que no existen usando Codigo y Descripcion del archivo</label>
        </div>
      </div>
      <div class="col-md-3">
        <button class="btn btn-primary w-100">Importar listas</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const reporte = document.getElementById("export-reporte");
  const ayuda = document.getElementById("export-help");
  const campos = Array.from(document.querySelectorAll(".export-field"));
  const boton = document.querySelector('button[type="submit"], .btn.btn-outline-primary');
  const hayListasPrecios = <?= $hay_listas_precios ? "true" : "false" ?>;

  function actualizarCampos() {
    const opcion = reporte.options[reporte.selectedIndex];
    const visibles = new Set((opcion.dataset.fields || "").split(",").filter(Boolean));
    const requiereLista = opcion.dataset.requiresList === "1";
    const esBalanza = opcion.value === "balanza";
    ayuda.textContent = requiereLista && !hayListasPrecios
      ? "Sin lista de precios cargada. Primero carga una lista de precios."
      : (opcion.dataset.help || "");
    if (boton)
      boton.disabled = requiereLista && !hayListasPrecios;

    campos.forEach(function (campo) {
      const activo = visibles.has(campo.dataset.field);
      campo.classList.toggle("d-none", !activo);
      campo.querySelectorAll("input, select").forEach(function (control) {
        control.disabled = !activo;
      });
    });

    const formato = document.querySelector('select[name="formato"]');
    if (formato) {
      formato.value = esBalanza ? "csv" : formato.value;
      formato.closest(".col-md-3, .col-lg-2")?.classList.toggle("d-none", esBalanza);
      formato.disabled = esBalanza;
    }
  }

  reporte.addEventListener("change", actualizarCampos);
  actualizarCampos();
});
</script>
