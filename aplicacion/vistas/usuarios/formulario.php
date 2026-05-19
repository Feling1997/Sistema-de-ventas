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
$modulos_permisos = $modulos_permisos ?? [];
$permisos_usuario = $permisos_usuario ?? [];
$sin_permisos_guardados = count($permisos_usuario) === 0;
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

      <div class="mt-4" id="permisosUsuarioPanel">
        <h5 class="mb-1">Permisos del vendedor</h5>
        <div class="text-muted small mb-3">El administrador siempre ve todo. Para vendedor, marca que partes puede usar.</div>
        <div class="row g-2">
          <?php foreach ($modulos_permisos as $clave => $modulo): ?>
            <?php $checked = $sin_permisos_guardados || in_array((string)$clave, $permisos_usuario, true); ?>
            <label class="col-md-3 menu-config-item">
              <input type="checkbox" name="permisos[]" value="<?= htmlspecialchars((string)$clave) ?>" <?= $checked ? "checked" : "" ?>>
              <span><i class="bi <?= htmlspecialchars((string)$modulo["icono"]) ?>"></i> <?= htmlspecialchars((string)$modulo["texto"]) ?></span>
            </label>
          <?php endforeach; ?>
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
document.addEventListener("DOMContentLoaded", function () {
  const rol = document.querySelector('select[name="rol"]');
  const panel = document.getElementById("permisosUsuarioPanel");
  function syncPermisos() {
    if (panel && rol)
      panel.classList.toggle("d-none", rol.value === "ADMIN");
  }
  if (rol)
    rol.addEventListener("change", syncPermisos);
  syncPermisos();
});
</script>
