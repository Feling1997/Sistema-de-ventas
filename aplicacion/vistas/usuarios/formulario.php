<?php
$es_editar = false;
$accion = "index.php?c=usuarios&a=crear";
$titulo = "Nuevo usuario";
$texto_btn = "Crear";

if (isset($modo) && $modo === "editar") {
    $es_editar = true;
    $accion = "index.php?c=usuarios&a=actualizar";
    $titulo = "Editar usuario";
    $texto_btn = "Guardar cambios";
}

$id = (int)($u["id"] ?? 0);
$usuario = (string)($u["usuario"] ?? "");
$rol = (string)($u["rol"] ?? "VENDEDOR");
$activo = (int)($u["activo"] ?? 1);
?>

<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <h3 class="mb-0"><?= htmlspecialchars($titulo) ?></h3>
  <a class="btn btn-outline-secondary" href="index.php?c=usuarios&a=index">Volver</a>
</div>

<div class="card form-shell">
  <div class="card-body p-4">
    <form method="POST" action="<?= htmlspecialchars($accion) ?>" class="smart-form">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <?php if ($es_editar): ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
      <?php endif; ?>

      <div class="row g-2">
        <div class="col-md-3">
          <label class="form-label">Usuario</label>
          <input class="form-control form-control-lg" name="usuario" value="<?= htmlspecialchars($usuario) ?>" placeholder="Ingresar usuario">
        </div>
        <div class="col-md-3">
          <label class="form-label">Clave</label>
          <div class="input-group">
            <input type="password" class="form-control form-control-lg" name="clave" id="<?= $es_editar ? "clave_edit" : "clave" ?>" placeholder="<?= $es_editar ? "Dejar vacio para mantener" : "Ingresar clave" ?>">
            <button class="btn btn-outline-secondary" type="button" onclick="toggleClave('<?= $es_editar ? "clave_edit" : "clave" ?>')">Ver</button>
          </div>
        </div>
        <?php if (!$es_editar): ?>
          <div class="col-md-3">
            <label class="form-label">Repetir clave</label>
            <div class="input-group">
              <input type="password" class="form-control form-control-lg" name="clave2" id="clave2" placeholder="Repetir clave">
              <button class="btn btn-outline-secondary" type="button" onclick="toggleClave('clave2')">Ver</button>
            </div>
          </div>
        <?php endif; ?>
        <div class="col-md-3">
          <label class="form-label">Rol</label>
          <select class="form-select form-select-lg" name="rol">
            <option value="VENDEDOR" <?= ($rol === "VENDEDOR") ? "selected" : "" ?>>VENDEDOR</option>
            <option value="ADMIN" <?= ($rol === "ADMIN") ? "selected" : "" ?>>ADMIN</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Activo</label>
          <select class="form-select form-select-lg" name="activo">
            <option value="1" <?= ($activo === 1) ? "selected" : "" ?>>Si</option>
            <option value="0" <?= ($activo === 0) ? "selected" : "" ?>>No</option>
          </select>
        </div>
      </div>

      <div class="form-actions mt-2">
        <a class="btn btn-outline-secondary" href="index.php?c=usuarios&a=index">Cancelar</a>
        <button class="btn btn-primary"><?= htmlspecialchars($texto_btn) ?></button>
      </div>
    </form>
  </div>
</div>
<script>
function toggleClave(id) {
  const inp = document.getElementById(id);
  if (!inp) return;
  inp.type = (inp.type === "password") ? "text" : "password";
}
</script>
