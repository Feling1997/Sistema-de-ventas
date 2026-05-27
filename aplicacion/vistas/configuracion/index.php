<?php
$config = $config ?? [];
$secciones = $secciones ?? [];
$seccion_actual = $seccion_actual ?? "inicio";
$modulos_navbar = $modulos_navbar ?? [];

function cfg_get(array $config, string $clave, string $defecto = ""): string {
    return htmlspecialchars((string)($config[$clave] ?? $defecto));
}
function cfg_checked(array $config, string $clave, string $defecto = "0"): string {
    return ((string)($config[$clave] ?? $defecto) === "1") ? "checked" : "";
}
function cfg_color_mod(array $config, string $clave, string $defecto): string {
    $valor = (string)($config[$clave] ?? $defecto);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $valor) ? $valor : $defecto;
}
function cfg_select(array $config, string $clave, string $valor, string $defecto = ""): string {
    return (string)($config[$clave] ?? $defecto) === $valor ? "selected" : "";
}

$cards_inicio = [
  "apariencia" => ["icono" => "bi-palette-fill", "titulo" => "Apariencia", "texto" => "Tema, colores y logo", "detalle" => "Vista previa en vivo"],
  "comercio" => ["icono" => "bi-shop", "titulo" => "Comercio", "texto" => "Datos fiscales", "detalle" => "CUIT, direccion, comprobantes"],
  "menu" => ["icono" => "bi-grid-fill", "titulo" => "Menu", "texto" => "Modulos visibles", "detalle" => "Orden drag and drop"],
  "ventas" => ["icono" => "bi-cart-check-fill", "titulo" => "Ventas", "texto" => "Opciones venta", "detalle" => "Descuentos y tickets"],
  "productos" => ["icono" => "bi-box-seam-fill", "titulo" => "Productos", "texto" => "Importaciones", "detalle" => "Codigo barras y stock"],
  "clientes" => ["icono" => "bi-people-fill", "titulo" => "Clientes", "texto" => "Campos", "detalle" => "Comportamientos"],
  "impresion" => ["icono" => "bi-printer-fill", "titulo" => "Impresion", "texto" => "Ticket y PDF", "detalle" => "Vista previa"],
  "notificaciones" => ["icono" => "bi-bell-fill", "titulo" => "Notificaciones", "texto" => "Alertas", "detalle" => "Sonidos y toasts"],
  "backup" => ["icono" => "bi-cloud-arrow-up-fill", "titulo" => "Backup", "texto" => "Backblaze", "detalle" => "Copias automaticas"],
  "seguridad" => ["icono" => "bi-shield-lock-fill", "titulo" => "Seguridad", "texto" => "Sesiones", "detalle" => "Permisos y logs"],
  "sistema" => ["icono" => "bi-gear-fill", "titulo" => "Sistema", "texto" => "Avanzado", "detalle" => "Parametros"],
];

$paneles = ["inicio", "apariencia", "comercio", "menu", "ventas", "productos", "clientes", "impresion", "notificaciones", "backup", "seguridad", "sistema"];
if (!in_array($seccion_actual, $paneles, true))
    $seccion_actual = "inicio";

$orden_nav = array_values(array_filter(array_map("trim", explode(",", (string)($config["navbar_modulos_orden"] ?? "")))));
$visibles_nav = array_values(array_filter(array_map("trim", explode(",", (string)($config["navbar_modulos_visibles"] ?? "")))));
$modulos_ordenados = [];
foreach ($orden_nav as $clave_nav) {
    if (isset($modulos_navbar[$clave_nav]))
        $modulos_ordenados[$clave_nav] = $modulos_navbar[$clave_nav];
}
foreach ($modulos_navbar as $clave_nav => $modulo_nav) {
    if (!isset($modulos_ordenados[$clave_nav]))
        $modulos_ordenados[$clave_nav] = $modulo_nav;
}
$logo_ticket_rel = trim((string)($config["logo_ticket"] ?? ""));
$logo_ticket_preview = "";
if ($logo_ticket_rel !== "") {
    $formato_logo_preview = (string)($config["formato_impresion_ticket"] ?? "80");
    $logo_ticket_preview = function_exists("procesar_logo_ticket_termico")
        ? procesar_logo_ticket_termico_hd($logo_ticket_rel, $formato_logo_preview === "58" ? 384 : 576, true)
        : $logo_ticket_rel;
    $logo_preview_abs = function_exists("resolver_ruta_proyecto") ? resolver_ruta_proyecto($logo_ticket_preview) : "";
    if ($logo_preview_abs !== "" && is_file($logo_preview_abs))
        $logo_ticket_preview .= "?v=" . (string)filemtime($logo_preview_abs);
}
?>

<div class="config-app" data-config-root data-active-panel="<?= htmlspecialchars($seccion_actual) ?>">
  <div class="config-hero">
    <div>
      <div class="text-muted small">Administracion</div>
      <h3>Centro de personalizacion</h3>
      <p>Configura apariencia, comercio, ventas, productos y seguridad desde paneles modulares.</p>
    </div>
    <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=inicio">Volver</a>
  </div>

  <div class="config-workspace">
    <aside class="config-left">
      <?php foreach ($cards_inicio as $clave => $card): ?>
        <button class="config-left-item" type="button" data-open-panel="<?= htmlspecialchars($clave) ?>">
          <div class="icon-circle"><i class="bi <?= htmlspecialchars($card["icono"]) ?>"></i></div>
          <span><strong><?= htmlspecialchars($card["titulo"]) ?></strong><small><?= htmlspecialchars($card["texto"]) ?></small></span>
        </button>
      <?php endforeach; ?>
    </aside>

    <main class="config-center">
      <?php include __DIR__ . "/inicio.php"; ?>
      <?php include __DIR__ . "/apariencia.php"; ?>
      <?php include __DIR__ . "/comercio.php"; ?>
      <?php include __DIR__ . "/menu.php"; ?>
      <?php include __DIR__ . "/ventas.php"; ?>
      <?php include __DIR__ . "/productos.php"; ?>
      <?php include __DIR__ . "/clientes.php"; ?>
      <?php include __DIR__ . "/impresion.php"; ?>
      <?php include __DIR__ . "/notificaciones.php"; ?>
      <?php include __DIR__ . "/backup.php"; ?>
      <?php include __DIR__ . "/seguridad.php"; ?>
      <?php include __DIR__ . "/sistema.php"; ?>
    </main>

    <aside class="config-right">
      <div class="live-preview" id="livePreview">
        <div class="preview-topline">
          <i class="bi bi-circle-fill"></i><i class="bi bi-circle-fill"></i><i class="bi bi-circle-fill"></i>
        </div>
        <div class="preview-navbar">
          <div class="icon-circle preview-logo-slot" id="previewLogoSlot"><i class="bi bi-shop"></i></div>
          <strong id="previewBrand"><?= cfg_get($config, "nombre_comercio", "MI COMERCIO") ?></strong>
          <small>POS</small>
        </div>
        <div class="preview-body">
          <div class="preview-menu">
            <div class="icon-circle"><i class="bi bi-cart-check-fill"></i></div>
            <div class="icon-circle"><i class="bi bi-box-seam-fill"></i></div>
            <div class="icon-circle"><i class="bi bi-people-fill"></i></div>
            <div class="icon-circle"><i class="bi bi-gear-fill"></i></div>
          </div>
          <div class="preview-content" id="configPreviewContent">
            <div class="preview-title-row"><strong>Ventas de hoy</strong><em>En vivo</em></div>
            <div class="preview-kpis">
              <div><i class="bi bi-cash-stack"></i><b>$ 2.350</b><small>Ventas</small></div>
              <div><i class="bi bi-box-seam-fill"></i><b>18</b><small>Items</small></div>
              <div><i class="bi bi-bell-fill"></i><b>3</b><small>Alertas</small></div>
            </div>
            <div class="preview-table">
              <div><i class="bi bi-cart-check-fill"></i><span>Venta mostrador</span><strong>$ 1.500</strong></div>
              <div><i class="bi bi-person-fill"></i><span>Cliente general</span><strong>$ 850</strong></div>
              <div><i class="bi bi-box-fill"></i><span>Stock actualizado</span><strong>OK</strong></div>
            </div>
            <div class="preview-ticket" id="ticketPreview">
              <strong><?= cfg_get($config, "nombre_comercio", "MI COMERCIO") ?></strong>
              <span>Ticket demo</span>
              <hr>
              <div>Producto A $ 1.500,50</div>
              <div>Producto B $ 850,00</div>
              <hr>
              <strong>Total $ 2.350,50</strong>
              <small id="ticketFooter"><?= cfg_get($config, "texto_pie_ticket", "Gracias por su compra") ?></small>
            </div>
          </div>
        </div>
      </div>
    </aside>
  </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="configToast" class="toast" role="alert">
    <div class="toast-header"><strong class="me-auto">Configuracion</strong><button type="button" class="btn-close" data-bs-dismiss="toast"></button></div>
    <div class="toast-body">Vista previa actualizada.</div>
  </div>
</div>

<style>
.icon-circle{
width:42px;
height:42px;
border-radius:12px;
display:flex;
align-items:center;
justify-content:center;
background:#eef8fc;
color:#15799b;
font-size:20px;
}
.campo-icon{
display:inline-flex;
width:24px;
margin-right:8px;
color:#15799b;
}
.icon-circle i{line-height:1;width:auto!important;height:auto!important;border-radius:0!important;background:transparent!important;color:inherit!important;box-shadow:none!important;display:inline-block!important}
.config-app{--preview-accent:<?= cfg_color_mod($config, "color_acento", "#1f6f8b") ?>;--preview-secondary:<?= cfg_color_mod($config, "color_secundario", "#48aaa5") ?>;--preview-bg:<?= cfg_color_mod($config, "color_fondo", "#f4f6f8") ?>;--preview-card:<?= cfg_color_mod($config, "color_tarjetas", "#ffffff") ?>;--preview-nav-a:<?= cfg_color_mod($config, "navbar_color_1", "#000000") ?>;--preview-nav-b:<?= cfg_color_mod($config, "navbar_color_2", "#1f2937") ?>;--preview-radius:<?= (int)($config["ui_radio_bordes"] ?? 8) ?>px}
.config-hero{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:18px;padding:18px 20px;border:1px solid color-mix(in srgb,var(--ui-border) 80%,transparent);border-radius:14px;background:linear-gradient(135deg,color-mix(in srgb,var(--ui-card) 88%,white),color-mix(in srgb,var(--ui-accent) 8%,var(--ui-card)));box-shadow:0 14px 34px rgba(15,23,42,.07)}.config-hero h3{margin:0;font-size:1.55rem;letter-spacing:0}.config-hero p{margin:.25rem 0 0;color:var(--ui-muted)}
.config-workspace{display:grid;grid-template-columns:265px minmax(0,1fr) 340px;gap:18px;align-items:start}
.config-left,.config-panel,.config-right{background:color-mix(in srgb,var(--ui-card) 96%,white);border:1px solid color-mix(in srgb,var(--ui-border) 78%,transparent);border-radius:14px}
.config-left{position:sticky;top:14px;padding:10px;display:grid;gap:7px;box-shadow:0 16px 34px rgba(15,23,42,.06);backdrop-filter:blur(8px)}.config-left-item{position:relative;display:flex;align-items:center;gap:12px;border:0;background:transparent;color:var(--ui-text);text-align:left;padding:12px 12px 12px 14px;border-radius:12px;transition:transform .18s ease,box-shadow .18s ease,background .18s ease;cursor:pointer}.config-left-item::before{content:"";position:absolute;left:0;top:12px;bottom:12px;width:3px;border-radius:999px;background:transparent}.config-left-item i{width:38px;height:38px;display:grid;place-items:center;border-radius:11px;background:color-mix(in srgb,var(--ui-accent) 10%,transparent);font-size:20px;color:var(--ui-accent);transition:.18s ease}.config-left-item span{display:grid;gap:1px}.config-left-item strong{font-size:.96rem}.config-left-item small{color:var(--ui-muted);font-size:12px}.config-left-item:hover{transform:translateY(-1px);background:color-mix(in srgb,var(--ui-accent) 7%,transparent);box-shadow:0 12px 24px rgba(15,23,42,.08)}.config-left-item.active{background:linear-gradient(135deg,color-mix(in srgb,var(--ui-accent) 18%,var(--ui-card)),color-mix(in srgb,var(--preview-secondary) 10%,var(--ui-card)));box-shadow:0 14px 28px color-mix(in srgb,var(--ui-accent) 20%,transparent)}.config-left-item.active::before{background:var(--ui-accent)}.config-left-item.active i{background:var(--ui-accent);color:#fff;box-shadow:0 8px 18px color-mix(in srgb,var(--ui-accent) 35%,transparent)}
.config-panel{display:none;padding:20px;animation:panelIn .22s ease;box-shadow:0 18px 42px rgba(15,23,42,.06)}.config-panel.active{display:block}@keyframes panelIn{from{opacity:.35;transform:translateY(7px)}to{opacity:1;transform:none}}
.config-panel-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid color-mix(in srgb,var(--ui-border) 80%,transparent)}.config-panel-head h4{margin:0;font-size:1.35rem}.config-panel-head p{margin:4px 0 0;color:var(--ui-muted)}
.config-actions .btn,.config-panel-head .btn{min-height:42px;padding:.55rem .9rem;border-radius:11px;font-weight:700;transition:transform .16s ease,box-shadow .16s ease}.config-actions .btn:hover,.config-panel-head .btn:hover{transform:translateY(-1px);box-shadow:0 10px 20px rgba(15,23,42,.12)}.config-panel-head .btn i{margin-right:4px}
.config-card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(215px,1fr));gap:16px}.config-big-card{display:grid;gap:8px;min-height:158px;padding:19px;background:linear-gradient(180deg,color-mix(in srgb,var(--ui-card) 96%,white),var(--ui-card));border:1px solid color-mix(in srgb,var(--ui-border) 80%,transparent);border-radius:14px;color:var(--ui-text);text-decoration:none;box-shadow:0 12px 28px rgba(15,23,42,.07);transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease;text-align:left;cursor:pointer}.config-big-card:hover{transform:translateY(-4px);border-color:color-mix(in srgb,var(--ui-accent) 70%,var(--ui-border));box-shadow:0 18px 36px rgba(15,23,42,.12)}.config-big-icon{width:48px;height:48px;display:grid;place-items:center;border-radius:14px;background:linear-gradient(135deg,color-mix(in srgb,var(--ui-accent) 18%,transparent),color-mix(in srgb,var(--preview-secondary) 15%,transparent));color:var(--ui-accent);font-size:23px}
.config-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.config-block{padding:18px;border:1px solid color-mix(in srgb,var(--ui-border) 82%,transparent);border-radius:14px;background:linear-gradient(180deg,color-mix(in srgb,var(--ui-card) 94%,white),var(--ui-card));box-shadow:0 12px 26px rgba(15,23,42,.055)}.config-block h5{display:flex;align-items:center;gap:8px;margin-bottom:16px;font-size:1rem}.config-block h5 i{width:30px;height:30px;display:grid;place-items:center;border-radius:9px;background:color-mix(in srgb,var(--ui-accent) 12%,transparent);color:var(--ui-accent)}.config-block .form-label{font-weight:700;color:var(--ui-text);display:inline-flex;align-items:center;gap:6px}.color-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px}.color-grid label{display:grid;gap:7px;font-size:13px;color:var(--ui-muted);font-weight:700}.form-control,.form-select{min-height:42px;border-radius:11px;border-color:color-mix(in srgb,var(--ui-border) 90%,#cbd5e1);transition:border-color .16s ease,box-shadow .16s ease}.form-control:focus,.form-select:focus{border-color:var(--ui-accent);box-shadow:0 0 0 .2rem color-mix(in srgb,var(--ui-accent) 16%,transparent)}
.config-current-logo{display:block;width:72px;height:72px;object-fit:contain;border:1px solid var(--ui-border);border-radius:12px;background:#fff;padding:8px}.config-tabs{gap:8px;border:0;background:color-mix(in srgb,var(--ui-bg) 55%,transparent);padding:6px;border-radius:13px}.config-tabs .nav-link{border:0;border-radius:10px;color:var(--ui-text);font-weight:700;padding:.62rem .9rem;transition:.16s ease}.config-tabs .nav-link:hover{background:color-mix(in srgb,var(--ui-accent) 8%,transparent)}.config-tabs .nav-link.active{background:linear-gradient(135deg,var(--ui-accent),color-mix(in srgb,var(--preview-secondary) 82%,var(--ui-accent)));color:#fff;box-shadow:0 10px 22px color-mix(in srgb,var(--ui-accent) 24%,transparent)}
.menu-dnd{display:grid;gap:8px}.menu-dnd-row{display:grid;grid-template-columns:28px 44px 28px minmax(0,1fr);align-items:center;gap:10px;padding:12px;border:1px solid var(--ui-border);border-radius:12px;background:#fff;cursor:grab;transition:.16s ease}.menu-dnd-row:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(15,23,42,.09)}.menu-dnd-row.dragging{opacity:.45}
.config-right{position:sticky;top:14px;padding:12px;background:linear-gradient(180deg,color-mix(in srgb,var(--ui-card) 96%,white),var(--ui-card));box-shadow:0 16px 34px rgba(15,23,42,.07)}.live-preview{overflow:hidden;border-radius:calc(var(--preview-radius) + 8px);border:1px solid #d8e1ea;background:var(--preview-bg);box-shadow:0 20px 42px rgba(15,23,42,.16);transition:.2s ease}.live-preview.dark-preview{--preview-bg:#0f172a;--preview-card:#172033;color:#eef2f7}.preview-topline{height:30px;display:flex;gap:6px;align-items:center;padding:0 12px;background:#f8fafc;border-bottom:1px solid #e2e8f0}.preview-topline span{width:9px;height:9px;border-radius:50%;background:#cbd5e1}.preview-topline span:first-child{background:#ef4444}.preview-topline span:nth-child(2){background:#f59e0b}.preview-topline span:nth-child(3){background:#22c55e}
.preview-navbar{height:56px;display:flex;align-items:center;gap:10px;padding:0 13px;color:#fff;background:linear-gradient(90deg,var(--preview-nav-a),var(--preview-nav-b));font-weight:800}.preview-navbar small{margin-left:auto;border:1px solid rgba(255,255,255,.35);border-radius:8px;padding:4px 8px}.preview-logo-slot{width:32px;height:32px;border-radius:9px;display:grid;place-items:center;background:rgba(255,255,255,.16);overflow:hidden}.preview-logo-slot img{width:100%;height:100%;object-fit:cover}
.preview-body{display:grid;grid-template-columns:64px 1fr;min-height:420px}.preview-menu{display:grid;align-content:start;gap:11px;padding:13px;background:linear-gradient(180deg,var(--preview-accent),var(--preview-secondary));box-shadow:inset -1px 0 0 rgba(255,255,255,.16)}.preview-menu span{height:30px;border-radius:10px;background:rgba(255,255,255,.24);box-shadow:inset 0 0 0 1px rgba(255,255,255,.12)}.preview-menu span:first-child{background:rgba(255,255,255,.44)}.preview-content{padding:15px}.preview-title-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}.preview-title-row strong{font-size:13px}.preview-title-row em{font-style:normal;font-size:11px;padding:3px 7px;border-radius:999px;background:color-mix(in srgb,var(--preview-accent) 14%,transparent);color:var(--preview-accent)}
.preview-kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}.preview-kpis div{height:68px;border-radius:var(--preview-radius);background:var(--preview-card);border:1px solid #dde5ee;box-shadow:0 10px 20px rgba(15,23,42,.08);padding:9px}.preview-kpis i{display:block;width:19px;height:19px;border-radius:7px;background:linear-gradient(135deg,var(--preview-accent),var(--preview-secondary));opacity:.9}.preview-kpis b,.preview-kpis small{display:block;height:7px;border-radius:99px;background:#cbd5e1;margin-top:8px}.preview-kpis small{width:60%;opacity:.65}.preview-table{display:grid;gap:7px;margin-top:12px;padding:10px;border-radius:var(--preview-radius);background:#fff;border:1px solid #dde5ee;box-shadow:0 10px 22px rgba(15,23,42,.07)}.preview-table span{height:12px;border-radius:99px;background:linear-gradient(90deg,#e2e8f0,#f8fafc)}.preview-table span:first-child{background:linear-gradient(90deg,var(--preview-accent),color-mix(in srgb,var(--preview-accent) 20%,#fff))}
.preview-ticket{margin:13px auto 0;padding:12px;width:190px;border-radius:0;background:#fff;border:0;font-family:Arial,sans-serif;font-size:12px;color:#111;display:grid;gap:4px;box-shadow:0 12px 28px rgba(15,23,42,.12)}.preview-ticket.a4{width:235px;min-height:270px}.preview-ticket.f58{width:145px}.preview-ticket small{text-align:center;margin-top:8px}
.preview-screen{display:grid;gap:10px}.preview-card{padding:10px;border-radius:var(--preview-radius);background:var(--preview-card);border:1px solid #dde5ee;box-shadow:0 10px 22px rgba(15,23,42,.07)}.preview-card h6{margin:0 0 8px;font-size:12px}.preview-list{display:grid;gap:6px}.preview-list div{display:grid;grid-template-columns:18px minmax(0,1fr) auto;gap:6px;align-items:center;font-size:10px;color:#334155}.preview-list i{color:var(--preview-accent)}.preview-pill{display:inline-flex;align-items:center;gap:4px;width:max-content;padding:3px 7px;border-radius:999px;background:color-mix(in srgb,var(--preview-accent) 12%,transparent);color:var(--preview-accent);font-size:10px;font-weight:800}.preview-barcode{height:42px;border-radius:6px;background:repeating-linear-gradient(90deg,#111 0 2px,#fff 2px 4px,#111 4px 5px,#fff 5px 9px);border:8px solid #fff;box-shadow:inset 0 0 0 1px #dbe3ea}.preview-product-row{display:grid;grid-template-columns:1fr auto;gap:8px;padding:7px 0;border-bottom:1px solid #edf2f7;font-size:10px}.preview-product-row:last-child{border-bottom:0}.preview-ticket-large{width:min(210px,100%);margin:0 auto;padding:13px;background:#fff;color:#111;border:0;border-radius:0;font-family:Arial,sans-serif;font-size:11px;display:grid;gap:4px}.preview-ticket-large.a4{width:100%;min-height:250px}.preview-ticket-large.f58{width:145px}.preview-ticket-head{text-align:center;font-weight:800}.preview-menu-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px}.preview-menu-tile{min-height:52px;padding:8px;border-radius:10px;background:#fff;border:1px solid #dde5ee;display:grid;align-content:center;justify-items:center;gap:4px;font-size:10px;font-weight:800}.preview-progress{height:9px;border-radius:999px;background:#e2e8f0;overflow:hidden}.preview-progress span{display:block;height:100%;width:68%;background:linear-gradient(90deg,var(--preview-accent),var(--preview-secondary))}
.preview-ticket-logo,.ticket-preview-logo{display:block;width:auto;max-width:100%;height:auto;max-height:82px;object-fit:contain;margin:0 auto 10px auto;background:#fff;border:0;outline:0;box-shadow:none;image-rendering:auto;filter:contrast(1.15)}.ticket-preview-paper{background:#fff;color:#000}
@media(max-width:1180px){.config-workspace{grid-template-columns:240px minmax(0,1fr)}.config-right{grid-column:2}.config-grid{grid-template-columns:1fr}}@media(max-width:820px){.config-workspace{grid-template-columns:1fr}.config-left{position:relative;top:auto;display:flex;overflow-x:auto;scroll-snap-type:x mandatory;padding:8px}.config-left-item{min-width:190px;scroll-snap-align:start}.config-right{position:static;grid-column:auto}.config-hero,.config-panel-head{flex-direction:column}.config-panel{padding:14px}.config-actions,.config-panel-head .d-flex{width:100%;flex-wrap:wrap}.config-actions .btn,.config-panel-head .btn{flex:1 1 160px}.preview-body{min-height:360px}}

/* Compact dashboard UI */
.config-hero{padding:12px 16px;margin-bottom:12px;border-radius:12px}.config-hero h3{font-size:1.28rem}.config-hero p{font-size:.88rem}
.config-workspace{grid-template-columns:220px minmax(0,1fr) 270px;gap:12px}
.config-left{gap:4px;padding:8px;border-radius:12px}.config-left-item{min-height:54px;padding:8px 9px;gap:9px;border-radius:10px}.config-left-item::before{top:9px;bottom:9px}.config-left-item i{width:32px;height:32px;border-radius:9px;font-size:18px}.config-left-item strong{font-size:.88rem}.config-left-item small{font-size:11px;line-height:1.15}
.config-panel{padding:14px;border-radius:12px}.config-panel-head{margin-bottom:12px;padding-bottom:10px}.config-panel-head h4{font-size:1.12rem}.config-panel-head p{font-size:.84rem}.config-panel-head .btn{min-height:36px;padding:.42rem .7rem;font-size:.88rem}
.config-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.config-block{padding:12px;border-radius:12px}.config-block h5{font-size:.92rem;margin-bottom:10px}.config-block h5 i{width:25px;height:25px;border-radius:8px}.config-block .form-label{font-size:.82rem;margin-bottom:.25rem}.form-control,.form-select,.input-group-text{min-height:36px;font-size:.88rem}.form-check{min-height:25px}.form-check-label{font-size:.86rem}.config-tabs{padding:4px;gap:5px;margin-bottom:.7rem!important}.config-tabs .nav-link{padding:.42rem .7rem;font-size:.86rem}.color-grid{gap:8px}.color-grid label{font-size:12px}.form-control-color{height:34px}
.config-card-grid{grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px}.config-big-card{min-height:122px;padding:13px;border-radius:12px}.config-big-icon{width:38px;height:38px;border-radius:11px;font-size:20px}.config-big-card strong{font-size:.95rem}.config-big-card span,.config-big-card small{font-size:.82rem}
.config-right{padding:9px;border-radius:12px}.live-preview{border-radius:12px}.preview-topline{height:22px}.preview-navbar{height:43px;padding:0 10px;font-size:.8rem}.preview-logo-slot{width:26px;height:26px}.preview-body{grid-template-columns:48px 1fr;min-height:315px}.preview-menu{gap:8px;padding:10px}.preview-menu span{height:23px;border-radius:8px}.preview-content{padding:10px}.preview-title-row{margin-bottom:7px}.preview-title-row strong{font-size:12px}.preview-kpis{gap:6px}.preview-kpis div{height:50px;padding:7px}.preview-kpis i{width:15px;height:15px}.preview-kpis b,.preview-kpis small{height:5px;margin-top:6px}.preview-table{gap:5px;margin-top:8px;padding:7px}.preview-table span{height:9px}.preview-ticket{width:155px;margin-top:8px;padding:9px;font-size:10.5px;gap:2px}.preview-ticket hr{margin:4px 0}.preview-ticket.a4{width:195px;min-height:210px}.preview-ticket.f58{width:125px}
@media(max-width:1360px){.config-workspace{grid-template-columns:205px minmax(0,1fr) 245px}.config-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:1020px){.config-workspace{grid-template-columns:190px minmax(0,1fr)}.config-right{grid-column:2}.config-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:820px){.config-workspace{grid-template-columns:1fr}.config-left{display:flex;overflow-x:auto}.config-left-item{min-width:156px}.config-grid{grid-template-columns:1fr}.config-right{grid-column:auto}.config-hero{padding:12px}.preview-body{min-height:300px}}

/* Real Bootstrap Icons only: remove simulated square/icon styling */
.config-left-item .icon-circle{width:36px;height:36px;font-size:18px}
.config-left-item.active .icon-circle{background:var(--ui-accent);color:#fff;box-shadow:0 8px 18px color-mix(in srgb,var(--ui-accent) 35%,transparent)}
.config-big-card .icon-circle{width:42px;height:42px;font-size:21px}
.config-block h5 .icon-circle{width:34px;height:34px;font-size:17px}
.preview-logo-slot.icon-circle{width:30px;height:30px;background:rgba(255,255,255,.18);color:#fff}
.preview-menu .icon-circle{width:30px;height:30px;border-radius:10px;background:rgba(255,255,255,.24);color:#fff;font-size:15px;box-shadow:inset 0 0 0 1px rgba(255,255,255,.12)}
.preview-menu .icon-circle:first-child{background:rgba(255,255,255,.44)}
.preview-topline i{font-size:9px;color:#cbd5e1}.preview-topline i:first-child{color:#ef4444}.preview-topline i:nth-child(2){color:#f59e0b}.preview-topline i:nth-child(3){color:#22c55e}
.preview-kpis i{display:block;color:var(--preview-accent);font-size:17px;line-height:1;width:auto;height:auto;border-radius:0;background:transparent}.preview-kpis b,.preview-kpis small{display:block;margin-top:5px;line-height:1.1;height:auto;border-radius:0;background:transparent}.preview-kpis b{font-size:11px}.preview-kpis small{font-size:9px;color:#64748b;width:auto;opacity:1}
.preview-table{gap:6px}.preview-table div{display:grid;grid-template-columns:18px minmax(0,1fr) auto;align-items:center;gap:6px;font-size:10px;color:#334155}.preview-table i{color:var(--preview-accent)}.preview-table span{height:auto;border-radius:0;background:transparent}.preview-table strong{font-size:10px}
</style>

<script>
(function(){
  const root = document.querySelector('[data-config-root]');
  const preview = document.getElementById('livePreview');
  const previewContent = document.getElementById('configPreviewContent');
  const panels = Array.from(document.querySelectorAll('[data-panel]'));
  const openers = Array.from(document.querySelectorAll('[data-open-panel]'));
  const previewDefaults = {
    brand: <?= json_encode((string)($config["nombre_comercio"] ?? "MI COMERCIO")) ?>,
    ticketFooter: <?= json_encode((string)($config["texto_pie_ticket"] ?? "Gracias por su compra")) ?>,
    ticketFormat: <?= json_encode((string)($config["formato_impresion_ticket"] ?? "80")) ?>,
    ticketFont: <?= json_encode((string)($config["ticket_fuente"] ?? "Arial")) ?>,
    ticketSize: <?= json_encode((string)($config["ticket_tamano_fuente"] ?? "12")) ?>,
    ticketLogo: "",
    ticketLogoOriginal: <?= json_encode($logo_ticket_rel) ?>,
    barcodeFormat: <?= json_encode((string)($config["productos_formato_codigo_barras"] ?? "ean13")) ?>,
    defaultClient: <?= json_encode((string)($config["ventas_cliente_defecto"] ?? "Consumidor Final")) ?>
  };
  const systemLogoUrl = document.body ? (document.body.dataset.logoSistema || '') : '';
  let ticketLogoPreviewUrl = '';
  let ticketLogoOriginalFile = null;
  function setPreviewSystemLogo(url){
    const slot = document.getElementById('previewLogoSlot');
    if(!slot || !url)
      return;
    const img = document.createElement('img');
    img.src = url;
    img.alt = 'Logo sistema';
    slot.innerHTML = '';
    slot.appendChild(img);
  }
  function bindConfigSubmitFeedback(){
    document.querySelectorAll('form[action*="c=configuracion&a=guardar"]').forEach(form => {
      if(form.dataset.configSubmitFeedback === '1')
        return;
      form.dataset.configSubmitFeedback = '1';
      form.addEventListener('submit', event => {
        const submitter = event.submitter || form.querySelector('button[type="submit"]:not([form]), input[type="submit"]:not([form])');
        if(submitter){
          submitter.dataset.originalText = submitter.innerHTML || submitter.value || '';
          if(submitter.tagName === 'BUTTON')
            submitter.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Guardando...';
          else
            submitter.value = 'Guardando...';
          submitter.disabled = true;
        }
      });
    });
  }
  bindConfigSubmitFeedback();
  document.querySelectorAll('.config-panel-head .btn-primary').forEach(btn => { if(!btn.querySelector('i')) btn.insertAdjacentHTML('afterbegin','<i class="bi bi-check-circle"></i> '); });
  document.querySelectorAll('.config-panel-head .btn-outline-danger').forEach(btn => { if(!btn.querySelector('i')) btn.insertAdjacentHTML('afterbegin','<i class="bi bi-arrow-counterclockwise"></i> '); });
  const h5Icons = {Marca:'bi-palette', Archivos:'bi-folder2-open', Paleta:'bi-palette2', Modo:'bi-moon-stars', Detalles:'bi-sliders', Identificacion:'bi-shop', Fiscal:'bi-receipt', Direccion:'bi-geo-alt', Contacto:'bi-telephone', 'Datos comprobante':'bi-file-earmark-text', 'Logo ticket':'bi-image', Modulos:'bi-grid-fill', Operacion:'bi-sliders', Comportamiento:'bi-cpu', 'Color de estados':'bi-palette2', Reglas:'bi-box-seam', 'Codigo de barras':'bi-upc-scan', Formato:'bi-printer', Contenido:'bi-receipt', Eventos:'bi-bell', 'Backblaze B2':'bi-cloud-arrow-up', Automatico:'bi-clock-history', Sesion:'bi-person-lock', Controles:'bi-shield-check', Campos:'bi-ui-checks', Sistema:'bi-gear-fill', Opciones:'bi-toggles' };
  document.querySelectorAll('.config-block h5').forEach(h => { const key=(h.textContent || '').trim(); if(!h.querySelector('i') && h5Icons[key]) h.insertAdjacentHTML('afterbegin','<div class="icon-circle"><i class="bi '+h5Icons[key]+'"></i></div> '); });
  const labelIcons = {'Cantidad decimal':'bi-123','Descuento automatico %':'bi-percent','Cliente por defecto':'bi-person','Ventas rapidas':'bi-lightning','Consumidor Final automatico':'bi-person-check','Confirmacion sonora':'bi-volume-up','Controlar stock al vender':'bi-box','Nombre comercio':'bi-shop','Logo sistema':'bi-image','Favicon':'bi-star','Imagen panel':'bi-card-image','Formato':'bi-printer','Fuente':'bi-fonts','Tamano fuente':'bi-type','Mensaje pie':'bi-receipt','Tiempo sesion minutos':'bi-clock','Modo de acceso':'bi-person-lock','IP permitidas':'bi-hdd-network','Lista por defecto':'bi-tags','PLU digitos balanza':'bi-upc-scan','URL Reparaciones':'bi-link-45deg','Atajo':'bi-keyboard'};
  document.querySelectorAll('.form-label,.form-check-label').forEach(label => { const key=(label.textContent || '').trim(); if(!label.querySelector('i') && labelIcons[key]) label.insertAdjacentHTML('afterbegin','<i class="bi '+labelIcons[key]+'"></i> '); });
  function currentValue(selector, fallback){
    const el = document.querySelector(selector);
    return el ? (el.value || fallback) : fallback;
  }
  function esc(value){
    return String(value).replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
  }
  function checkedText(selector, on, off){
    const el = document.querySelector(selector);
    return el && el.checked ? on : off;
  }
  function cfgValue(key, fallback){
    return currentValue('[name="config[' + key + ']"]', fallback || '');
  }
  function cfgChecked(key, on, off){
    return checkedText('[name="config[' + key + ']"]', on, off);
  }
  function cfgSelectedText(key, fallback){
    const el = document.querySelector('[name="config[' + key + ']"]');
    return el && el.selectedOptions && el.selectedOptions[0] ? el.selectedOptions[0].textContent : fallback;
  }
  function assetUrl(path){
    const raw = String(path || '').trim();
    if(!raw) return '';
    if(/^https?:\/\//i.test(raw) || raw.startsWith('data:') || raw.startsWith('blob:')) return raw;
    if(raw.indexOf('index.php?') === 0) return raw;
    return '/VENTAS/' + raw.replace(/^\/+/, '');
  }
  function procesarLogoTermicoPreview(file){
    return new Promise(resolve => {
      const img = new Image();
      img.onload = () => resolve(procesarLogoTermicoCanvas(img));
      img.onerror = () => resolve('');
      if(file instanceof File) img.src = URL.createObjectURL(file);
      else img.src = file;
    }).then(url => {
      if(url) guardarLogoProcesado(url);
      return url;
    });
  }
  function procesarLogoTermicoCanvas(img){
    const formato = cfgValue('formato_impresion_ticket', previewDefaults.ticketFormat);
    const finalWidth = formato === '58' ? 384 : 576;
    const width = finalWidth * 4;
    const height = Math.max(1, Math.round((img.naturalHeight * width) / img.naturalWidth));
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, width, height);
    ctx.drawImage(img, 0, 0, width, height);
    const data = ctx.getImageData(0, 0, width, height);
    const px = data.data;
    const lumAt = (x, y) => {
      const i = ((y * width) + x) * 4;
      return (px[i] * 0.299) + (px[i + 1] * 0.587) + (px[i + 2] * 0.114);
    };
    const samples = [];
    const stepX = Math.max(1, Math.floor(width / 28));
    const stepY = Math.max(1, Math.floor(height / 28));
    for(let x = 0; x < width; x += stepX){ samples.push(lumAt(x, 0)); samples.push(lumAt(x, height - 1)); }
    for(let y = 0; y < height; y += stepY){ samples.push(lumAt(0, y)); samples.push(lumAt(width - 1, y)); }
    samples.sort((a,b) => a - b);
    const bg = samples.length ? samples[Math.floor(samples.length / 2)] : 255;
    const darkBg = bg < 145;
    const map = new Uint8Array(width * height);
    const visited = new Uint8Array(width * height);
    for(let y = 0; y < height; y++){
      for(let x = 0; x < width; x++){
        const l = lumAt(x, y);
        let isLogo = darkBg ? l > Math.max(82, bg + 24) : l < Math.min(210, bg - 30);
        if(Math.abs(l - bg) < 16) isLogo = false;
        map[(y * width) + x] = isLogo ? 1 : 0;
      }
    }
    const queue = [];
    for(let x = 0; x < width; x++){ if(map[x]) queue.push([x,0]); if(map[((height - 1) * width) + x]) queue.push([x,height - 1]); }
    for(let y = 0; y < height; y++){ if(map[y * width]) queue.push([0,y]); if(map[(y * width) + width - 1]) queue.push([width - 1,y]); }
    while(queue.length){
      const [cx, cy] = queue.pop();
      if(cx < 0 || cy < 0 || cx >= width || cy >= height) continue;
      const idx = (cy * width) + cx;
      if(!map[idx] || visited[idx]) continue;
      visited[idx] = 1;
      map[idx] = 0;
      queue.push([cx + 1, cy], [cx - 1, cy], [cx, cy + 1], [cx, cy - 1]);
    }
    for(let y = 0; y < height; y++){
      let run = 0;
      for(let x = 0; x < width; x++){
        const idx = (y * width) + x;
        run = map[idx] ? run + 1 : 0;
        if(run > width * 0.68){ for(let rx = x - run + 1; rx <= x; rx++) map[(y * width) + rx] = 0; }
      }
    }
    for(let x = 0; x < width; x++){
      let run = 0;
      for(let y = 0; y < height; y++){
        const idx = (y * width) + x;
        run = map[idx] ? run + 1 : 0;
        if(run > height * 0.68){ for(let ry = y - run + 1; ry <= y; ry++) map[(ry * width) + x] = 0; }
      }
    }
    let minX = width, minY = height, maxX = -1, maxY = -1;
    for(let y = 0; y < height; y++){
      for(let x = 0; x < width; x++){
        if(map[(y * width) + x]){ minX = Math.min(minX, x); minY = Math.min(minY, y); maxX = Math.max(maxX, x); maxY = Math.max(maxY, y); }
      }
    }
    for(let y = 0; y < height; y++){
      for(let x = 0; x < width; x++){
        let isLogo = map[(y * width) + x] === 1;
        if(!isLogo){
          for(let dy = -1; dy <= 1; dy++){
            for(let dx = -1; dx <= 1; dx++){
              const nx = x + dx, ny = y + dy;
              if(nx >= 0 && ny >= 0 && nx < width && ny < height && map[(ny * width) + nx] === 1) isLogo = true;
            }
          }
        }
        const i = ((y * width) + x) * 4;
        const v = isLogo ? 0 : 255;
        px[i] = v; px[i + 1] = v; px[i + 2] = v; px[i + 3] = 255;
      }
    }
    ctx.putImageData(data, 0, 0);
    if(maxX < minX || maxY < minY){
      const blank = document.createElement('canvas');
      blank.width = finalWidth;
      blank.height = Math.max(1, Math.round((height * finalWidth) / width));
      const blankCtx = blank.getContext('2d');
      blankCtx.fillStyle = '#fff';
      blankCtx.fillRect(0, 0, blank.width, blank.height);
      return blank.toDataURL('image/png');
    }
    const pad = Math.max(12, Math.round(width * 0.035));
    const sx = Math.max(0, minX - pad), sy = Math.max(0, minY - pad);
    const sw = Math.min(width - sx, (maxX - minX + 1) + pad * 2);
    const sh = Math.min(height - sy, (maxY - minY + 1) + pad * 2);
    const outHd = document.createElement('canvas');
    outHd.width = width;
    outHd.height = Math.max(1, Math.round((sh * width) / sw));
    const outHdCtx = outHd.getContext('2d', { willReadFrequently: true });
    outHdCtx.fillStyle = '#fff';
    outHdCtx.fillRect(0, 0, outHd.width, outHd.height);
    outHdCtx.drawImage(canvas, sx, sy, sw, sh, 0, 0, outHd.width, outHd.height);
    const hdData = outHdCtx.getImageData(0, 0, outHd.width, outHd.height);
    const hdPx = hdData.data;
    for(let i = 0; i < hdPx.length; i += 4){
      const v = hdPx[i] < 235 ? 0 : 255;
      hdPx[i] = v; hdPx[i + 1] = v; hdPx[i + 2] = v; hdPx[i + 3] = 255;
    }
    outHdCtx.putImageData(hdData, 0, 0);
    const out = document.createElement('canvas');
    out.width = finalWidth;
    out.height = Math.max(1, Math.round((outHd.height * finalWidth) / outHd.width));
    const outCtx = out.getContext('2d');
    outCtx.imageSmoothingEnabled = true;
    outCtx.imageSmoothingQuality = 'high';
    outCtx.fillStyle = '#fff';
    outCtx.fillRect(0, 0, out.width, out.height);
    outCtx.drawImage(outHd, 0, 0, out.width, out.height);
    const finalData = outCtx.getImageData(0, 0, out.width, out.height);
    const finalPx = finalData.data;
    for(let i = 0; i < finalPx.length; i += 4){
      const g = finalPx[i];
      const v = g < 220 ? 0 : 255;
      finalPx[i] = v; finalPx[i + 1] = v; finalPx[i + 2] = v; finalPx[i + 3] = 255;
    }
    outCtx.putImageData(finalData, 0, 0);
    return out.toDataURL('image/png');
  }
  function guardarLogoProcesado(dataUrl){
    const csrf = document.querySelector('[name="csrf"]');
    const fd = new FormData();
    fd.append('csrf', csrf ? csrf.value : '');
    fd.append('logo_png', dataUrl);
    fd.append('formato', cfgValue('formato_impresion_ticket', previewDefaults.ticketFormat));
    fetch('index.php?c=configuracion&a=guardar_logo_ticket_procesado', { method: 'POST', body: fd, credentials: 'same-origin' }).catch(() => {});
  }
  function renderPreview(name){
    if(!previewContent) return;
    const commerceName = esc(cfgValue('nombre_comercio', previewDefaults.brand));
    const businessName = esc(cfgValue('razon_social', cfgValue('nombre_comercio', previewDefaults.brand)));
    const navBrand = commerceName;
    const cuit = esc(cfgValue('cuit', 'CUIT sin cargar'));
    const iva = esc(cfgSelectedText('condicion_iva', 'Condicion IVA'));
    const address = esc(cfgValue('domicilio', 'Direccion sin cargar'));
    const location = esc(cfgValue('localidad', 'Localidad'));
    const province = esc(cfgValue('provincia', 'Provincia'));
    const phone = esc(cfgValue('telefonos', 'Telefono'));
    const whatsapp = esc(cfgValue('whatsapp', 'WhatsApp'));
    const email = esc(cfgValue('email', 'Email'));
    const ticketFooter = esc(cfgValue('texto_pie_ticket', previewDefaults.ticketFooter));
    const ticketFormat = cfgValue('formato_impresion_ticket', previewDefaults.ticketFormat);
    const ticketFontRaw = cfgValue('ticket_fuente', previewDefaults.ticketFont);
    const ticketFont = ['Arial','Verdana','Courier New','Tahoma'].includes(ticketFontRaw) ? ticketFontRaw : 'Arial';
    const ticketSize = Math.max(10, Math.min(18, parseInt(cfgValue('ticket_tamano_fuente', previewDefaults.ticketSize), 10) || 12));
    const ticketLogoUrl = ticketLogoPreviewUrl || assetUrl(previewDefaults.ticketLogo);
    const ticketLogoHtml = ticketLogoUrl ? `<img class="preview-ticket-logo ticket-preview-logo" src="${esc(ticketLogoUrl)}" alt="Logo ticket">` : '';
    const ticketImageFull = cfgChecked('ticket_imagen_completa', '1', '0') === '1';
    const ticketHeaderHtml = ticketImageFull
      ? `${ticketLogoHtml}`
      : `${ticketLogoHtml}<div class="preview-ticket-head">${commerceName}</div><strong>${businessName}</strong><span>CUIT: ${cuit}</span><span>${address}</span><span>${location} ${province}</span><span>Tel: ${phone}</span>`;
    const ticketBodyHtml = `<hr><div>Producto A $ 1.500,50</div><div>Producto B $ 850,00</div><hr><strong>Total $ 2.350,50</strong><small id="ticketFooter">${ticketFooter}</small>`;
    const barcodeFormat = esc(cfgValue('productos_formato_codigo_barras', previewDefaults.barcodeFormat));
    const defaultClient = esc(cfgValue('ventas_cliente_defecto', previewDefaults.defaultClient));
    const decimals = esc(cfgValue('ventas_cantidad_decimales', '3'));
    const discount = esc(cfgValue('ventas_descuento_automatico', '0'));
    const priceList = esc(cfgValue('clientes_lista_defecto', 'General'));
    const backupFolder = esc(cfgValue('backup_b2_carpeta', 'ventas-reparaciones'));
    const backupBucket = esc(cfgValue('backup_b2_bucket_name', 'Bucket sin cargar'));
    const authMode = esc(cfgSelectedText('auth_modo', 'Con usuario y contrasena'));
    const ticketClass = ticketFormat === '58' ? 'f58' : (ticketFormat === 'a4' ? 'a4' : '');
    const views = {
      inicio: `<div class="preview-title-row"><strong>Centro de configuracion</strong><em>Preview</em></div><div class="preview-menu-grid"><div class="preview-menu-tile"><i class="bi bi-palette-fill"></i>Apariencia</div><div class="preview-menu-tile"><i class="bi bi-cart-check-fill"></i>Ventas</div><div class="preview-menu-tile"><i class="bi bi-box-seam-fill"></i>Productos</div><div class="preview-menu-tile"><i class="bi bi-printer-fill"></i>Impresion</div></div>`,
      apariencia: `<div class="preview-title-row"><strong>${navBrand}</strong><em>Apariencia</em></div><div class="preview-kpis"><div><i class="bi bi-palette-fill"></i><b>${esc(cfgValue('color_acento', '#1f6f8b'))}</b><small>Principal</small></div><div><i class="bi bi-shop"></i><b>${commerceName}</b><small>Marca</small></div><div><i class="bi bi-moon-stars"></i><b>${esc(cfgSelectedText('tema_modo', 'Claro'))}</b><small>Tema</small></div></div><div class="preview-table"><div><i class="bi bi-cart-check-fill"></i><span>Navbar</span><strong>${navBrand}</strong></div><div><i class="bi bi-box-seam-fill"></i><span>Radio bordes</span><strong>${esc(cfgValue('ui_radio_bordes', '8'))}px</strong></div></div>`,
      comercio: `<div class="preview-title-row"><strong>Comprobante real</strong><em>Comercio</em></div><div class="preview-ticket-large ticket-preview-paper ${ticketClass}" id="ticketPreview" style="font-family:${ticketFont};font-size:${ticketSize}px">${ticketImageFull ? ticketLogoHtml + ticketBodyHtml : `${ticketLogoHtml}<div class="preview-ticket-head">${commerceName}</div><strong>${businessName}</strong><span>CUIT: ${cuit}</span><span>${iva}</span><span>${address}</span><span>${location} ${province}</span><span>Tel: ${phone}</span><span>${whatsapp}</span><span>${email}</span>${ticketBodyHtml}`}</div>`,
      menu: `<div class="preview-title-row"><strong>Menu principal</strong><em>Orden</em></div><div class="preview-menu-grid"><div class="preview-menu-tile"><i class="bi bi-cart-check-fill"></i>Ventas</div><div class="preview-menu-tile"><i class="bi bi-box-seam-fill"></i>Stock</div><div class="preview-menu-tile"><i class="bi bi-people-fill"></i>Clientes</div><div class="preview-menu-tile"><i class="bi bi-gear-fill"></i>Config</div></div>`,
      ventas: `<div class="preview-title-row"><strong>Nueva venta real</strong><em>${decimals} dec.</em></div><div class="preview-kpis"><div><i class="bi bi-cash-stack"></i><b>$ 2.350</b><small>Total</small></div><div><i class="bi bi-person-fill"></i><b>${defaultClient}</b><small>Cliente</small></div><div><i class="bi bi-volume-up-fill"></i><b>${cfgChecked('ventas_sonido_confirmacion','Sonido','Silencio')}</b><small>Confirmacion</small></div></div><div class="preview-table"><div><i class="bi bi-cart-check-fill"></i><span>Venta rapida</span><strong>${cfgChecked('ventas_rapidas','ON','OFF')}</strong></div><div><i class="bi bi-percent"></i><span>Descuento automatico</span><strong>${discount}%</strong></div><div><i class="bi bi-box-fill"></i><span>Stock negativo</span><strong>${cfgChecked('controlar_stock_ventas','Controla','Libre')}</strong></div></div>`,
      productos: `<div class="preview-title-row"><strong>Ficha producto real</strong><em>${barcodeFormat.toUpperCase()}</em></div><div class="preview-card"><h6>Producto importado / escaneado</h6><div class="preview-product-row"><span>Codigo</span><strong>${barcodeFormat === 'interno' ? 'INT-0001' : '7791234567895'}</strong></div><div class="preview-product-row"><span>Multiples listas</span><strong>${cfgChecked('productos_multiples_listas','Si','No')}</strong></div><div class="preview-product-row"><span>Stock minimo</span><strong>${cfgChecked('productos_mostrar_stock_minimo','Visible','Oculto')}</strong></div><div class="preview-product-row"><span>Stock negativo</span><strong>${cfgChecked('productos_permitir_stock_negativo','Permitido','Bloqueado')}</strong></div><div class="preview-product-row"><span>Excel</span><strong>${cfgChecked('productos_importacion_excel','Activo','Inactivo')}</strong></div><div class="preview-barcode"></div><span class="preview-pill"><i class="bi bi-upc-scan"></i> PLU ${esc(cfgValue('balanza_plu_digitos','5'))} digitos</span></div>`,
      clientes: `<div class="preview-title-row"><strong>Ficha cliente real</strong><em>${priceList || 'Sin lista'}</em></div><div class="preview-card"><h6>Cliente mostrador</h6><div class="preview-list"><div><i class="bi bi-person-fill"></i><span>Campos extra</span><strong>${cfgChecked('clientes_campos_extra','Visibles','Ocultos')}</strong></div><div><i class="bi bi-file-earmark-text"></i><span>Documento</span><strong>${cfgChecked('clientes_validar_documento','Validado','Libre')}</strong></div><div><i class="bi bi-tags"></i><span>Lista por defecto</span><strong>${priceList || 'General'}</strong></div></div></div>`,
      impresion: `<div class="preview-title-row"><strong>Ticket real</strong><em>${ticketFormat === 'a4' ? 'A4' : ticketFormat + 'mm'}</em></div><div class="preview-ticket-large ticket-preview-paper ${ticketClass}" id="ticketPreview" style="font-family:${ticketFont};font-size:${ticketSize}px">${ticketHeaderHtml}${ticketBodyHtml}</div>`,
      notificaciones: `<div class="preview-title-row"><strong>Notificaciones reales</strong><em>${cfgChecked('notificaciones_toasts','Toast','Sin toast')}</em></div><div class="preview-card"><div class="preview-list"><div><i class="bi bi-bell-fill"></i><span>Alertas generales</span><strong>${cfgChecked('notificaciones_alertas','ON','OFF')}</strong></div><div><i class="bi bi-box-fill"></i><span>Stock bajo</span><strong>${cfgChecked('notificaciones_stock_bajo','ON','OFF')}</strong></div><div><i class="bi bi-cart-check-fill"></i><span>Ventas completadas</span><strong>${cfgChecked('notificaciones_ventas','ON','OFF')}</strong></div><div><i class="bi bi-volume-up-fill"></i><span>Sonidos</span><strong>${cfgChecked('notificaciones_sonidos','ON','OFF')}</strong></div></div></div>`,
      backup: `<div class="preview-title-row"><strong>Backup real</strong><em>${esc(cfgValue('backup_frecuencia','diario'))}</em></div><div class="preview-card"><h6>${backupBucket}</h6><div class="preview-progress"><span></span></div><div class="preview-list mt-2"><div><i class="bi bi-cloud-arrow-up-fill"></i><span>Backblaze B2</span><strong>${cfgChecked('backup_b2_habilitado','Activo','Manual')}</strong></div><div><i class="bi bi-folder2-open"></i><span>Carpeta</span><strong>${backupFolder}</strong></div><div><i class="bi bi-clock-history"></i><span>Automatico</span><strong>${cfgChecked('backup_automatico','ON','OFF')}</strong></div></div></div>`,
      seguridad: `<div class="preview-title-row"><strong>Seguridad real</strong><em>${authMode}</em></div><div class="preview-card"><div class="preview-list"><div><i class="bi bi-person-lock"></i><span>Tiempo sesion</span><strong>${esc(cfgValue('seguridad_tiempo_sesion','120'))} min</strong></div><div><i class="bi bi-shield-lock-fill"></i><span>Bloqueos</span><strong>${cfgChecked('seguridad_bloqueos','ON','OFF')}</strong></div><div><i class="bi bi-file-earmark-text"></i><span>Logs</span><strong>${cfgChecked('seguridad_logs','ON','OFF')}</strong></div><div><i class="bi bi-hdd-network"></i><span>IPs</span><strong>${esc(cfgValue('seguridad_ips_permitidas','Todas'))}</strong></div></div></div>`,
      sistema: `<div class="preview-title-row"><strong>Sistema real</strong><em>Avanzado</em></div><div class="preview-card"><div class="preview-list"><div><i class="bi bi-tools"></i><span>Reparaciones</span><strong>${cfgChecked('mostrar_reparaciones','Visible','Oculto')}</strong></div><div><i class="bi bi-keyboard"></i><span>Atajo</span><strong>${esc(cfgValue('atajo_reparaciones','F9'))}</strong></div><div><i class="bi bi-link-45deg"></i><span>URL</span><strong>${esc(cfgValue('url_reparaciones','index.php?c=reparaciones&a=index'))}</strong></div></div></div>`
    };
    previewContent.innerHTML = views[name] || views.inicio;
  }
  function showPanel(name){
    if(root) root.dataset.activePanel = name;
    panels.forEach(p => p.classList.toggle('active', p.dataset.panel === name));
    openers.forEach(b => b.classList.toggle('active', b.dataset.openPanel === name));
    renderPreview(name);
    if (history.replaceState) history.replaceState(null, '', 'index.php?c=configuracion&a=index' + (name && name !== 'inicio' ? '&seccion=' + encodeURIComponent(name) : ''));
  }
  openers.forEach(b => b.addEventListener('click', () => showPanel(b.dataset.openPanel)));
  showPanel(root ? root.dataset.activePanel : 'inicio');
  if(previewDefaults.ticketLogoOriginal){
    procesarLogoTermicoPreview(assetUrl(previewDefaults.ticketLogoOriginal)).then(url => {
      if(url){
        ticketLogoPreviewUrl = url;
        renderPreview(root ? root.dataset.activePanel : 'inicio');
      }
    });
  }
  function setVar(name,value){ if(root && value) root.style.setProperty(name,value); }
  document.querySelectorAll('.live-color').forEach(el => el.addEventListener('input', () => {
    setVar(el.dataset.var, el.value);
    const name = el.getAttribute('name') || '';
    if(name.indexOf('[color_acento]') !== -1){ document.documentElement.style.setProperty('--ui-accent', el.value); document.documentElement.style.setProperty('--nav-bg-a', el.value); }
    if(name.indexOf('[navbar_color_1]') !== -1) document.documentElement.style.setProperty('--nav-bg-a', el.value);
    if(name.indexOf('[navbar_color_2]') !== -1) document.documentElement.style.setProperty('--nav-bg-b', el.value);
    renderPreview(root ? root.dataset.activePanel : 'inicio');
  }));
  document.querySelectorAll('.live-range').forEach(el => el.addEventListener('input', () => setVar(el.dataset.var, el.value + (el.dataset.unit || ''))));
  document.querySelectorAll('input,select,textarea').forEach(el => el.addEventListener('input', () => renderPreview(root ? root.dataset.activePanel : 'inicio')));
  document.querySelectorAll('input,select,textarea').forEach(el => el.addEventListener('change', () => renderPreview(root ? root.dataset.activePanel : 'inicio')));
  document.querySelectorAll('.live-input').forEach(el => el.addEventListener('input', () => { const t=document.querySelector(el.dataset.previewText); if(t) t.textContent=el.value || ' '; }));
  setPreviewSystemLogo(systemLogoUrl);
  document.querySelectorAll('.live-logo').forEach(el => el.addEventListener('change', () => { const f=el.files && el.files[0]; if(f){ setPreviewSystemLogo(URL.createObjectURL(f)); }}));
  document.querySelectorAll('[name="logo_ticket_archivo"]').forEach(el => el.addEventListener('change', () => {
    const f = el.files && el.files[0];
    if(f){
      if(ticketLogoPreviewUrl && ticketLogoPreviewUrl.startsWith('blob:')) URL.revokeObjectURL(ticketLogoPreviewUrl);
      ticketLogoOriginalFile = f;
      procesarLogoTermicoPreview(f).then(url => {
        ticketLogoPreviewUrl = url;
        renderPreview(root ? root.dataset.activePanel : 'inicio');
      });
    }
  }));
  document.querySelectorAll('[name="config[ticket_logo_termico]"],[name="config[formato_impresion_ticket]"]').forEach(el => el.addEventListener('change', () => {
    if(ticketLogoOriginalFile){
      procesarLogoTermicoPreview(ticketLogoOriginalFile).then(url => {
        ticketLogoPreviewUrl = url;
        renderPreview(root ? root.dataset.activePanel : 'inicio');
      });
    } else if(previewDefaults.ticketLogo){
      const formato = cfgValue('formato_impresion_ticket', previewDefaults.ticketFormat);
      ticketLogoPreviewUrl = 'index.php?c=configuracion&a=logo_ticket_actual&formato=' + encodeURIComponent(formato) + '&termico=1&t=' + Date.now();
      renderPreview(root ? root.dataset.activePanel : 'inicio');
    }
  }));
  document.querySelectorAll('.live-mode').forEach(el => el.addEventListener('change', () => { if(preview) preview.classList.toggle('dark-preview', el.value === 'oscuro'); document.body.classList.toggle('modo-oscuro', el.value === 'oscuro'); }));
  document.querySelectorAll('.live-ticket-format').forEach(el => el.addEventListener('change', () => { const t=document.getElementById('ticketPreview'); if(t){ t.classList.toggle('f58', el.value === '58'); t.classList.toggle('a4', el.value === 'a4'); }}));
  document.querySelectorAll('.live-ticket-font').forEach(el => el.addEventListener('change', () => { const t=document.getElementById('ticketPreview'); if(t) t.style.fontFamily=el.value; }));
  document.querySelectorAll('.live-ticket-size').forEach(el => el.addEventListener('input', () => { const t=document.getElementById('ticketPreview'); if(t) t.style.fontSize=el.value + 'px'; }));
  document.querySelectorAll('.config-tabs [data-bs-target]').forEach(btn => btn.addEventListener('click', e => {
    e.preventDefault();
    const tabs = btn.closest('.config-tabs');
    const panel = document.querySelector(btn.dataset.bsTarget);
    if(!tabs || !panel) return;
    tabs.querySelectorAll('.nav-link').forEach(x => x.classList.remove('active'));
    btn.classList.add('active');
    panel.parentElement.querySelectorAll('.tab-pane').forEach(x => x.classList.remove('show','active'));
    panel.classList.add('show','active');
  }));
  document.querySelectorAll('[data-demo-toast]').forEach(btn => btn.addEventListener('click', () => {
    const el=document.getElementById('configToast');
    if(el && window.bootstrap) bootstrap.Toast.getOrCreateInstance(el).show();
    else if(el){ el.classList.add('show'); setTimeout(() => el.classList.remove('show'), 2200); }
  }));
  const dnd = document.getElementById('menuDnd');
  if(dnd){ let dragging=null; dnd.querySelectorAll('.menu-dnd-row').forEach(row => { row.addEventListener('dragstart',()=>{dragging=row;row.classList.add('dragging')}); row.addEventListener('dragend',()=>{row.classList.remove('dragging');dragging=null}); row.addEventListener('dragover',e=>{e.preventDefault(); const after=row.getBoundingClientRect().top+row.offsetHeight/2; if(dragging && dragging!==row){ if(e.clientY<after)dnd.insertBefore(dragging,row); else dnd.insertBefore(dragging,row.nextSibling); }}); }); }
})();
</script>
