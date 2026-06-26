<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\AdjuntoReparacionModel;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

final class EliminarAdjunto
{
    public function __construct(private readonly FilesystemFactory $filesystem)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function ejecutar(int $id): array
    {
        $adjunto = AdjuntoReparacionModel::query()->where('id', $id)->first();
        $ok = false;

        if ($adjunto instanceof AdjuntoReparacionModel) {
            $this->filesystem->disk('local')->delete($adjunto->ruta);
            if ($adjunto->miniatura !== null && $adjunto->miniatura !== $adjunto->ruta) {
                $this->filesystem->disk('local')->delete($adjunto->miniatura);
            }
            $ok = (bool) $adjunto->delete();
        }

        $resultado = ['ok' => $ok, 'id' => $id];

        return $resultado;
    }
}
