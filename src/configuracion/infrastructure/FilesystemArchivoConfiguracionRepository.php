<?php

declare(strict_types=1);

namespace Ventas\Configuracion\Infrastructure;

use Ventas\Configuracion\Domain\Repositorios\ArchivoConfiguracionRepository;
use Ventas\Configuracion\Domain\Repositorios\ConfiguracionRepository;

final class FilesystemArchivoConfiguracionRepository implements ArchivoConfiguracionRepository
{
    public function __construct(
        private readonly string $rutaProyecto,
        private readonly ConfiguracionRepository $configuracionRepository
    ) {
    }

    public function guardarArchivo(string $campo, string $actual, string $nombreBase): string
    {
        $ruta = $actual;

        if (isset($_FILES[$campo]) && is_array($_FILES[$campo])) {
            $archivo = $_FILES[$campo];
            $error = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);

            if ($error === UPLOAD_ERR_OK) {
                $tmp = (string) ($archivo['tmp_name'] ?? '');
                $nombre = (string) ($archivo['name'] ?? '');
                $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
                $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'ico', 'webp'];

                if (is_uploaded_file($tmp) && in_array($extension, $permitidas, true)) {
                    $carpeta = $this->rutaProyecto . '/publico/assets/img';

                    if (!is_dir($carpeta)) {
                        @mkdir($carpeta, 0777, true);
                    }

                    $destino = $carpeta . '/' . $nombreBase . '.' . $extension;

                    if ($nombreBase === 'ticket_logo') {
                        $formato = (string) (($_POST['config']['formato_impresion_ticket'] ?? '') ?: ($this->configuracionSistema()['formato_impresion_ticket'] ?? '80'));
                        $modoTermico = (string) ($_POST['config']['ticket_logo_termico'] ?? '1') === '1';
                        $destino = $carpeta . '/' . $nombreBase . '_original.' . $extension;

                        if (@move_uploaded_file($tmp, $destino)) {
                            $ruta = 'publico/assets/img/' . $nombreBase . '_original.' . $extension;
                            $anchoTicket = $formato === '58' ? 384 : 576;
                            $this->procesarLogoTicketTermicoHD($ruta, $anchoTicket, $modoTermico);
                        } else {
                            $this->flashError('No se pudo guardar el archivo ' . $nombre . '. Revisa permisos de publico/assets/img.');
                        }
                    } elseif (@move_uploaded_file($tmp, $destino)) {
                        $ruta = 'publico/assets/img/' . $nombreBase . '.' . $extension;
                    } else {
                        $this->flashError('No se pudo guardar el archivo ' . $nombre . '. Revisa permisos de publico/assets/img.');
                    }
                } else {
                    $this->flashError('Archivo de imagen no valido.');
                }
            } elseif ($error !== UPLOAD_ERR_NO_FILE) {
                $mensajes = [
                    UPLOAD_ERR_INI_SIZE => 'El archivo supera el tamano permitido por PHP.',
                    UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamano permitido por el formulario.',
                    UPLOAD_ERR_PARTIAL => 'El archivo se subio incompleto.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal de PHP.',
                    UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
                    UPLOAD_ERR_EXTENSION => 'Una extension de PHP bloqueo la subida.',
                ];
                $this->flashError($mensajes[$error] ?? 'No se pudo subir el archivo.');
            }
        }

        return $ruta;
    }

    public function procesarLogoTermico(string $rutaImagen, int $ancho, bool $modoTermico = true, string $destino = ''): string
    {
        $resultado = $destino !== '' ? $destino : $rutaImagen;
        $anchoNormalizado = max(120, min(900, $ancho));

        if (function_exists('imagecreatefromstring') && function_exists('imagefilter')) {
            $bytes = @file_get_contents($rutaImagen);
            $origen = is_string($bytes) ? @imagecreatefromstring($bytes) : false;

            if ($origen !== false) {
                $anchoOrigen = imagesx($origen);
                $altoOrigen = imagesy($origen);

                if ($anchoOrigen > 0 && $altoOrigen > 0) {
                    $alto = max(1, (int) round(($altoOrigen * $anchoNormalizado) / $anchoOrigen));
                    $lienzo = imagecreatetruecolor($anchoNormalizado, $alto);

                    if ($lienzo !== false) {
                        $blanco = imagecolorallocate($lienzo, 255, 255, 255);
                        imagefilledrectangle($lienzo, 0, 0, $anchoNormalizado, $alto, $blanco);
                        imagecopyresampled($lienzo, $origen, 0, 0, 0, 0, $anchoNormalizado, $alto, $anchoOrigen, $altoOrigen);

                        if ($modoTermico) {
                            imagefilter($lienzo, IMG_FILTER_GRAYSCALE);
                            imagefilter($lienzo, IMG_FILTER_CONTRAST, -35);
                        } else {
                            imagefilter($lienzo, IMG_FILTER_GRAYSCALE);
                        }

                        imagepng($lienzo, $resultado, 9);
                        imagedestroy($lienzo);
                    }
                }

                imagedestroy($origen);
            } elseif ($destino !== '' && is_file($rutaImagen)) {
                @copy($rutaImagen, $destino);
            }
        } elseif ($destino !== '' && is_file($rutaImagen)) {
            @copy($rutaImagen, $destino);
        }

        return $resultado;
    }

    public function procesarLogoTicketTermico(string $rutaOriginal, string $formatoTicket, bool $modoTermico = true): string
    {
        $ancho = $formatoTicket === '58' ? 384 : 576;
        $resultado = $this->procesarLogoTicketTermicoHD($rutaOriginal, $ancho, $modoTermico);

        return $resultado;
    }

    public function procesarLogoTicketTermicoHD(string $rutaOriginal, int $anchoTicket, bool $modoTermico = true): string
    {
        $resultado = $rutaOriginal;
        $rutaResuelta = $this->resolverRutaProyecto($rutaOriginal);

        if ($rutaResuelta !== '' && is_file($rutaResuelta)) {
            if (!$modoTermico) {
                $resultado = $this->rutaRelativaProyecto($rutaResuelta);
            } else {
                $ancho = $anchoTicket <= 384 ? 384 : 576;
                $formato = $ancho === 384 ? '58' : '80';
                $carpeta = $this->rutaProyecto . '/almacenamiento/tickets/logos_procesados';

                if (!is_dir($carpeta)) {
                    @mkdir($carpeta, 0777, true);
                }

                $base = pathinfo($rutaResuelta, PATHINFO_FILENAME);
                $base = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $base) ?: 'logo_original';
                $destino = $carpeta . '/' . $base . '_termico_hd_' . $formato . '.png';
                $marcaCanvas = $destino . '.ok';
                $puedeProcesarPhp = function_exists('imagecreatefromstring') && function_exists('imagecopyresampled');

                if (!$puedeProcesarPhp && is_file($destino) && is_file($marcaCanvas)) {
                    $resultado = $this->rutaRelativaProyecto($destino);
                } elseif ($puedeProcesarPhp && is_file($destino)) {
                    $resultado = $this->rutaRelativaProyecto($destino);
                } elseif (!$puedeProcesarPhp) {
                    $resultado = $this->rutaRelativaProyecto($rutaResuelta);
                } else {
                    $origenMtime = @filemtime($rutaResuelta) ?: 0;
                    $destinoMtime = is_file($destino) ? (@filemtime($destino) ?: 0) : 0;

                    if (!is_file($destino) || $destinoMtime < $origenMtime) {
                        $tmpHd = $carpeta . '/' . $base . '_termico_hd_tmp_' . $formato . '.png';
                        $this->procesarLogoTermico($rutaResuelta, $ancho * 4, true, $tmpHd);
                        $this->redimensionarPng($tmpHd, $destino, $ancho);

                        if (!is_file($destino) && is_file($tmpHd)) {
                            @copy($tmpHd, $destino);
                        }

                        if (is_file($tmpHd)) {
                            @unlink($tmpHd);
                        }
                    }

                    $resultado = is_file($destino) ? $this->rutaRelativaProyecto($destino) : $this->rutaRelativaProyecto($rutaResuelta);
                }
            }
        }

        return $resultado;
    }

    private function redimensionarPng(string $origenRuta, string $destino, int $ancho): void
    {
        if (function_exists('imagecreatefrompng') && function_exists('imagecopyresampled') && is_file($origenRuta)) {
            $origen = @imagecreatefrompng($origenRuta);

            if ($origen !== false) {
                $anchoOrigen = imagesx($origen);
                $altoOrigen = imagesy($origen);
                $alto = max(1, (int) round(($altoOrigen * $ancho) / max(1, $anchoOrigen)));
                $final = imagecreatetruecolor($ancho, $alto);

                if ($final !== false) {
                    $blanco = imagecolorallocate($final, 255, 255, 255);
                    imagefilledrectangle($final, 0, 0, $ancho, $alto, $blanco);
                    imagecopyresampled($final, $origen, 0, 0, 0, 0, $ancho, $alto, $anchoOrigen, $altoOrigen);
                    imagepng($final, $destino, 9);
                    imagedestroy($final);
                }

                imagedestroy($origen);
            }
        }
    }

    private function rutaRelativaProyecto(string $ruta): string
    {
        $base = realpath($this->rutaProyecto);
        $real = realpath($ruta);
        $resultado = str_replace('\\', '/', ltrim($ruta, '/'));

        if ($base !== false && $real !== false && str_starts_with($real, $base)) {
            $resultado = ltrim(str_replace('\\', '/', substr($real, strlen($base))), '/');
        }

        return $resultado;
    }

    private function resolverRutaProyecto(string $ruta): string
    {
        $resultado = '';

        if ($ruta !== '') {
            if (preg_match('/^[a-zA-Z]:[\\\\\\/]/', $ruta) || str_starts_with($ruta, '\\\\')) {
                $resultado = $ruta;
            } else {
                $resultado = $this->rutaProyecto . '/' . ltrim($ruta, '/\\');
            }
        }

        return $resultado;
    }

    /**
     * @return array<string, mixed>
     */
    private function configuracionSistema(): array
    {
        $configuracion = $this->configuracionRepository->obtenerGeneral();

        return $configuracion;
    }

    private function flashError(string $mensaje): void
    {
        if (function_exists('flash_error')) {
            \flash_error($mensaje);
        }
    }
}
