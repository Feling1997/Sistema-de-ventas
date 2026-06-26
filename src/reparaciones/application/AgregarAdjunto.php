<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\AdjuntoReparacionModel;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class AgregarAdjunto
{
    public function __construct(private readonly FilesystemFactory $filesystem)
    {
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function ejecutar(array $datos): array
    {
        $archivo = $datos['archivo'] ?? null;
        $reparacionId = (int) ($datos['reparacion_id'] ?? 0);
        $ok = false;
        $adjuntoId = null;
        $mensaje = 'Archivo invalido.';

        if ($archivo instanceof UploadedFile && $this->archivoPermitido($archivo)) {
            $ruta = $this->filesystem->disk('local')->putFile('reparaciones/adjuntos/' . $reparacionId, $archivo);
            $miniatura = $this->miniatura($archivo, $ruta);
            $adjunto = AdjuntoReparacionModel::query()->create([
                'reparacion_id' => $reparacionId,
                'nombre' => $archivo->getClientOriginalName(),
                'ruta' => $ruta,
                'miniatura' => $miniatura,
                'mime' => $archivo->getMimeType(),
                'tamano' => $archivo->getSize(),
            ]);
            $ok = true;
            $adjuntoId = $adjunto->id;
            $mensaje = 'Adjunto guardado.';
        }

        $resultado = ['ok' => $ok, 'id' => $adjuntoId, 'mensaje' => $mensaje];

        return $resultado;
    }

    private function archivoPermitido(UploadedFile $archivo): bool
    {
        $permitido = $archivo->getSize() <= 10 * 1024 * 1024
            && in_array($archivo->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true);

        return $permitido;
    }

    private function miniatura(UploadedFile $archivo, string $ruta): ?string
    {
        $miniatura = null;
        $mime = (string) $archivo->getMimeType();

        if (Str::startsWith($mime, 'image/')) {
            $miniatura = $ruta;
        }

        return $miniatura;
    }
}
