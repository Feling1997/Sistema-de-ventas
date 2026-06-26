<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', config('app.name', 'Sistema de Ventas'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --app-sidebar-expanded: 248px;
            --app-sidebar-collapsed: 72px;
            --app-sidebar-width: var(--app-sidebar-expanded);
            --app-navbar-height: 50px;
            --erp-sidebar: #111827;
            --erp-bg: #f3f4f6;
            --erp-border: rgba(17, 24, 39, .1);
            --erp-shadow: 0 1px 2px rgba(15, 23, 42, .06);
            --erp-radius: 10px;
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
        }

        body {
            background: var(--erp-bg);
            color: #212529;
            font-size: 12.5px;
        }

        .app-shell {
            height: 100vh;
            overflow: hidden;
        }

        .app-sidebar {
            background: var(--erp-sidebar);
            border-right: 1px solid rgba(255, 255, 255, .08);
            height: 100vh;
            overflow-x: hidden;
            overflow-y: auto;
            position: sticky;
            top: 0;
            transition: width 150ms ease;
            width: var(--app-sidebar-width);
            scrollbar-color: rgba(255, 255, 255, .25) transparent;
            scrollbar-width: thin;
        }

        .app-sidebar::-webkit-scrollbar {
            width: 7px;
        }

        .app-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .25);
            border-radius: 999px;
        }

        .app-sidebar .offcanvas-body {
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .app-sidebar .nav-link {
            align-items: center;
            border-radius: .5rem;
            color: rgba(255, 255, 255, .78);
            display: flex;
            font-weight: 500;
            min-height: 36px;
            white-space: nowrap;
        }

        .app-sidebar .nav-link:hover,
        .app-sidebar .nav-link.active {
            background: rgba(255, 255, 255, .12);
            color: #fff;
        }

        .sidebar-module {
            border-bottom: 1px solid rgba(255, 255, 255, .1);
            color: rgba(255, 255, 255, .92);
            font-size: .76rem;
            font-weight: 800;
            letter-spacing: .06em;
            margin-bottom: .55rem;
            padding: 0 0 .55rem;
            text-transform: uppercase;
        }

        .tree-menu {
            display: grid;
            gap: .25rem;
        }

        .tree-section {
            border-radius: .55rem;
        }

        .tree-toggle {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: .5rem;
            color: rgba(255, 255, 255, .82);
            display: grid;
            gap: .45rem;
            grid-template-columns: 22px minmax(0, 1fr) 18px;
            min-height: 36px;
            padding: .35rem .5rem;
            text-align: left;
            transition: background 150ms ease, color 150ms ease;
            width: 100%;
        }

        .tree-toggle:hover,
        .tree-section-active > .tree-toggle {
            background: rgba(255, 255, 255, .1);
            color: #fff;
        }

        .tree-chevron {
            font-size: .78rem;
            transition: transform 150ms ease;
        }

        .tree-section-open > .tree-toggle .tree-chevron {
            transform: rotate(90deg);
        }

        .tree-actions {
            display: grid;
            gap: .1rem;
            max-height: 0;
            overflow: hidden;
            padding-left: 1.85rem;
            transition: max-height 150ms ease;
        }

        .tree-section-open > .tree-actions {
            max-height: 420px;
            padding-bottom: .25rem;
        }

        .tree-action {
            align-items: center;
            border-radius: .42rem;
            color: rgba(255, 255, 255, .7);
            display: flex;
            gap: .35rem;
            min-height: 30px;
            padding: .25rem .45rem;
            text-decoration: none;
        }

        .tree-action:hover,
        .tree-action-active {
            background: rgba(13, 110, 253, .34);
            color: #fff;
            text-decoration: none;
        }

        .tree-action-disabled {
            color: rgba(255, 255, 255, .38);
            cursor: not-allowed;
        }

        .tree-action-disabled:hover {
            background: transparent;
            color: rgba(255, 255, 255, .38);
        }

        .tree-subgroup {
            color: rgba(255, 255, 255, .56);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .04em;
            margin: .3rem 0 .05rem;
            text-transform: uppercase;
        }

        .tree-lock {
            color: rgba(255, 255, 255, .45);
            font-size: .8rem;
            margin-left: auto;
        }

        .app-content {
            min-width: 0;
            height: 100vh;
            overflow: hidden;
        }

        .app-main {
            height: calc(100vh - var(--app-navbar-height));
            overflow: auto;
            padding: .65rem !important;
        }

        .module-switcher .btn {
            min-width: 124px;
        }

        .module-switcher .btn.active {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }

        .sidebar-text,
        .sidebar-title {
            opacity: 1;
            transition: opacity 160ms ease;
        }

        .sidebar-collapsed {
            --app-sidebar-width: var(--app-sidebar-collapsed);
        }

        .sidebar-collapsed .sidebar-text,
        .sidebar-collapsed .sidebar-title {
            opacity: 0;
            pointer-events: none;
            width: 0;
        }

        .sidebar-collapsed .app-sidebar .nav-link {
            justify-content: center;
            padding-left: .75rem;
            padding-right: .75rem;
        }

        .sidebar-collapsed .app-sidebar .nav-link i {
            margin-right: 0 !important;
        }

        .sidebar-collapsed .sidebar-module,
        .sidebar-collapsed .tree-label,
        .sidebar-collapsed .tree-chevron,
        .sidebar-collapsed .tree-actions {
            display: none;
        }

        .sidebar-collapsed .tree-toggle {
            grid-template-columns: 1fr;
            justify-items: center;
            padding-left: .65rem;
            padding-right: .65rem;
        }

        main > h1:first-child,
        .content-panel > h1:first-child {
            font-size: 1.35rem;
            margin-bottom: .75rem;
        }

        .navbar {
            min-height: var(--app-navbar-height);
        }

        main > section,
        .content-panel > section,
        main > form,
        .content-panel > form {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: .5rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            margin-bottom: .75rem;
            padding: .65rem;
        }

        .compact-screen {
            display: grid;
            gap: .5rem;
            min-height: calc(100vh - 86px);
        }

        .module-header {
            align-items: center;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
        }

        .module-header h1 {
            font-size: 1.18rem;
            margin: 0;
        }

        .module-toolbar {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .module-header {
            min-height: 38px;
        }

        .module-header p {
            font-size: .78rem;
        }

        .compact-screen section,
        .compact-screen form {
            margin-bottom: 0;
            padding: .5rem;
        }

        .compact-row {
            display: grid;
            gap: .45rem;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .fill-panel {
            min-height: 0;
            overflow: auto;
        }

        .reparaciones-screen,
        .productos-screen,
        .stock-screen,
        .contactos-screen,
        .usuarios-screen,
        .equipos-screen {
            grid-template-rows: auto auto auto minmax(0, 1fr) auto;
        }

        .usuarios-screen {
            grid-template-rows: auto auto minmax(0, 1fr);
        }

        .usuarios-grid {
            display: grid;
            gap: .55rem;
            min-height: 0;
        }

        .erp-tabs {
            align-items: center;
            display: flex;
            gap: .35rem;
        }

        .erp-tab {
            background: #fff;
            border: 1px solid var(--erp-border);
            color: #374151;
        }

        .erp-tab.active {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }

        .tab-panel[hidden] {
            display: none !important;
        }

        .switch-row {
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            display: grid;
            gap: .75rem;
            grid-template-columns: minmax(130px, 1fr) 52px;
            min-height: 38px;
        }

        .switch-row:last-child {
            border-bottom: 0;
        }

        .erp-switch {
            display: inline-flex;
            height: 26px;
            position: relative;
            width: 46px;
        }

        .erp-switch input {
            height: 0;
            opacity: 0;
            width: 0;
        }

        .erp-slider {
            background: #d1d5db;
            border-radius: 999px;
            cursor: pointer;
            inset: 0;
            position: absolute;
            transition: background 150ms ease;
        }

        .erp-slider::before {
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .25);
            content: "";
            height: 20px;
            left: 3px;
            position: absolute;
            top: 3px;
            transition: transform 150ms ease;
            width: 20px;
        }

        .erp-switch input:checked + .erp-slider {
            background: #0d6efd;
        }

        .erp-switch input:checked + .erp-slider::before {
            transform: translateX(20px);
        }

        .venta-screen {
            grid-template-rows: auto minmax(0, 1fr);
        }

        .venta-pos-grid {
            display: grid;
            gap: .55rem;
            grid-template-columns: 330px minmax(0, 1fr) 250px;
            min-height: 0;
        }

        .venta-pos-left,
        .venta-pos-center,
        .venta-pos-right {
            display: grid;
            gap: .55rem;
            min-height: 0;
        }

        .venta-pos-left {
            align-content: start;
        }

        .venta-pos-center {
            grid-template-rows: minmax(0, 1fr) auto;
        }

        .venta-pos-right {
            grid-template-rows: repeat(4, auto) minmax(0, 1fr) auto;
        }

        .venta-pos-right .dato:last-of-type strong {
            color: #0d6efd;
            font-size: 1.45rem;
        }

        .venta-carrito {
            min-height: 0;
        }

        .venta-top {
            display: grid;
            gap: .5rem;
            grid-template-columns: minmax(320px, 1fr) 440px;
        }

        .venta-middle {
            display: grid;
            gap: .5rem;
            grid-template-columns: 380px minmax(0, 1fr);
            min-height: 0;
        }

        .venta-confirmacion .compact-row {
            grid-template-columns: 220px minmax(220px, 1fr) auto auto;
        }

        .venta-totales .resumen {
            grid-template-columns: 1fr;
            margin-bottom: 0;
        }

        .stock-alertas .resumen {
            grid-template-columns: repeat(4, minmax(120px, 1fr));
        }

        .resumen {
            display: grid;
            gap: .45rem;
            grid-template-columns: repeat(auto-fit, minmax(116px, 1fr));
            margin-bottom: .45rem;
        }

        .dato {
            background: #fff;
            border: 1px solid var(--erp-border);
            border-radius: var(--erp-radius);
            box-shadow: var(--erp-shadow);
            min-height: 58px;
            padding: .45rem .58rem;
        }

        .dato strong {
            display: block;
            font-size: 1.12rem;
            line-height: 1.15;
            margin-top: .15rem;
        }

        .barra {
            display: grid;
            gap: .5rem;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        }

        .acciones,
        .paginacion {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            margin-bottom: .5rem;
        }

        table {
            --bs-table-bg: #fff;
            background: #fff;
            border: 1px solid var(--erp-border);
            border-radius: var(--erp-radius);
            overflow: hidden;
            width: 100%;
        }

        .fill-panel table {
            margin-bottom: 0;
        }

        body table {
            border-collapse: collapse;
            font-size: 12.5px;
        }

        body thead th {
            background: #eef2f7;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        body tbody tr:hover {
            background: #f8fafc;
        }

        body th,
        body td {
            border-bottom: 1px solid #e5e7eb;
            height: 34px;
            padding: .28rem .45rem;
            vertical-align: middle;
        }

        input,
        select,
        textarea {
            border: 1px solid #ced4da;
            border-radius: 8px;
            display: block;
            max-width: 100%;
            min-height: 32px;
            padding: .24rem .45rem;
            width: 100%;
        }

        label {
            color: #495057;
            font-weight: 600;
            margin-bottom: .18rem;
        }

        button,
        input[type="submit"],
        a.button {
            border-radius: .375rem;
        }

        body button {
            background: #0d6efd;
            border: 1px solid #0d6efd;
            color: #fff;
            min-height: 32px;
            padding: .25rem .55rem;
        }

        .btn-light-action {
            background: #fff;
            border: 1px solid #ced4da;
            color: #212529;
        }

        body a:not(.nav-link):not(.navbar-brand) {
            color: #0d6efd;
            text-decoration: none;
        }

        body a:not(.nav-link):not(.navbar-brand):hover {
            text-decoration: underline;
        }

        .badge,
        .PENDIENTE,
        .EN_REPARACION,
        .REPARADO,
        .ENTREGADO,
        .CANCELADO {
            border-radius: 999px;
            display: inline-block;
            font-size: .75rem;
            font-weight: 700;
            padding: .25rem .55rem;
        }

        .PENDIENTE { background: #fff3cd; color: #664d03; }
        .EN_REPARACION { background: #cff4fc; color: #055160; }
        .REPARADO { background: #d1e7dd; color: #0f5132; }
        .ENTREGADO { background: #e0cffc; color: #3d0a91; }
        .CANCELADO { background: #f8d7da; color: #842029; }

        ul[id^="sugerencias"],
        .suggestions {
            list-style: none;
            margin: .5rem 0 0;
            padding: 0;
        }

        ul[id^="sugerencias"] li {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: .375rem;
            margin-bottom: .35rem;
            padding: .3rem;
        }

        .pos-panel {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: .5rem;
            padding: .75rem;
        }

        .pagination {
            margin-top: .5rem;
        }

        .filter-strip {
            align-items: end;
            display: grid;
            gap: .45rem;
            grid-template-columns: repeat(auto-fit, minmax(125px, 1fr));
        }

        .erp-card {
            background: #fff;
            border: 1px solid var(--erp-border);
            border-radius: var(--erp-radius);
            box-shadow: var(--erp-shadow);
            padding: .55rem;
        }

        .erp-card h2,
        .erp-card h3,
        section h2 {
            font-size: .92rem;
            margin: 0 0 .45rem;
        }

        .offcanvas-end {
            width: min(460px, 96vw);
        }

        .offcanvas-body form {
            display: grid;
            gap: .45rem;
        }

        .results-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .results-list li {
            border-bottom: 1px solid #dee2e6;
            padding: .45rem .5rem;
        }

        @media (max-width: 991.98px) {
            html,
            body,
            .app-shell,
            .app-content {
                height: auto;
                overflow: auto;
            }

            .app-sidebar {
                width: 100%;
            }

            .app-main {
                height: auto;
                overflow: visible;
            }

            .compact-screen {
                min-height: auto;
            }

            .venta-confirmacion .resumen,
            .stock-alertas .resumen {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            }

            .venta-pos-grid,
            .usuarios-grid,
            .venta-top,
            .venta-middle,
            .venta-confirmacion .compact-row {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            .navbar,
            .app-sidebar,
            .no-print {
                display: none !important;
            }

            body {
                background: #fff;
            }

            .app-content {
                margin: 0 !important;
                padding: 0 !important;
            }

            main > section,
            .content-panel > section {
                border: 0;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
@php
    $esReparaciones = request()->is('reparaciones*')
        || request()->is('contactos*')
        || request()->is('equipos*');
    $sistemaActivo = $esReparaciones ? 'reparaciones' : 'ventas';
    $usuarioLaravel = session('usuario_logueado');
    $usuarioSesion = is_array($usuarioLaravel) ? $usuarioLaravel : [];
    if ($usuarioSesion === [] && isset($_SESSION['usuario_logueado']) && is_array($_SESSION['usuario_logueado'])) {
        $usuarioSesion = $_SESSION['usuario_logueado'];
    }
    $esAdminInterno = (string) ($usuarioSesion['rol'] ?? '') === 'ADMIN_INTERNO';
    $esAdminLegacy = in_array((string) ($usuarioSesion['rol'] ?? ''), ['ADMIN', 'ADMIN_INTERNO'], true);
    $puedeVer = static function (?string $permiso) use ($usuarioSesion, $esAdminLegacy): bool {
        $permitido = $permiso !== null && $esAdminLegacy;

        if (!$permitido && isset($usuarioSesion['id'])) {
            try {
                $permitido = app(\Ventas\Core\Permisos\Application\VerificarPermisoSistema::class)
                    ->ejecutar((int) $usuarioSesion['id'], (string) $permiso);
            } catch (\Throwable) {
                $permitido = false;
            }
        }

        return $permitido;
    };
    $menuVentas = [
        [
            'id' => 'ventas',
            'label' => 'Ventas',
            'icon' => 'bi-cart4',
            'actions' => [
                ['label' => 'Nueva venta', 'url' => url('/ventas/nueva'), 'match' => 'ventas/nueva', 'real' => true, 'permiso' => 'ventas.ver'],
            ],
        ],
        [
            'id' => 'clientes',
            'label' => 'Clientes',
            'icon' => 'bi-people',
            'actions' => [
                ['label' => 'Ver clientes', 'url' => url('/clientes'), 'match' => 'clientes*', 'real' => true, 'permiso' => 'clientes.ver'],
                ['label' => 'Nuevo cliente', 'url' => url('/clientes') . '#nuevo', 'match' => 'clientes/nuevo*', 'real' => true, 'permiso' => 'clientes.crear'],
            ],
        ],
        [
            'id' => 'productos',
            'label' => 'Productos',
            'icon' => 'bi-box-seam',
            'actions' => [
                ['label' => 'Ver productos', 'url' => url('/productos'), 'match' => 'productos*', 'real' => true, 'permiso' => 'productos.ver'],
                ['label' => 'Nuevo producto', 'url' => url('/productos') . '#nuevo', 'match' => 'productos/nuevo*', 'real' => true, 'permiso' => 'productos.crear'],
                ['label' => 'Importar', 'url' => url('/exportaciones'), 'match' => 'exportaciones*', 'real' => true, 'permiso' => 'productos.ver'],
                ['label' => 'Exportar', 'url' => url('/exportaciones'), 'match' => 'exportaciones*', 'real' => true, 'permiso' => 'productos.ver'],
            ],
        ],
        [
            'id' => 'stock',
            'label' => 'Stock',
            'icon' => 'bi-boxes',
            'actions' => [
                ['label' => 'Ver stock', 'url' => url('/stock'), 'match' => 'stock*', 'real' => true, 'permiso' => 'stock.ver'],
                ['label' => 'Nuevo stock', 'url' => url('/stock') . '#nuevo', 'match' => 'stock/nuevo*', 'real' => true, 'permiso' => 'stock.crear'],
                ['label' => 'Movimientos', 'url' => url('/stock'), 'match' => 'stock/movimientos*', 'real' => true, 'permiso' => 'stock.movimientos'],
                ['label' => 'Alertas', 'url' => url('/stock'), 'match' => 'stock/alertas*', 'real' => true, 'permiso' => 'stock.ver'],
            ],
        ],
        [
            'id' => 'cuentas-corrientes',
            'label' => 'Cuentas Corrientes',
            'icon' => 'bi-credit-card',
            'actions' => [
                ['label' => 'Ver cuentas', 'url' => url('/cuentas-corrientes'), 'match' => 'cuentas-corrientes*', 'real' => true, 'permiso' => 'cuentascorrientes.ver'],
                ['label' => 'Registrar pago', 'url' => url('/cuentas-corrientes'), 'match' => 'cuentas-corrientes/pago*', 'real' => true, 'permiso' => 'cuentascorrientes.pagos'],
            ],
        ],
        [
            'id' => 'presupuestos',
            'label' => 'Presupuestos',
            'icon' => 'bi-file-earmark-text',
            'actions' => [
                ['label' => 'Consultar presupuestos', 'url' => url('/presupuestos'), 'match' => 'presupuestos*', 'real' => true, 'permiso' => 'presupuestos.ver'],
            ],
        ],
        [
            'id' => 'configuracion',
            'label' => 'Configuracion',
            'icon' => 'bi-gear',
            'groups' => [
                [
                    'label' => 'Negocio',
                    'actions' => [
                        ['label' => 'Usuarios', 'url' => url('/usuarios'), 'match' => 'usuarios*', 'real' => true, 'permiso' => 'usuarios.ver'],
                    ],
                ],
                [
                    'label' => 'Ventas',
                    'actions' => [
                        ['label' => 'Moneda principal', 'url' => url('/configuracion'), 'match' => 'configuracion*', 'real' => true, 'permiso' => 'configuracion.ver'],
                        ['label' => 'Valor del dolar', 'url' => url('/configuracion'), 'match' => 'configuracion*', 'real' => true, 'permiso' => 'configuracion.ver'],
                    ],
                ],
                [
                    'label' => 'Stock',
                    'actions' => [
                        ['label' => 'Alertas de stock', 'url' => url('/stock'), 'match' => 'stock/alertas*', 'real' => true, 'permiso' => 'stock.ver'],
                        ['label' => 'Movimientos', 'url' => url('/stock'), 'match' => 'stock/movimientos*', 'real' => true, 'permiso' => 'stock.movimientos'],
                    ],
                ],
            ],
        ],
    ];
    $menuReparaciones = [
        [
            'id' => 'reparaciones',
            'label' => 'Reparaciones',
            'icon' => 'bi-tools',
            'actions' => [
                ['label' => 'Nueva reparacion', 'url' => url('/reparaciones') . '#nuevo', 'match' => 'reparaciones/nueva*', 'real' => true, 'permiso' => 'reparaciones.crear'],
                ['label' => 'Ver reparaciones', 'url' => url('/reparaciones'), 'match' => 'reparaciones*', 'real' => true, 'permiso' => 'reparaciones.ver'],
                ['label' => 'Pendientes', 'url' => url('/reparaciones?estado=PENDIENTE'), 'match' => 'reparaciones/pendientes*', 'real' => true, 'permiso' => 'reparaciones.ver'],
                ['label' => 'Terminadas', 'url' => url('/reparaciones?estado=ENTREGADO'), 'match' => 'reparaciones/terminadas*', 'real' => true, 'permiso' => 'reparaciones.ver'],
            ],
        ],
        [
            'id' => 'equipos',
            'label' => 'Equipos',
            'icon' => 'bi-phone',
            'actions' => [
                ['label' => 'Ver equipos', 'url' => url('/equipos'), 'match' => 'equipos*', 'real' => true, 'permiso' => 'reparaciones.equipos'],
                ['label' => 'Nuevo equipo', 'url' => url('/equipos') . '#nuevo', 'match' => 'equipos/nuevo*', 'real' => true, 'permiso' => 'reparaciones.equipos'],
            ],
        ],
        [
            'id' => 'contactos',
            'label' => 'Contactos',
            'icon' => 'bi-people',
            'actions' => [
                ['label' => 'Ver contactos', 'url' => url('/contactos'), 'match' => 'contactos*', 'real' => true, 'permiso' => 'reparaciones.contactos'],
                ['label' => 'Nuevo contacto', 'url' => url('/contactos') . '#nuevo', 'match' => 'contactos/nuevo*', 'real' => true, 'permiso' => 'reparaciones.contactos'],
            ],
        ],
        [
            'id' => 'configuracion-reparaciones',
            'label' => 'Configuracion',
            'icon' => 'bi-gear',
            'groups' => [
                [
                    'label' => 'Negocio',
                    'actions' => [
                        ['label' => 'Datos del negocio', 'url' => url('/reparaciones/configuracion'), 'match' => 'reparaciones/configuracion*', 'real' => true, 'permiso' => 'configuracion.ver'],
                    ],
                ],
                [
                    'label' => 'Reparaciones',
                    'actions' => [
                        ['label' => 'Estados', 'url' => url('/reparaciones/estados'), 'match' => 'reparaciones/estados*', 'real' => true, 'permiso' => 'reparaciones.ver'],
                    ],
                ],
            ],
        ],
    ];
    $menu = $esReparaciones ? $menuReparaciones : $menuVentas;
    if ($esAdminInterno) {
        $menu[] = [
            'id' => 'administrador',
            'label' => 'Administrador',
            'icon' => 'bi-shield-lock',
            'groups' => [
                [
                    'label' => 'Herramientas del sistema',
                    'actions' => [
                        ['label' => 'Diagnostico', 'url' => url('/sistema/diagnostico'), 'match' => 'sistema/diagnostico*', 'real' => true, 'permiso' => 'configuracion.ver'],
                        ['label' => 'Backups', 'url' => url('/backups'), 'match' => 'backups*', 'real' => true, 'permiso' => 'backups.ver'],
                    ],
                ],
            ],
        ];
    }
    $nombreSistema = $esReparaciones ? 'Reparaciones' : 'Ventas';
    $iconoSistema = $esReparaciones ? 'bi-tools' : 'bi-cart4';
@endphp
<div class="app-shell d-lg-flex">
    <aside class="app-sidebar offcanvas-lg offcanvas-start text-bg-dark" tabindex="-1" id="appSidebar">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title sidebar-title">Sistema</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-3">
            <a class="navbar-brand text-white fw-bold mb-3 d-flex align-items-center" href="{{ $esReparaciones ? url('/reparaciones') : url('/ventas/nueva') }}">
                <i class="bi {{ $iconoSistema }} me-2"></i><span class="sidebar-text">{{ $nombreSistema }}</span>
            </a>
            <div class="sidebar-module sidebar-text">
                <i class="bi {{ $iconoSistema }} me-1"></i>{{ $nombreSistema }}
            </div>
            <nav class="tree-menu" aria-label="Menu {{ $nombreSistema }}">
                @foreach ($menu as $section)
                    @php
                        $sectionActions = collect($section['actions'] ?? []);
                        $sectionGroups = collect($section['groups'] ?? []);
                        $groupActions = $sectionGroups->flatMap(fn ($group) => $group['actions'] ?? []);
                        $allSectionActions = $sectionActions->merge($groupActions)->filter(fn ($action) => $puedeVer($action['permiso'] ?? null));
                        $sectionActive = $allSectionActions->contains(fn ($action) => request()->is($action['match']));
                        $sectionId = $sistemaActivo . '-' . $section['id'];
                    @endphp
                    @if (!$allSectionActions->isEmpty())
                    <div class="tree-section {{ $sectionActive ? 'tree-section-active tree-section-open' : '' }}" data-tree-section="{{ $sectionId }}">
                        <button class="tree-toggle" type="button" aria-expanded="{{ $sectionActive ? 'true' : 'false' }}" title="{{ $section['label'] }}">
                            <i class="bi {{ $section['icon'] }}"></i>
                            <span class="tree-label sidebar-text">{{ $section['label'] }}</span>
                            <i class="bi bi-chevron-right tree-chevron"></i>
                        </button>
                        <div class="tree-actions">
                            @foreach ($sectionActions as $action)
                                @php
                                    $actionActive = request()->is($action['match']);
                                    $actionReal = (bool) $action['real'];
                                @endphp
                                @if ($puedeVer($action['permiso'] ?? null) && $actionReal)
                                    <a class="tree-action {{ $actionActive ? 'tree-action-active' : '' }}" href="{{ $action['url'] }}">
                                        <span>{{ $action['label'] }}</span>
                                    </a>
                                @elseif ($puedeVer($action['permiso'] ?? null))
                                    <span class="tree-action tree-action-disabled" aria-disabled="true">
                                        <span>{{ $action['label'] }}</span>
                                        <i class="bi bi-lock tree-lock" aria-hidden="true"></i>
                                    </span>
                                @endif
                            @endforeach
                            @foreach ($sectionGroups as $group)
                                @php
                                    $accionesGrupo = collect($group['actions'] ?? [])->filter(fn ($action) => $puedeVer($action['permiso'] ?? null));
                                @endphp
                                @if (!$accionesGrupo->isEmpty())
                                <div class="tree-subgroup sidebar-text">{{ $group['label'] }}</div>
                                @foreach ($accionesGrupo as $action)
                                    @php
                                        $actionActive = request()->is($action['match']);
                                        $actionReal = (bool) $action['real'];
                                    @endphp
                                    @if ($actionReal)
                                        <a class="tree-action {{ $actionActive ? 'tree-action-active' : '' }}" href="{{ $action['url'] }}">
                                            <span>{{ $action['label'] }}</span>
                                        </a>
                                    @else
                                        <span class="tree-action tree-action-disabled" aria-disabled="true">
                                            <span>{{ $action['label'] }}</span>
                                            <i class="bi bi-lock tree-lock" aria-hidden="true"></i>
                                        </span>
                                    @endif
                                @endforeach
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                @endforeach
            </nav>
        </div>
    </aside>

    <div class="app-content flex-grow-1">
        <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary d-none d-lg-inline-flex me-2" type="button" id="sidebarToggle" aria-label="Alternar menu">
                    <i class="bi bi-list"></i>
                </button>
                <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar">
                    <i class="bi bi-list"></i>
                </button>
                <span class="navbar-brand mb-0 h1">@yield('titulo', $nombreSistema)</span>
                <div class="module-switcher ms-auto btn-group btn-group-sm" role="group" aria-label="Selector de sistema">
                    <a class="btn btn-outline-primary {{ $sistemaActivo === 'ventas' ? 'active' : '' }}" href="{{ url('/ventas/nueva') }}">
                        <i class="bi bi-shop me-1"></i>Ventas
                    </a>
                    <a class="btn btn-outline-primary {{ $sistemaActivo === 'reparaciones' ? 'active' : '' }}" href="{{ url('/reparaciones') }}">
                        <i class="bi bi-tools me-1"></i>Reparaciones
                    </a>
                </div>
            </div>
        </nav>
        <main class="app-main container-fluid py-3">
            <div class="content-panel">
                @yield('contenido')
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const key = 'sistema.sidebar';
    const treeKey = 'sistema.menu.arbol';
    const shell = document.querySelector('.app-shell');
    const toggle = document.getElementById('sidebarToggle');
    const stored = window.localStorage.getItem(key);
    let treeStored = {};
    const treeSections = document.querySelectorAll('[data-tree-section]');

    try {
        treeStored = JSON.parse(window.localStorage.getItem(treeKey) || '{}');
    } catch (error) {
        treeStored = {};
    }

    if (stored === 'collapsed') {
        shell.classList.add('sidebar-collapsed');
    }

    treeSections.forEach((section) => {
        const sectionId = section.dataset.treeSection;
        const button = section.querySelector('.tree-toggle');
        const hasStoredState = Object.prototype.hasOwnProperty.call(treeStored, sectionId);
        const isOpen = hasStoredState ? treeStored[sectionId] === true : section.classList.contains('tree-section-open');

        section.classList.toggle('tree-section-open', isOpen);
        button?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        button?.addEventListener('click', () => {
            const open = section.classList.toggle('tree-section-open');
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
            treeStored[sectionId] = open;
            window.localStorage.setItem(treeKey, JSON.stringify(treeStored));
        });
    });

    toggle?.addEventListener('click', () => {
        const collapsed = shell.classList.toggle('sidebar-collapsed');
        window.localStorage.setItem(key, collapsed ? 'collapsed' : 'expanded');
    });
})();
</script>
@stack('scripts')
</body>
</html>
