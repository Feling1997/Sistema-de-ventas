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
$url_panel_principal = $seccion_actual === "reparaciones" ? "/Sistema-de-ventas/laravel/public/reparaciones" : "index.php?c=ventas&a=inicio";
$url_cambio_modulo = $seccion_actual === "reparaciones" ? "index.php?c=ventas&a=inicio" : "/Sistema-de-ventas/laravel/public/reparaciones";
$texto_cambio_modulo = $seccion_actual === "reparaciones" ? "Ventas" : "Reparaciones";
$icono_cambio_modulo = $seccion_actual === "reparaciones" ? "bi-cash-stack" : "bi-tools";
$es_panel_aparte = $es_panel_aparte ?? false;
$url_volver = $url_volver ?? "index.php?c=ventas&a=inicio";
$config_navbar = config_sistema_simple();
$logo_sistema_nav = trim((string)($config_navbar["logo"] ?? ""));
$logo_sistema_nav_url = "";
if ($logo_sistema_nav !== "") {
    if (preg_match('/^https?:\/\//i', $logo_sistema_nav))
        $logo_sistema_nav_url = $logo_sistema_nav;
    else {
        $logo_sistema_nav_path = __DIR__ . "/../../../" . ltrim($logo_sistema_nav, "/\\");
        $logo_sistema_nav_version = is_file($logo_sistema_nav_path) ? "?v=" . (string)filemtime($logo_sistema_nav_path) : "";
        $logo_sistema_nav_url = asset($logo_sistema_nav) . $logo_sistema_nav_version;
    }
}
$es_configuracion = in_array($controlador_actual, ["configuracion", "configuraciones"], true) && !($controlador_actual === "configuraciones" && $seccion_param === "reparaciones");
$configuracion_separada = (string)($config_navbar["configuracion_separada"] ?? "1") === "1";
if ($es_configuracion && $configuracion_separada)
    $seccion_actual = "configuracion";
else
    $seccion_actual = ($controlador_actual === "reparaciones" || ($controlador_actual === "configuraciones" && $seccion_param === "reparaciones")) ? "reparaciones" : "ventas";
$url_panel_principal = $seccion_actual === "reparaciones" ? "/Sistema-de-ventas/laravel/public/reparaciones" : ($seccion_actual === "configuracion" ? "index.php?c=configuracion&a=index" : "index.php?c=ventas&a=inicio");
$url_cambio_modulo = $seccion_actual === "reparaciones" ? "index.php?c=ventas&a=inicio" : "/Sistema-de-ventas/laravel/public/reparaciones";
$texto_cambio_modulo = $seccion_actual === "reparaciones" ? "Ventas" : "Reparaciones";
$icono_cambio_modulo = $seccion_actual === "reparaciones" ? "bi-cash-stack" : "bi-tools";
$cc_alertas_no_leidas = 0;
$stock_alertas_resumen = ["total" => 0, "pendientes" => 0, "leidas" => 0];
$menu_alertas_cache_segundos = 45;

if (isset($_SESSION["usuario_logueado"])) {
    $logueado = true;
    $usuario = (string)($_SESSION["usuario_logueado"]["usuario"] ?? "");
    $rol = (string)($_SESSION["usuario_logueado"]["rol"] ?? "");
    $id_usuario = (int)($_SESSION["usuario_logueado"]["id"] ?? 0);
    $cache_alertas = $_SESSION["menu_alertas_cache"] ?? [];
    $cache_key = "u" . $id_usuario;
    $cache_valido = is_array($cache_alertas)
        && isset($cache_alertas[$cache_key])
        && is_array($cache_alertas[$cache_key])
        && (time() - (int)($cache_alertas[$cache_key]["ts"] ?? 0)) < $menu_alertas_cache_segundos;
    if ($cache_valido) {
        $cc_alertas_no_leidas = (int)($cache_alertas[$cache_key]["cc"] ?? 0);
        $stock_cache = $cache_alertas[$cache_key]["stock"] ?? [];
        if (is_array($stock_cache))
            $stock_alertas_resumen = [
                "total" => (int)($stock_cache["total"] ?? 0),
                "pendientes" => (int)($stock_cache["pendientes"] ?? 0),
                "leidas" => (int)($stock_cache["leidas"] ?? 0)
            ];
    } else {
        $cc_alertas_no_leidas = (int)($cantidad_cuentas_vencidas_no_leidas ?? 0);
        global $container;
        $resumenAlertasStockBajo = $container->get(\Ventas\Stock\Application\ResumenAlertasStockBajo::class);
        $stock_alertas_resumen = $resumenAlertasStockBajo->ejecutar($id_usuario);
        if (!is_array($cache_alertas))
            $cache_alertas = [];
        $cache_alertas[$cache_key] = [
            "ts" => time(),
            "cc" => $cc_alertas_no_leidas,
            "stock" => $stock_alertas_resumen
        ];
        $_SESSION["menu_alertas_cache"] = $cache_alertas;
    }
    $permitidos = menu_modulos_permitidos_por_rol($rol);
    $claves_visibles = menu_obtener_preferencias_usuario($id_usuario, $rol);
    if (isset($permitidos["inicio"]))
        $menu_inicio = $permitidos["inicio"];
    foreach ($claves_visibles as $clave) {
        if (isset($permitidos[$clave]) && usuario_puede_modulo($clave))
            $modulos_visibles[$clave] = $permitidos[$clave];
    }
    foreach ($permitidos as $clave => $modulo) {
        if ($clave !== "inicio" && usuario_puede_modulo($clave)) {
            $modulo["clave"] = $clave;
            $modulo["activo"] = isset($modulos_visibles[$clave]);
            $modulos_config[$clave] = $modulo;
        }
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

$current_section_modulos = $seccion_actual === "reparaciones" ? $reparaciones_acciones : ($seccion_actual === "configuracion" ? [] : $ventas_modulos);
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
            <?php if ($logo_sistema_nav_url !== ""): ?>
              <img src="<?= htmlspecialchars($logo_sistema_nav_url) ?>" alt="Logo">
            <?php else: ?>
              <i class="bi bi-cpu"></i>
            <?php endif; ?>
          </span>
          <span class="app-brand-text"><?= htmlspecialchars((string)($config_navbar["nombre_comercio"] ?? "MI COMERCIO")) ?></span>
        </a>
      </div>

      <?php if ($logueado && !empty($current_section_modulos) && !$es_panel_aparte): ?>
        <div class="app-module-bar-inline">
          <?php foreach ($current_section_modulos as $clave_modulo => $modulo): ?>
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
                <a class="menu-icono menu-icono-inline" href="/Sistema-de-ventas/laravel/public/reparaciones">
                  <i class="bi <?= htmlspecialchars($modulo["icono"]) ?> <?= htmlspecialchars($modulo["clase"]) ?>"></i>
                  <span><?= htmlspecialchars($modulo["texto"]) ?></span>
                </a>
              <?php endif; ?>
            <?php else: ?>
              <?php $es_cta_cte = (string)($modulo["clave"] ?? $clave_modulo) === "cuentas_corrientes"; ?>
              <?php $es_stock = (string)($modulo["clave"] ?? $clave_modulo) === "stock"; ?>
              <?php $stock_pendientes = (int)($stock_alertas_resumen["pendientes"] ?? 0); ?>
              <?php $stock_total_alertas = (int)($stock_alertas_resumen["total"] ?? 0); ?>
              <a class="menu-icono menu-icono-inline<?= $es_cta_cte && $cc_alertas_no_leidas > 0 ? " menu-icono-alerta" : "" ?><?= $es_stock && $stock_pendientes > 0 ? " menu-icono-alerta menu-icono-stock-alerta" : "" ?>" href="<?= htmlspecialchars($modulo["url"]) ?>">
                <i class="bi <?= htmlspecialchars($modulo["icono"]) ?> <?= htmlspecialchars($modulo["clase"]) ?>"></i>
                <?php if ($es_cta_cte && $cc_alertas_no_leidas > 0): ?>
                  <span class="nav-alert-badge"><?= $cc_alertas_no_leidas > 99 ? "99+" : (int)$cc_alertas_no_leidas ?></span>
                <?php endif; ?>
                <?php if ($es_stock && $stock_total_alertas > 0): ?>
                  <span class="nav-alert-badge badge-stock-alerta <?= $stock_pendientes > 0 ? "is-pending" : "is-read" ?>"><?= ($stock_pendientes > 0 ? $stock_pendientes : $stock_total_alertas) > 99 ? "99+" : (int)($stock_pendientes > 0 ? $stock_pendientes : $stock_total_alertas) ?></span>
                <?php endif; ?>
                <span><?= htmlspecialchars($modulo["texto"]) ?></span>
              </a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($logueado): ?>
        <div class="app-navbar-user">
          <?php if ($seccion_actual === "configuracion"): ?>
            <a class="btn btn-sm btn-outline-light app-switch-near-exit" href="index.php?c=ventas&a=inicio">
              <i class="bi bi-cash-stack"></i>
              <span>Ventas</span>
            </a>
          <?php endif; ?>
          <?php if ($modulo_reparaciones_accion !== null): ?>
            <a class="btn btn-sm btn-outline-light app-switch-near-exit" href="<?= htmlspecialchars($modulo_reparaciones_accion["url"]) ?>">
              <i class="bi <?= htmlspecialchars($modulo_reparaciones_accion["icono"]) ?>"></i>
              <span><?= htmlspecialchars((string)($modulo_reparaciones_accion["texto"] ?? "Reparaciones")) ?></span>
            </a>
          <?php endif; ?>
          <?php if ($seccion_actual !== "configuracion" && $rol === "ADMIN" && (string)($config_navbar["navbar_mostrar_config"] ?? "1") === "1"): ?>
            <a class="btn btn-sm btn-outline-light app-nav-action" href="index.php?c=configuracion&a=index">
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
