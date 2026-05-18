<?php
$logueado = false;
$usuario = "";
$rol = "";
$menu_inicio = null;
$modulos_visibles = [];
$modulos_config = [];
$url_actual = (string)($_SERVER["REQUEST_URI"] ?? "index.php?c=ventas&a=inicio");
$controlador_actual = (string)($_GET["c"] ?? "");
$accion_actual = (string)($_GET["a"] ?? "");
$seccion_param = (string)($_GET["seccion"] ?? "");
$seccion_actual = ($controlador_actual === "reparaciones" || ($controlador_actual === "configuraciones" && $seccion_param === "reparaciones")) ? "reparaciones" : "ventas";
$url_panel_principal = $seccion_actual === "reparaciones" ? "index.php?c=reparaciones&a=index" : "index.php?c=ventas&a=inicio";
$url_cambio_modulo = $seccion_actual === "reparaciones" ? "index.php?c=ventas&a=inicio" : "index.php?c=reparaciones&a=index";
$texto_cambio_modulo = $seccion_actual === "reparaciones" ? "Ventas" : "Reparaciones";
$icono_cambio_modulo = $seccion_actual === "reparaciones" ? "bi-cash-stack" : "bi-tools";
$es_panel_aparte = $es_panel_aparte ?? false;
$url_volver = $url_volver ?? "index.php?c=ventas&a=inicio";
$config_navbar = config_sistema_simple();

if (isset($_SESSION["usuario_logueado"])) {
    $logueado = true;
    $usuario = (string)($_SESSION["usuario_logueado"]["usuario"] ?? "");
    $rol = (string)($_SESSION["usuario_logueado"]["rol"] ?? "");
    $id_usuario = (int)($_SESSION["usuario_logueado"]["id"] ?? 0);
    $permitidos = menu_modulos_permitidos_por_rol($rol);
    $claves_visibles = menu_obtener_preferencias_usuario($id_usuario, $rol);
    if (isset($permitidos["inicio"]))
        $menu_inicio = $permitidos["inicio"];
    foreach ($claves_visibles as $clave) {
        if (isset($permitidos[$clave]))
            $modulos_visibles[$clave] = $permitidos[$clave];
    }
    foreach ($permitidos as $clave => $modulo) {
        if ($clave === "inicio")
            continue;
        $modulo["clave"] = $clave;
        $modulo["activo"] = isset($modulos_visibles[$clave]);
        $modulos_config[$clave] = $modulo;
    }
}

$ventas_claves = ["ventas", "nueva_venta", "clientes", "stock", "productos", "listas_precios", "exportaciones", "cuentas_corrientes", "reparaciones"];
$reparaciones_claves = ["reparaciones"];
$ventas_modulos = [];
$reparaciones_modulos = [];
$orden_global = array_filter(array_map("trim", explode(",", (string)($config_navbar["navbar_modulos_orden"] ?? ""))));
$visibles_global = array_filter(array_map("trim", explode(",", (string)($config_navbar["navbar_modulos_visibles"] ?? ""))));
if (!empty($orden_global)) {
    $modulos_ordenados = [];
    foreach ($orden_global as $clave_orden) {
        if (isset($modulos_visibles[$clave_orden]) && (empty($visibles_global) || in_array($clave_orden, $visibles_global, true)))
            $modulos_ordenados[$clave_orden] = $modulos_visibles[$clave_orden];
    }
    foreach ($modulos_visibles as $clave => $modulo) {
        if (!isset($modulos_ordenados[$clave]) && (empty($visibles_global) || in_array($clave, $visibles_global, true)))
            $modulos_ordenados[$clave] = $modulo;
    }
    $modulos_visibles = $modulos_ordenados;
}
foreach ($modulos_visibles as $clave => $modulo) {
    if (in_array($clave, $ventas_claves, true)) {
        $ventas_modulos[$clave] = $modulo;
    } elseif (in_array($clave, $reparaciones_claves, true)) {
        $reparaciones_modulos[$clave] = $modulo;
    }
}

if ($seccion_actual === "reparaciones" && isset($modulos_config["reparaciones"])) {
    $reparaciones_modulos["reparaciones"] = $modulos_config["reparaciones"];
}
if ($seccion_actual === "ventas" && isset($modulos_config["ventas"]) && (empty($visibles_global) || in_array("ventas", $visibles_global, true))) {
    $ventas_modulos["ventas"] = $modulos_config["ventas"]; 
}
if ($seccion_actual === "ventas" && isset($modulos_config["listas_precios"]) && (empty($visibles_global) || in_array("listas_precios", $visibles_global, true))) {
    $ventas_modulos["listas_precios"] = $modulos_config["listas_precios"];
}
if ($seccion_actual === "ventas" && isset($modulos_config["exportaciones"]) && (empty($visibles_global) || in_array("exportaciones", $visibles_global, true))) {
    $ventas_modulos["exportaciones"] = $modulos_config["exportaciones"];
}
if ($seccion_actual === "ventas" && isset($modulos_config["cuentas_corrientes"]) && (empty($visibles_global) || in_array("cuentas_corrientes", $visibles_global, true))) {
    $ventas_modulos["cuentas_corrientes"] = $modulos_config["cuentas_corrientes"];
}
if ($seccion_actual === "ventas" && isset($modulos_config["reparaciones"]) && (string)($config_navbar["mostrar_reparaciones"] ?? "1") === "1") {
    $ventas_modulos["reparaciones"] = $modulos_config["reparaciones"];
}

$reparaciones_acciones = [
    [
        "texto" => "Inicio",
        "icono" => "bi-house-door-fill",
        "clase" => "icono-inicio",
        "vista" => "inicio",
        "estado" => "TODOS",
        "activo" => true,
    ],
    [
        "texto" => "Nueva reparacion",
        "icono" => "bi-plus-circle-fill",
        "clase" => "icono-nueva",
        "vista" => "nueva",
        "estado" => "TODOS",
    ],
    [
        "texto" => "Consultas",
        "icono" => "bi-search",
        "clase" => "icono-clientes",
        "vista" => "consultas",
        "estado" => "TODOS",
    ],
    [
        "texto" => "Pendientes",
        "icono" => "bi-exclamation-circle-fill",
        "clase" => "icono-stock",
        "vista" => "consultas",
        "estado" => "PENDIENTE",
    ],
    [
        "texto" => "Entregados",
        "icono" => "bi-check-circle-fill",
        "clase" => "icono-reparaciones",
        "vista" => "consultas",
        "estado" => "ENTREGADO",
    ],
];

$current_section_modulos = $seccion_actual === "reparaciones" ? $reparaciones_acciones : $ventas_modulos;
$modulo_reparaciones_accion = null;
if (isset($modulos_config["reparaciones"]) && (string)($config_navbar["mostrar_reparaciones"] ?? "1") === "1") {
    $modulo_reparaciones_accion = $modulos_config["reparaciones"];
    if ($seccion_actual === "reparaciones") {
        $modulo_reparaciones_accion["url"] = "index.php?c=ventas&a=inicio";
        $modulo_reparaciones_accion["icono"] = "bi-cash-stack";
        $modulo_reparaciones_accion["texto"] = "Ventas";
    }
}
if (isset($current_section_modulos["reparaciones"]))
    unset($current_section_modulos["reparaciones"]);
?>
<nav class="app-navbar">
  <div class="container">
    <div class="app-navbar-row app-navbar-main-row">
      <div class="app-navbar-left">
        <a class="navbar-brand app-brand" href="<?= htmlspecialchars($url_panel_principal) ?>">
          <span class="app-brand-mark">
            <i class="bi bi-cpu"></i>
          </span>
          <span class="app-brand-text"><?= htmlspecialchars((string)($config_navbar["navbar_marca_texto"] ?? "MI COMERCIO")) ?></span>
        </a>
      </div>

      <?php if ($logueado && !empty($current_section_modulos) && !$es_panel_aparte): ?>
        <div class="app-module-bar-inline">
          <?php foreach ($current_section_modulos as $modulo): ?>
            <?php if ($seccion_actual === "reparaciones"): ?>
              <?php if ($controlador_actual === "reparaciones"): ?>
                <button class="menu-icono menu-icono-inline app-nav-module-button js-reparaciones-nav<?= !empty($modulo["activo"]) ? " active" : "" ?>"
                        type="button"
                        data-reparaciones-view="<?= htmlspecialchars($modulo["vista"]) ?>"
                        data-reparaciones-estado="<?= htmlspecialchars($modulo["estado"]) ?>">
                  <i class="bi <?= htmlspecialchars($modulo["icono"]) ?> <?= htmlspecialchars($modulo["clase"]) ?>"></i>
                  <span><?= htmlspecialchars($modulo["texto"]) ?></span>
                </button>
              <?php else: ?>
                <a class="menu-icono menu-icono-inline" href="index.php?c=reparaciones&a=index">
                  <i class="bi <?= htmlspecialchars($modulo["icono"]) ?> <?= htmlspecialchars($modulo["clase"]) ?>"></i>
                  <span><?= htmlspecialchars($modulo["texto"]) ?></span>
                </a>
              <?php endif; ?>
            <?php else: ?>
              <a class="menu-icono menu-icono-inline" href="<?= htmlspecialchars($modulo["url"]) ?>">
                <i class="bi <?= htmlspecialchars($modulo["icono"]) ?> <?= htmlspecialchars($modulo["clase"]) ?>"></i>
                <span><?= htmlspecialchars($modulo["texto"]) ?></span>
              </a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($logueado): ?>
        <div class="app-navbar-user">
          <?php if ($modulo_reparaciones_accion !== null): ?>
            <a class="btn btn-sm btn-outline-light app-switch-near-exit" href="<?= htmlspecialchars($modulo_reparaciones_accion["url"]) ?>">
              <i class="bi <?= htmlspecialchars($modulo_reparaciones_accion["icono"]) ?>"></i>
              <span><?= htmlspecialchars((string)($modulo_reparaciones_accion["texto"] ?? "Reparaciones")) ?></span>
            </a>
          <?php endif; ?>
          <?php if ($rol === "ADMIN" && (string)($config_navbar["navbar_mostrar_config"] ?? "1") === "1"): ?>
            <a class="btn btn-sm btn-outline-light app-nav-action" href="index.php?c=configuraciones&a=sistema<?= $seccion_actual === "reparaciones" ? "&seccion=reparaciones" : "" ?>">
              <i class="bi bi-gear-fill nav-icon-config"></i>
              <span>Config</span>
            </a>
          <?php endif; ?>
          <?php if ($es_panel_aparte): ?>
            <a class="btn btn-sm btn-outline-light app-nav-action" href="<?= htmlspecialchars($url_volver) ?>">
              <i class="bi bi-arrow-left nav-icon-back"></i>
              <span>Volver</span>
            </a>
          <?php else: ?>
            <a class="btn btn-sm btn-outline-light app-nav-action" href="<?= htmlspecialchars($url_panel_principal) ?>">
              <i class="bi bi-box-arrow-right nav-icon-exit"></i>
              <span>Salir</span>
            </a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="app-navbar-user">
          <a class="btn btn-sm btn-outline-light app-nav-action" href="index.php?c=auth&a=login">
            <i class="bi bi-person-circle nav-icon-user"></i>
            <span>Login</span>
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</nav>
