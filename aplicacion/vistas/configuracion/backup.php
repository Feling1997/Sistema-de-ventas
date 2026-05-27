<section class="config-panel" data-panel="backup">
  <div class="config-panel-head">
    <div>
      <h4>Backup</h4>
      <p>Copias manuales y automaticas en carpeta local, disco extraible y Backblaze B2.</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-danger" form="reset-backup" type="submit">Restablecer</button>
      <button class="btn btn-primary" form="backup-config-form" type="submit">Guardar cambios</button>
    </div>
  </div>

  <?php
  $backup_estado = (string)($config["backup_ultimo_estado"] ?? "");
  $backup_error = (string)($config["backup_ultimo_error"] ?? "");
  $backup_local_carpeta = (string)($config["backup_local_carpeta"] ?? "");
  ?>

  <div class="config-grid mb-3">
    <div class="config-block">
      <h5><span class="bi bi-folder2-open"></span> Hacer backup ahora</h5>
      <p class="text-muted small mb-3">Usa este bloque cuando quieras elegir a donde guardar la copia en el momento.</p>
      <button class="btn btn-success w-100 mb-3" type="button" data-backup-save-picker="true">
        <span class="bi bi-folder2-open"></span> Examinar y guardar en esta PC
      </button>
      <form method="POST" action="index.php?c=configuracion&a=ejecutar_respaldo">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="backupManualCarpeta" name="destinos[]" value="carpeta" checked>
          <label class="form-check-label" for="backupManualCarpeta">Guardar en esta PC, pendrive o disco externo</label>
        </div>
        <input class="form-control mb-2" name="carpeta_destino" placeholder="Ej: E:\Respaldos o D:\Backups\Ventas" value="<?= htmlspecialchars($backup_local_carpeta) ?>">
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="backupManualB2" name="destinos[]" value="backblaze" <?= $b2_configurado ? "" : "disabled" ?>>
          <label class="form-check-label" for="backupManualB2">Subir tambien a Backblaze B2<?= $b2_configurado ? "" : " (configuralo primero)" ?></label>
        </div>
        <button class="btn btn-success" type="submit"><span class="bi bi-cloud-arrow-up-fill"></span> Hacer backup ahora</button>
      </form>
    </div>

    <div class="config-block">
      <h5><span class="bi bi-clock-history"></span> Estado</h5>
      <div class="alert <?= $backup_estado === "error" ? "alert-danger" : ($backup_estado === "parcial" ? "alert-warning" : "alert-info") ?> mb-2">
        <strong>Ultimo backup:</strong><br><?= cfg_get($config, "backup_ultimo", "Sin registros") ?>
      </div>
      <?php if ($backup_error !== ""): ?>
        <div class="alert alert-danger mb-0"><span class="bi bi-exclamation-circle-fill"></span> <?= htmlspecialchars($backup_error) ?></div>
      <?php else: ?>
        <div class="alert alert-success mb-0"><span class="bi bi-check-circle-fill"></span> Sin errores registrados.</div>
      <?php endif; ?>
    </div>
  </div>

  <form id="backup-config-form" method="POST" action="index.php?c=configuracion&a=guardar">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <input type="hidden" name="seccion" value="backup">
    <div class="config-grid">
      <div class="config-block">
        <h5><span class="bi bi-folder2-open"></span> Respaldo local automatico</h5>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="config[backup_local_habilitado]" value="1" <?= cfg_checked($config, "backup_local_habilitado", "1") ?>>
          <label class="form-check-label">Usar carpeta local o unidad externa</label>
        </div>
        <label class="form-label">Carpeta destino</label>
        <input class="form-control" name="config[backup_local_carpeta]" placeholder="Ej: E:\Respaldos" value="<?= cfg_get($config, "backup_local_carpeta") ?>">
        <button class="btn btn-outline-primary w-100 mt-2" type="button" data-backup-directory-picker="true">
          <span class="bi bi-folder2-open"></span> Examinar carpeta para backup automatico
        </button>
        <div class="form-text" data-backup-directory-label="true">Tambien podes escribir una ruta fija. Si elegis carpeta con Examinar, el permiso queda guardado en este navegador.</div>
        <div class="form-text">Si el disco extraible no esta conectado, el sistema avisa y deja el error registrado.</div>
      </div>

      <div class="config-block">
        <h5><span class="bi bi-cloud-arrow-up-fill"></span> Backblaze B2</h5>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="config[backup_b2_habilitado]" value="1" <?= cfg_checked($config, "backup_b2_habilitado") ?>>
          <label class="form-check-label">Activar Backblaze</label>
        </div>
        <input class="form-control mb-2" name="config[backup_b2_key_id]" placeholder="Key ID" value="<?= cfg_get($config, "backup_b2_key_id") ?>">
        <input class="form-control mb-2" type="password" name="config[backup_b2_application_key]" placeholder="<?= cfg_get($config, "backup_b2_application_key") !== "" ? "Clave guardada, dejar vacio para conservar" : "Application Key" ?>">
        <input class="form-control mb-2" name="config[backup_b2_bucket_id]" placeholder="Bucket ID" value="<?= cfg_get($config, "backup_b2_bucket_id") ?>">
        <input class="form-control mb-2" name="config[backup_b2_bucket_name]" placeholder="Bucket name" value="<?= cfg_get($config, "backup_b2_bucket_name") ?>">
        <input class="form-control" name="config[backup_b2_carpeta]" placeholder="Carpeta remota" value="<?= cfg_get($config, "backup_b2_carpeta", "ventas-reparaciones") ?>">
      </div>

      <div class="config-block">
        <h5><span class="bi bi-clock-history"></span> Automatico</h5>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="config[backup_automatico]" value="1" <?= cfg_checked($config, "backup_automatico", "1") ?>>
          <label class="form-check-label">Copias automaticas</label>
        </div>
        <label class="form-label">Frecuencia</label>
        <select class="form-select mb-2" name="config[backup_frecuencia]">
          <option value="diario" <?= cfg_select($config, "backup_frecuencia", "diario", "diario") ?>>Diario</option>
          <option value="semanal" <?= cfg_select($config, "backup_frecuencia", "semanal") ?>>Semanal</option>
          <option value="manual" <?= cfg_select($config, "backup_frecuencia", "manual") ?>>Solo manual</option>
        </select>
        <label class="form-label">Hora del aviso</label>
        <input class="form-control mb-2" type="time" name="config[backup_hora]" value="<?= cfg_get($config, "backup_hora", "18:55") ?>">
        <label class="form-label">Avisar minutos antes</label>
        <input class="form-control" type="number" min="0" max="180" name="config[backup_aviso_minutos]" value="<?= cfg_get($config, "backup_aviso_minutos", "5") ?>">
      </div>

      <div class="config-block">
        <h5>Destinos automaticos</h5>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="backupAutoLocal" name="config[backup_auto_local]" value="1" <?= cfg_checked($config, "backup_auto_local", "1") ?>>
          <label class="form-check-label" for="backupAutoLocal">Guardar en carpeta local o disco extraible</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="backupAutoB2" name="config[backup_auto_backblaze]" value="1" <?= cfg_checked($config, "backup_auto_backblaze") ?>>
          <label class="form-check-label" for="backupAutoB2">Subir a Backblaze B2</label>
        </div>
        <div class="form-text mt-2">Para tener dos respaldos automaticos, deja marcadas las dos opciones.</div>
      </div>
    </div>
  </form>
  <form id="reset-backup" method="POST" action="index.php?c=configuracion&a=restablecer">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <input type="hidden" name="seccion" value="backup">
  </form>
</section>
