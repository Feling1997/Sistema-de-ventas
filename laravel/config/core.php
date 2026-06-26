<?php

declare(strict_types=1);

return [
    /*
     * sistema_core es obligatorio y alojara usuarios, roles, permisos,
     * sesiones, configuraciones globales, contactos, preferencias y auditoria.
     *
     * sistema_ventas y sistema_reparaciones son bases opcionales para
     * instalaciones independientes o combinadas.
     */
    'conexiones' => [
        'core' => [
            'nombre' => 'sistema_core',
            'obligatoria' => true,
            'descripcion' => 'Base compartida por todos los sistemas.',
        ],
        'ventas' => [
            'nombre' => 'sistema_ventas',
            'obligatoria' => false,
            'descripcion' => 'Base del modulo Ventas cuando esta instalado.',
        ],
        'reparaciones' => [
            'nombre' => 'sistema_reparaciones',
            'obligatoria' => false,
            'descripcion' => 'Base del modulo Reparaciones cuando esta instalado.',
        ],
    ],
];
