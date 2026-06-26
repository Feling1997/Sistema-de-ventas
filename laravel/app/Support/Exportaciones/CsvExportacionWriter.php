<?php

declare(strict_types=1);

namespace App\Support\Exportaciones;

use Illuminate\Support\Facades\Storage;

final class CsvExportacionWriter
{
    public function escribir(string $directorio, string $nombreBase, array $filas): string
    {
        Storage::disk('local')->makeDirectory($directorio);

        $nombre = $nombreBase . '_' . date('Ymd_His') . '.csv';
        $ruta = $directorio . '/' . $nombre;
        $contenido = $this->generarContenido($filas);

        Storage::disk('local')->put($ruta, $contenido);

        return $ruta;
    }

    private function generarContenido(array $filas): string
    {
        $salida = fopen('php://temp', 'r+');
        $normalizadas = $this->normalizarFilas($filas);
        $encabezados = array_keys($normalizadas[0]);

        fputcsv($salida, $encabezados);

        foreach ($normalizadas as $fila) {
            fputcsv($salida, $fila);
        }

        rewind($salida);
        $contenido = stream_get_contents($salida);
        fclose($salida);

        return (string) $contenido;
    }

    private function normalizarFilas(array $filas): array
    {
        $normalizadas = [['mensaje' => 'Sin registros para exportar.']];

        if ($filas !== []) {
            $normalizadas = array_map(
                static fn (array $fila): array => array_map(
                    static fn (mixed $valor): string|int|float => is_bool($valor) ? (int) $valor : (is_scalar($valor) ? $valor : (string) json_encode($valor, JSON_UNESCAPED_UNICODE)),
                    $fila
                ),
                $filas
            );
        }

        return $normalizadas;
    }
}
