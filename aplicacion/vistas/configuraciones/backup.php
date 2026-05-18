<?php
$config = $config ?? [];
$carpeta_respaldos = $carpeta_respaldos ?? "";
$b2_configurado = $b2_configurado ?? false;
$bucket = trim((string)($config["backup_b2_bucket_name"] ?? ""));
$carpeta_b2 = trim((string)($config["backup_b2_carpeta"] ?? "ventas-reparaciones"));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="mb-1">Copia de seguridad</h3>
    <div class="text-muted small">Respaldo completo de clientes, ventas, stock, configuracion, reparaciones y tickets.</div>
  </div>
  <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=inicio">Volver</a>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card form-shell">
      <div class="card-body p-4">
        <div class="backup-head mb-3">
          <div class="backup-mark">
            <i class="bi bi-database-fill-check"></i>
          </div>
          <div>
            <h4 class="mb-1">Generar respaldo ahora</h4>
            <div class="text-muted small">El archivo incluye todas las tablas MySQL de Ventas y la base SQLite de Reparaciones.</div>
          </div>
        </div>

        <form method="POST" action="index.php?c=configuraciones&a=ejecutar_respaldo">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

          <div class="backup-options">
            <label class="backup-option">
              <input type="radio" name="destino" value="descargar" checked>
              <span>
                <strong>Descargar en esta PC</strong>
                <small>Genera el archivo y el navegador lo descarga.</small>
              </span>
            </label>

            <label class="backup-option">
              <input type="radio" name="destino" value="carpeta">
              <span>
                <strong>Copiar a carpeta, pendrive o Drive sincronizado</strong>
                <small>Indica una ruta como E:\Backups o C:\Users\Usuario\Google Drive\Backups.</small>
              </span>
            </label>

            <div class="mt-2 mb-3">
              <label class="form-label">Ruta de destino</label>
              <input class="form-control" name="carpeta_destino" placeholder="Ej: E:\Respaldos o C:\Users\Usuario\Google Drive\Respaldos">
            </div>

            <label class="backup-option <?= $b2_configurado ? "" : "backup-option-muted" ?>">
              <input type="radio" name="destino" value="backblaze" <?= $b2_configurado ? "" : "disabled" ?>>
              <span>
                <strong>Subir a Backblaze B2</strong>
                <small><?= $b2_configurado ? "Bucket: " . htmlspecialchars($bucket !== "" ? $bucket : "configurado") . " / " . htmlspecialchars($carpeta_b2) : "Primero configura Backblaze B2 en Config." ?></small>
              </span>
            </label>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-4">
            <button class="btn btn-primary" type="submit">Generar copia</button>
            <a class="btn btn-outline-secondary" href="index.php?c=configuraciones&a=sistema">Configurar Backblaze</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card form-shell h-100">
      <div class="card-body p-4">
        <h5 class="mb-2">Que se guarda</h5>
        <ul class="backup-list">
          <li>Clientes, ventas, productos, stock, cuentas y usuarios.</li>
          <li>Configuracion del comercio, tickets e imagenes.</li>
          <li>Reparaciones, pendientes, entregados y tickets del taller.</li>
          <li>Archivos esenciales del programa para reconstruir la instalacion.</li>
        </ul>
        <div class="small text-muted mt-3">
          Carpeta local de respaldo:<br>
          <code><?= htmlspecialchars((string)$carpeta_respaldos) ?></code>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.backup-head {
  display: flex;
  align-items: center;
  gap: 12px;
}
.backup-mark {
  width: 54px;
  height: 54px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  color: #fff;
  background: linear-gradient(135deg, #16a34a, #0f766e);
  font-size: 1.45rem;
  flex: 0 0 auto;
}
.backup-options {
  display: grid;
  gap: 10px;
}
.backup-option {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px;
  border: 1px solid var(--ui-border, #dbe3ea);
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
}
.backup-option input {
  margin-top: 4px;
}
.backup-option strong,
.backup-option small {
  display: block;
}
.backup-option small {
  margin-top: 3px;
  color: var(--ui-muted, #657789);
}
.backup-option-muted {
  opacity: .62;
  cursor: not-allowed;
}
.backup-list {
  margin: 0;
  padding-left: 18px;
  color: var(--ui-text, #203040);
}
.backup-list li + li {
  margin-top: 8px;
}
</style>
