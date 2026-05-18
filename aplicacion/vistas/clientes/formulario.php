<?php
$es_editar = false;
$accion = "index.php?c=clientes&a=crear";
$titulo = "Nuevo cliente";
$texto_btn = "Crear";

if (isset($modo) && $modo === "editar") {
    $es_editar = true;
    $accion = "index.php?c=clientes&a=actualizar";
    $titulo = "Editar cliente";
    $texto_btn = "Guardar cambios";
}

$id = (int)($c["id"] ?? 0);
$nombre = (string)($c["nombre"] ?? "");
$dni = (string)($c["dni"] ?? "");
$tipo_documento = (string)($c["tipo_documento"] ?? "DNI");
$condicion_iva = (string)($c["condicion_iva"] ?? "Consumidor Final");
$email = (string)($c["email"] ?? "");
$telefono = (string)($c["telefono"] ?? "");
$direccion = (string)($c["direccion"] ?? "");
$id_lista_precio = (int)($c["id_lista_precio"] ?? 0);
$listas_precios = $listas_precios ?? [];
$tipos_documento = ["DNI" => "DNI", "CUIT" => "CUIT", "CUIL" => "CUIL", "PASAPORTE" => "Pasaporte"];
$condiciones_iva = [
    "Consumidor Final" => "Consumidor Final",
    "Responsable Inscripto" => "Responsable Inscripto",
    "Monotributista" => "Monotributista",
    "Exento" => "Exento",
    "No Responsable" => "No Responsable"
];
?>

<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <h3 class="mb-0"><?= htmlspecialchars($titulo) ?></h3>
  <a class="btn btn-outline-secondary" href="index.php?c=clientes&a=index">Volver</a>
</div>

<div class="card form-shell">
  <div class="card-body p-4">
    <form method="POST" action="<?= htmlspecialchars($accion) ?>" class="smart-form">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <?php if ($es_editar): ?>
        <input type="hidden" name="id" value="<?= $id ?>">
      <?php endif; ?>

      <div class="row g-2">
        <div class="col-md-6">
          <label class="form-label">Nombre *</label>
          <input class="form-control form-control-lg" name="nombre" value="<?= htmlspecialchars($nombre) ?>" placeholder="Ingresar nombre">
        </div>
        <div class="col-md-3">
          <label class="form-label">Tipo documento</label>
          <select class="form-select form-select-lg" name="tipo_documento">
            <?php foreach ($tipos_documento as $valor => $etiqueta): ?>
              <option value="<?= htmlspecialchars($valor) ?>" <?= $tipo_documento === $valor ? "selected" : "" ?>><?= htmlspecialchars($etiqueta) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">CUIT / DNI</label>
          <input class="form-control form-control-lg" name="dni" value="<?= htmlspecialchars($dni) ?>" placeholder="Sin guiones">
        </div>
        <div class="col-md-3">
          <label class="form-label">Condicion IVA</label>
          <select class="form-select form-select-lg" name="condicion_iva">
            <?php foreach ($condiciones_iva as $valor => $etiqueta): ?>
              <option value="<?= htmlspecialchars($valor) ?>" <?= $condicion_iva === $valor ? "selected" : "" ?>><?= htmlspecialchars($etiqueta) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Email fiscal</label>
          <input type="email" class="form-control form-control-lg" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="cliente@dominio.com">
        </div>
        <div class="col-md-3">
          <label class="form-label">Telefono</label>
          <input class="form-control form-control-lg" name="telefono" value="<?= htmlspecialchars($telefono) ?>" placeholder="Ingresar telefono">
        </div>
        <div class="col-md-3">
          <label class="form-label">Direccion</label>
          <input class="form-control form-control-lg" name="direccion" value="<?= htmlspecialchars($direccion) ?>" placeholder="Ingresar direccion">
        </div>
        <div class="col-md-3">
          <label class="form-label">Lista de precios</label>
          <select class="form-select form-select-lg" name="id_lista_precio">
            <?php foreach ($listas_precios as $lista): ?>
              <?php $lid = (int)$lista["id"]; ?>
              <option value="<?= $lid ?>" <?= $id_lista_precio === $lid ? "selected" : "" ?>><?= htmlspecialchars($lista["nombre"] ?? "") ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-actions mt-2">
        <a class="btn btn-outline-secondary" href="index.php?c=clientes&a=index">Cancelar</a>
        <button class="btn btn-primary"><?= htmlspecialchars($texto_btn) ?></button>
      </div>
    </form>
  </div>
</div>
