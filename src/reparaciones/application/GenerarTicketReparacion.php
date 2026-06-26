<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\ReparacionModel;

final class GenerarTicketReparacion
{
    public function __construct(private readonly ObtenerConfiguracionReparaciones $obtenerConfiguracion)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function ejecutar(int $reparacionId): array
    {
        $reparacion = ReparacionModel::query()->with(['contacto', 'equipo', 'estado'])->where('id', $reparacionId)->first();
        $resultado = [
            'ok' => $reparacion instanceof ReparacionModel,
            'reparacion_id' => $reparacionId,
            'html' => $reparacion instanceof ReparacionModel ? $this->html($reparacion, $this->obtenerConfiguracion->ejecutar()) : '',
        ];

        return $resultado;
    }

    /**
     * @param array<string, string> $configuracion
     */
    private function html(ReparacionModel $reparacion, array $configuracion): string
    {
        $equipo = trim((string) ($reparacion->equipo?->marca . ' ' . $reparacion->equipo?->modelo));
        $cliente = trim((string) ($reparacion->contacto?->nombre . ' ' . $reparacion->contacto?->apellido));
        $lineas = [
            '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Ticket</title>',
            '<style>body{font-family:monospace;margin:24px;color:#111827}.ticket{max-width:360px}.center{text-align:center}.line{border-top:1px dashed #999;margin:10px 0}.row{display:flex;justify-content:space-between;gap:12px}.label{font-weight:700}button{margin-bottom:12px}@media print{button{display:none}body{margin:0}.ticket{margin:0 auto;padding:8px}}</style></head><body>',
            '<button onclick="window.print()">Imprimir</button><div class="ticket">',
            '<h2 class="center">' . e((string) ($configuracion['nombre_comercio'] ?? 'Reparaciones')) . '</h2>',
            '<div class="center">' . e((string) ($configuracion['telefono_comercio'] ?? '')) . '</div>',
            '<div class="center">' . e((string) ($configuracion['direccion_comercio'] ?? '')) . '</div>',
            '<div class="line"></div>',
            '<h3>Ticket ' . e((string) $reparacion->codigo) . '</h3>',
            $this->fila('Cliente', $cliente),
            $this->fila('Telefono', (string) $reparacion->contacto?->telefono),
            $this->fila('Equipo', $equipo),
            $this->fila('Marca', (string) $reparacion->equipo?->marca),
            $this->fila('Modelo', (string) $reparacion->equipo?->modelo),
            $this->fila('Serie', (string) $reparacion->equipo?->serie),
            $this->fila('Problema', (string) $reparacion->problema),
            $this->fila('Diagnostico', (string) $reparacion->diagnostico),
            $this->fila('Garantia', (string) $reparacion->garantia),
            $this->fila('Precio', (string) $reparacion->precio),
            $this->fila('Estado', (string) $reparacion->estado?->nombre),
            $this->fila('Ingreso', (string) $reparacion->fecha_ingreso),
            $this->fila('Observaciones', (string) $reparacion->observaciones),
            '<div class="line"></div>',
            '<div class="center">' . e((string) ($configuracion['texto_ticket'] ?? 'Gracias por su visita')) . '</div>',
            '<div class="center">' . e((string) ($configuracion['observaciones_ticket'] ?? '')) . '</div>',
            '</div></body></html>',
        ];
        $html = implode('', $lineas);

        return $html;
    }

    private function fila(string $clave, string $valor): string
    {
        $html = '<p><span class="label">' . e($clave) . ':</span> ' . e($valor) . '</p>';

        return $html;
    }
}
