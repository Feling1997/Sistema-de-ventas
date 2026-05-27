<section class="config-panel" data-panel="apariencia">
  <form method="POST" action="index.php?c=configuracion&a=guardar" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <input type="hidden" name="seccion" value="apariencia">
    <div class="config-panel-head">
      <div><h4>Apariencia</h4><p>Tema, colores, logo y comportamiento visual con vista previa inmediata.</p></div>
      <div class="d-flex gap-2 config-actions"><button class="btn btn-outline-danger" form="reset-apariencia" type="submit"><i class="bi bi-arrow-counterclockwise"></i> Restablecer</button><button class="btn btn-primary" type="submit"><i class="bi bi-check-circle"></i> Guardar cambios</button></div>
    </div>
    <ul class="nav nav-tabs config-tabs mb-3" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#apariencia-identidad" type="button">Identidad</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#apariencia-colores" type="button">Colores</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#apariencia-ui" type="button">Interfaz</button></li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="apariencia-identidad">
        <div class="config-grid">
          <div class="config-block"><div class="d-flex align-items-center gap-2 mb-3"><div class="icon-circle"><i class="bi bi-palette"></i></div><h5 class="mb-0">Marca visual</h5></div><div class="text-muted small mb-3">El nombre visible se toma de Comercio para evitar configurarlo dos veces.</div><?php if (trim((string)($config["logo"] ?? "")) !== ""): ?><div class="mb-3"><img class="config-current-logo" src="/VENTAS/<?= htmlspecialchars(ltrim((string)$config["logo"], "/")) ?>?v=<?= is_file(__DIR__ . "/../../../" . ltrim((string)$config["logo"], "/")) ? (string)filemtime(__DIR__ . "/../../../" . ltrim((string)$config["logo"], "/")) : "1" ?>" alt="Logo sistema"></div><?php endif; ?><label class="form-label"><div class="campo-icon"><i class="bi bi-image"></i></div> Logo sistema</label><input class="form-control live-logo" type="file" name="logo_archivo" accept=".jpg,.jpeg,.png,.gif,.webp"></div>
          <div class="config-block"><div class="d-flex align-items-center gap-2 mb-3"><div class="icon-circle"><i class="bi bi-folder2-open"></i></div><h5 class="mb-0">Archivos</h5></div><label class="form-label"><div class="campo-icon"><i class="bi bi-star"></i></div> Favicon</label><input class="form-control" type="file" name="favicon_archivo" accept=".ico,.png,.jpg,.jpeg"><label class="form-label mt-3"><div class="campo-icon"><i class="bi bi-card-image"></i></div> Imagen panel</label><input class="form-control" type="file" name="imagen_panel_archivo" accept=".jpg,.jpeg,.png,.gif,.webp"></div>
        </div>
      </div>
      <div class="tab-pane fade" id="apariencia-colores">
        <div class="config-block"><div class="d-flex align-items-center gap-2 mb-3"><div class="icon-circle"><i class="bi bi-eyedropper"></i></div><h5 class="mb-0">Paleta</h5></div><div class="color-grid">
          <label>Principal<input type="color" class="form-control form-control-color live-color" name="config[color_acento]" data-var="--preview-accent" value="<?= cfg_color_mod($config, "color_acento", "#1f6f8b") ?>"></label>
          <label>Secundario<input type="color" class="form-control form-control-color live-color" name="config[color_secundario]" data-var="--preview-secondary" value="<?= cfg_color_mod($config, "color_secundario", "#48aaa5") ?>"></label>
          <label>Fondo<input type="color" class="form-control form-control-color live-color" name="config[color_fondo]" data-var="--preview-bg" value="<?= cfg_color_mod($config, "color_fondo", "#f4f6f8") ?>"></label>
          <label>Tarjetas<input type="color" class="form-control form-control-color live-color" name="config[color_tarjetas]" data-var="--preview-card" value="<?= cfg_color_mod($config, "color_tarjetas", "#ffffff") ?>"></label>
          <label>Navbar A<input type="color" class="form-control form-control-color live-color" name="config[navbar_color_1]" data-var="--preview-nav-a" value="<?= cfg_color_mod($config, "navbar_color_1", "#000000") ?>"></label>
          <label>Navbar B<input type="color" class="form-control form-control-color live-color" name="config[navbar_color_2]" data-var="--preview-nav-b" value="<?= cfg_color_mod($config, "navbar_color_2", "#1f2937") ?>"></label>
        </div></div>
      </div>
      <div class="tab-pane fade" id="apariencia-ui">
        <div class="config-grid">
          <div class="config-block"><div class="d-flex align-items-center gap-2 mb-3"><div class="icon-circle"><i class="bi bi-moon-stars"></i></div><h5 class="mb-0">Tema</h5></div><select class="form-select live-mode" name="config[tema_modo]"><option value="claro" <?= cfg_select($config, "tema_modo", "claro", "claro") ?>>Claro</option><option value="oscuro" <?= cfg_select($config, "tema_modo", "oscuro") ?>>Oscuro</option><option value="automatico" <?= cfg_select($config, "tema_modo", "automatico") ?>>Automatico</option></select><label class="form-label mt-3">Tamano tarjetas</label><select class="form-select" name="config[ui_tamano_tarjetas]"><option value="compacto" <?= cfg_select($config, "ui_tamano_tarjetas", "compacto") ?>>Compactas</option><option value="medio" <?= cfg_select($config, "ui_tamano_tarjetas", "medio", "medio") ?>>Medias</option><option value="grande" <?= cfg_select($config, "ui_tamano_tarjetas", "grande") ?>>Grandes</option></select></div>
          <div class="config-block"><div class="d-flex align-items-center gap-2 mb-3"><div class="icon-circle"><i class="bi bi-sliders"></i></div><h5 class="mb-0">Detalles</h5></div><label class="form-label">Bordes redondeados</label><input type="range" min="0" max="22" class="form-range live-range" name="config[ui_radio_bordes]" data-var="--preview-radius" data-unit="px" value="<?= cfg_get($config, "ui_radio_bordes", "8") ?>"><div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" name="config[ui_sombras]" value="1" <?= cfg_checked($config, "ui_sombras", "1") ?>><label class="form-check-label">Sombras</label></div><div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="config[ui_animaciones]" value="1" <?= cfg_checked($config, "ui_animaciones", "1") ?>><label class="form-check-label">Animaciones suaves</label></div></div>
        </div>
      </div>
    </div>
  </form>
  <form id="reset-apariencia" method="POST" action="index.php?c=configuracion&a=restablecer"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="seccion" value="apariencia"></form>
</section>
