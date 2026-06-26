<?php

declare(strict_types=1);

return [
    'nombre' => env('SISTEMA_NOMBRE', 'Sistema de Ventas'),
    'version' => env('SISTEMA_VERSION', '33N'),
    'modo' => env('SISTEMA_MODO', env('REPARACIONES_MODO', 'laravel')),
    'debug_instalacion' => (bool) env('SISTEMA_DEBUG_INSTALACION', false),
];
