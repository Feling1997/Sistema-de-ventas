<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\AdjuntoReparacionModel;

final class ListarAdjuntos
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function ejecutar(int $reparacionId): array
    {
        $adjuntos = AdjuntoReparacionModel::query()
            ->where('reparacion_id', $reparacionId)
            ->orderByDesc('id')
            ->get()
            ->map(static fn (AdjuntoReparacionModel $adjunto): array => [
                'id' => $adjunto->id,
                'reparacion_id' => $adjunto->reparacion_id,
                'nombre' => $adjunto->nombre,
                'ruta' => $adjunto->ruta,
                'miniatura' => $adjunto->miniatura,
                'mime' => $adjunto->mime,
                'tamano' => $adjunto->tamano,
            ])
            ->all();

        return $adjuntos;
    }
}
