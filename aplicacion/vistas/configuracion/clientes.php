<section class="config-panel" data-panel="clientes">
  <form method="POST" action="index.php?c=configuracion&a=guardar">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="seccion" value="clientes">
    <div class="config-panel-head"><div><h4>Clientes</h4><p>Campos disponibles y comportamiento predeterminado para ventas.</p></div><div class="d-flex gap-2"><button class="btn btn-outline-danger" form="reset-clientes" type="submit">Restablecer</button><button class="btn btn-primary" type="submit">Guardar cambios</button></div></div>
    <div class="config-grid">
      <div class="config-block"><h5>Campos</h5><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="config[clientes_campos_extra]" value="1" <?= cfg_checked($config, "clientes_campos_extra") ?>><label class="form-check-label">Campos extra</label></div><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="config[clientes_validar_documento]" value="1" <?= cfg_checked($config, "clientes_validar_documento") ?>><label class="form-check-label">Validar documento</label></div></div>
      <div class="config-block"><h5>Comportamiento</h5><label class="form-label">Lista por defecto</label><input class="form-control" name="config[clientes_lista_defecto]" value="<?= cfg_get($config, "clientes_lista_defecto") ?>"><div class="form-text">Se usa como preferencia futura para clientes nuevos.</div></div>
    </div>
  </form>
  <form id="reset-clientes" method="POST" action="index.php?c=configuracion&a=restablecer"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="seccion" value="clientes"></form>
</section>
