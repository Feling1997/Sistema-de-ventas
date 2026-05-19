<?php
$config = $config ?? [];
function cfg_valor(array $config, string $clave): string {
    return htmlspecialchars((string)($config[$clave] ?? ""));
}
function cfg_color(array $config, string $clave, string $defecto): string {
    $valor = (string)($config[$clave] ?? $defecto);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $valor) ? $valor : $defecto;
}
function cfg_css_url_panel(string $ruta): string {
    $ruta = trim($ruta);
    if ($ruta === "")
        return "none";
    $ruta = str_replace([" ", ")", "("], ["%20", "%29", "%28"], $ruta);
    if (preg_match('/^https?:\/\//i', $ruta))
        return "url(" . $ruta . ")";
    return "url(/VENTAS/" . ltrim($ruta, "/") . ")";
}
$imagen_panel_preview_css = cfg_css_url_panel((string)($config["imagen_panel"] ?? ""));
$modulos_navbar = menu_modulos_base();
unset($modulos_navbar["inicio"]);
unset($modulos_navbar["configuraciones"]);
unset($modulos_navbar["usuarios"]);
$orden_navbar = array_filter(array_map("trim", explode(",", (string)($config["navbar_modulos_orden"] ?? ""))));
$visibles_navbar = array_filter(array_map("trim", explode(",", (string)($config["navbar_modulos_visibles"] ?? ""))));
$ordenados_navbar = [];
foreach ($orden_navbar as $clave_nav) {
    if (isset($modulos_navbar[$clave_nav]))
        $ordenados_navbar[$clave_nav] = $modulos_navbar[$clave_nav];
}
foreach ($modulos_navbar as $clave_nav => $modulo_nav) {
    if (!isset($ordenados_navbar[$clave_nav]))
        $ordenados_navbar[$clave_nav] = $modulo_nav;
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="mb-1">Configuracion del sistema</h3>
    <div class="text-muted small">Datos del comercio, ticket, apariencia del panel y accesos.</div>
  </div>
  <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url_volver ?? "index.php?c=ventas&a=inicio") ?>">Volver</a>
</div>

<form method="POST" action="index.php?c=configuraciones&a=guardar_sistema" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
  <input type="hidden" name="seccion_navbar" value="<?= htmlspecialchars((string)($seccion_navbar ?? "ventas")) ?>">

  <div class="card form-shell mb-3">
    <div class="card-body p-4">
      <h4 class="mb-1">Datos del comercio</h4>
      <div class="text-muted small mb-3">Se usan en comprobantes, reportes y cabeceras formales.</div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre del comercio</label>
          <input class="form-control" name="nombre_comercio" value="<?= cfg_valor($config, "nombre_comercio") ?>" placeholder="Ej: Mi Comercio">
        </div>
        <div class="col-md-6">
          <label class="form-label">Razon social</label>
          <input class="form-control" name="razon_social" value="<?= cfg_valor($config, "razon_social") ?>" placeholder="Nombre fiscal o titular">
        </div>
        <div class="col-md-4">
          <label class="form-label">CUIT</label>
          <input class="form-control" name="cuit" value="<?= cfg_valor($config, "cuit") ?>" placeholder="20-00000000-0">
        </div>
        <div class="col-md-4">
          <label class="form-label">Condicion IVA</label>
          <select class="form-select" name="condicion_iva">
            <?php
            $condicion = (string)($config["condicion_iva"] ?? "");
            foreach (["", "Responsable Inscripto", "Monotributista", "Exento", "Consumidor Final", "No Responsable"] as $opcion):
            ?>
              <option value="<?= htmlspecialchars($opcion) ?>" <?= $condicion === $opcion ? "selected" : "" ?>><?= $opcion === "" ? "Seleccionar" : htmlspecialchars($opcion) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Punto de venta</label>
          <input type="number" min="1" class="form-control" name="punto_venta" value="<?= (int)($config["punto_venta"] ?? 1) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Direccion</label>
          <input class="form-control" name="domicilio" value="<?= cfg_valor($config, "domicilio") ?>" placeholder="Calle, numero, local">
        </div>
        <div class="col-md-3">
          <label class="form-label">Localidad</label>
          <input class="form-control" name="localidad" value="<?= cfg_valor($config, "localidad") ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Provincia</label>
          <input class="form-control" name="provincia" value="<?= cfg_valor($config, "provincia") ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Telefonos</label>
          <input class="form-control" name="telefonos" value="<?= cfg_valor($config, "telefonos") ?>" placeholder="Lineas separadas por coma">
        </div>
        <div class="col-md-4">
          <label class="form-label">WhatsApp</label>
          <input class="form-control" name="whatsapp" value="<?= cfg_valor($config, "whatsapp") ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" name="email" value="<?= cfg_valor($config, "email") ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Sitio web o redes</label>
          <input class="form-control" name="sitio_web" value="<?= cfg_valor($config, "sitio_web") ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Ingresos brutos / No contrib.</label>
          <input class="form-control" name="ingresos_brutos" value="<?= cfg_valor($config, "ingresos_brutos") ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Inicio de actividades</label>
          <input type="date" class="form-control" name="inicio_actividades" value="<?= cfg_valor($config, "inicio_actividades") ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card form-shell mb-3">
    <div class="card-body p-4">
      <h4 class="mb-1">Tickets y ventas</h4>
      <div class="text-muted small mb-3">Estos datos se aplican a tickets, presupuestos y comprobantes de compra/venta.</div>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Formato para imprimir ventas</label>
          <select class="form-select" name="formato_impresion_ticket">
            <?php $formato_impresion_ticket = (string)($config["formato_impresion_ticket"] ?? "80"); ?>
            <option value="80" <?= $formato_impresion_ticket === "80" ? "selected" : "" ?>>Comandera 80 mm</option>
            <option value="58" <?= $formato_impresion_ticket === "58" ? "selected" : "" ?>>Ticket 58 mm</option>
            <option value="a4" <?= $formato_impresion_ticket === "a4" ? "selected" : "" ?>>Hoja A4</option>
          </select>
        </div>
        <div class="col-md-8">
          <label class="form-label">Mensaje al pie del ticket</label>
          <textarea class="form-control" name="texto_pie_ticket" rows="2"><?= cfg_valor($config, "texto_pie_ticket") ?></textarea>
          <div class="form-text">Solo aparece en tickets/comprobantes de venta, no en stock ni listas de precios.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Control de stock en ventas</label>
          <?php $controlar_stock_ventas = (string)($config["controlar_stock_ventas"] ?? "1"); ?>
          <select class="form-select" name="controlar_stock_ventas">
            <option value="1" <?= $controlar_stock_ventas === "1" ? "selected" : "" ?>>Controlar stock disponible</option>
            <option value="0" <?= $controlar_stock_ventas === "0" ? "selected" : "" ?>>Permitir stock negativo</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Codigo de balanza</label>
          <?php $balanza_modo = (string)($config["balanza_modo"] ?? "auto"); ?>
          <select class="form-select" name="balanza_modo">
            <option value="auto" <?= $balanza_modo === "auto" ? "selected" : "" ?>>Automatico</option>
            <option value="cantidad" <?= $balanza_modo === "cantidad" ? "selected" : "" ?>>PLU + peso / cantidad</option>
            <option value="importe" <?= $balanza_modo === "importe" ? "selected" : "" ?>>PLU + importe</option>
          </select>
          <div class="form-text">Usa automatico salvo que la balanza cargue mal cantidad o importe.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Digitos del PLU</label>
          <input type="number" min="1" max="8" step="1" class="form-control" name="balanza_plu_digitos" value="<?= cfg_valor($config, "balanza_plu_digitos") !== "" ? cfg_valor($config, "balanza_plu_digitos") : "5" ?>">
          <div class="form-text">Si el PLU es 1, podes cargar 1; se interpreta como 00001.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Decimales de peso/cantidad</label>
          <input type="number" min="0" max="4" step="1" class="form-control" name="balanza_valor_decimales" value="<?= cfg_valor($config, "balanza_valor_decimales") !== "" ? cfg_valor($config, "balanza_valor_decimales") : "3" ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Decimales de importe</label>
          <input type="number" min="0" max="4" step="1" class="form-control" name="balanza_importe_decimales" value="<?= cfg_valor($config, "balanza_importe_decimales") !== "" ? cfg_valor($config, "balanza_importe_decimales") : "2" ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Prefijos peso/cantidad</label>
          <input class="form-control" name="balanza_prefijos_cantidad" value="<?= cfg_valor($config, "balanza_prefijos_cantidad") !== "" ? cfg_valor($config, "balanza_prefijos_cantidad") : "20,21,23,25,27,29" ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Prefijos importe</label>
          <input class="form-control" name="balanza_prefijos_importe" value="<?= cfg_valor($config, "balanza_prefijos_importe") !== "" ? cfg_valor($config, "balanza_prefijos_importe") : "22,24,26,28" ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Logo del ticket</label>
          <input type="file" class="form-control" name="logo_ticket_archivo" accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif">
        </div>
        <div class="col-md-6">
          <label class="form-label">Logo actual</label>
          <div class="form-control bg-light"><?= cfg_valor($config, "logo_ticket") !== "" ? cfg_valor($config, "logo_ticket") : "Sin imagen cargada" ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card form-shell mb-3">
    <div class="card-body p-4">
      <h4 class="mb-1">Apariencia del panel</h4>
      <div class="text-muted small mb-3">Colores y foto del panel principal. Se aplican al guardar y recargar.</div>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Estilo general</label>
          <select class="form-select" name="tema_paneles">
            <?php $tema_paneles = (string)($config["tema_paneles"] ?? "claro"); ?>
            <option value="claro" <?= $tema_paneles === "claro" ? "selected" : "" ?>>Claro</option>
            <option value="compacto" <?= $tema_paneles === "compacto" ? "selected" : "" ?>>Compacto</option>
            <option value="alto_contraste" <?= $tema_paneles === "alto_contraste" ? "selected" : "" ?>>Alto contraste</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Color principal</label>
          <input type="color" class="form-control form-control-color" name="color_acento" value="<?= cfg_color($config, "color_acento", "#1f6f8b") ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Fondo principal</label>
          <input type="color" class="form-control form-control-color" name="color_fondo" value="<?= cfg_color($config, "color_fondo", "#f4f6f8") ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Fondo superior</label>
          <input type="color" class="form-control form-control-color" name="color_fondo_secundario" value="<?= cfg_color($config, "color_fondo_secundario", "#f9fbfc") ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Tarjetas y formularios</label>
          <input type="color" class="form-control form-control-color" name="color_tarjetas" value="<?= cfg_color($config, "color_tarjetas", "#ffffff") ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Bordes</label>
          <input type="color" class="form-control form-control-color" name="color_borde" value="<?= cfg_color($config, "color_borde", "#dbe3ea") ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Texto principal</label>
          <input type="color" class="form-control form-control-color" name="color_texto" value="<?= cfg_color($config, "color_texto", "#203040") ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Texto secundario</label>
          <input type="color" class="form-control form-control-color" name="color_texto_suave" value="<?= cfg_color($config, "color_texto_suave", "#657789") ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Panel inicio color 1</label>
          <input type="color" class="form-control form-control-color" name="color_panel_inicio" value="<?= cfg_color($config, "color_panel_inicio", "#155e75") ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Panel inicio color 2</label>
          <input type="color" class="form-control form-control-color" name="color_panel_inicio_2" value="<?= cfg_color($config, "color_panel_inicio_2", "#48aaa5") ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Foto del panel principal</label>
          <input type="file" class="form-control" name="imagen_panel_archivo" accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif">
        </div>
        <div class="col-md-4">
          <label class="form-label">Foto actual</label>
          <div class="form-control bg-light"><?= cfg_valor($config, "imagen_panel") !== "" ? cfg_valor($config, "imagen_panel") : "Sin imagen cargada" ?></div>
        </div>
        <div class="col-12">
          <div class="config-preview" id="panelPreview" style="--preview-image: <?= htmlspecialchars($imagen_panel_preview_css) ?>;">
            <div class="config-preview-hero">
              <div>
                <div class="config-preview-brand">Panel principal</div>
                <div class="config-preview-sub">Vista previa de colores, fondo y foto</div>
              </div>
              <span class="config-preview-pill">Acceso rapido</span>
            </div>
            <div class="config-preview-grid">
              <div class="config-preview-card">
                <div class="config-preview-icon"></div>
                <strong>Ventas</strong>
                <span>Modulo principal</span>
              </div>
              <div class="config-preview-card">
                <div class="config-preview-icon"></div>
                <strong>Stock</strong>
                <span>Control de productos</span>
              </div>
              <div class="config-preview-card">
                <div class="config-preview-icon"></div>
                <strong>Listas</strong>
                <span>Precios y reportes</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card form-shell mb-3">
    <div class="card-body p-4">
      <h4 class="mb-1">Barra superior</h4>
      <div class="text-muted small mb-3">Controla marca, botones, colores y orden de modulos de la barra superior.</div>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Texto de marca</label>
          <input class="form-control" name="navbar_marca_texto" value="<?= cfg_valor($config, "navbar_marca_texto") ?>" placeholder="MI COMERCIO">
        </div>
        <div class="col-md-4">
          <label class="form-label">Opacidad de botones</label>
          <input type="number" min="0" max="100" class="form-control" name="navbar_boton_opacidad" value="<?= (int)($config["navbar_boton_opacidad"] ?? 10) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Color fondo 1</label>
          <input type="color" class="form-control form-control-color" name="navbar_color_1" value="<?= cfg_color($config, "navbar_color_1", "#000000") ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Color fondo 2</label>
          <input type="color" class="form-control form-control-color" name="navbar_color_2" value="<?= cfg_color($config, "navbar_color_2", "#1f2937") ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Color texto</label>
          <input type="color" class="form-control form-control-color" name="navbar_texto_color" value="<?= cfg_color($config, "navbar_texto_color", "#ffffff") ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Color botones</label>
          <input type="color" class="form-control form-control-color" name="navbar_boton_fondo" value="<?= cfg_color($config, "navbar_boton_fondo", "#ffffff") ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Borde botones</label>
          <input type="color" class="form-control form-control-color" name="navbar_boton_borde" value="<?= cfg_color($config, "navbar_boton_borde", "#ffffff") ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Datos y botones visibles</label>
          <div class="form-text mb-2">Marca, Config, usuario y Salir/Volver quedan siempre visibles por seguridad y acceso rapido.</div>
          <div class="row g-2">
            <?php
            $checks_nav = [
              "navbar_mostrar_rol" => "Rol",
            ];
            foreach ($checks_nav as $clave_check => $texto_check):
            ?>
              <label class="col-md-4 menu-config-item">
                <input type="checkbox" name="<?= htmlspecialchars($clave_check) ?>" value="1" <?= (string)($config[$clave_check] ?? "1") === "1" ? "checked" : "" ?>>
                <span><?= htmlspecialchars($texto_check) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label">Modulos visibles y orden</label>
          <div class="table-responsive">
            <table class="table table-striped align-middle admin-table">
              <thead>
                <tr><th>Mostrar</th><th>Orden</th><th>Modulo</th></tr>
              </thead>
              <tbody>
                <?php $pos_nav = 1; foreach ($ordenados_navbar as $clave_nav => $modulo_nav): ?>
                  <tr>
                    <td>
                      <input type="checkbox" name="navbar_modulos_visibles[]" value="<?= htmlspecialchars($clave_nav) ?>" <?= empty($visibles_navbar) || in_array($clave_nav, $visibles_navbar, true) ? "checked" : "" ?>>
                    </td>
                    <td style="width: 120px;">
                      <input type="number" min="1" class="form-control form-control-sm navbar-order-input" data-module="<?= htmlspecialchars($clave_nav) ?>" value="<?= $pos_nav ?>">
                      <input type="hidden" name="navbar_modulos_orden[]" value="<?= htmlspecialchars($clave_nav) ?>">
                    </td>
                    <td><?= htmlspecialchars((string)($modulo_nav["texto"] ?? $clave_nav)) ?></td>
                  </tr>
                <?php $pos_nav++; endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="form-text">Cambia los numeros de orden; al guardar se aplican en la barra superior.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card form-shell mb-3">
    <div class="card-body p-4">
      <h4 class="mb-1">Accesos y modulos</h4>
      <div class="text-muted small mb-3">Opciones de acceso rapido dentro del sistema.</div>
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">Acceso a Reparaciones Python</label>
          <input class="form-control" name="url_reparaciones" value="<?= cfg_valor($config, "url_reparaciones") ?>" placeholder="index.php?c=reparaciones&a=index">
        </div>
        <div class="col-md-2">
          <label class="form-label">Mostrar acceso</label>
          <select class="form-select" name="mostrar_reparaciones">
            <?php $mostrar_reparaciones = (string)($config["mostrar_reparaciones"] ?? "1"); ?>
            <option value="1" <?= $mostrar_reparaciones === "1" ? "selected" : "" ?>>Si</option>
            <option value="0" <?= $mostrar_reparaciones === "0" ? "selected" : "" ?>>No</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Tecla rapida</label>
          <select class="form-select" name="atajo_reparaciones">
            <?php
            $atajo_reparaciones = (string)($config["atajo_reparaciones"] ?? "F9");
            foreach (["F8", "F9", "F10", "F12"] as $opcion_atajo):
            ?>
              <option value="<?= htmlspecialchars($opcion_atajo) ?>" <?= $atajo_reparaciones === $opcion_atajo ? "selected" : "" ?>><?= htmlspecialchars($opcion_atajo) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="card form-shell mb-3">
    <div class="card-body p-4">
      <h4 class="mb-1">Copia de seguridad en Backblaze B2</h4>
      <div class="text-muted small mb-3">Sube automaticamente los respaldos diarios a un bucket de Backblaze B2.</div>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Subida automatica</label>
          <?php $backup_b2_habilitado = (string)($config["backup_b2_habilitado"] ?? "0"); ?>
          <select class="form-select" name="backup_b2_habilitado">
            <option value="1" <?= $backup_b2_habilitado === "1" ? "selected" : "" ?>>Activada</option>
            <option value="0" <?= $backup_b2_habilitado === "0" ? "selected" : "" ?>>Desactivada</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Key ID</label>
          <input class="form-control" name="backup_b2_key_id" value="<?= cfg_valor($config, "backup_b2_key_id") ?>" placeholder="Application Key ID">
        </div>
        <div class="col-md-3">
          <label class="form-label">Application Key</label>
          <input class="form-control" name="backup_b2_application_key" type="password" value="" placeholder="<?= cfg_valor($config, "backup_b2_application_key") !== "" ? "Guardada, dejar vacio para conservar" : "Application Key" ?>">
          <div class="form-text">Por seguridad no se muestra. Si queda vacio, conserva la clave guardada.</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Bucket ID</label>
          <input class="form-control" name="backup_b2_bucket_id" value="<?= cfg_valor($config, "backup_b2_bucket_id") ?>" placeholder="Bucket ID">
        </div>
        <div class="col-md-4">
          <label class="form-label">Nombre del bucket</label>
                <input class="form-control" name="backup_b2_bucket_name" value="<?= cfg_valor($config, "backup_b2_bucket_name") ?>" placeholder="Ej: backups-mi-comercio">
        </div>
        <div class="col-md-4">
          <label class="form-label">Carpeta remota</label>
          <input class="form-control" name="backup_b2_carpeta" value="<?= cfg_valor($config, "backup_b2_carpeta") ?>" placeholder="ventas-reparaciones">
        </div>
        <div class="col-md-4">
          <label class="form-label">Carpeta local respaldada</label>
          <div class="form-control bg-light">C:\xampp82\htdocs\VENTAS\respaldos</div>
        </div>
      </div>
      <div class="form-text mt-3">Guarda esta configuracion antes de probar conexion o subir un respaldo.</div>
    </div>
  </div>

  <div class="card form-shell mb-3">
    <div class="card-body p-4">
      <h4 class="mb-1">Acceso al sistema</h4>
      <div class="text-muted small mb-3">Define si el programa pide usuario y contraseña al abrir.</div>
      <?php $auth_modo = (string)($config["auth_modo"] ?? "login"); ?>
      <div class="row g-2">
        <div class="col-md-6">
          <label class="form-label">Modo de acceso</label>
          <select class="form-select" name="auth_modo">
            <option value="login" <?= $auth_modo === "login" ? "selected" : "" ?>>Con usuario y contraseña</option>
            <option value="sin_login" <?= $auth_modo === "sin_login" ? "selected" : "" ?>>Entrar sin pedir nada</option>
          </select>
          <div class="form-text">Sin contraseña entra como administrador local. Para limitar vendedores, usa Usuarios.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end mb-4">
    <button class="btn btn-primary">Guardar configuracion</button>
  </div>
</form>

<form method="POST" action="index.php?c=configuraciones&a=restablecer_sistema" onsubmit="return confirm('Seguro que deseas restablecer la configuración a los valores iniciales? Se perderán los cambios actuales.');">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
  <div class="d-flex justify-content-end mb-4">
    <button type="submit" class="btn btn-outline-danger">Restablecer valores iniciales</button>
  </div>
</form>

<div class="card form-shell mb-3">
  <div class="card-body p-4">
    <h4 class="mb-1">Acciones de respaldo</h4>
    <div class="text-muted small mb-3">Usan la configuracion de Backblaze B2 guardada.</div>
    <div class="d-flex flex-wrap gap-2">
      <form method="POST" action="index.php?c=configuraciones&a=probar_backblaze">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <button class="btn btn-outline-secondary" type="submit">Probar Backblaze</button>
      </form>
      <form method="POST" action="index.php?c=configuraciones&a=subir_respaldo_backblaze" onsubmit="return confirm('Generar un respaldo ahora y subirlo a Backblaze B2?');">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <button class="btn btn-outline-primary" type="submit">Generar y subir ahora</button>
      </form>
    </div>
  </div>
</div>

<style>
.config-preview {
  margin-top: 8px;
  padding: 14px;
  border: 1px solid var(--preview-border, #dbe3ea);
  border-radius: 14px;
  background: linear-gradient(180deg, var(--preview-bg-start, #f9fbfc), var(--preview-bg, #f4f6f8));
  color: var(--preview-text, #203040);
}
.config-preview-hero {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  min-height: 112px;
  padding: 18px;
  border-radius: 12px;
  color: #fff;
  background-color: var(--preview-home-a, #155e75);
  background-image:
    linear-gradient(115deg, rgba(0,0,0,.46), rgba(0,0,0,.14)),
    var(--preview-image, none),
    linear-gradient(115deg, var(--preview-home-a, #155e75), var(--preview-home-b, #48aaa5));
  background-size: cover, 100% 100%, cover;
  background-position: center;
  background-repeat: no-repeat;
}
.config-preview-brand {
  font-weight: 800;
  font-size: 22px;
}
.config-preview-sub {
  margin-top: 4px;
  color: rgba(255,255,255,.86);
  font-size: 13px;
}
.config-preview-pill {
  padding: 10px 14px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,.35);
  background: rgba(255,255,255,.16);
  font-size: 13px;
  font-weight: 700;
  white-space: nowrap;
}
.config-preview-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  margin-top: 12px;
}
.config-preview-card {
  min-height: 96px;
  padding: 14px;
  border: 1px solid var(--preview-border, #dbe3ea);
  border-radius: 12px;
  background: var(--preview-card, #fff);
  color: var(--preview-text, #203040);
  box-shadow: 0 10px 22px rgba(15,23,42,.08);
}
.config-preview-card strong,
.config-preview-card span {
  display: block;
}
.config-preview-card span {
  margin-top: 3px;
  color: var(--preview-muted, #657789);
  font-size: 12px;
}
.config-preview-icon {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  margin-bottom: 8px;
  background: var(--preview-accent, #1f6f8b);
}
@media (max-width: 760px) {
  .config-preview-grid { grid-template-columns: 1fr; }
  .config-preview-hero { align-items: flex-start; flex-direction: column; }
}
</style>

<script>
(function () {
  function input(name) {
    return document.querySelector('[name="' + name + '"]');
  }

  function aplicarPreview() {
    const preview = document.getElementById('panelPreview');
    if (!preview)
      return;
    const mapa = {
      color_acento: '--preview-accent',
      color_fondo: '--preview-bg',
      color_fondo_secundario: '--preview-bg-start',
      color_tarjetas: '--preview-card',
      color_texto: '--preview-text',
      color_texto_suave: '--preview-muted',
      color_borde: '--preview-border',
      color_panel_inicio: '--preview-home-a',
      color_panel_inicio_2: '--preview-home-b'
    };
    Object.keys(mapa).forEach(function (name) {
      const el = input(name);
      if (el && el.value)
        preview.style.setProperty(mapa[name], el.value);
    });
  }

  function enlazarImagenPreview() {
    const archivo = input('imagen_panel_archivo');
    const preview = document.getElementById('panelPreview');
    if (!archivo || !preview)
      return;
    archivo.addEventListener('change', function () {
      const file = archivo.files && archivo.files[0] ? archivo.files[0] : null;
      if (!file)
        return;
      const url = URL.createObjectURL(file);
      preview.style.setProperty('--preview-image', "url('" + url + "')");
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    aplicarPreview();
    enlazarImagenPreview();
    document.querySelectorAll('.form-control-color').forEach(function (el) {
      el.addEventListener('input', aplicarPreview);
      el.addEventListener('change', aplicarPreview);
    });
    const form = document.querySelector('form[action*="guardar_sistema"]');
    if (form) {
      form.addEventListener('submit', function () {
        const filas = Array.from(document.querySelectorAll('.navbar-order-input')).sort(function (a, b) {
          return (parseInt(a.value || '0', 10) || 0) - (parseInt(b.value || '0', 10) || 0);
        });
        const existentes = form.querySelectorAll('input[name="navbar_modulos_orden[]"]');
        existentes.forEach(function (el) { el.remove(); });
        filas.forEach(function (inputOrden) {
          const hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = 'navbar_modulos_orden[]';
          hidden.value = inputOrden.dataset.module || '';
          form.appendChild(hidden);
        });
      });
    }
  });
})();
</script>
