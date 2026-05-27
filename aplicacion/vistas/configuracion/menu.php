<section class="config-panel" data-panel="menu">
  <form method="POST" action="index.php?c=configuracion&a=guardar">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="seccion" value="menu">
    <div class="config-panel-head"><div><h4>Menu</h4><p>Mostrar, ocultar y ordenar los modulos del menu superior sin recargar la pantalla.</p></div><div class="d-flex gap-2"><button class="btn btn-outline-danger" form="reset-menu" type="submit">Restablecer</button><button class="btn btn-primary" type="submit">Guardar cambios</button></div></div>
    <div class="config-block">
      <h5>Modulos</h5>
      <div class="menu-dnd" id="menuDnd">
        <?php foreach ($modulos_ordenados as $clave_nav => $modulo): ?>
          <div class="menu-dnd-row" draggable="true" data-module="<?= htmlspecialchars($clave_nav) ?>">
            <span class="drag-handle"><i class="bi bi-grip-vertical"></i></span>
            <input type="hidden" name="navbar_modulos_orden[]" value="<?= htmlspecialchars($clave_nav) ?>">
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="navbar_modulos_visibles[]" value="<?= htmlspecialchars($clave_nav) ?>" <?= empty($visibles_nav) || in_array($clave_nav, $visibles_nav, true) ? "checked" : "" ?>></div>
            <i class="bi <?= htmlspecialchars((string)($modulo["icono"] ?? "bi-grid")) ?>"></i>
            <strong><?= htmlspecialchars((string)($modulo["texto"] ?? $clave_nav)) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </form>
  <form id="reset-menu" method="POST" action="index.php?c=configuracion&a=restablecer"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="seccion" value="menu"></form>
</section>
