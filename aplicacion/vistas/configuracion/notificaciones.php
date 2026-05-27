<section class="config-panel" data-panel="notificaciones">
  <form method="POST" action="index.php?c=configuracion&a=guardar">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="seccion" value="notificaciones">
    <div class="config-panel-head"><div><h4>Notificaciones</h4><p>Alertas, sonidos, toasts y eventos importantes.</p></div><div class="d-flex gap-2"><button class="btn btn-outline-danger" form="reset-notificaciones" type="submit">Restablecer</button><button class="btn btn-primary" type="submit">Guardar cambios</button></div></div>
    <div class="config-block"><h5>Eventos</h5><?php foreach (["notificaciones_sonidos" => "Sonidos", "notificaciones_toasts" => "Toasts", "notificaciones_alertas" => "Alertas", "notificaciones_stock_bajo" => "Stocks bajos", "notificaciones_ventas" => "Ventas completadas", "notificaciones_backup" => "Backup realizado"] as $clave => $txt): ?><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="config[<?= htmlspecialchars($clave) ?>]" value="1" <?= cfg_checked($config, $clave, "1") ?>><label class="form-check-label"><?= htmlspecialchars($txt) ?></label></div><?php endforeach; ?><button type="button" class="btn btn-outline-primary mt-2" data-demo-toast>Probar toast</button></div>
  </form>
  <form id="reset-notificaciones" method="POST" action="index.php?c=configuracion&a=restablecer"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="seccion" value="notificaciones"></form>
</section>
