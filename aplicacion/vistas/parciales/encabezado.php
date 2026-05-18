<?php
require_once __DIR__ . "/../../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../../configuraciones/ayudas.php";
iniciar_sesion();
$config_visual = config_sistema_simple();
$color_acento = (string)($config_visual["color_acento"] ?? "#1f6f8b");
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color_acento))
  $color_acento = "#1f6f8b";
$color_claves = [
  "color_fondo" => "#f4f6f8",
  "color_fondo_secundario" => "#f9fbfc",
  "color_tarjetas" => "#ffffff",
  "color_texto" => "#203040",
  "color_texto_suave" => "#657789",
  "color_borde" => "#dbe3ea",
  "color_panel_inicio" => "#155e75",
  "color_panel_inicio_2" => "#48aaa5",
];
$colores_ui = [];
foreach ($color_claves as $clave_color => $defecto_color) {
  $valor_color = (string)($config_visual[$clave_color] ?? $defecto_color);
  $colores_ui[$clave_color] = preg_match('/^#[0-9a-fA-F]{6}$/', $valor_color) ? $valor_color : $defecto_color;
}
$imagen_panel = trim((string)($config_visual["imagen_panel"] ?? ""));
$imagen_panel_css = "none";
if ($imagen_panel !== "") {
  if (preg_match('/^https?:\/\//i', $imagen_panel))
    $imagen_panel_css = "url(" . str_replace([" ", ")", "("], ["%20", "%29", "%28"], $imagen_panel) . ")";
  else
    $imagen_panel_css = "url(/VENTAS/" . str_replace([" ", ")", "("], ["%20", "%29", "%28"], ltrim($imagen_panel, "/")) . ")";
}
$navbar_opacidad = max(0, min(100, (int)($config_visual["navbar_boton_opacidad"] ?? 10))) / 100;
$tema_paneles = (string)($config_visual["tema_paneles"] ?? "claro");
if (!in_array($tema_paneles, ["claro", "compacto", "alto_contraste"], true))
  $tema_paneles = "claro";
$url_reparaciones_visual = normalizar_url_reparaciones((string)($config_visual["url_reparaciones"] ?? ""));
$css_path = __DIR__ . "/../../../publico/assets/css/app.css";
$css_version = is_file($css_path) ? (string)filemtime($css_path) : "1";
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars((string)($config_visual["navbar_marca_texto"] ?? "MI COMERCIO")) ?></title>
  <link rel="stylesheet" href="/VENTAS/publico/assets/css/app.css?v=<?= htmlspecialchars($css_version) ?>">
</head>
<?php $body_class = trim((string)($body_class ?? "bg-light")); ?>
<body class="<?= htmlspecialchars(trim($body_class . " tema-" . $tema_paneles)) ?>" data-url-reparaciones="<?= htmlspecialchars($url_reparaciones_visual) ?>" data-atajo-reparaciones="<?= htmlspecialchars((string)($config_visual["atajo_reparaciones"] ?? "F9")) ?>">
<style>:root{--ui-accent:<?= htmlspecialchars($color_acento) ?>;--ui-bg:<?= htmlspecialchars($colores_ui["color_fondo"]) ?>;--ui-bg-start:<?= htmlspecialchars($colores_ui["color_fondo_secundario"]) ?>;--ui-card:<?= htmlspecialchars($colores_ui["color_tarjetas"]) ?>;--ui-text:<?= htmlspecialchars($colores_ui["color_texto"]) ?>;--ui-muted:<?= htmlspecialchars($colores_ui["color_texto_suave"]) ?>;--ui-border:<?= htmlspecialchars($colores_ui["color_borde"]) ?>;--ui-home-a:<?= htmlspecialchars($colores_ui["color_panel_inicio"]) ?>;--ui-home-b:<?= htmlspecialchars($colores_ui["color_panel_inicio_2"]) ?>;--ui-panel-image:<?= $imagen_panel_css ?>;--nav-bg-a:<?= htmlspecialchars((string)($config_visual["navbar_color_1"] ?? "#000000")) ?>;--nav-bg-b:<?= htmlspecialchars((string)($config_visual["navbar_color_2"] ?? "#1f2937")) ?>;--nav-text:<?= htmlspecialchars((string)($config_visual["navbar_texto_color"] ?? "#ffffff")) ?>;--nav-btn-bg:<?= htmlspecialchars((string)($config_visual["navbar_boton_fondo"] ?? "#ffffff")) ?>;--nav-btn-border:<?= htmlspecialchars((string)($config_visual["navbar_boton_borde"] ?? "#ffffff")) ?>;--nav-btn-opacity:<?= htmlspecialchars((string)$navbar_opacidad) ?>;--nav-btn-opacity-pct:<?= htmlspecialchars((string)(max(0, min(100, (int)($config_visual["navbar_boton_opacidad"] ?? 10))) . "%")) ?>;}</style>
<?php include __DIR__ . "/menu.php"; ?>
<div class="container py-5">
<?php include __DIR__ . "/alertas.php"; ?>
